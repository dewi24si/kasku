<?php

namespace App\Services\Ai;

use App\AiFunctionStatus;
use App\ChatRole;
use App\Exceptions\ClaudeRequestException;
use App\Models\AiFunctionLog;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\Ai\Contracts\RequiresConfirmation;
use App\Services\Claude\ClaudeClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ChatAssistant
{
    public function __construct(
        private ClaudeClient $claudeClient,
        private PromptBuilder $promptBuilder,
        private FunctionRegistry $functionRegistry,
    ) {}

    public function handle(User $user, string $text, ?UploadedFile $photo = null): void
    {
        $attachment = null;

        if ($photo) {
            $path = $photo->store("receipts/{$user->id}", 'public');
            $attachment = ['type' => 'receipt_photo', 'url' => Storage::url($path)];
        }

        $userMessage = $user->chatMessages()->create([
            'role' => ChatRole::User,
            'content' => $text !== '' ? $text : 'Mengirim foto struk/bukti transaksi.',
            'attachment' => $attachment,
        ]);

        $systemInstruction = $this->promptBuilder->build($user);
        $messages = $this->buildMessages($user);

        if ($photo) {
            $messages[array_key_last($messages)]['content'] = [
                [
                    'type' => 'image',
                    'source' => [
                        'type' => 'base64',
                        'media_type' => $photo->getMimeType(),
                        'data' => base64_encode(file_get_contents($photo->getRealPath())),
                    ],
                ],
                ['type' => 'text', 'text' => $userMessage->content],
            ];
        }

        try {
            $response = $this->claudeClient->createMessage($systemInstruction, $messages, FunctionDefinitions::all());
        } catch (ClaudeRequestException $exception) {
            $this->logClaudeFailure($user, $exception);
            $this->reply($user, $this->claudeFailureMessage($exception));

            return;
        }

        $textBuffer = [];

        foreach ($response->parts as $part) {
            if ($part['type'] === 'text') {
                $textBuffer[] = $part['text'];

                continue;
            }

            if ($textBuffer !== []) {
                $this->reply($user, implode("\n\n", $textBuffer));
                $textBuffer = [];
            }

            $this->handleFunctionCall($user, $userMessage, $part['name'], $part['args']);
        }

        if ($textBuffer !== []) {
            $this->reply($user, implode("\n\n", $textBuffer));
        }
    }

    /**
     * @param  array<string, mixed>  $args
     */
    private function handleFunctionCall(User $user, ChatMessage $userMessage, string $name, array $args): void
    {
        try {
            $function = $this->functionRegistry->resolve($name);
        } catch (UnknownAiFunctionException) {
            $this->reply($user, 'Maaf, saya mencoba melakukan sesuatu yang tidak dikenali sistem. Bisa diulang dengan kalimat lain?');

            return;
        }

        $validator = Validator::make($args, $function->rules($user, $args));

        if ($validator->fails()) {
            AiFunctionLog::query()->create([
                'chat_message_id' => $userMessage->id,
                'user_id' => $user->id,
                'function_name' => $name,
                'arguments' => $args,
                'result' => ['errors' => $validator->errors()->all()],
                'status' => AiFunctionStatus::Failed,
            ]);

            $this->reply($user, $validator->errors()->first());

            return;
        }

        $validated = $validator->validated();

        if ($function instanceof RequiresConfirmation) {
            $log = AiFunctionLog::query()->create([
                'chat_message_id' => $userMessage->id,
                'user_id' => $user->id,
                'function_name' => $name,
                'arguments' => $validated,
                'result' => null,
                'status' => AiFunctionStatus::PendingConfirmation,
            ]);

            $preview = $function->preview($user, $validated);

            $this->reply($user, $this->confirmationQuestion($name), [
                'type' => 'confirmation',
                'log_id' => $log->id,
                'function_name' => $name,
                'preview' => $preview,
            ]);

            return;
        }

        $result = $function->execute($user, $validated, $userMessage);

        AiFunctionLog::query()->create([
            'chat_message_id' => $userMessage->id,
            'user_id' => $user->id,
            'function_name' => $name,
            'arguments' => $validated,
            'result' => $result->logPayload,
            'status' => $result->success ? AiFunctionStatus::Success : AiFunctionStatus::Failed,
        ]);

        $this->reply($user, $result->message, $result->attachment);
    }

    private function confirmationQuestion(string $functionName): string
    {
        return match ($functionName) {
            'hapus_transaksi' => 'Apakah Anda yakin ingin menghapus transaksi ini?',
            'nonaktifkan_transaksi_rutin' => 'Nonaktifkan transaksi rutin ini? Transaksi yang sudah tercatat tidak ikut terhapus.',
            default => 'Apakah Anda ingin menyimpan perubahan ini?',
        };
    }

    /**
     * @param  array<string, mixed>|null  $attachment
     */
    /**
     * Pesan yang ramah pengguna sesuai penyebab kegagalan Claude API. Detail teknis tidak ditampilkan.
     */
    private function claudeFailureMessage(ClaudeRequestException $exception): string
    {
        return match (true) {
            $exception->isCreditBalanceExhausted() => 'Fitur AI sedang tidak tersedia. Kamu tetap bisa mencatat transaksi manual lewat tombol +.',
            $exception->isOverloaded() => 'Layanan AI sedang sibuk. Coba lagi dalam beberapa saat ya.',
            $exception->isConnectionFailure => 'Koneksi ke layanan AI terputus. Coba kirim ulang pesanmu.',
            default => 'Maaf, saya tidak bisa menghubungi layanan AI saat ini. Silakan coba lagi sebentar.',
        };
    }

    /**
     * Catat detail teknis kegagalan ke log. Sengaja tidak memakai report() supaya stack trace
     * (yang bisa memuat argumen header berisi API key) tidak ikut tertulis.
     */
    private function logClaudeFailure(User $user, ClaudeRequestException $exception): void
    {
        Log::error('Permintaan ke Claude API gagal.', [
            'user_id' => $user->id,
            'status' => $exception->status,
            'error_type' => $exception->errorType,
            'connection_failure' => $exception->isConnectionFailure,
            'message' => $exception->getMessage(),
        ]);
    }

    private function reply(User $user, string $content, ?array $attachment = null): ChatMessage
    {
        return $user->chatMessages()->create([
            'role' => ChatRole::Assistant,
            'content' => $content,
            'attachment' => $attachment,
        ]);
    }

    /**
     * @return array<int, array{role: string, content: string|array<int, array<string, mixed>>}>
     */
    private function buildMessages(User $user): array
    {
        return $user->chatMessages()
            ->latest('id')
            ->limit(20)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (ChatMessage $message): array => [
                'role' => $message->role === ChatRole::User ? 'user' : 'assistant',
                'content' => $message->content,
            ])
            ->all();
    }
}
