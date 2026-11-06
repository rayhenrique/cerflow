<?php

declare(strict_types=1);

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
        Schema::create('profissionais', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->string('nome', 255);
            $table->string('cpf', 11)->unique();
            $table->string('cns', 15)->unique();
            $table->string('registro_conselho', 50);
            $table->string('cbo_padrao', 10);
            $table->string('especialidade', 100);
            $table->string('pin_assinatura_hash', 255);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->foreign('user_id', 'fk_profissionais_user')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->index('cns', 'idx_profissionais_cns');
            $table->index('cbo_padrao', 'idx_profissionais_cbo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profissionais');
    }
};
