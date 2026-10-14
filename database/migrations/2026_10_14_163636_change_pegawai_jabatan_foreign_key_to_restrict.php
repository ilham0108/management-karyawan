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
        Schema::table('pegawais', function (Blueprint $table) {
            $table->dropForeign(['jabatan_id']);
            $table->foreign('jabatan_id')
                ->references('id')
                ->on('jabatans')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pegawais', function (Blueprint $table) {
            $table->dropForeign(['jabatan_id']);
            $table->foreign('jabatan_id')
                ->references('id')
                ->on('jabatans')
                ->cascadeOnDelete();
        });
    }
};
