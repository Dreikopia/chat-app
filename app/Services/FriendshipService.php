<?php

namespace App\Services;

use App\Enums\FriendshipStatus;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FriendshipService
{
    public function sendRequest(User $requester, User $addressee): Friendship
    {
        if ($requester->is($addressee)) {
            throw ValidationException::withMessages([
                'user' => 'You cannot send a friend request to yourself.',
            ]);
        }

        return DB::transaction(function () use ($requester, $addressee) {
            $this->lockUsers($requester, $addressee);

            $existing = Friendship::query()
                ->where(function ($query) use ($requester, $addressee) {
                    $query->where('requester_id', $requester->id)
                        ->where('addressee_id', $addressee->id);
                })
                ->orWhere(function ($query) use ($requester, $addressee) {
                    $query->where('requester_id', $addressee->id)
                        ->where('addressee_id', $requester->id);
                })
                ->lockForUpdate()
                ->get();

            $accepted = $existing->first(
                fn (Friendship $friendship) => $friendship->status === FriendshipStatus::ACCEPTED
            );

            if ($accepted) {
                return $accepted;
            }

            $reverseRequest = $existing->first(
                fn (Friendship $friendship) => $friendship->requester_id === $addressee->id
                    && $friendship->addressee_id === $requester->id
            );

            if ($reverseRequest) {
                $reverseRequest->update([
                    'status' => FriendshipStatus::ACCEPTED,
                    'accepted_at' => now(),
                ]);

                return $reverseRequest->refresh();
            }

            $existingRequest = $existing->first(
                fn (Friendship $friendship) => $friendship->requester_id === $requester->id
                    && $friendship->addressee_id === $addressee->id
            );

            if ($existingRequest) {
                return $existingRequest;
            }

            return Friendship::create([
                'requester_id' => $requester->id,
                'addressee_id' => $addressee->id,
                'status' => FriendshipStatus::PENDING,
            ]);
        });
    }

    public function acceptRequest(Friendship $friendship, User $user): Friendship
    {
        return DB::transaction(function () use ($friendship, $user) {
            $this->lockUsers(
                $friendship->requester,
                $friendship->addressee
            );

            $friendship = Friendship::query()
                ->lockForUpdate()
                ->findOrFail($friendship->id);

            if ($friendship->addressee_id !== $user->id) {
                throw new AuthorizationException(
                    'You cannot accept this friend request.'
                );
            }

            if ($friendship->status === FriendshipStatus::ACCEPTED) {
                return $friendship;
            }

            $this->ensurePending($friendship);

            $friendship->update([
                'status' => FriendshipStatus::ACCEPTED,
                'accepted_at' => now(),
            ]);

            return $friendship->refresh();
        });
    }

    public function declineRequest(Friendship $friendship, User $user): void
    {
        DB::transaction(function () use ($friendship, $user) {
            $this->lockUsers(
                $friendship->requester,
                $friendship->addressee
            );

            $friendship = Friendship::query()
                ->lockForUpdate()
                ->findOrFail($friendship->id);

            if ($friendship->addressee_id !== $user->id) {
                throw new AuthorizationException(
                    'You cannot decline this friend request.'
                );
            }

            $this->ensurePending($friendship);

            $friendship->delete();
        });
    }

    public function cancelRequest(Friendship $friendship, User $user): void
    {
        DB::transaction(function () use ($friendship, $user) {
            $this->lockUsers(
                $friendship->requester,
                $friendship->addressee
            );

            $friendship = Friendship::query()
                ->lockForUpdate()
                ->findOrFail($friendship->id);

            if ($friendship->requester_id !== $user->id) {
                throw new AuthorizationException(
                    'You cannot cancel this friend request.'
                );
            }

            $this->ensurePending($friendship);

            $friendship->delete();
        });
    }

    public function removeFriend(Friendship $friendship, User $user): void
    {
        DB::transaction(function () use ($friendship, $user) {
            $this->lockUsers(
                $friendship->requester,
                $friendship->addressee
            );

            $friendship = Friendship::query()
                ->lockForUpdate()
                ->findOrFail($friendship->id);

            $isParticipant = $friendship->requester_id === $user->id
                || $friendship->addressee_id === $user->id;

            if (! $isParticipant) {
                throw new AuthorizationException(
                    'You cannot remove this friendship.'
                );
            }

            if ($friendship->status !== FriendshipStatus::ACCEPTED) {
                throw ValidationException::withMessages([
                    'friendship' => 'This friendship is not accepted.',
                ]);
            }

            $friendship->delete();
        });
    }

    private function ensurePending(Friendship $friendship): void
    {
        if ($friendship->status !== FriendshipStatus::PENDING) {
            throw ValidationException::withMessages([
                'friendship' => 'This friend request is no longer pending.',
            ]);
        }
    }

    private function lockUsers(User $first, User $second): void
    {
        User::query()
            ->whereKey([$first->id, $second->id])
            ->orderBy('id')
            ->lockForUpdate()
            ->get();
    }
}
