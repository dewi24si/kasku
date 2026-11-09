@php $log = App\Models\AiFunctionLog::where('user_id', auth()->id())->find($attachment['log_id']); @endphp
@if ($log)
    @php
        $isDelete = $log->function_name === 'hapus_transaksi';
        $isDeactivate = $log->function_name === 'nonaktifkan_transaksi_rutin';
    @endphp
    <div class="w-full max-w-xs rounded-box border border-base-300 bg-base-100 p-4 md:max-w-sm">
        <div class="mb-3 flex items-center justify-between">
            <p class="label-struk">{{ $isDelete ? 'Konfirmasi hapus' : ($isDeactivate ? 'Konfirmasi nonaktifkan' : 'Konfirmasi ubah') }}</p>
            <i data-lucide="{{ $isDelete ? 'trash-2' : ($isDeactivate ? 'repeat' : 'pencil') }}" class="size-4 text-neutral"></i>
        </div>

        @if (! empty($attachment['preview']['before']))
            <div class="mb-3 flex items-baseline gap-2 text-sm">
                <span class="truncate">{{ $attachment['preview']['before']['category']['name'] ?? '' }}</span>
                <x-ledger-leader />
                <span class="rupiah">Rp{{ number_format($attachment['preview']['before']['amount'] ?? 0, 0, ',', '.') }}</span>
            </div>
            @if (! empty($attachment['preview']['before']['schedule']))
                <p class="-mt-2 mb-3 text-xs text-neutral">{{ $attachment['preview']['before']['schedule'] }}</p>
            @endif
        @endif

        @if ($log->status === App\AiFunctionStatus::PendingConfirmation)
            <div class="flex gap-2 border-t border-dashed border-base-content/30 pt-3">
                <button type="button" wire:click="confirmFunction({{ $log->id }})" class="btn btn-sm flex-1 {{ $isDelete ? 'btn-error' : 'btn-primary' }}">
                    <i data-lucide="check" class="size-4"></i> Ya
                </button>
                <button type="button" wire:click="cancelFunction({{ $log->id }})" class="btn btn-outline btn-sm flex-1 border-base-300">
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
