<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(User::class, 'sender_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignIdFor(User::class, 'recipient_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('body', 1000)->nullable();

            $table->timestamp('read_at')->nullable();
            $table->timestamp('deleted_at')->nullable();

            $table->timestamps();

            $table->index(
                ['sender_id', 'recipient_id', 'id'],
                'messages_pair_idx'
            );

            $table->index(
                ['recipient_id', 'sender_id', 'read_at', 'id'],
                'messages_inbox_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
