<?php

namespace Tests\Feature\Profile;

use App\Livewire\Profile;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\TransactionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/profil')->assertRedirect('/login');
    }

    public function test_authenticated_user_sees_profile_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/profil')
            ->assertOk()
            ->assertSee($user->email);
    }

    public function test_can_update_name_and_email(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Profile::class)
            ->set('name', 'Nama Baru')
            ->set('email', 'baru@example.com')
            ->call('updateProfile');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Nama Baru', 'email' => 'baru@example.com']);
    }

    public function test_cannot_update_email_to_one_already_taken(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create(['email' => 'taken@example.com']);

        Livewire::actingAs($user)->test(Profile::class)
            ->set('email', 'taken@example.com')
            ->call('updateProfile')
            ->assertHasErrors('email');

        $this->assertDatabaseMissing('users', ['id' => $user->id, 'email' => 'taken@example.com']);
    }

    public function test_can_update_password_with_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password')]);

        Livewire::actingAs($user)->test(Profile::class)
            ->set('current_password', 'old-password')
            ->set('password', 'new-password-123')
            ->set('password_confirmation', 'new-password-123')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('new-password-123', $user->refresh()->password));
    }

    public function test_rejects_password_update_with_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('old-password')]);

        Livewire::actingAs($user)->test(Profile::class)
            ->set('current_password', 'wrong-password')
            ->set('password', 'new-password-123')
            ->set('password_confirmation', 'new-password-123')
            ->call('updatePassword')
            ->assertHasErrors('current_password');

        $this->assertTrue(Hash::check('old-password', $user->refresh()->password));
    }

    public function test_can_add_a_custom_category(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(Profile::class)
            ->set('categoryName', 'Hobi')
            ->set('categoryType', 'pengeluaran')
            ->set('categoryIcon', '🎮')
            ->call('addCategory');

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id, 'name' => 'Hobi', 'type' => 'pengeluaran',
        ]);
    }

    public function test_cannot_add_duplicate_category_name_and_type(): void
    {
        $user = User::factory()->create();
        Category::factory()->create(['user_id' => $user->id, 'name' => 'Hobi', 'type' => TransactionType::Pengeluaran]);

        Livewire::actingAs($user)->test(Profile::class)
            ->set('categoryName', 'Hobi')
            ->set('categoryType', 'pengeluaran')
            ->call('addCategory')
            ->assertHasErrors('categoryName');

        $this->assertDatabaseCount('categories', 1);
    }

    public function test_can_delete_unused_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);

        Livewire::actingAs($user)->test(Profile::class)
            ->call('deleteCategory', $category->id);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_cannot_delete_category_used_by_a_transaction(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);
        Transaction::factory()->create(['user_id' => $user->id, 'category_id' => $category->id, 'type' => TransactionType::Pengeluaran]);

        Livewire::actingAs($user)->test(Profile::class)
            ->call('deleteCategory', $category->id);

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_cannot_delete_category_used_by_a_budget(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $user->id, 'type' => TransactionType::Pengeluaran]);
        Budget::factory()->create(['user_id' => $user->id, 'category_id' => $category->id]);

        Livewire::actingAs($user)->test(Profile::class)
            ->call('deleteCategory', $category->id);

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_cannot_delete_another_users_category(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $category = Category::factory()->create(['user_id' => $otherUser->id, 'type' => TransactionType::Pengeluaran]);

        Livewire::actingAs($user)->test(Profile::class)
            ->call('deleteCategory', $category->id);

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
