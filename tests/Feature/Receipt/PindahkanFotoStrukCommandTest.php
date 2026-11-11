<?php

namespace Tests\Feature\Receipt;

use App\ChatRole;
use App\Models\ChatMessage;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PindahkanFotoStrukCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    /**
     * @return array{0: ChatMessage, 1: Transaction, 2: string}
     */
    private function legacyReceipt(): array
    {
        $user = User::factory()->create();
        $path = UploadedFile::fake()->image('struk.jpg')->store("receipts/{$user->id}", 'public');

        $chatMessage = ChatMessage::factory()->create([
            'user_id' => $user->id,
            'role' => ChatRole::User,
            'attachment' => ['type' => 'receipt_photo', 'url' => 'http://kasku.test/storage/'.$path],
        ]);

        $transaction = Transaction::factory()->create([
            'user_id' => $user->id,
            'chat_message_id' => $chatMessage->id,
            'receipt_path' => null,
        ]);

        return [$chatMessage, $transaction, $path];
    }

    public function test_moves_legacy_photos_to_the_private_disk_and_updates_attachment(): void
    {
        [$chatMessage, $transaction, $path] = $this->legacyReceipt();

        $this->artisan('kasku:pindahkan-foto-struk')->assertSuccessful();

        Storage::disk('local')->assertExists($path);
        Storage::disk('public')->assertMissing($path);

        $this->assertEquals(['type' => 'receipt_photo', 'path' => $path], $chatMessage->fresh()->attachment);
        $this->assertSame($path, $transaction->fresh()->receipt_path);
    }

    public function test_is_safe_to_run_twice(): void
    {
        [$chatMessage, $transaction, $path] = $this->legacyReceipt();
        $originalContents = Storage::disk('public')->get($path);

        $this->artisan('kasku:pindahkan-foto-struk')->assertSuccessful();
        $this->artisan('kasku:pindahkan-foto-struk')
            ->expectsOutputToContain('0 foto dipindahkan, 1 sudah di disk privat')
            ->assertSuccessful();

        Storage::disk('local')->assertExists($path);
        $this->assertSame($originalContents, Storage::disk('local')->get($path));
        $this->assertEquals(['type' => 'receipt_photo', 'path' => $path], $chatMessage->fresh()->attachment);
        $this->assertSame($path, $transaction->fresh()->receipt_path);
    }

    public function test_ignores_legacy_urls_pointing_outside_the_owners_folder(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherUsersPath = UploadedFile::fake()->image('struk.jpg')->store("receipts/{$otherUser->id}", 'public');

        $chatMessage = ChatMessage::factory()->create([
            'user_id' => $owner->id,
            'role' => ChatRole::User,
            'attachment' => ['type' => 'receipt_photo', 'url' => '/storage/'.$otherUsersPath],
        ]);

        $this->artisan('kasku:pindahkan-foto-struk')->assertSuccessful();

        Storage::disk('public')->assertExists($otherUsersPath);
        Storage::disk('local')->assertMissing($otherUsersPath);
        $this->assertArrayHasKey('url', $chatMessage->fresh()->attachment);
    }
}
