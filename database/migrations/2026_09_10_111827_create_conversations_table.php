<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('channel');
            $table->string('zernio_conversation_id')->unique();
            $table->string('zernio_account_id');
            $table->string('participant_id')->nullable();
            $table->string('participant_name')->nullable();
            $table->string('participant_phone')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
