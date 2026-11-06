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
        Schema::create('agendamento_profissionais', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agendamento_id');
            $table->unsignedBigInteger('profissional_id');
            $table->enum('papel', ['PRINCIPAL', 'COMPARTILHADO'])->default('PRINCIPAL');
            $table->timestamps();

            $table->foreign('agendamento_id', 'fk_agend_prof_agendamento')
                ->references('id')
                ->on('agendamentos')
                ->cascadeOnDelete();

            $table->foreign('profissional_id', 'fk_agend_prof_profissional')
                ->references('id')
                ->on('profissionais')
                ->restrictOnDelete();

            $table->unique(['agendamento_id', 'profissional_id'], 'unq_agendamento_profissional');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agendamento_profissionais');
    }
};
