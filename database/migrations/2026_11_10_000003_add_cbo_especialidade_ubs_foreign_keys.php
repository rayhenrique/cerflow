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
        Schema::table('profissionais', function (Blueprint $table) {
            $table->foreignId('cbo_id')
                ->nullable()
                ->after('registro_conselho')
                ->constrained('cbos')
                ->restrictOnDelete();

            $table->foreignId('especialidade_id')
                ->nullable()
                ->after('cbo_id')
                ->constrained('especialidades')
                ->restrictOnDelete();
        });

        Schema::table('pacientes', function (Blueprint $table) {
            $table->foreignId('ubs_id')
                ->nullable()
                ->after('municipio_ibge')
                ->constrained('unidades_basicas_saude')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropForeign(['ubs_id']);
            $table->dropColumn('ubs_id');
        });

        Schema::table('profissionais', function (Blueprint $table) {
            $table->dropForeign(['especialidade_id']);
            $table->dropForeign(['cbo_id']);
            $table->dropColumn(['especialidade_id', 'cbo_id']);
        });
    }
};
