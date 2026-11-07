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
        Schema::table('configuracoes_esus_pec', function (Blueprint $table) {
            $table->string('modo_sincronizacao', 30)->default('AUTO')->after('endereco_web_pec');
            $table->integer('tamanho_lote')->default(500)->after('modo_sincronizacao');
            $table->integer('janela_seguranca_minutos')->default(1440)->after('tamanho_lote'); // 24 horas por padrão
            $table->timestamp('ultimo_checkpoint_em')->nullable()->after('janela_seguranca_minutos');
            $table->unsignedBigInteger('ultimo_checkpoint_id')->nullable()->after('ultimo_checkpoint_em');
            $table->string('coluna_atualizacao_detectada', 100)->nullable()->after('ultimo_checkpoint_id');
            $table->string('coluna_sequencial_detectada', 100)->nullable()->after('coluna_atualizacao_detectada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuracoes_esus_pec', function (Blueprint $table) {
            $table->dropColumn([
                'modo_sincronizacao',
                'tamanho_lote',
                'janela_seguranca_minutos',
                'ultimo_checkpoint_em',
                'ultimo_checkpoint_id',
                'coluna_atualizacao_detectada',
                'coluna_sequencial_detectada',
            ]);
        });
    }
};
