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
        Schema::table('users', function (Blueprint $table) {
            $table->string('security_question_1')->nullable()->after('status_message');
            $table->string('security_answer_1')->nullable()->after('security_question_1');
            $table->string('security_question_2')->nullable()->after('security_answer_1');
            $table->string('security_answer_2')->nullable()->after('security_question_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'security_question_1',
                'security_answer_1',
                'security_question_2',
                'security_answer_2',
            ]);
        });
    }
};
