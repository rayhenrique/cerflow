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
        Schema::create('evolucao_adendos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evolucao_id');
            $table->unsignedBigInteger('profissional_id');
            $table->text('justificativa');
            $table->text('texto_adendo');
            $table->timestamp('assinado_em');
            $table->timestamps();

            $table->foreign('evolucao_id', 'fk_adendos_evolucao')
                ->references('id')
                ->on('evolucoes_clinicas')
                ->cascadeOnDelete();

            $table->foreign('profissional_id', 'fk_adendos_profissional')
                ->references('id')
                ->on('profissionais')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evolucao_adendos');
    }
};
