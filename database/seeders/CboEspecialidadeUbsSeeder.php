<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Cbo;
use App\Models\Especialidade;
use App\Models\Paciente;
use App\Models\Profissional;
use App\Models\UnidadeBasicaSaude;
use Illuminate\Database\Seeder;

class CboEspecialidadeUbsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $agora = now();

        // 1. Cadastros de CBOs oficiais e suas especialidades padrão
        $listaCbos = [
            [
                'codigo' => '223605',
                'titulo_oficial' => 'Fisioterapeuta geral',
                'especialidades' => ['Fisioterapia', 'Fisioterapia Neurofuncional'],
            ],
            [
                'codigo' => '223810',
                'titulo_oficial' => 'Fonoaudiólogo',
                'especialidades' => ['Fonoaudiologia'],
            ],
            [
                'codigo' => '223905',
                'titulo_oficial' => 'Terapeuta ocupacional',
                'especialidades' => ['Terapia Ocupacional'],
            ],
            [
                'codigo' => '251510',
                'titulo_oficial' => 'Psicólogo clínico',
                'especialidades' => ['Psicologia', 'Psicologia Clínica'],
            ],
            [
                'codigo' => '251545',
                'titulo_oficial' => 'Neuropsicólogo',
                'especialidades' => ['Neuropsicologia'],
            ],
            [
                'codigo' => '251605',
                'titulo_oficial' => 'Assistente social',
                'especialidades' => ['Serviço Social'],
            ],
            [
                'codigo' => '223710',
                'titulo_oficial' => 'Nutricionista',
                'especialidades' => ['Nutrição'],
            ],
            [
                'codigo' => '223505',
                'titulo_oficial' => 'Enfermeiro',
                'especialidades' => ['Enfermagem'],
            ],
            [
                'codigo' => '225112',
                'titulo_oficial' => 'Médico neurologista',
                'especialidades' => ['Neurologia'],
            ],
            [
                'codigo' => '225133',
                'titulo_oficial' => 'Médico psiquiatra',
                'especialidades' => ['Psiquiatria'],
            ],
            [
                'codigo' => '225270',
                'titulo_oficial' => 'Médico ortopedista e traumatologista',
                'especialidades' => ['Ortopedia e Traumatologia'],
            ],
        ];

        foreach ($listaCbos as $item) {
            $cbo = Cbo::firstOrCreate(
                ['codigo' => $item['codigo']],
                [
                    'titulo_oficial' => $item['titulo_oficial'],
                    'ativo' => true,
                    'fonte_referencia' => 'Ministério do Trabalho e Emprego (MTE) - CBO',
                    'versao_referencia' => 'CBO/2026',
                    'verificado_em' => $agora,
                ]
            );

            foreach ($item['especialidades'] as $nomeEsp) {
                $especialidade = Especialidade::firstOrCreate(
                    ['nome' => $nomeEsp],
                    [
                        'descricao' => 'Especialidade de '.$item['titulo_oficial'],
                        'ativo' => true,
                    ]
                );

                if (! $cbo->especialidades()->where('especialidades.id', $especialidade->id)->exists()) {
                    $cbo->especialidades()->attach($especialidade->id);
                }
            }
        }

        // 2. Reconciliação dos Profissionais existentes (atualiza cbo_id e especialidade_id)
        $profissionais = Profissional::all();
        foreach ($profissionais as $prof) {
            $alterou = false;

            if ($prof->cbo_padrao) {
                $cboModel = Cbo::where('codigo', $prof->cbo_padrao)->first();
                if ($cboModel && $prof->cbo_id !== $cboModel->id) {
                    $prof->cbo_id = $cboModel->id;
                    $alterou = true;
                }
            }

            if ($prof->especialidade) {
                $espModel = Especialidade::where('nome', $prof->especialidade)->first();
                if (! $espModel) {
                    $espModel = Especialidade::create([
                        'nome' => trim($prof->especialidade),
                        'ativo' => true,
                    ]);
                    if ($prof->cbo_id) {
                        $prof->cbo?->especialidades()->syncWithoutDetaching([$espModel->id]);
                    }
                }
                if ($prof->especialidade_id !== $espModel->id) {
                    $prof->especialidade_id = $espModel->id;
                    $alterou = true;
                }
            }

            if ($alterou) {
                $prof->save();
            }
        }

        // 3. Unidades Básicas de Saúde (UBS) padrão e reconciliação a partir dos pacientes
        $padroesUbs = [
            ['nome' => 'UBS Centro / PSF Central', 'cnes' => '2005001', 'bairro' => 'Centro'],
            ['nome' => 'UBS Dr. José Ferreira', 'cnes' => '2005002', 'bairro' => 'São Bento'],
            ['nome' => 'UBS Santa Luzia', 'cnes' => '2005003', 'bairro' => 'Santa Luzia'],
            ['nome' => 'UBS Bela Vista', 'cnes' => '2005004', 'bairro' => 'Bela Vista'],
            ['nome' => 'UBS São José', 'cnes' => '2005005', 'bairro' => 'São José'],
            ['nome' => 'USF Esperança', 'cnes' => '2005006', 'bairro' => 'Esperança'],
            ['nome' => 'UBS Zona Rural', 'cnes' => '2005007', 'bairro' => 'Zona Rural'],
        ];

        foreach ($padroesUbs as $pUbs) {
            UnidadeBasicaSaude::firstOrCreate(
                ['nome' => $pUbs['nome']],
                [
                    'cnes' => $pUbs['cnes'],
                    'bairro' => $pUbs['bairro'],
                    'municipio_ibge' => '270430',
                    'origem' => 'MANUAL',
                    'ativo' => true,
                ]
            );
        }

        $ubsDistintas = Paciente::whereNotNull('ubs_referencia')
            ->where('ubs_referencia', '!=', '')
            ->distinct()
            ->pluck('ubs_referencia');

        foreach ($ubsDistintas as $nomeUbs) {
            $nomeLimpo = trim((string) $nomeUbs);
            if ($nomeLimpo === '') {
                continue;
            }

            $ubs = UnidadeBasicaSaude::firstOrCreate(
                ['nome' => $nomeLimpo],
                [
                    'municipio_ibge' => '270430',
                    'origem' => 'MANUAL',
                    'ativo' => true,
                ]
            );

            Paciente::where('ubs_referencia', $nomeLimpo)
                ->whereNull('ubs_id')
                ->update(['ubs_id' => $ubs->id]);
        }
    }
}
