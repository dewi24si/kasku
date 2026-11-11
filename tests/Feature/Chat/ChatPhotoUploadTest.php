<?php

namespace Tests\Feature\Chat;

use App\AiFunctionStatus;
use App\Livewire\Chat;
use App\Models\AiFunctionLog;
use App\Models\Category;
use App\Models\ChatMessage;
use App\Models\User;
use App\TransactionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\FakesClaude;
use Tests\TestCase;

class ChatPhotoUploadTest extends TestCase
{
    use FakesClaude, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');
    }

    public function test_can_send_a_photo_without_text_and_it_triggers_tambah_transaksi(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);

        $this->fakeClaudeToolUse('tambah_transaksi', [
            'items' => [[
                'type' => 'pengeluaran', 'category_id' => $category->id, 'amount' => 51000,
                'description' => 'Warung Kopi Senja', 'transaction_date' => '2026-11-02',
            ]],
        ]);

        $photo = UploadedFile::fake()->image('struk.jpg');

        Livewire::actingAs($user)->test(Chat::class)
            ->set('photo', $photo)
            ->call('sendMessage')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id, 'category_id' => $category->id, 'amount' => 51000, 'source' => 'chat',
        ]);
    }

    public function test_photo_is_stored_on_the_private_disk_and_attached_to_the_user_message(): void
    {
        $user = User::factory()->create();

        $this->fakeClaudeText('Baik, saya catat.');

        $photo = UploadedFile::fake()->image('struk.jpg');

        Livewire::actingAs($user)->test(Chat::class)
            ->set('message', 'tolong catat ini')
            ->set('photo', $photo)
            ->call('sendMessage');

        $userMessage = ChatMessage::where('user_id', $user->id)->where('role', 'user')->first();

        $this->assertNotNull($userMessage->attachment);
        $this->assertSame('receipt_photo', $userMessage->attachment['type']);

        $this->assertArrayNotHasKey('url', $userMessage->attachment);
        Storage::disk('local')->assertExists($userMessage->attachment['path']);
        Storage::disk('public')->assertMissing($userMessage->attachment['path']);
    }

    public function test_sends_image_content_block_to_claude(): void
    {
        $user = User::factory()->create();

        $this->fakeClaudeText('Baik.');

        $photo = UploadedFile::fake()->image('struk.jpg');

        Livewire::actingAs($user)->test(Chat::class)
            ->set('photo', $photo)
            ->call('sendMessage');

        Http::assertSent(function ($request): bool {
            $messages = $request['messages'];
            $lastMessage = end($messages);

            return is_array($lastMessage['content'])
                && $lastMessage['content'][0]['type'] === 'image'
                && $lastMessage['content'][0]['source']['type'] === 'base64';
        });
    }

    public function test_requires_either_message_or_photo(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Chat::class)
            ->set('message', '')
            ->call('sendMessage')
            ->assertHasErrors('message');

        $this->assertDatabaseCount('chat_messages', 0);
    }

    public function test_rejects_non_image_files(): void
    {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        Livewire::actingAs($user)->test(Chat::class)
            ->set('photo', $file)
            ->assertHasErrors('photo');
    }

    public function test_rejects_oversized_photo(): void
    {
        $user = User::factory()->create();

        $file = UploadedFile::fake()->image('big.jpg')->size(6000);

        Livewire::actingAs($user)->test(Chat::class)
            ->set('photo', $file)
            ->assertHasErrors('photo');
    }

    public function test_function_call_from_photo_is_still_logged(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);

        $this->fakeClaudeToolUse('tambah_transaksi', [
            'items' => [['type' => 'pengeluaran', 'category_id' => $category->id, 'amount' => 20000, 'transaction_date' => '2026-11-02']],
        ]);

        $photo = UploadedFile::fake()->image('struk.jpg');

        Livewire::actingAs($user)->test(Chat::class)
            ->set('photo', $photo)
            ->call('sendMessage');

        $this->assertSame(AiFunctionStatus::Success, AiFunctionLog::where('user_id', $user->id)->first()->status);
    }
}
