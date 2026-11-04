@php $log = App\Models\AiFunctionLog::where('user_id', auth()->id())->find($attachment['log_id']); @endphp
@if ($log)
    <div class="w-full max-w-xs rounded-2xl bg-base-100 p-3 shadow-sm ring-1 ring-base-300">
        @if (! empty($attachment['preview']['before']))
            <div class="mb-2 text-xs text-base-content/60">
                {{ $attachment['preview']['before']['category']['name'] ?? '' }}
                — Rp{{ number_format($attachment['preview']['before']['amount'] ?? 0, 0, ',', '.') }}
            </div>
        @endif

        @if ($log->status === App\AiFunctionStatus::PendingConfirmation)
            <div class="flex gap-2">
                <button type="button" wire:click="confirmFunction({{ $log->id }})" class="btn btn-success btn-sm flex-1">
                    <i data-lucide="check" class="size-4"></i> Ya
                </button>
                <button type="button" wire:click="cancelFunction({{ $log->id }})" class="btn btn-ghost btn-sm flex-1">
                    <i data-lucide="x" class="size-4"></i> Batal
                </button>
            </div>
        @else
            <span class="badge {{ $log->status === App\AiFunctionStatus::Confirmed ? 'badge-success' : ($log->status === App\AiFunctionStatus::Cancelled ? 'badge-ghost' : 'badge-error') }}">
                {{ match ($log->status) {
                    App\AiFunctionStatus::Confirmed => 'Dikonfirmasi',
                    App\AiFunctionStatus::Cancelled => 'Dibatalkan',
                    default => 'Gagal',
                } }}
            </span>
        @endif
    </div>
@endif
