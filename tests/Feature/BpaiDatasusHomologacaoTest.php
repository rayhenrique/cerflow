<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Agendamento;
use App\Models\Paciente;
use App\Models\ProcedimentoSigtap;
use App\Models\Profissional;
use App\Models\Sala;
use App\Models\User;
use App\Rules\CnsValido;
use App\Services\BpaiExportService;
use App\Services\EvolucaoService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BpaiDatasusHomologacaoTest extends TestCase
{
    use RefreshDatabase;

    public function test_homologacao_estrutural_do_arquivo_magnetico_bpa_datasus(): void
    {
        $this->seed(DatabaseSeeder::class);

        $coordenador = User::where('email', 'coordenacao@cerflow.gov.br')->firstOrFail();

        $profissional = Profissional::create([
            'user_id' => $coordenador->id,
            'nome' => 'Dra. Juliana Fonoaudióloga',
            'cpf' => '33344455566',
            'cns' => CnsValido::gerarDefinitivo('13344455566'),
            'registro_conselho' => 'CRFa 4-7788',
            'cbo_padrao' => '223810',
            'especialidade' => 'Fonoaudiologia',
            'pin_assinatura_hash' => Hash::make('9999'),
            'ativo' => true,
        ]);

        $paciente = Paciente::create([
            'cns' => CnsValido::gerarDefinitivo('21122233344'),
            'cpf' => '11122233344',
            'nome' => 'Miguel Henrique Araújo',
            'data_nascimento' => '2020-02-15',
            'sexo' => 'M',
            'nome_mae' => 'Camila Araújo',
            'municipio_ibge' => '270430',
            'endereco' => 'Av Fernandes Lima',
            'bairro' => 'Gruta de Lourdes',
            'telefone_contato' => '82988887777',
            'cid_principal' => 'F840',
            'modalidades_reabilitacao' => ['INTELECTUAL', 'AUDITIVA'],
            'ativo' => true,
        ]);

        $agendamento = Agendamento::create([
            'paciente_id' => $paciente->id,
            'sala_id' => Sala::firstOrFail()->id,
            'data_atendimento' => '2026-11-06',
            'hora_inicio' => '10:00',
            'hora_fim' => '10:30',
            'dia_semana' => 'SEXTA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'PRESENTE',
        ]);

        $procedimento = ProcedimentoSigtap::where('codigo', '0301070113')->firstOrFail();

        app(EvolucaoService::class)->registrarEAssinar(
            agendamento: $agendamento,
            profissional: $profissional,
            procedimento: $procedimento,
            dadosClinicos: [
                'queixa_estado_atual' => 'Atraso global de linguagem expressiva.',
                'conduta_terapeutica' => 'Terapia fonoaudiológica estruturada.',
            ],
            pinAssinatura: '9999',
        );

        $exportService = app(BpaiExportService::class);
        $response = $exportService->exportarStream('202611', $coordenador, '127.0.0.1');

        ob_start();
        $response->sendContent();
        $conteudoArquivo = (string) ob_get_clean();

        Storage::disk('local')->put('homologacao/BPA_I_202611.txt', $conteudoArquivo);

        // Validador estrutural oficial do layout BPA Magnético DATASUS:
        $this->assertStringEndsWith("\r\n", $conteudoArquivo);
        $linhas = explode("\r\n", rtrim($conteudoArquivo, "\r\n"));
        $this->assertGreaterThanOrEqual(2, count($linhas));

        // 1. Cabeçalho 01#BPA#
        $this->assertMatchesRegularExpression(
            '/^01#BPA#\d{6}\d{6}\d{6}(1\d{3}|2[01]\d{2}|22[01]\d|222[01]).{30}.{6}\d{14}.{40}[ME].{10}$/',
            $linhas[0]
        );

        // 2. Linhas Tipo 03 (BPA-i)
        foreach (array_slice($linhas, 1) as $linha03) {
            $this->assertSame(350, strlen($linha03));
            $this->assertMatchesRegularExpression(
                '/^03\d{7}\d{6}\d{15}[0-9A-Z ]{6}\d{8}\d{3}\d{2}\d{10}\d{15}[MF]\d{6}[A-Z0-9 ]{4}\d{3}\d{6}\d{2}.{13}BPA.{30}\d{8}\d{2}.{4}\d{3}.{191}$/',
                $linha03
            );
        }

        // 3. Verifica gravação na trilha de auditoria LGPD:
        $this->assertDatabaseHas('logs_auditoria', [
            'user_id' => $coordenador->id,
            'acao' => 'EXPORTACAO_BPA',
            'tabela_afetada' => 'producoes_bpai',
        ]);
    }
}

