<?php

namespace Database\Seeders;

use App\Models\Message;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;

class ChatDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $john = $this->demoUser(
            name: 'John Doe',
            username: 'johndoe',
            email: 'john@test',
        );

        foreach ($this->threads() as $thread) {
            $partner = $this->demoUser(
                name: $thread['name'],
                username: $thread['username'],
                email: $thread['email'],
                bio: $thread['bio'],
            );

            foreach ($thread['messages'] as $message) {
                $isFromJohn = $message['from'] === 'john';

                $this->seedMessage(
                    sender: $isFromJohn ? $john : $partner,
                    recipient: $isFromJohn ? $partner : $john,
                    body: $message['body'],
                    sentAt: now()->subMinutes($message['minutes_ago']),
                );
            }
        }
    }

    private function demoUser(
        string $name,
        string $username,
        string $email,
        ?string $bio = null,
    ): User {
        return User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'username' => $username,
                'bio' => $bio,
                'password' => 'password',
            ],
        );
    }

    private function seedMessage(
        User $sender,
        User $recipient,
        string $body,
        CarbonInterface $sentAt,
    ): void {
        $message = Message::query()->firstOrCreate([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'body' => $body,
        ]);

        $message->timestamps = false;
        $message->forceFill([
            'created_at' => $sentAt,
            'updated_at' => $sentAt,
        ])->save();
    }

    /**
     * Demo conversations for the john@test user, newest conversation first.
     *
     * @return array<int, array<string, mixed>>
     */
    private function threads(): array
    {
        return [
            [
                'name' => 'Sara Kim',
                'username' => 'sarakim',
                'email' => 'sara@test',
                'bio' => 'Product designer. Coffee first.',
                'messages' => [
                    ['from' => 'sara', 'body' => 'Morning! Did you see the new mockups?', 'minutes_ago' => 55],
                    ['from' => 'john', 'body' => 'Just opened them. The sidebar looks great.', 'minutes_ago' => 48],
                    ['from' => 'sara', 'body' => 'Right? I moved the conversation list into its own layout.', 'minutes_ago' => 40],
                    ['from' => 'sara', 'body' => 'Ping me when you have a minute to review.', 'minutes_ago' => 5],
                ],
            ],
            [
                'name' => 'Jane Cooper',
                'username' => 'janecooper',
                'email' => 'jane@test',
                'bio' => 'Backend engineer, plant collector.',
                'messages' => [
                    ['from' => 'john', 'body' => 'Hey Jane! Are we still on for coffee tomorrow?', 'minutes_ago' => 120],
                    ['from' => 'jane', 'body' => 'Definitely. Ten at the usual place?', 'minutes_ago' => 95],
                    ['from' => 'john', 'body' => 'Perfect, see you there.', 'minutes_ago' => 80],
                ],
            ],
            [
                'name' => 'Mike Ross',
                'username' => 'mikeross',
                'email' => 'mike@test',
                'bio' => 'Ships on Fridays.',
                'messages' => [
                    ['from' => 'mike', 'body' => 'The message migration indexes look good to me.', 'minutes_ago' => 2880],
                    ['from' => 'john', 'body' => 'Thanks for checking. Merging it.', 'minutes_ago' => 2860],
                ],
            ],
        ];
    }
}
