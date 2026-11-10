<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\ConfiguracoesSistema;
use App\Models\Cbo;
use App\Models\Especialidade;
use App\Models\LogAuditoria;
use App\Models\Paciente;
use App\Models\ProcedimentoSigtap;
use App\Models\Profissional;
use App\Models\UnidadeBasicaSaude;
use App\Models\User;
use App\Rules\CnsValido;
use Database\Seeders\CboEspecialidadeUbsSeeder;
use Database\Seeders\ProcedimentoSigtapSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class CboEspecialidadesUbsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(ProcedimentoSigtapSeeder::class);
        $this->seed(CboEspecialidadeUbsSeeder::class);
    }

    public function test_cbo_inicial_neuropsicologo_e_especialidade_neuropsicologia_estao_cadastrados(): void
    {
        $cbo = Cbo::where('codigo', '251545')->first();
        $this->assertNotNull($cbo, 'O CBO 251545 deve estar cadastrado.');
        $this->assertSame('Neuropsicólogo', $cbo->titulo_oficial);
        $this->assertTrue($cbo->ativo);
        $this->assertNotNull($cbo->verificado_em);
        $this->assertStringContainsString('Ministério do Trabalho', $cbo->fonte_referencia);

        $especialidade = Especialidade::where('nome', 'Neuropsicologia')->first();
        $this->assertNotNull($especialidade, 'A especialidade Neuropsicologia deve existir.');
        $this->assertTrue($especialidade->ativo);

        // Verifica o vínculo N:N entre CBO e Especialidade
        $this->assertTrue($cbo->especialidades->contains($especialidade));
        $this->assertTrue($especialidade->cbos->contains($cbo));
    }

    public function test_protecao_bpai_cbo_neuropsicologo_nao_e_adicionado_automaticamente_ao_sigtap(): void
    {
        // Todos os procedimentos SIGTAP existentes não devem ter 251545 inserido arbitrariamente
        $procedimentos = ProcedimentoSigtap::all();
        $this->assertNotEmpty($procedimentos);

        foreach ($procedimentos as $proc) {
            $cbosPermitidos = $proc->cbos_permitidos ?? [];
            $this->assertNotContains(
                '251545',
                $cbosPermitidos,
                "O procedimento {$proc->codigo} não deve conter CBO 251545 automaticamente sem portaria oficial do SUS."
            );
            $this->assertFalse(
                $proc->permiteCbo('251545'),
                "O procedimento {$proc->codigo} deve rejeitar validação BPA-i para CBO 251545 incompatível."
            );
        }
    }

    public function test_crud_cbo_validacoes_e_auditoria(): void
    {
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        // 1. Validação de código CBO com menos de 6 dígitos deve falhar
        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'cbos'])
            ->call('abrirModalNovoCbo')
            ->set('cbo_codigo', '12345') // apenas 5 dígitos
            ->set('cbo_titulo_oficial', 'Ocupação Teste Inválida')
            ->call('salvarCbo')
            ->assertHasErrors(['cbo_codigo']);

        // 2. Cadastro válido com 6 dígitos não cadastrado ainda
        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'cbos'])
            ->call('abrirModalNovoCbo')
            ->set('cbo_codigo', '225105')
            ->set('cbo_titulo_oficial', 'Médico Acupunturista')
            ->set('cbo_fonte_referencia', 'MTE Oficial')
            ->set('cbo_verificado', true)
            ->call('salvarCbo')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $cboNovo = Cbo::where('codigo', '225105')->first();
        $this->assertNotNull($cboNovo);
        $this->assertSame('Médico Acupunturista', $cboNovo->titulo_oficial);
        $this->assertTrue($cboNovo->ativo);
        $this->assertNotNull($cboNovo->verificado_em);

        // Verifica registro de auditoria
        $log = LogAuditoria::where('acao', 'CADASTRO_CBO')->latest()->first();
        $this->assertNotNull($log);
        $this->assertSame($coordenador->id, $log->user_id);
        $this->assertSame('225105', $log->detalhes['codigo']);

        // 3. Impedir duplicação de código CBO
        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'cbos'])
            ->call('abrirModalNovoCbo')
            ->set('cbo_codigo', '225105')
            ->set('cbo_titulo_oficial', 'Outro Médico')
            ->call('salvarCbo')
            ->assertHasErrors(['cbo_codigo']);

        // 4. Inativar / Ativar CBO
        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'cbos'])
            ->call('alternarStatusCbo', $cboNovo->id);

        $this->assertFalse($cboNovo->fresh()->ativo);

        // 5. Excluir CBO sem vínculos
        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'cbos'])
            ->call('confirmarExclusao', 'cbo', $cboNovo->id)
            ->call('excluirRegistro')
            ->assertDispatched('toast');

        $this->assertNull(Cbo::find($cboNovo->id));
    }

    public function test_bloqueio_de_exclusao_de_cbo_com_profissionais_vinculados(): void
    {
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        $cbo = Cbo::where('codigo', '223605')->firstOrFail();

        // Cria profissional vinculado ao CBO
        $userProf = User::factory()->create(['ativo' => true]);
        $userProf->assignRole('terapeuta');
        $cns = CnsValido::gerarDefinitivo('15566677788');
        Profissional::create([
            'user_id' => $userProf->id,
            'cbo_id' => $cbo->id,
            'cbo_padrao' => $cbo->codigo,
            'nome' => 'Dr. Fisioterapeuta Teste',
            'cns' => $cns,
            'cpf' => '12345678901',
            'registro_conselho' => 'CREFITO 1234',
            'especialidade' => 'Fisioterapia',
            'pin_assinatura_hash' => Hash::make('123456'),
            'ativo' => true,
        ]);

        $this->assertFalse($cbo->podeSerExcluido());

        // Tentativa de exclusão deve ser interceptada
        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'cbos'])
            ->call('confirmarExclusao', 'cbo', $cbo->id)
            ->call('excluirRegistro')
            ->assertDispatched('toast', fn ($event, $params) => $params['tipo'] === 'alerta');

        $this->assertNotNull(Cbo::find($cbo->id), 'CBO vinculado a profissionais não pode ser excluído.');
    }

    public function test_crud_especialidade_e_vinculo_com_cbos(): void
    {
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        $cbo1 = Cbo::where('codigo', '223605')->firstOrFail();
        $cbo2 = Cbo::where('codigo', '223905')->firstOrFail();

        // 1. Criar Especialidade vinculando a CBOs
        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'cbos'])
            ->call('abrirModalNovaEspecialidade')
            ->set('esp_nome', 'Reabilitação Vestibular')
            ->set('esp_cbos_vinculados', [$cbo1->id, $cbo2->id])
            ->call('salvarEspecialidade')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $esp = Especialidade::where('nome', 'Reabilitação Vestibular')->first();
        $this->assertNotNull($esp);
        $this->assertCount(2, $esp->cbos);

        // 2. Não permitir duplicata de nome de especialidade (case/space-insensitive)
        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'cbos'])
            ->call('abrirModalNovaEspecialidade')
            ->set('esp_nome', '  reabilitação vestibular  ') // minúsculo e com espaços
            ->call('salvarEspecialidade')
            ->assertHasErrors(['esp_nome']);

        // 3. Excluir especialidade sem vínculos de profissionais
        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'cbos'])
            ->call('confirmarExclusao', 'especialidade', $esp->id)
            ->call('excluirRegistro')
            ->assertDispatched('toast');

        $this->assertNull(Especialidade::find($esp->id));
    }

    public function test_crud_unidade_basica_saude_e_validacao_cnes(): void
    {
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        // 1. Validação de CNES inválido (não tem 7 dígitos)
        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'ubs'])
            ->call('abrirModalNovaUbs')
            ->set('ubs_nome', 'UBS Teste Inválida')
            ->set('ubs_cnes', '12345') // apenas 5 dígitos
            ->call('salvarUbs')
            ->assertHasErrors(['ubs_cnes']);

        // 2. Cadastro válido de UBS
        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'ubs'])
            ->call('abrirModalNovaUbs')
            ->set('ubs_nome', 'UBS Bairro Esperança')
            ->set('ubs_cnes', '2009876')
            ->set('ubs_municipio_ibge', '270430')
            ->set('ubs_bairro', 'Esperança')
            ->set('ubs_telefone', '(82) 3211-0000')
            ->set('ubs_origem', 'MANUAL')
            ->call('salvarUbs')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $ubs = UnidadeBasicaSaude::where('cnes', '2009876')->first();
        $this->assertNotNull($ubs);
        $this->assertSame('UBS Bairro Esperança', $ubs->nome);
        $this->assertSame('MANUAL', $ubs->origem);
        $this->assertTrue($ubs->ativo);

        // 3. Auditoria registrada
        $log = LogAuditoria::where('acao', 'CADASTRO_UBS')->latest()->first();
        $this->assertNotNull($log);
        $this->assertSame('2009876', $log->detalhes['cnes']);

        // 4. Bloquear exclusão se paciente estiver vinculado
        $cnsPac = CnsValido::gerarDefinitivo('26677788899');
        $paciente = Paciente::create([
            'nome' => 'Paciente Com UBS Vinculada',
            'cns' => $cnsPac,
            'data_nascimento' => '1995-05-15',
            'sexo' => 'M',
            'municipio_ibge' => '270430',
            'ubs_id' => $ubs->id,
            'ubs_referencia' => $ubs->nome,
            'ativo' => true,
        ]);

        $this->assertFalse($ubs->podeSerExcluida());

        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'ubs'])
            ->call('confirmarExclusao', 'ubs', $ubs->id)
            ->call('excluirRegistro')
            ->assertDispatched('toast', fn ($event, $params) => $params['tipo'] === 'alerta');

        $this->assertNotNull(UnidadeBasicaSaude::find($ubs->id));

        // 5. Remoção física do paciente permite a exclusão da UBS
        $paciente->forceDelete();
        $this->assertTrue($ubs->fresh()->podeSerExcluida());

        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'ubs'])
            ->call('confirmarExclusao', 'ubs', $ubs->id)
            ->call('excluirRegistro')
            ->assertDispatched('toast');

        $this->assertNull(UnidadeBasicaSaude::find($ubs->id));
    }

    public function test_cadastro_profissional_com_cbo_e_especialidade_dinamicos(): void
    {
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        $cbo = Cbo::where('codigo', '251545')->firstOrFail(); // Neuropsicólogo
        $especialidade = Especialidade::where('nome', 'Neuropsicologia')->firstOrFail();
        $cnsProf = CnsValido::gerarDefinitivo('15566677788');

        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'profissionais'])
            ->call('abrirModalNovoProfissional')
            ->set('usr_name', 'Dra. Luiza Neuropsicóloga')
            ->set('usr_email', 'luiza.neuro@exemplo.com')
            ->set('usr_role', 'terapeuta')
            ->set('usr_password', 'SenhaForte123')
            ->set('vincular_profissional', true)
            ->set('prof_cns', $cnsProf)
            ->set('prof_cpf', '98765432100')
            ->set('prof_conselho', 'CRP 15/9999')
            ->set('prof_cbo_id', $cbo->id)
            ->set('prof_especialidade_id', $especialidade->id)
            ->set('prof_pin', '654321')
            ->call('salvarUsuarioProfissional')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $user = User::where('email', 'luiza.neuro@exemplo.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->profissional);

        // Valida que tanto os novos relacionamentos quanto as strings legadas foram preenchidos
        $this->assertSame($cbo->id, $user->profissional->cbo_id);
        $this->assertSame($cbo->codigo, $user->profissional->cbo_padrao);
        $this->assertSame($especialidade->id, $user->profissional->especialidade_id);
        $this->assertSame($especialidade->nome, $user->profissional->especialidade);
    }

    public function test_cadastro_paciente_sincroniza_ubs_id_e_ubs_referencia(): void
    {
        $recepcionista = User::factory()->create(['ativo' => true]);
        $recepcionista->assignRole('recepcao');

        $ubs = UnidadeBasicaSaude::where('ativo', true)->firstOrFail();
        $cnsPac = CnsValido::gerarDefinitivo('26677788899');

        Livewire::actingAs($recepcionista)
            ->test(ConfiguracoesSistema::class, ['secao' => 'pacientes'])
            ->call('abrirModalNovoPaciente')
            ->set('pac_nome', 'Maria Silva dos Anjos')
            ->set('pac_cns', $cnsPac)
            ->set('pac_data_nascimento', '1988-11-20')
            ->set('pac_sexo', 'F')
            ->set('pac_municipio_ibge', '270430')
            ->set('pac_ubs_id', $ubs->id)
            ->call('salvarPaciente')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $paciente = Paciente::where('cns', $cnsPac)->first();
        $this->assertNotNull($paciente);
        $this->assertSame($ubs->id, $paciente->ubs_id);
        $this->assertSame($ubs->nome, $paciente->ubs_referencia);
    }

    public function test_controle_de_acesso_bloqueia_usuarios_sem_permissao(): void
    {
        $terapeuta = User::factory()->create(['ativo' => true]);
        $terapeuta->assignRole('terapeuta');

        // Terapeuta não pode acessar a seção 'cbos' nem 'ubs'
        $this->actingAs($terapeuta)
            ->get('/configuracoes/cbos')
            ->assertForbidden();

        $this->actingAs($terapeuta)
            ->get('/configuracoes/ubs')
            ->assertForbidden();
    }
}
