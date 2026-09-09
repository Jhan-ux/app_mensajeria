<?php

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
        Schema::table('conversations', function (Blueprint $table) {
            $table->unsignedInteger('ephemeral_timer')->default(0)->after('avatar');
            $table->string('description', 255)->nullable()->after('ephemeral_timer');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('is_deleted')->index();
            $table->boolean('view_once')->default(false)->after('expires_at');
            $table->timestamp('viewed_at')->nullable()->after('view_once');
            $table->boolean('is_pinned')->default(false)->after('viewed_at');
            $table->timestamp('pinned_at')->nullable()->after('is_pinned');
            $table->foreignId('pinned_by')->nullable()->after('pinned_at')->constrained('users')->nullOnDelete();
        });

        Schema::create('message_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('emoji', 16);
            $table->timestamps();

            $table->unique(['message_id', 'user_id', 'emoji']);
            $table->index('message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_reactions');

        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['pinned_by']);
            $table->dropColumn([
                'expires_at',
                'view_once',
                'viewed_at',
                'is_pinned',
                'pinned_at',
                'pinned_by',
            ]);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['ephemeral_timer', 'description']);
        });
    }
};
