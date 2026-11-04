<?php

namespace App\Livewire;

use App\AiFunctionStatus;
use App\ChatRole;
use App\Models\AiFunctionLog;
use App\Models\ChatMessage;
use App\Services\Ai\ChatAssistant;
use App\Services\Ai\FunctionRegistry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

#[Layout('components.layouts.app')]
#[Title('Chat - Kasku')]
class Chat extends Component
{
    use WithFileUploads;

    /**
     * Quick-reply chips that send a ready-made prompt to the AI.
     *
     * @var array<string, array{label: string, icon: string, prompt: string}>
     */
    public const SUGGESTIONS = [
        'ringkasan' => ['label' => 'Ringkasan bulan ini', 'icon' => 'wallet', 'prompt' => 'Tampilkan ringkasan keuanganku bulan ini'],
        'budget' => ['label' => 'Cek budget', 'icon' => 'piggy-bank', 'prompt' => 'Cek budget bulan ini'],
        'grafik' => ['label' => 'Grafik pengeluaran', 'icon' => 'chart-pie', 'prompt' => 'Tampilkan grafik pengeluaran per kategori bulan ini'],
        'minggu_ini' => ['label' => 'Pengeluaran minggu ini', 'icon' => 'search', 'prompt' => 'Cari semua pengeluaranku minggu ini'],
    ];

    /**
     * Chips that only prefill the input so the user can complete the sentence.
     *
     * @var array<string, array{label: string, icon: string, prefill: string}>
     */
    public const PREFILLS = [
        'catat_pengeluaran' => ['label' => 'Catat pengeluaran', 'icon' => 'pencil', 'prefill' => 'Catat pengeluaran '],
        'catat_pemasukan' => ['label' => 'Catat pemasukan', 'icon' => 'banknote', 'prefill' => 'Catat pemasukan '],
    ];

    #[Validate('nullable|string|max:2000')]
    public string $message = '';

    #[Validate('nullable|image|max:5120')]
    public ?UploadedFile $photo = null;

    /**
     * @return Collection<int, ChatMessage>
     */
    #[Computed]
    public function messages()
    {
        return auth()->user()->chatMessages()
            ->latest('id')
            ->limit(50)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * How many AI messages the user may still send today, counted from the
     * user-role rows in chat_messages so the number survives a cache reset.
     */
    #[Computed]
    public function remainingDailyMessages(): int
    {
        $sentToday = auth()->user()->chatMessages()
            ->where('role', ChatRole::User)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        return max(0, $this->dailyMessageLimit() - $sentToday);
    }

    public function dailyMessageLimit(): int
    {
        return (int) config('services.anthropic.daily_message_limit');
    }

    public function removePhoto(): void
    {
        $this->photo = null;
    }

    public function updatedPhoto(): void
    {
        // Validate immediately on selection, before the view ever tries to render a
        // preview — Livewire's temporaryUrl() throws for non-previewable file types,
        // which would otherwise crash the render before sendMessage() can validate.
        try {
            $this->validateOnly('photo');
        } catch (ValidationException $exception) {
            $this->photo = null;

            throw $exception;
        }
    }

    public function sendMessage(): void
    {
        $this->validate();

        $text = trim($this->message);

        if ($text === '' && ! $this->photo) {
            $this->addError('message', 'Tulis pesan atau lampirkan foto struk dulu.');

            return;
        }

        if ($this->remainingDailyMessages <= 0) {
            $limit = $this->dailyMessageLimit();
            $this->addError('message', "Kuota chat AI hari ini sudah habis ({$limit} pesan). Kamu tetap bisa mencatat transaksi manual lewat tombol +. Kuota direset besok.");

            return;
        }

        $rateLimitKey = 'chat-send-message:'.auth()->id();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 20)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            $this->addError('message', "Terlalu banyak pesan. Coba lagi dalam {$seconds} detik.");

            return;
        }

        RateLimiter::hit($rateLimitKey, 60);

        $photo = $this->photo;
        $this->message = '';
        $this->photo = null;

        try {
            app(ChatAssistant::class)->handle(auth()->user(), $text, $photo);
        } catch (Throwable $exception) {
            report($exception);
            $this->dispatch('toast', message: 'Terjadi kesalahan saat menghubungi AI. Silakan coba lagi.', type: 'error');
        }

        unset($this->messages, $this->remainingDailyMessages);
        $this->dispatch('chat-message-added');
    }

    public function sendSuggestion(string $key): void
    {
        if (! array_key_exists($key, self::SUGGESTIONS)) {
            return;
        }

        $this->message = self::SUGGESTIONS[$key]['prompt'];
        $this->sendMessage();
    }

    public function confirmFunction(int $logId): void
    {
        $log = AiFunctionLog::query()->where('user_id', auth()->id())->findOrFail($logId);

        if ($log->status !== AiFunctionStatus::PendingConfirmation) {
            return;
        }

        $function = app(FunctionRegistry::class)->resolve($log->function_name);
        $validator = Validator::make($log->arguments, $function->rules(auth()->user(), $log->arguments));

        if ($validator->fails()) {
            $log->update([
                'status' => AiFunctionStatus::Failed,
                'result' => ['errors' => $validator->errors()->all()],
            ]);

            $this->reply('⚠️ '.$validator->errors()->first());

            return;
        }

        try {
            $result = $function->execute(auth()->user(), $validator->validated(), $log->chatMessage);
        } catch (Throwable $exception) {
            report($exception);

            $log->update(['status' => AiFunctionStatus::Failed, 'result' => ['errors' => ['Gagal memproses permintaan.']]]);
            $this->reply('⚠️ Gagal memproses permintaan ini.');

            return;
        }

        $log->update([
            'status' => $result->success ? AiFunctionStatus::Confirmed : AiFunctionStatus::Failed,
            'result' => $result->logPayload,
        ]);

        $this->reply($result->message, $result->attachment);
    }

    public function cancelFunction(int $logId): void
    {
        $log = AiFunctionLog::query()->where('user_id', auth()->id())->findOrFail($logId);

        if ($log->status !== AiFunctionStatus::PendingConfirmation) {
            return;
        }

        $log->update(['status' => AiFunctionStatus::Cancelled]);

        $this->reply('Baik, dibatalkan.');
    }

    /**
     * @param  array<string, mixed>|null  $attachment
     */
    private function reply(string $content, ?array $attachment = null): void
    {
        auth()->user()->chatMessages()->create([
            'role' => ChatRole::Assistant,
            'content' => $content,
            'attachment' => $attachment,
        ]);

        unset($this->messages);
        $this->dispatch('chat-message-added');
    }

    public function render(): View
    {
        return view('livewire.chat');
    }
}
