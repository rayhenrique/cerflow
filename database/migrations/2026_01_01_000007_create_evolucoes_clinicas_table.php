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
        Schema::create('evolucoes_clinicas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agendamento_id');
            $table->unsignedBigInteger('paciente_id');
            $table->unsignedBigInteger('profissional_id');
            $table->text('queixa_estado_atual');
            $table->text('conduta_terapeutica');
            $table->text('intercorrencias')->nullable();
            $table->timestamp('assinado_em');
            $table->string('assinatura_hash', 64);
            $table->boolean('bloqueado_edicao')->default(false);
            $table->timestamps();

            $table->foreign('agendamento_id', 'fk_evolucoes_agendamento')
                ->references('id')
                ->on('agendamentos')
                ->restrictOnDelete();

            $table->foreign('paciente_id', 'fk_evolucoes_paciente')
                ->references('id')
                ->on('pacientes')
                ->restrictOnDelete();

            $table->foreign('profissional_id', 'fk_evolucoes_profissional')
                ->references('id')
                ->on('profissionais')
                ->restrictOnDelete();

            $table->unique(['agendamento_id', 'profissional_id'], 'unq_agendamento_profissional_evolucao');
            $table->index('paciente_id', 'idx_evolucoes_paciente');
            $table->index('assinado_em', 'idx_evolucoes_assinado_em');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evolucoes_clinicas');
    }
};
