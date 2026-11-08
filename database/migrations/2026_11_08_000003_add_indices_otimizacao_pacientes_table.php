<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Executa a criação de índices para mitigar lentidão no MySQL em bases grandes (~40 mil pacientes).
     */
    public function up(): void
    {
        Schema::table('pacientes', function (Blueprint $table): void {
            // Índice para acelerar a busca e DISTINCT de UBS de Referência
            $table->index('ubs_referencia', 'idx_pacientes_ubs_referencia');

            // Índice composto para acelerar escopo de pacientes ativos com soft deletes
            $table->index(['ativo', 'deleted_at'], 'idx_pacientes_ativo_deleted');

            // Em MySQL, adiciona índice FULLTEXT em nome para acelerar busca por palavras intermediárias sem Full Table Scan
            if (DB::getDriverName() === 'mysql') {
                $table->fullText('nome', 'idx_pacientes_nome_fulltext');
            }
        });
    }

    /**
     * Reverte a criação dos índices.
     */
    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table): void {
            if (DB::getDriverName() === 'mysql') {
                $table->dropIndex('idx_pacientes_nome_fulltext');
            }

            $table->dropIndex('idx_pacientes_ativo_deleted');
            $table->dropIndex('idx_pacientes_ubs_referencia');
        });
    }
};
