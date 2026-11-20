<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('friendships', function (Blueprint $table) {
            $table->id();

            $table->foreignId('requester_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('addressee_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('status', 10)->default('pending');

            $table->timestamp('accepted_at')->nullable();

            $table->timestamps();

            $table->unique(['requester_id', 'addressee_id']);

            $table->index(['addressee_id', 'status']);

            $table->index(['requester_id', 'status']);

        });

        $this->backfillAcceptedFriendships();
    }

    /**
     * Treat every existing message pair as an accepted friendship.
     */
    private function backfillAcceptedFriendships(): void
    {
        $now = now();

        $friendships = DB::table('messages')
            ->select('sender_id', 'recipient_id')
            ->whereColumn('sender_id', '!=', 'recipient_id')
            ->distinct()
            ->get()
            ->map(fn (object $pair): array => [
                'requester_id' => min($pair->sender_id, $pair->recipient_id),
                'addressee_id' => max($pair->sender_id, $pair->recipient_id),
                'status' => 'accepted',
                'accepted_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->unique(fn (array $pair): string => $pair['requester_id'].':'.$pair['addressee_id'])
            ->values()
            ->all();

        if ($friendships !== []) {
            DB::table('friendships')->insertOrIgnore($friendships);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('friendships');
    }
};
