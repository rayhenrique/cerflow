<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Amplia as colunas para suportar o código oficial de 7 dígitos do IBGE
        Schema::table('configuracoes_estabelecimento', function (Blueprint $table) {
            $table->string('codigo_ibge_padrao', 7)->default('2709152')->change();
        });

        Schema::table('pacientes', function (Blueprint $table) {
            $table->string('municipio_ibge', 7)->default('2709152')->change();
        });

        if (Schema::hasTable('unidades_basicas_saude')) {
            Schema::table('unidades_basicas_saude', function (Blueprint $table) {
                $table->string('municipio_ibge', 7)->default('2709152')->change();
            });
        }

        // 2. Atualiza a configuração do estabelecimento se ainda possuir os valores de exemplo legados
        DB::table('configuracoes_estabelecimento')
            ->where('codigo_ibge_padrao', '270430')
            ->update(['codigo_ibge_padrao' => '2709152']);

        DB::table('configuracoes_estabelecimento')
            ->where('cnes', '2000001')
            ->update(['cnes' => '0795690']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracoes_estabelecimento', function (Blueprint $table) {
            $table->string('codigo_ibge_padrao', 6)->default('270430')->change();
        });

        Schema::table('pacientes', function (Blueprint $table) {
            $table->string('municipio_ibge', 6)->default('270430')->change();
        });

        if (Schema::hasTable('unidades_basicas_saude')) {
            Schema::table('unidades_basicas_saude', function (Blueprint $table) {
                $table->string('municipio_ibge', 6)->default('270430')->change();
            });
        }
    }
};
