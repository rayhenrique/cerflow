<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispositivos_tv', function (Blueprint $table): void {
            $table->id();
            $table->string('nome', 100);
            $table->string('identificador', 64)->unique();
            $table->string('token_hash', 64)->unique()->index();
            $table->string('status', 20)->default('ATIVO')->index(); // ATIVO, REVOGADO
            $table->foreignId('autorizado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('autorizado_em');
            $table->dateTime('ultimo_acesso_em')->nullable();
            $table->dateTime('expira_em')->nullable();
            $table->dateTime('revogado_em')->nullable();
            $table->foreignId('revogado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_origem', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('solicitacoes_pareamento_tv', function (Blueprint $table): void {
            $table->id();
            $table->string('solicitacao_uid', 64)->unique()->index();
            $table->string('codigo', 16)->index();
            $table->string('segredo_temporario_hash', 64);
            $table->string('status', 20)->default('PENDENTE')->index(); // PENDENTE, APROVADO, CONCLUIDO, EXPIRADO, REJEITADO
            $table->foreignId('dispositivo_tv_id')->nullable()->constrained('dispositivos_tv')->nullOnDelete();
            $table->dateTime('expira_em')->index();
            $table->string('ip_origem', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->unsignedTinyInteger('tentativas')->default(0);
            $table->timestamps();
        });

        if (Schema::hasTable('configuracoes_estabelecimento') && ! Schema::hasColumn('configuracoes_estabelecimento', 'painel_permitir_token_legado')) {
            Schema::table('configuracoes_estabelecimento', function (Blueprint $table): void {
                $table->boolean('painel_permitir_token_legado')->default(true)->after('painel_token_acesso');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('configuracoes_estabelecimento') && Schema::hasColumn('configuracoes_estabelecimento', 'painel_permitir_token_legado')) {
            Schema::table('configuracoes_estabelecimento', function (Blueprint $table): void {
                $table->dropColumn('painel_permitir_token_legado');
            });
        }

        Schema::dropIfExists('solicitacoes_pareamento_tv');
        Schema::dropIfExists('dispositivos_tv');
    }
};
