<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Cbo;
use App\Models\Especialidade;
use App\Models\Profissional;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecuperarCbosLegadosCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'cer:recuperar-cbos-legados
                            {--dry-run : Executa em modo de simulação sem persistir alterações no banco de dados}';

    /**
     * @var string
     */
    protected $description = 'Recupera CBOs e Especialidades legadas a partir dos profissionais cadastrados e concilia os vínculos de forma segura e idempotente';

    /**
     * Mapeamento conhecido de ocupações frequentes do SUS/Reabilitação para atribuição inicial de títulos legados.
     *
     * @var array<string, string>
     */
    private const TITULOS_CONHECIDOS = [
        '223605' => 'Fisioterapeuta geral',
        '223625' => 'Fisioterapeuta respiratório',
        '223630' => 'Fisioterapeuta neurofuncional',
        '223635' => 'Fisioterapeuta traumato-ortopédica funcional',
        '223810' => 'Fonoaudiólogo geral',
        '223905' => 'Terapeuta ocupacional',
        '251510' => 'Psicólogo clínico',
        '251545' => 'Neuropsicólogo',
        '251605' => 'Assistente social',
        '223710' => 'Nutricionista',
        '223505' => 'Enfermeiro',
        '225112' => 'Médico neurologista',
        '225120' => 'Médico cardiologista',
        '225124' => 'Médico pediatra',
        '225125' => 'Médico clínico geral',
        '225133' => 'Médico psiquiatra',
        '225140' => 'Médico do trabalho',
        '225270' => 'Médico ortopedista e traumatologista',
        '322205' => 'Técnico de enfermagem',
        '322230' => 'Auxiliar de enfermagem',
        '411010' => 'Assistente administrativo',
        '422105' => 'Recepcionista, em geral',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info('================================================================');
        $this->info('  CER DIGITAL — RECUPERAÇÃO DE CBOs E ESPECIALIDADES LEGADAS');
        $this->info('================================================================');

        if ($dryRun) {
            $this->warn('⚠️  MODO DE SIMULAÇÃO ATIVO (--dry-run). Nenhuma gravação será realizada.');
        }

        $profissionais = Profissional::query()->get();
        $totalProfissionais = $profissionais->count();

        $this->line("Total de profissionais cadastrados avaliados: <info>{$totalProfissionais}</info>");

        $cbosCriados = 0;
        $cbosPreservados = 0;
        $especialidadesCriadas = 0;
        $especialidadesPreservadas = 0;
        $vinculosCboAtualizados = 0;
        $vinculosEspAtualizados = 0;
        $vinculosPivoCriados = 0;
        $divergencias = [];

        DB::beginTransaction();

        try {
            // -----------------------------------------------------------------
            // 1. Recuperação de CBOs distintos de profissionais.cbo_padrao
            // -----------------------------------------------------------------
            $cbosDistintos = $profissionais->pluck('cbo_padrao')
                ->map(fn ($val) => trim((string) $val))
                ->filter(fn ($val) => $val !== '')
                ->unique()
                ->values();

            $this->line('CBOs distintos encontrados nos profissionais: <info>'.$cbosDistintos->count().'</info>');

            foreach ($cbosDistintos as $codigoBruto) {
                $codigoLimpo = preg_replace('/\D/', '', $codigoBruto) ?? '';
                if ($codigoLimpo === '') {
                    $divergencias[] = "CBO vazio ou inválido ignorado: '{$codigoBruto}'";

                    continue;
                }

                $codigoPad = str_pad($codigoLimpo, 6, '0', STR_PAD_LEFT);

                $cboExistente = Cbo::where('codigo', $codigoPad)->first();
                if ($cboExistente === null) {
                    $tituloOficial = self::TITULOS_CONHECIDOS[$codigoPad] ?? "Ocupação CBO {$codigoPad}";

                    Cbo::create([
                        'codigo' => $codigoPad,
                        'titulo_oficial' => $tituloOficial,
                        'ativo' => true,
                        'fonte_referencia' => 'Recuperação Legada de Profissionais',
                        'versao_referencia' => 'Legado',
                        'verificado_em' => null, // NÃO marca como verificado sem conferência com fonte oficial MTE
                    ]);

                    $cbosCriados++;
                } else {
                    $cbosPreservados++;
                }
            }

            // -----------------------------------------------------------------
            // 2. Recuperação de Especialidades distintas de profissionais.especialidade
            // -----------------------------------------------------------------
            $espDistintas = $profissionais->pluck('especialidade')
                ->map(fn ($val) => trim((string) $val))
                ->filter(fn ($val) => $val !== '')
                ->unique()
                ->values();

            $this->line('Especialidades distintas encontradas nos profissionais: <info>'.$espDistintas->count().'</info>');

            foreach ($espDistintas as $nomeEsp) {
                $espExistente = Especialidade::where('nome', $nomeEsp)->first();
                if ($espExistente === null) {
                    Especialidade::create([
                        'nome' => $nomeEsp,
                        'descricao' => 'Especialidade recuperada do cadastro de profissionais',
                        'ativo' => true,
                    ]);
                    $especialidadesCriadas++;
                } else {
                    $especialidadesPreservadas++;
                }
            }

            // -----------------------------------------------------------------
            // 3. Garantir a existência de Neuropsicologia e vínculo ao CBO 251545
            // -----------------------------------------------------------------
            $espNeuro = Especialidade::firstOrCreate(
                ['nome' => 'Neuropsicologia'],
                [
                    'descricao' => 'Especialidade clínica de Neuropsicologia vinculada ao CBO 251545',
                    'ativo' => true,
                ]
            );

            $cboNeuro = Cbo::where('codigo', '251545')->first();
            if ($cboNeuro === null) {
                $cboNeuro = Cbo::create([
                    'codigo' => '251545',
                    'titulo_oficial' => 'Neuropsicólogo',
                    'ativo' => true,
                    'fonte_referencia' => 'Recuperação Legada de Profissionais',
                    'versao_referencia' => 'Legado',
                    'verificado_em' => null,
                ]);
                $cbosCriados++;
            }

            if (! $cboNeuro->especialidades()->where('especialidades.id', $espNeuro->id)->exists()) {
                $cboNeuro->especialidades()->attach($espNeuro->id);
                $vinculosPivoCriados++;
            }

            // -----------------------------------------------------------------
            // 4. Conciliação segura de vínculos cbo_id e especialidade_id
            // -----------------------------------------------------------------
            foreach ($profissionais as $prof) {
                $alterou = false;

                // 4.1 Conciliação de CBO
                if ($prof->cbo_padrao) {
                    $codProf = str_pad(preg_replace('/\D/', '', (string) $prof->cbo_padrao), 6, '0', STR_PAD_LEFT);
                    $cboModel = Cbo::where('codigo', $codProf)->first();

                    if ($cboModel !== null) {
                        // Não sobrescreve se já possuir um vínculo válido
                        if ($prof->cbo_id === null || $prof->cbo_id !== $cboModel->id) {
                            $prof->cbo_id = $cboModel->id;
                            $alterou = true;
                            $vinculosCboAtualizados++;
                        }
                    } else {
                        $divergencias[] = "Profissional ID {$prof->id} ({$prof->nome}): CBO {$prof->cbo_padrao} não pôde ser conciliado.";
                    }
                } else {
                    $divergencias[] = "Profissional ID {$prof->id} ({$prof->nome}) não possui CBO padrão informado.";
                }

                // 4.2 Conciliação de Especialidade
                if ($prof->especialidade) {
                    $nomeEspProf = trim((string) $prof->especialidade);
                    $espModel = Especialidade::where('nome', $nomeEspProf)->first();

                    if ($espModel !== null) {
                        if ($prof->especialidade_id === null || $prof->especialidade_id !== $espModel->id) {
                            $prof->especialidade_id = $espModel->id;
                            $alterou = true;
                            $vinculosEspAtualizados++;
                        }

                        // Sincroniza relação CBO x Especialidade no pivô
                        if ($prof->cbo_id) {
                            $cboInstancia = Cbo::find($prof->cbo_id);
                            if ($cboInstancia && ! $cboInstancia->especialidades()->where('especialidades.id', $espModel->id)->exists()) {
                                $cboInstancia->especialidades()->attach($espModel->id);
                                $vinculosPivoCriados++;
                            }
                        }
                    } else {
                        $divergencias[] = "Profissional ID {$prof->id} ({$prof->nome}): Especialidade '{$prof->especialidade}' não pôde ser conciliada.";
                    }
                }

                // Preserva os dados históricos cbo_padrao e especialidade sem qualquer alteração
                if ($alterou) {
                    $prof->save();
                }
            }

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Erro durante a recuperação: {$e->getMessage()}");

            return self::FAILURE;
        }

        // ---------------------------------------------------------------------
        // 5. Exibição do Resumo Estatístico da Execução
        // ---------------------------------------------------------------------
        $this->newLine();
        $this->table(
            ['Métrica de Recuperação e Conciliação', 'Quantidade'],
            [
                ['Profissionais avaliados', $totalProfissionais],
                ['CBOs legados criados no catálogo', $cbosCriados],
                ['CBOs já existentes preservados', $cbosPreservados],
                ['Especialidades criadas no catálogo', $especialidadesCriadas],
                ['Especialidades existentes preservadas', $especialidadesPreservadas],
                ['Vínculos cbo_id conciliados em profissionais', $vinculosCboAtualizados],
                ['Vínculos especialidade_id conciliados em profissionais', $vinculosEspAtualizados],
                ['Vínculos CBO ↔ Especialidade no pivô', $vinculosPivoCriados],
                ['Divergências / Avisos encontrados', count($divergencias)],
            ]
        );

        if (count($divergencias) > 0) {
            $this->newLine();
            $this->warn('Divergências registradas para revisão:');
            foreach (array_slice($divergencias, 0, 10) as $div) {
                $this->line("  • {$div}");
            }
            if (count($divergencias) > 10) {
                $this->line('  • ... e mais '.(count($divergencias) - 10).' divergências.');
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->warn('Operação concluída em modo de simulação (--dry-run). Nenhuma modificação foi gravada no banco.');
        } else {
            $this->info('✅ Recuperação e conciliação de dados legados concluída com sucesso!');
        }

        return self::SUCCESS;
    }
}
