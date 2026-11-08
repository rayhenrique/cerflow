<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuracoes_estabelecimento', function (Blueprint $table): void {
            $table->boolean('painel_voz_ativa')->default(true)->after('cnes');
            $table->unsignedTinyInteger('painel_volume')->default(80)->after('painel_voz_ativa');
            $table->unsignedSmallInteger('painel_tempo_destaque_segundos')->default(15)->after('painel_volume');
            $table->unsignedTinyInteger('painel_quantidade_historico')->default(5)->after('painel_tempo_destaque_segundos');
            $table->string('painel_modo_exibicao_nome', 30)->default('COMPLETO')->after('painel_quantidade_historico');
            $table->string('painel_token_acesso', 64)->nullable()->unique()->after('painel_modo_exibicao_nome');
        });

        // Gera token inicial para a unidade existente
        if (Schema::hasTable('configuracoes_estabelecimento')) {
            DB::table('configuracoes_estabelecimento')
                ->where('id', 1)
                ->update([
                    'painel_token_acesso' => Str::random(40),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('configuracoes_estabelecimento', function (Blueprint $table): void {
            $table->dropColumn([
                'painel_voz_ativa',
                'painel_volume',
                'painel_tempo_destaque_segundos',
                'painel_quantidade_historico',
                'painel_modo_exibicao_nome',
                'painel_token_acesso',
            ]);
        });
    }
};
