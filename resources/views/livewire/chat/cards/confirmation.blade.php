@php $log = App\Models\AiFunctionLog::where('user_id', auth()->id())->find($attachment['log_id']); @endphp
@if ($log)
    @php
        $isDelete = $log->function_name === 'hapus_transaksi';
        $isDeactivate = $log->function_name === 'nonaktifkan_transaksi_rutin';
        $isUpdate = ! $isDelete && ! $isDeactivate;

        $before = $attachment['preview']['before'] ?? [];
        $after = $attachment['preview']['after'] ?? null;

        $formatRupiah = fn (mixed $amount): string => \App\Services\Format\Rupiah::format((int) $amount);
        $formatDate = fn (?string $date): string => filled($date) ? \Illuminate\Support\Carbon::parse($date)->translatedFormat('d M Y') : '-';
        $formatType = fn (?string $type): string => filled($type) ? ucfirst($type) : '-';

        // Pratinjau lama untuk transaksi rutin menyimpan deskripsi di category.name dan belum punya "description".
        $isLegacyRecurringPreview = $isDeactivate && ! array_key_exists('description', $before);
        $title = $isLegacyRecurringPreview
            ? ($before['category']['name'] ?? '-')
            : (($before['description'] ?? null) ?: ($before['category']['name'] ?? '-'));

        $changes = [];

        if ($isUpdate && is_array($after)) {
            $fieldChanges = [
                'Nominal' => [$before['amount'] ?? null, $after['amount'] ?? null, $formatRupiah, true],
                'Kategori' => [$before['category']['id'] ?? null, $after['category']['id'] ?? null, null, false],
                'Tipe' => [$before['type'] ?? null, $after['type'] ?? null, $formatType, false],
                'Deskripsi' => [$before['description'] ?? null, $after['description'] ?? null, fn (?string $text): string => filled($text) ? $text : '-', false],
                'Tanggal' => [$before['transaction_date'] ?? null, $after['transaction_date'] ?? null, $formatDate, false],
            ];

            foreach ($fieldChanges as $label => [$old, $new, $format, $isMoney]) {
                if ((string) $old === (string) $new) {
                    continue;
                }

                $changes[] = $label === 'Kategori'
                    ? ['label' => $label, 'old' => $before['category']['name'] ?? '-', 'new' => $after['category']['name'] ?? '-', 'money' => false]
                    : ['label' => $label, 'old' => $format($old), 'new' => $format($new), 'money' => $isMoney];
            }
        }
    @endphp
    <div class="w-full max-w-xs rounded-box border border-base-300 bg-base-100 p-4 md:max-w-sm">
        <div class="mb-3 flex items-center justify-between">
            <p class="label-struk">{{ $isDelete ? 'Konfirmasi hapus' : ($isDeactivate ? 'Konfirmasi nonaktifkan' : 'Konfirmasi ubah') }}</p>
            <i data-lucide="{{ $isDelete ? 'trash-2' : ($isDeactivate ? 'repeat' : 'pencil') }}" class="size-4 text-neutral"></i>
        </div>

        @if ($before !== [])
            <div class="mb-3">
                @if ($isUpdate)
                    <p class="truncate text-sm font-medium">{{ $title }} &middot; {{ $formatDate($before['transaction_date'] ?? null) }}</p>
                    <p class="truncate text-xs text-neutral">
                        {{ $before['category']['name'] ?? '-' }} &middot; {{ $formatType($before['type'] ?? null) }} &middot; <span class="rupiah">{{ $formatRupiah($before['amount'] ?? 0) }}</span>
                    </p>

                    @if ($changes !== [])
                        <div class="mt-2.5 space-y-1.5 border-t border-dashed border-base-content/30 pt-2.5">
                            @foreach ($changes as $change)
                                <div class="flex items-baseline gap-2 text-sm">
                                    <p class="label-struk w-20 shrink-0">{{ $change['label'] }}</p>
                                    <p class="min-w-0 flex-1 break-words">
                                        <span @class(['line-through text-neutral', 'rupiah' => $change['money']])>{{ $change['old'] }}</span>
                                        <span class="text-neutral" aria-label="menjadi">&rarr;</span>
                                        <span @class(['font-semibold', 'rupiah' => $change['money']])>{{ $change['new'] }}</span>
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="flex items-baseline gap-2 text-sm">
                        <p class="min-w-0 truncate font-medium">{{ $title }}</p>
                        <x-ledger-leader />
                        <p @class(['rupiah shrink-0', 'text-error' => $isDelete])>{{ $formatRupiah($before['amount'] ?? 0) }}</p>
                    </div>

                    @if ($isDelete)
                        <p class="truncate text-xs text-neutral">
                            {{ $before['category']['name'] ?? '-' }} &middot; {{ $formatDate($before['transaction_date'] ?? null) }} &middot; {{ $formatType($before['type'] ?? null) }}
                        </p>
                    @else
                        <p class="truncate text-xs text-neutral">
                            @unless ($isLegacyRecurringPreview)
                                {{ $before['category']['name'] ?? '-' }} &middot;
                            @endunless
                            {{ $before['schedule'] ?? '' }}
                        </p>
                    @endif
                @endif
            </div>
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
