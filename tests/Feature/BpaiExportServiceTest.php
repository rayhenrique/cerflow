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
use Database\Seeders\ProcedimentoSigtapSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SalaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BpaiExportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RoleAndPermissionSeeder::class,
            ProcedimentoSigtapSeeder::class,
            SalaSeeder::class,
        ]);
    }

    public function test_audita_lote_e_gera_layout_posicional_datasus_header_bpa_e_linhas_03(): void
    {
        $userProf = User::factory()->create(['ativo' => true]);
        $userProf->assignRole('terapeuta');

        $cnsProf = CnsValido::gerarDefinitivo('12233344455');
        $profissional = Profissional::create([
            'user_id' => $userProf->id,
            'nome' => 'Dr. Rafael Fisioterapeuta',
            'cpf' => '22233344455',
            'cns' => $cnsProf,
            'registro_conselho' => 'CREFITO-1 8888-F',
            'cbo_padrao' => '223605',
            'especialidade' => 'Fisioterapia',
            'pin_assinatura_hash' => Hash::make('4321'),
            'ativo' => true,
        ]);

        $cnsPaciente = CnsValido::gerarDefinitivo('17788899900');
        $paciente = Paciente::create([
            'cns' => $cnsPaciente,
            'cpf' => '77788899900',
            'nome' => 'Ana Sofia Cavalcanti',
            'data_nascimento' => '2016-11-10',
            'sexo' => 'F',
            'nome_mae' => 'Patrícia Cavalcanti',
            'municipio_ibge' => '270430',
            'endereco' => 'Rua das Palmeiras',
            'bairro' => 'Farol',
            'telefone_contato' => '82999998888',
            'cid_principal' => 'G809',
            'modalidades_reabilitacao' => ['FISICA'],
            'ativo' => true,
        ]);

        $sala = Sala::firstOrFail();
        $procValido = ProcedimentoSigtap::where('codigo', '0301070075')->firstOrFail();
        $procIncompativelFono = ProcedimentoSigtap::where('codigo', '0301040044')->firstOrFail(); // Apenas CBO 223810

        $ag1 = Agendamento::create([
            'paciente_id' => $paciente->id,
            'sala_id' => $sala->id,
            'data_atendimento' => '2026-11-10',
            'hora_inicio' => '08:00',
            'hora_fim' => '08:30',
            'dia_semana' => 'TERCA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'PRESENTE',
        ]);

        $ag2 = Agendamento::create([
            'paciente_id' => $paciente->id,
            'sala_id' => $sala->id,
            'data_atendimento' => '2026-11-12',
            'hora_inicio' => '08:00',
            'hora_fim' => '08:30',
            'dia_semana' => 'QUINTA',
            'tipo_atendimento' => 'INDIVIDUAL',
            'status' => 'PRESENTE',
        ]);

        $evolucaoService = app(EvolucaoService::class);

        // Produção 1: Válida (Fisioterapeuta CBO 223605 + Procedimento 0301070075)
        $evolucaoService->registrarEAssinar(
            agendamento: $ag1,
            profissional: $profissional,
            procedimento: $procValido,
            dadosClinicos: [
                'queixa_estado_atual' => 'Marcha com apoio.',
                'conduta_terapeutica' => 'Treino de equilíbrio dinâmico.',
            ],
            pinAssinatura: '4321',
        );

        // Produção 2: Inconsistente (Fisioterapeuta CBO 223605 executando procedimento exclusivo de Fono 0301040044)
        $evolucaoService->registrarEAssinar(
            agendamento: $ag2,
            profissional: $profissional,
            procedimento: $procIncompativelFono,
            dadosClinicos: [
                'queixa_estado_atual' => 'Teste de CBO incompatível.',
                'conduta_terapeutica' => 'Deve ser retido na pré-auditoria.',
            ],
            pinAssinatura: '4321',
        );

        $bpaiExportService = app(BpaiExportService::class);
        $auditoria = $bpaiExportService->auditarCompetencia('202611', true);

        $this->assertSame(2, $auditoria['total']);
        $this->assertCount(1, $auditoria['validados']);
        $this->assertCount(1, $auditoria['inconsistentes']);
        $this->assertStringContainsString('CBO 223605 incompatível', (string) $auditoria['inconsistentes']->first()->mensagem_inconsistencia);

        // Gera arquivo posicional apenas com os registros validados:
        $conteudo = $bpaiExportService->gerarConteudoPosicional('202611', $auditoria['validados'], '2000001');
        $linhas = explode("\r\n", rtrim($conteudo, "\r\n"));

        $this->assertCount(2, $linhas);

        $header = $linhas[0];
        $linha03 = $linhas[1];

        // Validações posicionais do Header (Linha 01#BPA#):
        $this->assertSame('01#BPA#', substr($header, 0, 7));
        $this->assertSame('202611', substr($header, 7, 6));
        $this->assertSame('000001', substr($header, 13, 6)); // 1 linha tipo 03
        $this->assertSame('000001', substr($header, 19, 6)); // 1 folha
        $this->assertSame(130, strlen($header));

        // Validações posicionais da Linha Tipo 03 (BPA-i - 350 colunas):
        $this->assertSame(350, strlen($linha03));
        $this->assertSame('03', substr($linha03, 0, 2));             // 001-002 Indicador BPA-i
        $this->assertSame('2000001', substr($linha03, 2, 7));        // 003-009 CNES
        $this->assertSame('202611', substr($linha03, 9, 6));         // 010-015 Competência
        $this->assertSame($cnsProf, substr($linha03, 15, 15));       // 016-030 CNS Profissional
        $this->assertSame('223605', substr($linha03, 30, 6));        // 031-036 CBO
        $this->assertSame('20261110', substr($linha03, 36, 8));      // 037-044 Data atendimento
        $this->assertSame('001', substr($linha03, 44, 3));           // 045-047 Folha
        $this->assertSame('01', substr($linha03, 47, 2));            // 048-049 Sequência
        $this->assertSame('0301070075', substr($linha03, 49, 10));   // 050-059 Procedimento SIGTAP
        $this->assertSame($cnsPaciente, substr($linha03, 59, 15));   // 060-074 CNS Paciente
        $this->assertSame('F', substr($linha03, 74, 1));             // 075-075 Sexo
        $this->assertSame('270430', substr($linha03, 75, 6));        // 076-081 Código IBGE
        $this->assertSame('G809', substr($linha03, 81, 4));          // 082-085 CID-10
        $this->assertSame('010', substr($linha03, 85, 3));           // 086-088 Idade (10 anos)
        $this->assertSame('000001', substr($linha03, 88, 6));        // 089-094 Quantidade
        $this->assertSame('01', substr($linha03, 94, 2));            // 095-096 Caráter atendimento
        $this->assertSame('BPA', substr($linha03, 109, 3));          // 110-112 Origem
        $this->assertSame('ANA SOFIA CAVALCANTI          ', substr($linha03, 112, 30)); // 113-142 Nome
        $this->assertSame('20161110', substr($linha03, 142, 8));     // 143-150 Data nascimento
    }
}

