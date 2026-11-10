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
        Schema::create('cbos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 6)->unique();
            $table->string('titulo_oficial', 255);
            $table->boolean('ativo')->default(true)->index();
            $table->string('fonte_referencia', 255)->default('Ministério do Trabalho e Emprego (MTE) - CBO');
            $table->string('versao_referencia', 50)->nullable();
            $table->timestamp('verificado_em')->nullable();
            $table->timestamps();
        });

        Schema::create('especialidades', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 150)->unique();
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('cbo_especialidade', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cbo_id')->constrained('cbos')->cascadeOnDelete();
            $table->foreignId('especialidade_id')->constrained('especialidades')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['cbo_id', 'especialidade_id'], 'uniq_cbo_especialidade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cbo_especialidade');
        Schema::dropIfExists('especialidades');
        Schema::dropIfExists('cbos');
    }
};

