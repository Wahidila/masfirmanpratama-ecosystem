<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliator_payout_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('withdrawal_method_id')->constrained();
            $table->string('account_number', 50);
            $table->string('account_name', 100);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            // Rekening yang sama persis tidak perlu disimpan dua kali.
            $table->unique(
                ['affiliator_id', 'withdrawal_method_id', 'account_number'],
                'payout_accounts_owner_method_number_unique'
            );
            $table->index(['affiliator_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliator_payout_accounts');
    }
};
