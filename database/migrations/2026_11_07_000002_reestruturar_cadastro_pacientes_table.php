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
        Schema::table('pacientes', function (Blueprint $table) {
            // Tornar colunas clínicas e cadastrais opcionais no cadastro inicial
            $table->string('cns', 15)->nullable()->change();
            $table->string('nome_mae', 255)->nullable()->change();
            $table->string('cid_principal', 10)->nullable()->change();

            // Novos campos administrativos e de integração com a APS / e-SUS
            $table->string('ubs_referencia', 255)->nullable()->after('municipio_ibge');
            $table->string('nome_acs', 255)->nullable()->after('ubs_referencia');
            $table->string('telefone_secundario', 20)->nullable()->after('telefone_contato');
            $table->string('origem_cadastro', 20)->default('MANUAL')->after('ativo');
            $table->string('id_externo_esus', 100)->nullable()->index()->after('origem_cadastro');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->dropIndex(['id_externo_esus']);
            $table->dropColumn([
                'ubs_referencia',
                'nome_acs',
                'telefone_secundario',
                'origem_cadastro',
                'id_externo_esus',
            ]);

            $table->string('cid_principal', 10)->nullable(false)->change();
            $table->string('nome_mae', 255)->nullable(false)->change();
            $table->string('cns', 15)->nullable(false)->change();
        });
    }
};
