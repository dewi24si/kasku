<div class="-mx-4 -mt-6 -mb-24 flex h-[calc(100dvh-7.5rem-env(safe-area-inset-bottom))] flex-col md:-mx-6 lg:-mx-10 lg:-my-10 lg:h-dvh">
    <div
        id="chat-scroll"
        class="flex-1 overflow-y-auto px-4 py-4 md:px-6 lg:px-10 lg:pt-10"
        x-data
        x-init="$el.scrollTop = $el.scrollHeight"
        x-on:chat-message-added.window="setTimeout(() => $el.scrollTop = $el.scrollHeight, 50)"
    >
        <div class="mx-auto flex min-h-full w-full max-w-3xl flex-col space-y-3">
        @forelse ($this->messages as $chatMessage)
            @include('livewire.chat._message', ['chatMessage' => $chatMessage])
        @empty
            <div class="flex flex-1 flex-col items-center justify-center gap-2 text-center text-neutral">
                <i data-lucide="bot" class="size-10"></i>
                <p class="text-sm">Mulai chat untuk mencatat transaksi atau tanya keuanganmu.</p>
                <p class="text-xs">Contoh: "catat makan siang 25000" atau kirim foto struk</p>
                <p class="text-xs">Atau ketuk salah satu saran di bawah.</p>
            </div>
        @endforelse

        <div wire:loading wire:target="sendMessage,sendSuggestion" class="flex items-center gap-2 border-l-2 border-base-content/20 pl-3 text-sm text-neutral">
            <i data-lucide="loader-2" class="size-4 animate-spin"></i>
            AI sedang mengetik...
        </div>
        </div>
    </div>

    <div class="border-t border-base-300 bg-base-100 px-4 py-3 md:px-6 lg:border-t-0 lg:bg-transparent lg:px-10 lg:pt-2 lg:pb-6" x-data="voiceInput">
        <div class="mx-auto w-full max-w-3xl lg:rounded-box lg:bg-base-100 lg:p-4 lg:border lg:border-base-300">
        @if ($photo)
            <div class="mb-2 flex items-center gap-2 rounded-field border border-base-300 bg-base-200 p-2">
                <img src="{{ $photo->temporaryUrl() }}" alt="Pratinjau foto" class="size-12 rounded-field object-cover">
                <p class="flex-1 truncate text-xs text-neutral">{{ $photo->getClientOriginalName() }}</p>
                <button type="button" wire:click="removePhoto" class="btn btn-ghost btn-xs btn-square" aria-label="Hapus foto">
                    <i data-lucide="x" class="size-3.5"></i>
                </button>
            </div>
        @endif

        <div wire:loading wire:target="photo" class="mb-2 flex items-center gap-2 text-xs text-neutral">
            <i data-lucide="loader-2" class="size-3.5 animate-spin"></i>
            Mengunggah foto...
        </div>

        @unless ($photo)
        <div class="-mx-4 mb-2 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none] md:-mx-6 md:px-6 lg:mx-0 lg:flex-wrap lg:overflow-visible lg:px-0" wire:loading.class="pointer-events-none opacity-50" wire:target="sendMessage,sendSuggestion">
            @foreach (\App\Livewire\Chat::SUGGESTIONS as $key => $suggestion)
                <button
                    type="button"
                    wire:click="sendSuggestion('{{ $key }}')"
                    class="btn btn-sm shrink-0 border-base-300 bg-base-100 font-medium text-base-content"
                >
                    <i data-lucide="{{ $suggestion['icon'] }}" class="size-3.5 text-primary"></i>
                    {{ $suggestion['label'] }}
                </button>
            @endforeach
            @foreach (\App\Livewire\Chat::PREFILLS as $prefill)
                <button
                    type="button"
                    x-on:click="$wire.message = @js($prefill['prefill']); $nextTick(() => { $refs.messageInput.focus(); $refs.messageInput.setSelectionRange(999, 999) })"
                    class="btn btn-sm shrink-0 border-dashed border-base-300 bg-base-100 font-medium text-neutral"
                >
                    <i data-lucide="{{ $prefill['icon'] }}" class="size-3.5"></i>
                    {{ $prefill['label'] }}
                </button>
            @endforeach
        </div>
        @endunless

        <form wire:submit="sendMessage" class="flex items-center gap-2">
            <input type="file" wire:model="photo" accept="image/*" x-ref="photoInput" class="hidden">
            <button
                type="button"
                x-on:click="$refs.photoInput.click()"
                class="btn btn-square shrink-0 border-base-300 bg-base-200"
                title="Lampirkan foto struk"
                aria-label="Lampirkan foto struk"
            >
                <i data-lucide="camera" class="size-4"></i>
            </button>

            <button
                type="button"
                id="voice-input-button"
                wire:ignore
                x-show="supported"
                style="display: none"
                x-on:click="toggle()"
                x-bind:class="recording ? 'btn-primary animate-pulse ring-4 ring-primary/20' : 'border-base-300 bg-base-200'"
                x-bind:title="recording ? 'Berhenti merekam' : 'Bicara untuk mengisi pesan'"
                x-bind:aria-label="recording ? 'Berhenti merekam' : 'Bicara untuk mengisi pesan'"
                x-bind:aria-pressed="recording.toString()"
                class="btn btn-square shrink-0"
                title="Bicara untuk mengisi pesan"
                aria-label="Bicara untuk mengisi pesan"
            >
                <span x-show="!recording" class="flex"><i data-lucide="mic" class="size-4"></i></span>
                <span x-show="recording" style="display: none" class="flex"><i data-lucide="square" class="size-3.5 fill-current"></i></span>
            </button>

            <input
                type="text"
                wire:model="message"
                x-ref="messageInput"
                placeholder="Tulis pesan atau lampirkan foto..."
                autocomplete="off"
                class="input flex-1 border-base-300 bg-base-200"
            >
            <button
                type="submit"
                class="btn btn-square btn-primary shrink-0"
                aria-label="Kirim pesan"
                wire:loading.attr="disabled"
                wire:target="sendMessage,sendSuggestion,photo"
            >
                <i data-lucide="send" class="size-4"></i>
            </button>
        </form>

        @error('message')
            <p class="pt-2 text-xs text-error">{{ $message }}</p>
        @enderror
        @if ($this->remainingDailyMessages > 0 && $this->remainingDailyMessages <= 10)
            <p class="pt-2 text-center text-xs text-neutral">Sisa {{ $this->remainingDailyMessages }} pesan hari ini</p>
        @endif
        @error('photo')
            <p class="pt-2 text-xs text-error">{{ $message }}</p>
        @enderror
        </div>
    </div>
</div>
