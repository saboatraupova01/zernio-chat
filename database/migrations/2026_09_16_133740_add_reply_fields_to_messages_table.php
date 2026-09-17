<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('platform_message_id')
                ->nullable()
                ->after('zernio_message_id');

            $table->foreignId('reply_to_message_id')
                ->nullable()
                ->after('platform_message_id')
                ->constrained('messages')
                ->nullOnDelete();

            $table->index('platform_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['reply_to_message_id']);
            $table->dropIndex(['platform_message_id']);
            $table->dropColumn([
                'platform_message_id',
                'reply_to_message_id',
            ]);
        });
    }
};
