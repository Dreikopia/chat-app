<?php

namespace App\Models;

use App\Enums\FriendshipStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $username
 * @property string|null $bio
 * @property string|null $avatar_path
 * @property-read string|null $avatar
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Appends(['avatar'])]
#[Fillable(['name', 'username', 'email', 'password', 'bio', 'avatar_path'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * A user counts as active while their last seen timestamp is no older than this.
     */
    public const ACTIVE_WINDOW_MINUTES = 5;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'two_factor_confirmed_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    /**
     * Get the public URL of the user's avatar.
     *
     * @return Attribute<string|null, never>
     */
    protected function avatar(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->avatar_path === null
            ? null
            : Storage::disk('public')->url($this->avatar_path));
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function sentFriendRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'requester_id');
    }

    public function receivedFriendRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'addressee_id');
    }

    public function incomingFriendRequests(): HasMany
    {
        return $this->receivedFriendRequests()
            ->where('status', FriendshipStatus::PENDING->value);
    }

    public function outgoingFriendRequests(): HasMany
    {
        return $this->sentFriendRequests()
            ->where('status', FriendshipStatus::PENDING->value);
    }

    // a query to find the user friends
    public function friends(): Builder
    {
        return User::query()
            ->where(function (Builder $query) {
                $query->whereIn('id', Friendship::query()
                    ->select('addressee_id')
                    ->where('requester_id', $this->id)
                    ->where('status', FriendshipStatus::ACCEPTED->value)
                )
                    ->orWhereIn('id', Friendship::query()
                        ->select('requester_id')
                        ->where('addressee_id', $this->id)
                        ->where('status', FriendshipStatus::ACCEPTED->value)
                    );
            });
    }

    public function isFriendsWith(User $user): bool
    {
        return $this->friends()->whereKey($user->id)->exists();
    }

    public function hasConversationWith(User $user): bool
    {
        return Message::query()
            ->where(function (Builder $query) use ($user) {
                $query->where('sender_id', $this->id)
                    ->where('recipient_id', $user->id);
            })
            ->orWhere(function (Builder $query) use ($user) {
                $query->where('sender_id', $user->id)
                    ->where('recipient_id', $this->id);
            })
            ->exists();
    }

    /**
     * Whether the user was last seen recently enough to count as active.
     */
    public function isActive(): bool
    {
        return $this->last_seen_at !== null
            && $this->last_seen_at->isAfter(now()->subMinutes(self::ACTIVE_WINDOW_MINUTES));
    }

    /**
     * The distinct users this user has exchanged messages with, newest first,
     * followed by accepted friends without a conversation, alphabetically.
     *
     * @return Collection<int, User>
     */
    public function conversations(): Collection
    {
        $conversations = Message::query()
            ->with([
                'sender:id,name,username,avatar_path,last_seen_at',
                'recipient:id,name,username,avatar_path,last_seen_at',
            ])
            ->where(function (Builder $query) {
                $query->where('sender_id', $this->id)
                    ->orWhere('recipient_id', $this->id);
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Message $message) => $message->sender_id === $this->id
                ? $message->recipient
                : $message->sender)
            ->unique('id')
            ->values();

        $conversationIds = $conversations->pluck('id');

        $friends = $this->friends()
            ->select('id', 'name', 'username', 'avatar_path', 'last_seen_at')
            ->when(
                $conversationIds->isNotEmpty(),
                fn (Builder $query) => $query->whereNotIn('users.id', $conversationIds),
            )
            ->orderBy('name')
            ->get();

        $unreadCounts = Message::query()
            ->where('recipient_id', $this->id)
            ->whereNull('read_at')
            ->selectRaw('sender_id, count(*) as aggregate')
            ->groupBy('sender_id')
            ->pluck('aggregate', 'sender_id');

        return $conversations
            ->concat($friends)
            ->map(function (User $user) use ($unreadCounts) {
                $user->setAttribute('unread_count', (int) ($unreadCounts[$user->id] ?? 0));
                $user->setAttribute('is_active', $user->isActive());

                return $user;
            })
            ->values();
    }
}
