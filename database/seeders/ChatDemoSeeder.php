<?php

namespace Database\Seeders;

use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;

class ChatDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::factory()->count(5)->create();

        Message::factory()->create([
            'sender_id' => $users[0]->id,
            'recipient_id' => $users[1]->id,
            'body' => 'Hello my friend!',
        ]);

        Message::factory()->create([
            'sender_id' => $users[1]->id,
            'recipient_id' => $users[0]->id,
            'body' => 'Hello! How are you?',
        ]);

        Message::factory()->create([
            'sender_id' => $users[0]->id,
            'recipient_id' => $users[1]->id,
            'body' => 'Doing great!',
        ]);
    }
}
