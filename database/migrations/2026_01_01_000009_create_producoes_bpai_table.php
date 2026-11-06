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
        Schema::create('producoes_bpai', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evolucao_id');
            $table->unsignedBigInteger('paciente_id');
            $table->unsignedBigInteger('profissional_id');
            $table->unsignedBigInteger('procedimento_id');
            $table->string('cbo_executante', 10);
            $table->string('cid_atendimento', 10);
            $table->date('data_atendimento');
            $table->string('competencia', 6); // Formato: YYYYMM (ex: 202610)
            $table->enum('status_exportacao', ['PENDENTE', 'VALIDADO', 'INCONSISTENTE', 'EXPORTADO'])->default('PENDENTE');
            $table->text('mensagem_inconsistencia')->nullable();
            $table->string('lote_exportacao_id', 50)->nullable();
            $table->timestamps();

            $table->foreign('evolucao_id', 'fk_bpai_evolucao')
                ->references('id')
                ->on('evolucoes_clinicas')
                ->restrictOnDelete();

            $table->foreign('paciente_id', 'fk_bpai_paciente')
                ->references('id')
                ->on('pacientes')
                ->restrictOnDelete();

            $table->foreign('profissional_id', 'fk_bpai_profissional')
                ->references('id')
                ->on('profissionais')
                ->restrictOnDelete();

            $table->foreign('procedimento_id', 'fk_bpai_procedimento')
                ->references('id')
                ->on('procedimentos_sigtap')
                ->restrictOnDelete();

            $table->index('competencia', 'idx_bpai_competencia');
            $table->index('status_exportacao', 'idx_bpai_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producoes_bpai');
    }
};
