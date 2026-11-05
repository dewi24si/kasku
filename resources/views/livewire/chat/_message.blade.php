@php $isUser = $chatMessage->role === App\ChatRole::User; @endphp
<div class="flex {{ $isUser ? 'justify-end' : 'justify-start' }}">
    <div class="flex max-w-[85%] md:max-w-[75%] items-start gap-2 {{ $isUser ? 'flex-row-reverse' : '' }}">
        @unless ($isUser)
            <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-base-300 text-base-content/70">
                <i data-lucide="bot" class="size-4"></i>
            </span>
        @endunless

        <div class="flex flex-col gap-2 {{ $isUser ? 'items-end' : 'items-start' }}">
            <div class="{{ $isUser ? 'bg-primary text-primary-content' : 'bg-base-100 ring-1 ring-base-300 text-base-content' }} animate-pop-in rounded-2xl px-4 py-2 text-sm whitespace-pre-line">
                {{ $chatMessage->content }}
            </div>

            @if ($chatMessage->attachment)
                @includeIf("livewire.chat.cards.{$chatMessage->attachment['type']}", ['attachment' => $chatMessage->attachment])
            @endif
        </div>
    </div>
</div>
