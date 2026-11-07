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
        Schema::table('historico_sincronizacoes_esus', function (Blueprint $table) {
            $table->string('tipo_execucao', 30)->default('MANUAL')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historico_sincronizacoes_esus', function (Blueprint $table) {
            $table->enum('tipo_execucao', ['MANUAL', 'AGENDADA'])->default('MANUAL')->change();
        });
    }
};
