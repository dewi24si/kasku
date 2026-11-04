<div class="-mx-4 -mt-6 -mb-24 flex h-[calc(100dvh-8.5rem)] flex-col">
    <div
        id="chat-scroll"
        class="flex-1 space-y-3 overflow-y-auto px-4 py-4"
        x-data
        x-init="$el.scrollTop = $el.scrollHeight"
        x-on:chat-message-added.window="setTimeout(() => $el.scrollTop = $el.scrollHeight, 50)"
    >
        @forelse ($this->messages as $chatMessage)
            @include('livewire.chat._message', ['chatMessage' => $chatMessage])
        @empty
            <div class="flex h-full flex-col items-center justify-center gap-2 text-center text-base-content/50">
                <i data-lucide="bot" class="size-10"></i>
                <p class="text-sm">Mulai chat untuk mencatat transaksi atau tanya keuanganmu.</p>
                <p class="text-xs">Contoh: "catat makan siang 25000" atau kirim foto struk</p>
                <p class="text-xs">Atau ketuk salah satu saran di bawah 👇</p>
            </div>
        @endforelse

        <div wire:loading wire:target="sendMessage,sendSuggestion" class="flex items-center gap-2 text-sm text-base-content/50">
            <i data-lucide="loader-2" class="size-4 animate-spin"></i>
            AI sedang mengetik...
        </div>
    </div>

    <div class="border-t border-base-200 bg-base-100/95 px-4 py-3 backdrop-blur-xl" x-data>
        @if ($photo)
            <div class="mb-2 flex items-center gap-2 rounded-2xl bg-base-200 p-2">
                <img src="{{ $photo->temporaryUrl() }}" alt="Pratinjau foto" class="size-12 rounded-xl object-cover">
                <p class="flex-1 truncate text-xs text-base-content/60">{{ $photo->getClientOriginalName() }}</p>
                <button type="button" wire:click="removePhoto" class="btn btn-ghost btn-xs btn-circle">
                    <i data-lucide="x" class="size-3.5"></i>
                </button>
            </div>
        @endif

        <div wire:loading wire:target="photo" class="mb-2 flex items-center gap-2 text-xs text-base-content/50">
            <i data-lucide="loader-2" class="size-3.5 animate-spin"></i>
            Mengunggah foto...
        </div>

        @unless ($photo)
        <div class="-mx-4 mb-2 flex gap-2 overflow-x-auto px-4 pb-1 [scrollbar-width:none]" wire:loading.class="pointer-events-none opacity-50" wire:target="sendMessage,sendSuggestion">
            @foreach (\App\Livewire\Chat::SUGGESTIONS as $key => $suggestion)
                <button
                    type="button"
                    wire:click="sendSuggestion('{{ $key }}')"
                    class="btn btn-sm shrink-0 rounded-full border-base-300 bg-base-100 font-medium text-base-content/80"
                >
                    <i data-lucide="{{ $suggestion['icon'] }}" class="size-3.5 text-primary"></i>
                    {{ $suggestion['label'] }}
                </button>
            @endforeach
            @foreach (\App\Livewire\Chat::PREFILLS as $prefill)
                <button
                    type="button"
                    x-on:click="$wire.message = @js($prefill['prefill']); $nextTick(() => { $refs.messageInput.focus(); $refs.messageInput.setSelectionRange(999, 999) })"
                    class="btn btn-sm shrink-0 rounded-full border-dashed border-base-300 bg-base-100 font-medium text-base-content/60"
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
                class="btn btn-ghost btn-circle shrink-0 bg-base-200"
                title="Lampirkan foto struk"
            >
                <i data-lucide="camera" class="size-4"></i>
            </button>

            <input
                type="text"
                wire:model="message"
                x-ref="messageInput"
                placeholder="Tulis pesan atau lampirkan foto..."
                autocomplete="off"
                class="input flex-1 rounded-full bg-base-200"
            >
            <button
                type="submit"
                class="btn btn-circle btn-primary shrink-0 border-none"
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
            <p class="pt-2 text-center text-xs text-base-content/50">Sisa {{ $this->remainingDailyMessages }} pesan hari ini</p>
        @endif
        @error('photo')
            <p class="pt-2 text-xs text-error">{{ $message }}</p>
        @enderror
    </div>
</div>
