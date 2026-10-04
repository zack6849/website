<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    private const string OLD_TABLE_NAME = 'password_resets';
    private const string NEW_TABLE_NAME = 'password_reset_tokens';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename(self::OLD_TABLE_NAME, self::NEW_TABLE_NAME);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename(self::NEW_TABLE_NAME, self::OLD_TABLE_NAME);
    }
};
