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
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->string('cns', 15)->unique();
            $table->string('cpf', 11)->nullable()->unique();
            $table->string('nome', 255);
            $table->date('data_nascimento');
            $table->enum('sexo', ['M', 'F']);
            $table->string('nome_mae', 255);
            $table->string('municipio_ibge', 6);
            $table->string('endereco', 255)->nullable();
            $table->string('bairro', 100)->nullable();
            $table->string('telefone_contato', 20)->nullable();
            $table->string('cid_principal', 10);
            $table->string('cid_secundario', 10)->nullable();
            $table->json('modalidades_reabilitacao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('cns', 'idx_pacientes_cns');
            $table->index('nome', 'idx_pacientes_nome');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
