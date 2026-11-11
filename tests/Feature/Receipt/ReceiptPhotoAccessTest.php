<?php

namespace Tests\Feature\Receipt;

use App\ChatRole;
use App\Livewire\Chat;
use App\Models\ChatMessage;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\FakesClaude;
use Tests\TestCase;

class ReceiptPhotoAccessTest extends TestCase
{
    use FakesClaude, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    /**
     * @return array{0: User, 1: ChatMessage, 2: Transaction}
     */
    private function ownerWithReceipt(): array
    {
        $owner = User::factory()->create();
        $path = UploadedFile::fake()->image('struk.jpg')->store("receipts/{$owner->id}", 'local');

        $chatMessage = ChatMessage::factory()->create([
            'user_id' => $owner->id,
            'role' => ChatRole::User,
            'attachment' => ['type' => 'receipt_photo', 'path' => $path],
        ]);

        $transaction = Transaction::factory()->create([
            'user_id' => $owner->id,
            'chat_message_id' => $chatMessage->id,
            'receipt_path' => $path,
        ]);

        return [$owner, $chatMessage, $transaction];
    }

    public function test_guests_cannot_open_receipt_photos(): void
    {
        [, $chatMessage, $transaction] = $this->ownerWithReceipt();

        $this->get(route('struk.foto', $transaction))->assertRedirect(route('login'));
        $this->get(route('struk.foto-chat', $chatMessage))->assertRedirect(route('login'));
    }

    public function test_other_users_cannot_open_receipt_photos(): void
    {
        [, $chatMessage, $transaction] = $this->ownerWithReceipt();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser)->get(route('struk.foto', $transaction))->assertForbidden();
        $this->actingAs($otherUser)->get(route('struk.foto-chat', $chatMessage))->assertForbidden();
    }

    public function test_owner_can_open_receipt_photo_of_transaction_and_chat_message(): void
    {
        [$owner, $chatMessage, $transaction] = $this->ownerWithReceipt();

        $this->actingAs($owner)->get(route('struk.foto', $transaction))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');

        $this->actingAs($owner)->get(route('struk.foto-chat', $chatMessage))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_returns_not_found_when_transaction_has_no_receipt_photo(): void
    {
        $owner = User::factory()->create();
        $transaction = Transaction::factory()->create(['user_id' => $owner->id, 'receipt_path' => null]);
        $textMessage = ChatMessage::factory()->create(['user_id' => $owner->id, 'attachment' => null]);

        $this->actingAs($owner)->get(route('struk.foto', $transaction))->assertNotFound();
        $this->actingAs($owner)->get(route('struk.foto-chat', $textMessage))->assertNotFound();
    }

    public function test_new_photos_are_not_publicly_reachable_and_chat_card_uses_the_protected_route(): void
    {
        $user = User::factory()->create();

        $this->fakeClaudeText('Baik.');

        Livewire::actingAs($user)->test(Chat::class)
            ->set('photo', UploadedFile::fake()->image('struk.jpg'))
            ->call('sendMessage');

        $userMessage = ChatMessage::query()->where('user_id', $user->id)->where('role', ChatRole::User)->firstOrFail();

        Storage::disk('local')->assertExists($userMessage->attachment['path']);
        $this->assertSame([], Storage::disk('public')->allFiles());

        $this->actingAs($user)->get(route('chat'))
            ->assertSee(route('struk.foto-chat', $userMessage), false)
            ->assertDontSee('/storage/receipts/', false);
    }
}
