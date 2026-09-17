<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('assigned_operator_id')
                ->nullable()
                ->after('operator_status')
                ->constrained('users')
                ->nullOnDelete()
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['assigned_operator_id']);
            $table->dropColumn('assigned_operator_id');
        });
    }
};
