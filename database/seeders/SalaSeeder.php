<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $salas = [
            ['nome' => 'Sala de Integração Sensorial', 'tipo' => 'SENSORIAL'],
            ['nome' => 'Sala de AVD (Atividades de Vida Diária)', 'tipo' => 'AVD'],
            ['nome' => 'Consultório 08 - Fonoaudiologia / Psicologia', 'tipo' => 'CONSULTORIO'],
            ['nome' => 'Consultório 09 - Terapia Ocupacional / Psicologia', 'tipo' => 'CONSULTORIO'],
            ['nome' => 'Consultório 10 - Avaliação Multiprofissional', 'tipo' => 'CONSULTORIO'],
            ['nome' => 'Ginásio de Fisioterapia Pediátrica', 'tipo' => 'PEDIATRIA'],
            ['nome' => 'Ginásio de Fisioterapia Adulto / Neurofuncional', 'tipo' => 'CONSULTORIO'],
            ['nome' => 'Anexo 01 - Estimulação Precoce', 'tipo' => 'ANEXO'],
        ];

        foreach ($salas as $sala) {
            DB::table('salas')->updateOrInsert(
                ['nome' => $sala['nome']],
                [
                    'tipo' => $sala['tipo'],
                    'ativo' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
