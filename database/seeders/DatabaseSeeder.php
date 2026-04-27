<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketClassifier;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    
    public function run(): void
    {
        User::query()->create([
            'first_name' => 'Герман',
            'last_name' => 'Прудниченков',
            'name' => 'Герман Прудниченков',
            'login' => 'admin',
            'email' => 'admin@helpdesk.local',
            'password' => 'admin12345',
            'role' => 'admin',
            'avatar_path' => 'avatars/german-prudnichenkov.jpg',
        ]);

        User::query()->create([
            'first_name' => 'Сергей',
            'last_name' => 'Петров',
            'name' => 'Сергей Петров',
            'login' => 'support',
            'email' => 'support@helpdesk.local',
            'password' => 'support12345',
            'role' => 'support',
        ]);

        $user = User::query()->create([
            'first_name' => 'Антон',
            'last_name' => 'Иванов',
            'name' => 'Антон Иванов',
            'login' => 'user',
            'email' => 'user@helpdesk.local',
            'password' => 'user12345',
            'role' => 'user',
        ]);

        $this->call(ClassificationRuleSeeder::class);

        $classifier = app(TicketClassifier::class);

        $tickets = [
            [
                'title' => 'Не работает фотошоп',
                'description' => 'Не удается войти в Adobe аккаунт.',
                'status' => Ticket::STATUS_NEW,
            ],
            [
                'title' => 'Компьютер не включается',
                'description' => 'После нажатия кнопки питания экран остается черным.',
                'status' => Ticket::STATUS_IN_PROGRESS,
            ],
            [
                'title' => 'Срочно не работает CRM у всего отдела',
                'description' => 'CRM выдает ошибку, работа остановилась, не открываются сделки.',
                'status' => Ticket::STATUS_RESOLVED,
            ],
        ];

        foreach ($tickets as $data) {
            $classification = $classifier->classify($data['title'], $data['description']);

            Ticket::query()->create([
                'user_id' => $user->id,
                'title' => $data['title'],
                'description' => $data['description'],
                'status' => $data['status'],
                'priority' => $classification['priority'],
                'category' => $classification['category'],
                'assigned_to' => $classification['assigned_to'],
                'classification_score' => $classification['classification_score'],
                'needs_manual_review' => $classification['needs_manual_review'],
            ]);
        }
    }
}
