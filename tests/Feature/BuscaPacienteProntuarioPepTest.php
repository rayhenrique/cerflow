<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\ProntuarioPaciente;
use App\Models\Paciente;
use App\Models\Profissional;
use App\Models\User;
use App\Rules\CnsValido;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SalaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class BuscaPacienteProntuarioPepTest extends TestCase
{
    use RefreshDatabase;

    private User $terapeuta;

    private Profissional $profissional;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RoleAndPermissionSeeder::class,
            SalaSeeder::class,
        ]);

        $this->terapeuta = User::factory()->create(['ativo' => true]);
        $this->terapeuta->assignRole('terapeuta');

        $this->profissional = Profissional::create([
            'user_id' => $this->terapeuta->id,
            'nome' => 'Dr. Vinicius Fisioterapeuta',
            'cpf' => '12312312344',
            'cns' => CnsValido::gerarDefinitivo('12312312344'),
            'cbo_padrao' => '223605',
            'especialidade' => 'Fisioterapia',
            'registro_conselho' => 'CREFITO-1 9988',
            'pin_assinatura_hash' => Hash::make('1234'),
            'ativo' => true,
        ]);
    }

    public function test_prontuario_inicial_sem_parametro_nao_carrega_primeiro_paciente_nem_todos_pacientes(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            Paciente::create([
                'nome' => sprintf('Paciente Inicial %02d', $i),
                'cpf' => sprintf('888000000%02d', $i),
                'cns' => CnsValido::gerarDefinitivo(sprintf('888000000%02d', $i)),
                'data_nascimento' => '1990-01-01',
                'sexo' => 'M',
                'nome_mae' => 'Mãe Teste',
                'municipio_ibge' => '270430',
                'ativo' => true,
            ]);
        }

        Livewire::actingAs($this->terapeuta)
            ->test(ProntuarioPaciente::class)
            ->assertSet('pacienteId', null)
            ->assertSet('pesquisaPaciente', '')
            ->assertViewHas('pacientesResultados', [])
            ->assertViewHas('pacienteSelecionado', null)
            ->assertSee('Nenhum Paciente Selecionado')
            ->assertSee('Digite o nome, CPF ou Cartão SUS do paciente...')
            ->assertDontSee('Ficha Resumo do Usuário')
            ->assertDontSee('Paciente Inicial 01');
    }

    public function test_prontuario_com_parametro_paciente_carrega_prontuario_direto(): void
    {
        $paciente = Paciente::create([
            'nome' => 'Julio Cesar de Alcantara',
            'cpf' => '77712345678',
            'cns' => CnsValido::gerarDefinitivo('77712345678'),
            'data_nascimento' => '1985-04-18',
            'sexo' => 'M',
            'nome_mae' => 'Mãe Julio',
            'municipio_ibge' => '270430',
            'cid_principal' => 'G800',
            'modalidades_reabilitacao' => ['FISICA'],
            'ativo' => true,
        ]);

        Livewire::actingAs($this->terapeuta)
            ->test(ProntuarioPaciente::class, ['paciente' => $paciente->id])
            ->assertSet('pacienteId', $paciente->id)
            ->assertViewHas('pacienteSelecionado', function ($p) use ($paciente): bool {
                return $p !== null && $p->id === $paciente->id;
            })
            ->assertSee('Julio Cesar de Alcantara')
            ->assertSee('G800')
            ->assertSee('Trocar');
    }

    public function test_busca_dinamica_exige_tres_caracteres_e_encontra_por_nome(): void
    {
        $paciente = Paciente::create([
            'nome' => 'Marcio Aurelio Fonseca',
            'cpf' => '66611122233',
            'cns' => CnsValido::gerarDefinitivo('66611122233'),
            'data_nascimento' => '1992-10-10',
            'sexo' => 'M',
            'nome_mae' => 'Mãe Marcio',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        // Menos de 3 caracteres não busca
        Livewire::actingAs($this->terapeuta)
            ->test(ProntuarioPaciente::class)
            ->set('pesquisaPaciente', 'Ma')
            ->assertViewHas('pacientesResultados', [])
            ->assertSee('Digite pelo menos 3 caracteres para iniciar a pesquisa');

        // 3 ou mais caracteres busca e exibe resultados
        Livewire::actingAs($this->terapeuta)
            ->test(ProntuarioPaciente::class)
            ->set('pesquisaPaciente', 'Marcio')
            ->assertViewHas('pacientesResultados', function (array $resultados) use ($paciente): bool {
                return count($resultados) === 1
                    && $resultados[0]['id'] === $paciente->id
                    && $resultados[0]['nome'] === 'Marcio Aurelio Fonseca';
            })
            ->assertSee('Marcio Aurelio Fonseca')
            ->assertSee('Abrir PEP');
    }

    public function test_busca_dinamica_encontra_por_cpf_e_cns_normalizados(): void
    {
        $cns = CnsValido::gerarDefinitivo('55522233344');
        $paciente = Paciente::create([
            'nome' => 'Camila Vasconcelos Rios',
            'cpf' => '55522233344',
            'cns' => $cns,
            'data_nascimento' => '1999-03-25',
            'sexo' => 'F',
            'nome_mae' => 'Mãe Camila',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        // Busca com pontuação de CPF
        Livewire::actingAs($this->terapeuta)
            ->test(ProntuarioPaciente::class)
            ->set('pesquisaPaciente', '555.222')
            ->assertViewHas('pacientesResultados', function (array $resultados) use ($paciente): bool {
                return count($resultados) === 1 && $resultados[0]['id'] === $paciente->id;
            })
            ->assertSee('Camila Vasconcelos Rios');

        // Busca com trecho de CNS
        Livewire::actingAs($this->terapeuta)
            ->test(ProntuarioPaciente::class)
            ->set('pesquisaPaciente', substr($cns, 0, 8))
            ->assertViewHas('pacientesResultados', function (array $resultados) use ($paciente): bool {
                return count($resultados) === 1 && $resultados[0]['id'] === $paciente->id;
            });
    }

    public function test_selecionar_paciente_carrega_prontuario_e_limpar_reseta_estado(): void
    {
        $paciente = Paciente::create([
            'nome' => 'Luciana Medeiros Lima',
            'cpf' => '44433322211',
            'cns' => CnsValido::gerarDefinitivo('44433322211'),
            'data_nascimento' => '1987-12-01',
            'sexo' => 'F',
            'nome_mae' => 'Mãe Luciana',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        $component = Livewire::actingAs($this->terapeuta)
            ->test(ProntuarioPaciente::class)
            ->set('pesquisaPaciente', 'Luciana')
            ->call('selecionarPaciente', $paciente->id);

        $component->assertSet('pacienteId', $paciente->id)
            ->assertSet('pesquisaPaciente', '')
            ->assertSee('Luciana Medeiros Lima')
            ->assertSee('Trocar')
            ->assertDontSee('Nenhum Paciente Selecionado');

        // Ao clicar em Trocar
        $component->call('limparPacienteSelecionado')
            ->assertSet('pacienteId', null)
            ->assertSet('pesquisaPaciente', '')
            ->assertSee('Nenhum Paciente Selecionado')
            ->assertSee('Digite o nome, CPF ou Cartão SUS do paciente...');
    }

    public function test_busca_ignora_pacientes_inativos_e_excluidos_e_limita_em_20(): void
    {
        Paciente::create([
            'nome' => 'Inativo Silva Teste',
            'cpf' => '33311122201',
            'cns' => CnsValido::gerarDefinitivo('33311122201'),
            'data_nascimento' => '1990-01-01',
            'sexo' => 'M',
            'nome_mae' => 'Mãe Inativo',
            'municipio_ibge' => '270430',
            'ativo' => false,
        ]);

        for ($i = 1; $i <= 25; $i++) {
            Paciente::create([
                'nome' => sprintf('Limite Pep Silva %02d', $i),
                'cpf' => sprintf('222000000%02d', $i),
                'cns' => CnsValido::gerarDefinitivo(sprintf('222000000%02d', $i)),
                'data_nascimento' => '1991-01-01',
                'sexo' => 'M',
                'nome_mae' => 'Mãe Silva',
                'municipio_ibge' => '270430',
                'ativo' => true,
            ]);
        }

        Livewire::actingAs($this->terapeuta)
            ->test(ProntuarioPaciente::class)
            ->set('pesquisaPaciente', 'Inativo Silva')
            ->assertViewHas('pacientesResultados', [])
            ->assertSee('Nenhum paciente encontrado');

        Livewire::actingAs($this->terapeuta)
            ->test(ProntuarioPaciente::class)
            ->set('pesquisaPaciente', 'Limite Pep Silva')
            ->assertViewHas('pacientesResultados', function (array $res): bool {
                return count($res) === 20;
            });
    }
}
