@php $isUser = $chatMessage->role === App\ChatRole::User; @endphp
<div class="flex {{ $isUser ? 'justify-end' : 'justify-start' }}">
    @if ($isUser)
        <div class="flex max-w-[85%] flex-col items-end gap-2 md:max-w-[75%]">
            <div class="animate-pop-in rounded-box rounded-br-sm bg-secondary px-4 py-2 text-sm whitespace-pre-line text-secondary-content">{{ trim($chatMessage->content) }}</div>

            @if ($chatMessage->attachment)
                @includeIf("livewire.chat.cards.{$chatMessage->attachment['type']}", ['attachment' => $chatMessage->attachment])
            @endif
        </div>
    @else
        <div class="flex max-w-[90%] animate-pop-in flex-col items-start gap-2 border-l-2 border-base-content/20 pl-3 md:max-w-[75%]">
            <p class="label-struk">Kasku</p>
            <div class="-mt-1 text-sm whitespace-pre-line text-base-content">{{ trim($chatMessage->content) }}</div>

            @if ($chatMessage->attachment)
                @includeIf("livewire.chat.cards.{$chatMessage->attachment['type']}", ['attachment' => $chatMessage->attachment])
            @endif
        </div>
    @endif
</div>
