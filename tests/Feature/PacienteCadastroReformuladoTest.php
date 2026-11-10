<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\ConfiguracoesSistema;
use App\Livewire\ProntuarioPaciente;
use App\Models\ConfiguracaoEstabelecimento;
use App\Models\Paciente;
use App\Models\Sala;
use App\Models\UnidadeBasicaSaude;
use App\Models\User;
use App\Rules\CnsValido;
use App\Services\AgendamentoService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class PacienteCadastroReformuladoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        ConfiguracaoEstabelecimento::atual()->update([
            'codigo_ibge_padrao' => '270430',
            'cnes' => '2000001',
        ]);
    }

    public function test_recepcao_pode_cadastrar_paciente_sem_cns_e_sem_dados_clinicos(): void
    {
        $ubs = UnidadeBasicaSaude::create([
            'nome' => 'UBS Centro / PSF Central',
            'cnes' => '2005432',
            'municipio_ibge' => '270430',
            'origem' => 'MANUAL',
            'ativo' => true,
        ]);

        $recepcionista = User::factory()->create(['ativo' => true]);
        $recepcionista->assignRole('recepcao');

        Livewire::actingAs($recepcionista)
            ->test(ConfiguracoesSistema::class, ['secao' => 'pacientes'])
            ->call('abrirModalNovoPaciente')
            ->assertSet('pac_municipio_ibge', '270430')
            ->set('pac_nome', 'Carlos Eduardo da Silva')
            ->set('pac_data_nascimento', '1995-08-20')
            ->set('pac_sexo', 'M')
            ->set('pac_ubs_id', $ubs->id)
            ->set('pac_nome_acs', 'Maria do Carmo ACS')
            ->set('pac_telefone_contato', '(82) 98888-1111')
            ->set('pac_telefone_secundario', '(82) 97777-2222')
            ->set('pac_endereco', 'Rua das Flores, 120')
            ->set('pac_bairro', 'Centro')
            ->call('salvarPaciente')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertDatabaseHas('pacientes', [
            'nome' => 'Carlos Eduardo da Silva',
            'cns' => null,
            'cpf' => null,
            'nome_mae' => null,
            'cid_principal' => null,
            'cid_secundario' => null,
            'modalidades_reabilitacao' => null,
            'origem_cadastro' => 'MANUAL',
            'ubs_id' => $ubs->id,
            'ubs_referencia' => 'UBS Centro / PSF Central',
            'nome_acs' => 'Maria do Carmo ACS',
            'telefone_contato' => '(82) 98888-1111',
            'telefone_secundario' => '(82) 97777-2222',
        ]);
    }

    public function test_recepcao_pode_cadastrar_paciente_com_cns_e_cpf_opcionais_validados(): void
    {
        $recepcionista = User::factory()->create(['ativo' => true]);
        $recepcionista->assignRole('recepcao');

        $cnsValido = CnsValido::gerarDefinitivo('17788899900');

        Livewire::actingAs($recepcionista)
            ->test(ConfiguracoesSistema::class, ['secao' => 'pacientes'])
            ->call('abrirModalNovoPaciente')
            ->set('pac_nome', 'Juliana Cristina Pereira')
            ->set('pac_cpf', '123.456.789-01')
            ->set('pac_cns', $cnsValido)
            ->set('pac_data_nascimento', '2010-04-12')
            ->set('pac_sexo', 'F')
            ->set('pac_nome_mae', 'Helena Maria Pereira')
            ->set('pac_municipio_ibge', '270430')
            ->call('salvarPaciente')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertDatabaseHas('pacientes', [
            'nome' => 'Juliana Cristina Pereira',
            'cpf' => '12345678901',
            'cns' => $cnsValido,
            'nome_mae' => 'Helena Maria Pereira',
            'origem_cadastro' => 'MANUAL',
        ]);
    }

    public function test_cadastro_rejeita_cns_invalido_quando_informado(): void
    {
        $recepcionista = User::factory()->create(['ativo' => true]);
        $recepcionista->assignRole('recepcao');

        Livewire::actingAs($recepcionista)
            ->test(ConfiguracoesSistema::class, ['secao' => 'pacientes'])
            ->call('abrirModalNovoPaciente')
            ->set('pac_nome', 'Paciente Teste Invalido')
            ->set('pac_data_nascimento', '2000-01-01')
            ->set('pac_sexo', 'M')
            ->set('pac_cns', '111111111111111') // CNS com dígito verificador inválido
            ->call('salvarPaciente')
            ->assertHasErrors(['pac_cns']);
    }

    public function test_deteccao_duplicidade_por_cpf_ou_cns(): void
    {
        $cns = CnsValido::gerarDefinitivo('29988877766');
        Paciente::create([
            'nome' => 'Ana Clara Alves',
            'cpf' => '11122233344',
            'cns' => $cns,
            'data_nascimento' => '2005-05-15',
            'sexo' => 'F',
            'municipio_ibge' => '270430',
        ]);

        $recepcionista = User::factory()->create(['ativo' => true]);
        $recepcionista->assignRole('recepcao');

        // Tenta cadastrar outro paciente com o mesmo CPF
        Livewire::actingAs($recepcionista)
            ->test(ConfiguracoesSistema::class, ['secao' => 'pacientes'])
            ->call('abrirModalNovoPaciente')
            ->set('pac_nome', 'Outro Paciente com Mesmo CPF')
            ->set('pac_cpf', '111.222.333-44')
            ->set('pac_data_nascimento', '2001-01-01')
            ->set('pac_sexo', 'M')
            ->call('salvarPaciente')
            ->assertHasErrors(['pac_cpf']);

        // Tenta cadastrar outro paciente com o mesmo CNS
        Livewire::actingAs($recepcionista)
            ->test(ConfiguracoesSistema::class, ['secao' => 'pacientes'])
            ->call('abrirModalNovoPaciente')
            ->set('pac_nome', 'Outro Paciente com Mesmo CNS')
            ->set('pac_cns', $cns)
            ->set('pac_data_nascimento', '2002-02-02')
            ->set('pac_sexo', 'M')
            ->call('salvarPaciente')
            ->assertHasErrors(['pac_cns']);
    }

    public function test_deteccao_duplicidade_por_nome_e_data_nascimento_quando_sem_documentos(): void
    {
        Paciente::create([
            'nome' => 'Beatriz Souza Silva',
            'data_nascimento' => '2012-10-05',
            'sexo' => 'F',
            'municipio_ibge' => '270430',
        ]);

        $recepcionista = User::factory()->create(['ativo' => true]);
        $recepcionista->assignRole('recepcao');

        // Sem CPF e sem CNS, duplicidade por Nome + Data de Nascimento
        Livewire::actingAs($recepcionista)
            ->test(ConfiguracoesSistema::class, ['secao' => 'pacientes'])
            ->call('abrirModalNovoPaciente')
            ->set('pac_nome', 'Beatriz Souza Silva')
            ->set('pac_data_nascimento', '2012-10-05')
            ->set('pac_sexo', 'F')
            ->call('salvarPaciente')
            ->assertHasErrors(['pac_nome']);
    }

    public function test_recepcao_nao_visualiza_diagnostico_clinico_na_listagem(): void
    {
        $paciente = Paciente::create([
            'nome' => 'Marcos Vinicius Santos',
            'cns' => CnsValido::gerarDefinitivo('33344455566'),
            'data_nascimento' => '2008-03-20',
            'sexo' => 'M',
            'municipio_ibge' => '270430',
            'cid_principal' => 'F840',
            'modalidades_reabilitacao' => ['INTELECTUAL'],
            'ubs_referencia' => 'UBS Santa Luzia',
            'telefone_contato' => '(82) 99123-4567',
        ]);

        $recepcionista = User::factory()->create(['ativo' => true]);
        $recepcionista->assignRole('recepcao');

        Livewire::actingAs($recepcionista)
            ->test(ConfiguracoesSistema::class, ['secao' => 'pacientes'])
            ->assertSee('Marcos Vinicius Santos')
            ->assertSee('UBS Santa Luzia')
            ->assertSee('(82) 99123-4567')
            ->assertDontSee('F840')
            ->assertDontSee('INTELECTUAL');
    }

    public function test_coordenacao_visualiza_diagnostico_clinico_na_listagem(): void
    {
        Paciente::create([
            'nome' => 'Marcos Vinicius Santos',
            'cns' => CnsValido::gerarDefinitivo('33344455566'),
            'data_nascimento' => '2008-03-20',
            'sexo' => 'M',
            'municipio_ibge' => '270430',
            'cid_principal' => 'F840',
            'modalidades_reabilitacao' => ['INTELECTUAL'],
        ]);

        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'pacientes'])
            ->assertSee('Marcos Vinicius Santos')
            ->assertSee('F840')
            ->assertSee('INTELECTUAL');
    }

    public function test_edicao_pela_recepcao_preserva_dados_clinicos_pre_existentes(): void
    {
        $paciente = Paciente::create([
            'nome' => 'Gabriel Henrique Costa',
            'cns' => CnsValido::gerarDefinitivo('44455566677'),
            'data_nascimento' => '2015-06-10',
            'sexo' => 'M',
            'municipio_ibge' => '270430',
            'cid_principal' => 'G800',
            'cid_secundario' => 'F840',
            'modalidades_reabilitacao' => ['FISICA', 'INTELECTUAL'],
        ]);

        $recepcionista = User::factory()->create(['ativo' => true]);
        $recepcionista->assignRole('recepcao');

        // Recepção edita apenas telefone e endereço
        Livewire::actingAs($recepcionista)
            ->test(ConfiguracoesSistema::class, ['secao' => 'pacientes'])
            ->call('editarPaciente', $paciente->id)
            ->set('pac_telefone_contato', '(82) 99999-8888')
            ->set('pac_endereco', 'Nova Rua Residencial, 45')
            ->call('salvarPaciente')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $paciente->refresh();
        $this->assertSame('(82) 99999-8888', $paciente->telefone_contato);
        $this->assertSame('Nova Rua Residencial, 45', $paciente->endereco);
        // Garante que CID-10 e modalidades NÃO foram apagados
        $this->assertSame('G800', $paciente->cid_principal);
        $this->assertSame('F840', $paciente->cid_secundario);
        $this->assertSame(['FISICA', 'INTELECTUAL'], $paciente->modalidades_reabilitacao);
    }

    public function test_terapeuta_e_coordenacao_definem_diagnostico_no_prontuario(): void
    {
        $paciente = Paciente::create([
            'nome' => 'Larissa Manoela Teste',
            'data_nascimento' => '2016-01-15',
            'sexo' => 'F',
            'municipio_ibge' => '270430',
        ]);

        $terapeutaUser = User::factory()->create(['ativo' => true]);
        $terapeutaUser->assignRole('terapeuta');

        Livewire::actingAs($terapeutaUser)
            ->test(ProntuarioPaciente::class, ['paciente' => $paciente->id])
            ->call('abrirModalDiagnostico')
            ->set('diag_cid_principal', 'F840')
            ->set('diag_cid_secundario', 'F900')
            ->set('diag_modalidades', ['INTELECTUAL', 'AUDITIVA'])
            ->call('salvarDiagnostico')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $paciente->refresh();
        $this->assertSame('F840', $paciente->cid_principal);
        $this->assertSame('F900', $paciente->cid_secundario);
        $this->assertSame(['INTELECTUAL', 'AUDITIVA'], $paciente->modalidades_reabilitacao);
    }

    public function test_recepcao_nao_tem_permissao_para_acessar_prontuario(): void
    {
        $paciente = Paciente::create([
            'nome' => 'Paciente Sigiloso',
            'data_nascimento' => '2010-01-01',
            'sexo' => 'M',
            'municipio_ibge' => '270430',
        ]);

        $recepcionista = User::factory()->create(['ativo' => true]);
        $recepcionista->assignRole('recepcao');

        $this->actingAs($recepcionista)
            ->get('/prontuario/'.$paciente->id)
            ->assertForbidden();
    }

    public function test_agendamento_sem_cns_e_bloqueado_com_mensagem_clara(): void
    {
        $pacienteSemCns = Paciente::create([
            'nome' => 'Paciente Sem Cartao SUS',
            'cns' => null,
            'data_nascimento' => '2014-07-25',
            'sexo' => 'M',
            'municipio_ibge' => '270430',
        ]);

        $sala = Sala::create([
            'nome' => 'Consultório Geral',
            'tipo' => 'CONSULTORIO',
            'ativo' => true,
        ]);

        $terapeutaUser = User::factory()->create(['ativo' => true]);
        $terapeutaUser->assignRole('terapeuta');
        $prof = $terapeutaUser->profissional()->create([
            'nome' => 'Dr. Terapeuta',
            'cpf' => '55566677788',
            'cns' => CnsValido::gerarDefinitivo('55566677788'),
            'registro_conselho' => 'CREFITO-12345',
            'cbo_padrao' => '223605',
            'especialidade' => 'Fisioterapia',
            'pin_assinatura_hash' => bcrypt('1234'),
            'ativo' => true,
        ]);

        $agendamentoService = app(AgendamentoService::class);

        $this->expectException(ValidationException::class);

        try {
            $agendamentoService->criarGrade(
                dados: [
                    'paciente_id' => $pacienteSemCns->id,
                    'sala_id' => $sala->id,
                    'data_atendimento' => now()->addDay()->toDateString(),
                    'hora_inicio' => '08:00',
                    'hora_fim' => '09:00',
                    'tipo_atendimento' => 'INDIVIDUAL',
                ],
                profissionaisIds: [$prof->id]
            );
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('paciente_id', $e->errors());
            $this->assertStringContainsString('Cartão SUS (CNS)', $e->errors()['paciente_id'][0]);
            throw $e;
        }
    }
}
