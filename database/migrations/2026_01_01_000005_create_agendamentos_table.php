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
        Schema::create('agendamentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('paciente_id');
            $table->unsignedBigInteger('sala_id');
            $table->date('data_atendimento');
            $table->time('hora_inicio');
            $table->time('hora_fim');
            $table->enum('dia_semana', ['SEGUNDA', 'TERCA', 'QUARTA', 'QUINTA', 'SEXTA']);
            $table->enum('tipo_atendimento', ['INDIVIDUAL', 'COMPARTILHADO', 'TRABALHO_INTERNO'])->default('INDIVIDUAL');
            $table->enum('status', ['AGENDADO', 'PRESENTE', 'ATENDIDO', 'FALTA_JUSTIFICADA', 'FALTA_INJUSTIFICADA', 'CANCELADO'])->default('AGENDADO');
            $table->boolean('recorrente')->default(true);
            $table->string('observacao', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('paciente_id', 'fk_agendamentos_paciente')
                ->references('id')
                ->on('pacientes')
                ->restrictOnDelete();

            $table->foreign('sala_id', 'fk_agendamentos_sala')
                ->references('id')
                ->on('salas')
                ->restrictOnDelete();

            $table->index(['data_atendimento', 'hora_inicio'], 'idx_agendamentos_data_hora');
            $table->index('status', 'idx_agendamentos_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agendamentos');
    }
};
