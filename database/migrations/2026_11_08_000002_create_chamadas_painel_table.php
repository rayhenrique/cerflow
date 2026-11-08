<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chamadas_painel', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('agendamento_id')->constrained('agendamentos')->cascadeOnDelete();
            $table->foreignId('paciente_id')->constrained('pacientes')->cascadeOnDelete();
            $table->foreignId('sala_id')->constrained('salas')->cascadeOnDelete();
            $table->foreignId('profissional_id')->nullable()->constrained('profissionais')->nullOnDelete();
            $table->foreignId('chamado_por_id')->constrained('users')->cascadeOnDelete();

            $table->string('paciente_nome');
            $table->string('sala_nome');
            $table->string('profissional_nome')->nullable();

            $table->string('tipo', 20)->default('CHAMADA'); // CHAMADA, REPETICAO
            $table->string('status', 20)->default('CHAMANDO'); // CHAMANDO, CONCLUIDA, CANCELADA
            $table->dateTime('chamado_em');

            $table->timestamps();

            $table->index(['chamado_em', 'status']);
            $table->index('agendamento_id');
            $table->index('paciente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chamadas_painel');
    }
};
