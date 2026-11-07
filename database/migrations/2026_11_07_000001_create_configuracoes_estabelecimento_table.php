<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('configuracoes_estabelecimento', function (Blueprint $table) {
            $table->id();
            $table->string('logo_path')->nullable();
            $table->string('codigo_ibge_padrao', 6);
            $table->string('cnes', 7);
            $table->timestamps();
        });

        // Mantém os valores que o sistema já utilizava antes da parametrização.
        DB::table('configuracoes_estabelecimento')->insert([
            'id' => 1,
            'logo_path' => null,
            'codigo_ibge_padrao' => '270430',
            'cnes' => '2000001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracoes_estabelecimento');
    }
};
