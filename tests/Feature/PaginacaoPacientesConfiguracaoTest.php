<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\ConfiguracoesSistema;
use App\Models\Paciente;
use App\Models\User;
use App\Rules\CnsValido;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use Tests\TestCase;

class PaginacaoPacientesConfiguracaoTest extends TestCase
{
    use RefreshDatabase;

    private User $coordenador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->coordenador = User::factory()->create(['ativo' => true]);
        $this->coordenador->assignRole('coordenacao');
    }

    public function test_pacientes_sao_paginados_por_padrao_em_25_registros(): void
    {
        for ($i = 1; $i <= 35; $i++) {
            Paciente::create([
                'nome' => sprintf('Paciente Paginado %02d', $i),
                'cpf' => sprintf('321000000%02d', $i),
                'cns' => CnsValido::gerarDefinitivo(sprintf('321000000%02d', $i)),
                'data_nascimento' => '1990-01-01',
                'sexo' => 'M',
                'nome_mae' => 'Mãe Teste',
                'municipio_ibge' => '270430',
                'ativo' => true,
            ]);
        }

        Livewire::actingAs($this->coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'pacientes'])
            ->assertViewHas('pacientes', function ($pacientes): bool {
                return $pacientes instanceof LengthAwarePaginator
                    && $pacientes->total() === 35
                    && $pacientes->perPage() === 25
                    && $pacientes->count() === 25
                    && $pacientes->currentPage() === 1;
            })
            ->assertSee('Exibindo 1 a 25 de 35 pacientes')
            ->assertSee('Por página:');
    }

    public function test_navegacao_para_proxima_pagina_exibe_registros_restantes(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            Paciente::create([
                'nome' => sprintf('Paciente Paginado %02d', $i),
                'cpf' => sprintf('421000000%02d', $i),
                'cns' => CnsValido::gerarDefinitivo(sprintf('421000000%02d', $i)),
                'data_nascimento' => '1990-01-01',
                'sexo' => 'M',
                'nome_mae' => 'Mãe Teste',
                'municipio_ibge' => '270430',
                'ativo' => true,
            ]);
        }

        Livewire::actingAs($this->coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'pacientes'])
            ->call('gotoPage', 2)
            ->assertViewHas('pacientes', function ($pacientes): bool {
                return $pacientes instanceof LengthAwarePaginator
                    && $pacientes->total() === 30
                    && $pacientes->currentPage() === 2
                    && $pacientes->count() === 5;
            })
            ->assertSee('Exibindo 26 a 30 de 30 pacientes');
    }

    public function test_alterar_quantidade_por_pagina_reinicia_e_ajusta_exibicao(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            Paciente::create([
                'nome' => sprintf('Paciente Teste %02d', $i),
                'cpf' => sprintf('521000000%02d', $i),
                'cns' => CnsValido::gerarDefinitivo(sprintf('521000000%02d', $i)),
                'data_nascimento' => '1990-01-01',
                'sexo' => 'M',
                'nome_mae' => 'Mãe Teste',
                'municipio_ibge' => '270430',
                'ativo' => true,
            ]);
        }

        Livewire::actingAs($this->coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'pacientes'])
            ->set('porPaginaPaciente', 15)
            ->assertViewHas('pacientes', function ($pacientes): bool {
                return $pacientes instanceof LengthAwarePaginator
                    && $pacientes->perPage() === 15
                    && $pacientes->count() === 15
                    && $pacientes->currentPage() === 1;
            })
            ->assertSee('Exibindo 1 a 15 de 20 pacientes');
    }

    public function test_busca_com_filtro_reinicia_paginacao_e_filtra_corretamente(): void
    {
        for ($i = 1; $i <= 30; $i++) {
            Paciente::create([
                'nome' => sprintf('Paciente Comum %02d', $i),
                'cpf' => sprintf('621000000%02d', $i),
                'cns' => CnsValido::gerarDefinitivo(sprintf('621000000%02d', $i)),
                'data_nascimento' => '1990-01-01',
                'sexo' => 'M',
                'nome_mae' => 'Mãe Teste',
                'municipio_ibge' => '270430',
                'ativo' => true,
            ]);
        }

        $especial = Paciente::create([
            'nome' => 'Zacarias Único Especial',
            'cpf' => '62199999999',
            'cns' => CnsValido::gerarDefinitivo('62199999999'),
            'data_nascimento' => '1980-05-12',
            'sexo' => 'M',
            'nome_mae' => 'Mãe Especial',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        Livewire::actingAs($this->coordenador)
            ->test(ConfiguracoesSistema::class, ['secao' => 'pacientes'])
            ->call('gotoPage', 2)
            ->set('buscaPaciente', 'Zacarias')
            ->assertViewHas('pacientes', function ($pacientes) use ($especial): bool {
                return $pacientes instanceof LengthAwarePaginator
                    && $pacientes->total() === 1
                    && $pacientes->currentPage() === 1
                    && $pacientes->first()->id === $especial->id;
            })
            ->assertSee('Zacarias Único Especial')
            ->assertSee('filtrados por');
    }

    public function test_recepcao_tem_acesso_a_listagem_paginada_de_pacientes(): void
    {
        $recepcao = User::factory()->create(['ativo' => true]);
        $recepcao->assignRole('recepcao');

        Paciente::create([
            'nome' => 'Paciente Recepcao Teste',
            'cpf' => '72100000001',
            'cns' => CnsValido::gerarDefinitivo('72100000001'),
            'data_nascimento' => '1992-06-15',
            'sexo' => 'F',
            'nome_mae' => 'Mãe Recepcao',
            'municipio_ibge' => '270430',
            'ativo' => true,
        ]);

        $this->actingAs($recepcao)
            ->get('/configuracoes/pacientes')
            ->assertOk()
            ->assertSee('Listagem de Pacientes Cadastrados')
            ->assertSee('Paciente Recepcao Teste');
    }
}
