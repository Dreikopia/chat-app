<?php

namespace Database\Factories;

use App\Enums\FriendshipStatus;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Friendship>
 */
class FriendshipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'requester_id' => User::factory(),
            'addressee_id' => User::factory(),
            'status' => FriendshipStatus::PENDING,
            'accepted_at' => null,
        ];
    }

    /**
     * Indicate that the friendship has been accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => FriendshipStatus::ACCEPTED,
            'accepted_at' => now(),
        ]);
    }

    /**
     * Indicate that the friendship is still pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => FriendshipStatus::PENDING,
            'accepted_at' => null,
        ]);
    }

    /**
     * Indicate the two users in the friendship.
     */
    public function between(User $requester, User $addressee): static
    {
        return $this->state(fn (array $attributes): array => [
            'requester_id' => $requester->id,
            'addressee_id' => $addressee->id,
        ]);
    }
}
