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
        // 1. Tabela de configuração da integração com o e-SUS APS PEC
        Schema::create('configuracoes_esus_pec', function (Blueprint $table) {
            $table->id();
            $table->boolean('ativo')->default(false);
            $table->string('driver', 20)->default('pgsql');
            $table->string('host', 255)->default('localhost');
            $table->integer('porta')->default(5433);
            $table->string('banco', 100)->default('esus');
            $table->string('usuario', 100)->default('esus_leitura');
            $table->text('senha')->nullable(); // Criptografado com o mecanismo nativo do Laravel (APP_KEY)
            $table->string('endereco_web_pec', 255)->default('http://189.89.61.50:8181');
            $table->string('ultimo_status_conexao', 30)->default('NAO_TESTADO');
            $table->text('mensagem_status_conexao')->nullable();
            $table->string('tipo_banco_detectado', 50)->nullable();
            $table->string('tabela_origem_detectada', 100)->nullable();
            $table->integer('total_tabelas_descobertas')->default(0);
            $table->json('detalhes_schema')->nullable();
            $table->timestamp('ultima_conexao_em')->nullable();
            $table->timestamp('ultima_sincronizacao_em')->nullable();
            $table->string('ultima_sincronizacao_status', 30)->nullable();
            $table->timestamps();
        });

        // 2. Tabela de histórico e auditoria das sincronizações do e-SUS PEC
        Schema::create('historico_sincronizacoes_esus', function (Blueprint $table) {
            $table->id();
            $table->timestamp('iniciado_em');
            $table->timestamp('finalizado_em')->nullable();
            $table->integer('duracao_segundos')->default(0);
            $table->enum('tipo_execucao', ['MANUAL', 'AGENDADA'])->default('MANUAL');
            $table->string('status', 30)->default('EM_ANDAMENTO'); // SUCESSO, ERRO, INTERROMPIDO, CONEXAO_FALHOU
            $table->integer('total_consultado')->default(0);
            $table->integer('novos_pacientes')->default(0);
            $table->integer('pacientes_atualizados')->default(0);
            $table->integer('registros_ignorados')->default(0);
            $table->integer('duplicidades_identificadas')->default(0);
            $table->text('mensagem_erro')->nullable();
            $table->json('detalhes')->nullable(); // Apenas métricas e diagnósticos agregados — NUNCA PII/PHI
            $table->timestamps();

            $table->index('iniciado_em', 'idx_hist_esus_iniciado');
            $table->index('status', 'idx_hist_esus_status');
        });

        // 3. Campo de controle de sincronização na tabela de pacientes
        if (! Schema::hasColumn('pacientes', 'data_ultima_sincronizacao_esus')) {
            Schema::table('pacientes', function (Blueprint $table) {
                $table->timestamp('data_ultima_sincronizacao_esus')->nullable()->after('id_externo_esus');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('pacientes', 'data_ultima_sincronizacao_esus')) {
            Schema::table('pacientes', function (Blueprint $table) {
                $table->dropColumn('data_ultima_sincronizacao_esus');
            });
        }

        Schema::dropIfExists('historico_sincronizacoes_esus');
        Schema::dropIfExists('configuracoes_esus_pec');
    }
};
