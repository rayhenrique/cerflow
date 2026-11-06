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
        Schema::create('procedimentos_sigtap', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->unique();
            $table->string('descricao', 255);
            $table->json('cbos_permitidos');
            $table->unsignedInteger('idade_minima')->default(0);
            $table->unsignedInteger('idade_maxima')->default(999);
            $table->enum('sexo_permitido', ['M', 'F', 'AMBOS'])->default('AMBOS');
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index('codigo', 'idx_procedimentos_codigo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procedimentos_sigtap');
    }
};
