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
        Schema::create('logs_auditoria', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('acao', 100); // ACESSO_PEP, ASSINATURA_EVOLUCAO, EXPORTACAO_BPA
            $table->string('tabela_afetada', 50);
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->string('ip_address', 45);
            $table->json('detalhes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id', 'fk_logs_user')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->index(['user_id', 'acao'], 'idx_logs_user_acao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs_auditoria');
    }
};
