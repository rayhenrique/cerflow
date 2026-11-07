<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\ConfiguracoesSistema;
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

    public function test_coordenacao_acessa_modulo_configuracoes_e_cadastra_sala_profissional_e_paciente(): void
    {
        $coordenador = User::factory()->create(['ativo' => true]);
        $coordenador->assignRole('coordenacao');

        $this->actingAs($coordenador)
            ->get('/configuracoes')
            ->assertOk()
            ->assertSee('Configurações, Parâmetros SUS & Cadastros Base', false);

        // 1. Cadastra nova Sala
        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class)
            ->set('sala_nome', 'Consultório 12 - Estimulação Auditiva')
            ->set('sala_tipo', 'CONSULTORIO')
            ->call('salvarSala')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('salas', [
            'nome' => 'Consultório 12 - Estimulação Auditiva',
            'tipo' => 'CONSULTORIO',
        ]);

        // 2. Cadastra novo Usuário + Profissional
        $cnsProf = CnsValido::gerarDefinitivo('15566677788');
        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class)
            ->call('selecionarAba', 'profissionais')
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
            ->assertHasNoErrors();

        $this->assertDatabaseHas('profissionais', [
            'cns' => $cnsProf,
            'cbo_padrao' => '251510',
        ]);

        // 3. Cadastra Paciente com CNS válido
        $cnsPac = CnsValido::gerarDefinitivo('26677788899');
        Livewire::actingAs($coordenador)
            ->test(ConfiguracoesSistema::class)
            ->call('selecionarAba', 'pacientes')
            ->set('pac_nome', 'Laura Beatriz Santos')
            ->set('pac_cns', $cnsPac)
            ->set('pac_data_nascimento', '2017-03-14')
            ->set('pac_sexo', 'F')
            ->set('pac_nome_mae', 'Aline Santos')
            ->set('pac_municipio_ibge', '270430')
            ->set('pac_cid_principal', 'F840')
            ->set('pac_modalidades', ['INTELECTUAL'])
            ->call('salvarPaciente')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('pacientes', [
            'cns' => $cnsPac,
            'nome' => 'Laura Beatriz Santos',
        ]);
    }
}
