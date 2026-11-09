<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Unique (recurring_transaction_id, transaction_date) menjamin satu aturan rutin tidak pernah mencatat dua
     * transaksi di tanggal yang sama. Transaksi biasa (recurring_transaction_id NULL) tidak terkena batasan ini.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('source', ['chat', 'manual', 'rutin'])->default('manual')->change();
            $table->foreignId('recurring_transaction_id')
                ->nullable()
                ->after('chat_message_id')
                ->constrained()
                ->nullOnDelete();

            $table->unique(['recurring_transaction_id', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // MySQL memakai unique index ini untuk foreign key, jadi foreign key harus dilepas lebih dulu.
            $table->dropForeign(['recurring_transaction_id']);
            $table->dropUnique(['recurring_transaction_id', 'transaction_date']);
            $table->dropColumn('recurring_transaction_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('source', ['chat', 'manual'])->default('manual')->change();
        });
    }
};
