<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\ConfiguracoesSistema;
use App\Models\Paciente;
use App\Models\ProcedimentoSigtap;
use App\Models\Sala;
use App\Models\User;
use App\Rules\CnsValido;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConfiguracoesSistemaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_rotas_de_submenus_configuracoes_respeitam_rbac(): void
    {
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        $terapeuta = User::factory()->create(['ativo' => true]);
        $terapeuta->assignRole('terapeuta');

        $this->actingAs($coordenador)
            ->get('/configuracoes/salas')
            ->assertOk()
            ->assertSee('Salas, Consultórios & Ginásios');

        $this->actingAs($coordenador)
            ->get('/configuracoes/pacientes')
            ->assertOk()
            ->assertSee('Cadastro de Pacientes (CER)');

        // Terapeuta acessa Meu Perfil mas recebe 403 ao tentar acessar Salas da Coordenação
        $this->actingAs($terapeuta)
            ->get('/configuracoes/perfil')
            ->assertOk()
            ->assertSee('Meu Perfil & PIN de Assinatura Clínica');

        $this->actingAs($terapeuta)
            ->get('/configuracoes/salas')
            ->assertForbidden();
    }

    public function test_crud_completo_com_modal_e_toast_nos_submodulos_de_configuracoes(): void
    {
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        // 1. CRUD Sala (Criar -> Editar -> Inativar -> Excluir)
        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'salas'])
            ->call('abrirModalNovaSala')
            ->assertSet('modalSalaAberto', true)
            ->set('sala_nome', 'Consultório 12 - Estimulação Auditiva')
            ->set('sala_tipo', 'CONSULTORIO')
            ->call('salvarSala')
            ->assertHasNoErrors()
            ->assertSet('modalSalaAberto', false)
            ->assertDispatched('toast');

        $sala = Sala::where('nome', 'Consultório 12 - Estimulação Auditiva')->firstOrFail();

        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'salas'])
            ->call('editarSala', $sala->id)
            ->assertSet('modalSalaAberto', true)
            ->set('sala_nome', 'Consultório 12 - Auditivo Renovado')
            ->call('salvarSala')
            ->assertDispatched('toast')
            ->call('confirmarExclusao', 'sala', $sala->id)
            ->assertSet('modalExclusaoAberto', true)
            ->call('excluirRegistro')
            ->assertSet('modalExclusaoAberto', false)
            ->assertDispatched('toast');

        $this->assertDatabaseMissing('salas', ['id' => $sala->id]);

        // 2. CRUD Usuário + Profissional (Criar -> Editar -> Excluir)
        $cnsProf = CnsValido::gerarDefinitivo('15566677788');
        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'profissionais'])
            ->call('abrirModalNovoProfissional')
            ->assertSet('modalProfissionalAberto', true)
            ->set('usr_name', 'Dr. Renato Psicólogo')
            ->set('usr_email', 'renato.psico@cerflow.gov.br')
            ->set('usr_password', 'senha123')
            ->set('usr_role', 'terapeuta')
            ->set('vincular_profissional', true)
            ->set('prof_cpf', '55566677788')
            ->set('prof_cns', $cnsProf)
            ->set('prof_conselho', 'CRP-15 4455')
            ->set('prof_cbo', '251510')
            ->set('prof_especialidade', 'Psicologia')
            ->set('prof_pin', '4567')
            ->call('salvarUsuarioProfissional')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $usrProf = User::where('email', 'renato.psico@cerflow.gov.br')->firstOrFail();

        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'profissionais'])
            ->call('editarUsuarioProfissional', $usrProf->id)
            ->set('usr_name', 'Dr. Renato Psicólogo Clínico')
            ->call('salvarUsuarioProfissional')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertDatabaseHas('users', [
            'id' => $usrProf->id,
            'name' => 'Dr. Renato Psicólogo Clínico',
        ]);

        // 3. CRUD Paciente (Criar -> Editar -> Arquivar SoftDelete)
        $cnsPac = CnsValido::gerarDefinitivo('26677788899');
        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'pacientes'])
            ->call('abrirModalNovoPaciente')
            ->set('pac_nome', 'Laura Beatriz Santos')
            ->set('pac_cns', $cnsPac)
            ->set('pac_data_nascimento', '2017-03-14')
            ->set('pac_sexo', 'F')
            ->set('pac_nome_mae', 'Aline Santos')
            ->set('pac_municipio_ibge', '270430')
            ->set('pac_cid_principal', 'F840')
            ->set('pac_modalidades', ['INTELECTUAL'])
            ->call('salvarPaciente')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $paciente = Paciente::where('cns', $cnsPac)->firstOrFail();

        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'pacientes'])
            ->call('editarPaciente', $paciente->id)
            ->set('pac_nome', 'Laura Beatriz dos Santos')
            ->call('salvarPaciente')
            ->assertDispatched('toast')
            ->call('confirmarExclusao', 'paciente', $paciente->id)
            ->call('excluirRegistro')
            ->assertDispatched('toast');

        $this->assertSoftDeleted('pacientes', ['id' => $paciente->id]);

        // 4. CRUD SIGTAP (Criar -> Editar -> Excluir)
        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'sigtap'])
            ->call('abrirModalNovoSigtap')
            ->set('sig_codigo', '0301070999')
            ->set('sig_descricao', 'PROCEDIMENTO TESTE REABILITACAO')
            ->set('sig_cbos_csv', '223605, 223810')
            ->call('salvarProcedimentoSigtap')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $proc = ProcedimentoSigtap::where('codigo', '0301070999')->firstOrFail();

        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'sigtap'])
            ->call('editarProcedimentoSigtap', $proc->id)
            ->set('sig_descricao', 'PROCEDIMENTO TESTE ATUALIZADO')
            ->call('salvarProcedimentoSigtap')
            ->assertDispatched('toast')
            ->call('confirmarExclusao', 'sigtap', $proc->id)
            ->call('excluirRegistro')
            ->assertDispatched('toast');

        $this->assertDatabaseMissing('procedimentos_sigtap', ['id' => $proc->id]);
    }
}
