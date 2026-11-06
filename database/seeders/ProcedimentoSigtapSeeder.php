<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProcedimentoSigtapSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $procedimentos = [
            [
                'codigo' => '0301070075',
                'descricao' => 'ATENDIMENTO / ACOMPANHAMENTO INTENSIVO DE PACIENTE EM REABILITACAO FISICA',
                'cbos_permitidos' => json_encode(['223605', '223810', '223905', '251510']),
                'idade_minima' => 0,
                'idade_maxima' => 130,
                'sexo_permitido' => 'AMBOS',
                'ativo' => true,
            ],
            [
                'codigo' => '0301070067',
                'descricao' => 'ATENDIMENTO / ACOMPANHAMENTO DE PACIENTE EM REABILITACAO DO DESENVOLVIMENTO NEUROPSICOMOTOR',
                'cbos_permitidos' => json_encode(['223605', '223810', '223905', '251510', '225112']),
                'idade_minima' => 0,
                'idade_maxima' => 130,
                'sexo_permitido' => 'AMBOS',
                'ativo' => true,
            ],
            [
                'codigo' => '0301070113',
                'descricao' => 'ATENDIMENTO / ACOMPANHAMENTO DE PACIENTE EM REABILITACAO INTELECTUAL / TRANSTORNO DO ESPECTRO AUTISTA',
                'cbos_permitidos' => json_encode(['223605', '223810', '223905', '251510', '251605']),
                'idade_minima' => 0,
                'idade_maxima' => 130,
                'sexo_permitido' => 'AMBOS',
                'ativo' => true,
            ],
            [
                'codigo' => '0301070032',
                'descricao' => 'ATENDIMENTO / ACOMPANHAMENTO DE PACIENTE EM REABILITACAO AUDITIVA',
                'cbos_permitidos' => json_encode(['223810', '251510', '251605']),
                'idade_minima' => 0,
                'idade_maxima' => 130,
                'sexo_permitido' => 'AMBOS',
                'ativo' => true,
            ],
            [
                'codigo' => '0301070040',
                'descricao' => 'ATENDIMENTO / ACOMPANHAMENTO DE PACIENTE EM REABILITACAO VISUAL',
                'cbos_permitidos' => json_encode(['223905', '223605', '251510']),
                'idade_minima' => 0,
                'idade_maxima' => 130,
                'sexo_permitido' => 'AMBOS',
                'ativo' => true,
            ],
            [
                'codigo' => '0301010048',
                'descricao' => 'CONSULTA DE PROFISSIONAIS DE NIVEL SUPERIOR NA ATENCAO ESPECIALIZADA (EXCETO MEDICO)',
                'cbos_permitidos' => json_encode(['223605', '223810', '223905', '251510', '251605', '223710', '223505']),
                'idade_minima' => 0,
                'idade_maxima' => 130,
                'sexo_permitido' => 'AMBOS',
                'ativo' => true,
            ],
            [
                'codigo' => '0302050027',
                'descricao' => 'ATENDIMENTO FISIOTERAPEUTICO EM PACIENTES COM DISTURBIOS NEURO-CINETICO-FUNCIONAIS SEM COMPLICACOES SISTEMICAS',
                'cbos_permitidos' => json_encode(['223605']),
                'idade_minima' => 0,
                'idade_maxima' => 130,
                'sexo_permitido' => 'AMBOS',
                'ativo' => true,
            ],
            [
                'codigo' => '0301040044',
                'descricao' => 'TERAPIA FONOAUDIOLOGICA INDIVIDUAL',
                'cbos_permitidos' => json_encode(['223810']),
                'idade_minima' => 0,
                'idade_maxima' => 130,
                'sexo_permitido' => 'AMBOS',
                'ativo' => true,
            ],
            [
                'codigo' => '0301080178',
                'descricao' => 'ATENDIMENTO INDIVIDUAL EM PSICOTERAPIA',
                'cbos_permitidos' => json_encode(['251510']),
                'idade_minima' => 0,
                'idade_maxima' => 130,
                'sexo_permitido' => 'AMBOS',
                'ativo' => true,
            ],
        ];

        foreach ($procedimentos as $procedimento) {
            DB::table('procedimentos_sigtap')->updateOrInsert(
                ['codigo' => $procedimento['codigo']],
                array_merge($procedimento, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }
    }
}
