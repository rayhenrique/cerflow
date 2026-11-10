<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\ConfiguracoesSistema;
use App\Models\Cbo;
use App\Models\ConfiguracaoEstabelecimento;
use App\Models\Paciente;
use App\Models\ProcedimentoSigtap;
use App\Models\Sala;
use App\Models\User;
use App\Rules\CnsValido;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
        $cbo = Cbo::create([
            'codigo' => '251510',
            'titulo_oficial' => 'Psicólogo Clínico',
            'ativo' => true,
        ]);
        $cnsProf = CnsValido::gerarDefinitivo('15566677788');
        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'profissionais'])
            ->call('abrirModalNovoProfissional')
            ->assertSet('modalProfissionalAberto', true)
            ->assertSet('prof_cbo_id', null)
            ->call('selecionarCbo', $cbo->id)
            ->set('usr_name', 'Dr. Renato Psicólogo')
            ->set('usr_email', 'renato.psico@cerflow.gov.br')
            ->set('usr_password', 'senha123')
            ->set('usr_role', 'terapeuta')
            ->set('vincular_profissional', true)
            ->set('prof_cpf', '55566677788')
            ->set('prof_cns', $cnsProf)
            ->set('prof_conselho', 'CRP-15 4455')
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

    public function test_identificacao_do_estabelecimento_e_restrita_a_coordenacao(): void
    {
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');
        $terapeuta = User::factory()->create(['ativo' => true]);
        $terapeuta->assignRole('terapeuta');

        $this->actingAs($coordenador)->get('/configuracoes/estabelecimento')
            ->assertOk()
            ->assertSee('Identificação do Estabelecimento');

        $this->actingAs($terapeuta)->get('/configuracoes/estabelecimento')->assertForbidden();
    }

    public function test_coordenacao_pode_salvar_substituir_e_remover_logo(): void
    {
        Storage::fake('public');
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        // 1. Upload inicial da logo
        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'estabelecimento'])
            ->set('estabelecimento_ibge', '270430')
            ->set('estabelecimento_cnes', '1234567')
            ->set('logoEstabelecimento', UploadedFile::fake()->image('logo.png', 300, 120))
            ->call('salvarIdentificacaoEstabelecimento')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $configuracao = ConfiguracaoEstabelecimento::atual();
        $this->assertSame('270430', $configuracao->codigo_ibge_padrao);
        $this->assertSame('1234567', $configuracao->cnes);
        $this->assertNotNull($configuracao->logo_path);
        $primeiraLogo = $configuracao->logo_path;
        Storage::disk('public')->assertExists($primeiraLogo);
        $this->assertDatabaseHas('logs_auditoria', [
            'acao' => 'ATUALIZACAO_ESTABELECIMENTO',
            'tabela_afetada' => 'configuracoes_estabelecimento',
        ]);

        // 2. Substituição da logo
        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'estabelecimento'])
            ->set('estabelecimento_ibge', '270430')
            ->set('estabelecimento_cnes', '1234567')
            ->set('logoEstabelecimento', UploadedFile::fake()->image('nova_logo.png', 300, 120))
            ->call('salvarIdentificacaoEstabelecimento')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $configuracao = ConfiguracaoEstabelecimento::atual();
        $segundaLogo = $configuracao->logo_path;
        $this->assertNotNull($segundaLogo);
        $this->assertNotSame($primeiraLogo, $segundaLogo);
        Storage::disk('public')->assertMissing($primeiraLogo);
        Storage::disk('public')->assertExists($segundaLogo);

        // 3. Remoção da logo
        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'estabelecimento'])
            ->set('estabelecimento_ibge', '270430')
            ->set('estabelecimento_cnes', '1234567')
            ->set('removerLogoEstabelecimento', true)
            ->call('salvarIdentificacaoEstabelecimento')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $configuracao = ConfiguracaoEstabelecimento::atual();
        $this->assertNull($configuracao->logo_path);
        Storage::disk('public')->assertMissing($segundaLogo);
    }

    public function test_exibicao_da_logo_no_login_e_menu_com_fallback(): void
    {
        Storage::fake('public');
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        // Sem logo: login exibe "CER Digital", layout exibe badge "CD" e "CER Digital"
        ConfiguracaoEstabelecimento::atual()->update(['logo_path' => null, 'cnes' => '2000001']);

        $this->get('/login')
            ->assertOk()
            ->assertSee('CER Digital')
            ->assertDontSee('alt="Logo do estabelecimento"', false);

        $this->actingAs($coordenador)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('CD')
            ->assertSee('CER Digital')
            ->assertSee('CNES 2000001')
            ->assertDontSee('alt="Logo do estabelecimento"', false);

        // Com logo configurada:
        $logoFake = UploadedFile::fake()->image('logo_estab.png', 200, 80)->store('identidade-estabelecimento', 'public');
        ConfiguracaoEstabelecimento::atual()->update([
            'logo_path' => $logoFake,
            'cnes' => '9988776',
        ]);

        Auth::logout();

        $this->get('/login')
            ->assertOk()
            ->assertSee('alt="Logo do estabelecimento"', false)
            ->assertSee(Storage::disk('public')->url($logoFake), false);

        $this->actingAs($coordenador)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('alt="Logo do estabelecimento"', false)
            ->assertSee('CNES 9988776');
    }

    public function test_novo_paciente_recebe_ibge_padrao_configurado(): void
    {
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');
        ConfiguracaoEstabelecimento::atual()->update(['codigo_ibge_padrao' => '2709152']);

        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'pacientes'])
            ->call('abrirModalNovoPaciente')
            ->assertSet('pac_municipio_ibge', '2709152');
    }

    public function test_configuracao_estabelecimento_padrao_e_fallback_sem_escrita(): void
    {
        $padrao = ConfiguracaoEstabelecimento::padrao();
        $this->assertSame('2709152', $padrao->codigo_ibge_padrao);
        $this->assertSame('0795690', $padrao->cnes);
        $this->assertNull($padrao->logo_path);
        $this->assertFalse($padrao->exists);

        $atual = ConfiguracaoEstabelecimento::atual();
        $this->assertInstanceOf(ConfiguracaoEstabelecimento::class, $atual);
        $this->assertSame('2709152', $atual->codigo_ibge_padrao);
    }

    public function test_fallback_temporario_seguro_quando_tabela_nao_existe(): void
    {
        Schema::dropIfExists('configuracoes_estabelecimento');

        $atual = ConfiguracaoEstabelecimento::atual();

        $this->assertSame('2709152', $atual->codigo_ibge_padrao);
        $this->assertSame('0795690', $atual->cnes);
        $this->assertNull($atual->logo_path);
    }
}
