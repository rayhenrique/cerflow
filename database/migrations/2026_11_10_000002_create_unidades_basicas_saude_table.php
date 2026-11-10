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
        Schema::create('unidades_basicas_saude', function (Blueprint $table) {
            $table->id();
            $table->string('nome', 255)->index();
            $table->string('cnes', 7)->nullable()->unique();
            $table->string('municipio_ibge', 6)->default('270430');
            $table->string('bairro', 100)->nullable();
            $table->string('endereco', 255)->nullable();
            $table->string('telefone', 20)->nullable();
            $table->string('origem', 20)->default('MANUAL')->index();
            $table->string('identificador_externo', 100)->nullable()->index();
            $table->boolean('ativo')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unidades_basicas_saude');
    }
};

