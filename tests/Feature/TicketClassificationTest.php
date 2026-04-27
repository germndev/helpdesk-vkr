<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\ClassificationRuleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TicketClassificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_gets_category_priority_and_assignee_automatically(): void
    {
        $admin = User::factory()->create([
            'first_name' => 'Иван',
            'last_name' => 'Админов',
            'login' => 'admin',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'first_name' => 'Сергей',
            'last_name' => 'Петров',
            'login' => 'support',
            'role' => 'support',
        ]);

        $user = User::factory()->create([
            'login' => 'user',
            'role' => 'user',
        ]);

        $this->seed(ClassificationRuleSeeder::class);

        DB::table('sessions')->insert([
            'id' => 'admin-session',
            'user_id' => $admin->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'test',
            'last_activity' => now()->timestamp,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('user.tickets.store'), [
                'title' => 'Срочно не работает CRM у всего отдела',
                'description' => 'CRM выдает ошибку, работа остановилась, нужно срочно восстановить доступ к сделкам.',
            ]);

        $response->assertRedirect(route('user.tickets.create'));

        $ticket = Ticket::query()->latest('id')->firstOrFail();

        $this->assertSame('CRM', $ticket->category);
        $this->assertSame('Критический', $ticket->priority);
        $this->assertSame($admin->id, $ticket->assigned_to);
        $this->assertFalse($ticket->needs_manual_review);
        $this->assertNotNull($ticket->classification_score);
    }

    public function test_ticket_chooses_less_loaded_online_responsible(): void
    {
        $admin = User::factory()->create([
            'first_name' => 'Иван',
            'last_name' => 'Админов',
            'login' => 'admin',
            'role' => 'admin',
        ]);

        $support = User::factory()->create([
            'first_name' => 'Сергей',
            'last_name' => 'Петров',
            'login' => 'support',
            'role' => 'support',
        ]);

        $user = User::factory()->create([
            'login' => 'user',
            'role' => 'user',
        ]);

        $this->seed(ClassificationRuleSeeder::class);

        DB::table('sessions')->insert([
            'id' => 'support-session',
            'user_id' => $support->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'test',
            'last_activity' => now()->timestamp,
        ]);

        Ticket::query()->create([
            'user_id' => $user->id,
            'title' => 'Тестовая загруженность',
            'description' => 'Нагрузка',
            'status' => Ticket::STATUS_IN_PROGRESS,
            'priority' => 'Критический',
            'category' => 'CRM',
            'assigned_to' => $admin->id,
            'needs_manual_review' => false,
        ]);

        $this
            ->actingAs($user)
            ->post(route('user.tickets.store'), [
                'title' => 'CRM не открывается',
                'description' => 'CRM выдает ошибку входа в карточку клиента.',
            ]);

        $ticket = Ticket::query()->latest('id')->firstOrFail();

        $this->assertSame('CRM', $ticket->category);
        $this->assertSame($support->id, $ticket->assigned_to);
    }

    public function test_ticket_goes_to_manual_review_when_category_confidence_is_too_low(): void
    {
        User::factory()->create([
            'first_name' => 'Сергей',
            'last_name' => 'Петров',
            'login' => 'support',
            'role' => 'support',
        ]);

        User::factory()->create([
            'first_name' => 'Иван',
            'last_name' => 'Админов',
            'login' => 'admin',
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'login' => 'user',
            'role' => 'user',
        ]);

        $this->seed(ClassificationRuleSeeder::class);

        $this
            ->actingAs($user)
            ->post(route('user.tickets.store'), [
                'title' => 'Нужна помощь',
                'description' => 'Есть вопрос по работе системы.',
            ]);

        $ticket = Ticket::query()->latest('id')->firstOrFail();

        $this->assertNull($ticket->category);
        $this->assertSame('Низкий', $ticket->priority);
        $this->assertNull($ticket->assigned_to);
        $this->assertTrue($ticket->needs_manual_review);
        $this->assertNull($ticket->classification_score);
    }
}
