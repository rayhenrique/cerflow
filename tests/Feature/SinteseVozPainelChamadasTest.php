<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\PainelChamadas;
use App\Models\Agendamento;
use App\Models\ChamadaPainel;
use App\Models\ConfiguracaoEstabelecimento;
use App\Models\Paciente;
use App\Models\Profissional;
use App\Models\Sala;
use App\Models\User;
use App\Rules\CnsValido;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class SinteseVozPainelChamadasTest extends TestCase
{
    use RefreshDatabase;

    private User $coordenador;

    private Sala $sala;

    private Paciente $paciente;

    private Profissional $profissional;

    private Agendamento $agendamento;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);

        $this->coordenador = User::factory()->create([
            'email' => 'coord.audio@cerflow.gov.br',
            'password' => Hash::make('password'),
            'ativo' => true,
        ]);
        $this->coordenador->assignRole('coordenacao');

        $this->sala = Sala::create([
            'nome' => 'Consultório 01 - Fisioterapia',
            'tipo' => 'CONSULTORIO',
            'ativo' => true,
        ]);

        $this->paciente = Paciente::create([
            'nome' => 'Carlos Eduardo da Silva',
            'cpf' => '33344455566',
            'cns' => CnsValido::gerarDefinitivo('33344455566'),
            'data_nascimento' => '1990-05-15',
            'sexo' => 'M',
        ]);

        $this->profissional = Profissional::create([
            'user_id' => $this->coordenador->id,
            'nome' => 'Dr. André Fisioterapeuta',
            'cpf' => '11122233344',
            'cns' => CnsValido::gerarDefinitivo('11122233344'),
            'registro_conselho' => 'CREFITO-1 1234-F',
            'cbo_padrao' => '223605',
            'especialidade' => 'Fisioterapia',
            'pin_assinatura_hash' => Hash::make('1234'),
            'ativo' => true,
        ]);

        $this->agendamento = Agendamento::create([
            'paciente_id' => $this->paciente->id,
            'sala_id' => $this->sala->id,
            'data_atendimento' => now()->toDateString(),
            'hora_inicio' => '08:00',
            'hora_fim' => '08:30',
            'dia_semana' => 'SEGUNDA',
            'status' => 'PRESENTE',
            'tipo_atendimento' => 'INDIVIDUAL',
        ]);
        $this->agendamento->profissionais()->attach($this->profissional->id, ['papel' => 'PRINCIPAL']);

        ConfiguracaoEstabelecimento::updateOrCreate(
            ['id' => 1],
            [
                'cnes' => '2000001',
                'codigo_ibge_padrao' => '270430',
                'painel_voz_ativa' => true,
                'painel_volume' => 80,
                'painel_tempo_destaque_segundos' => 15,
                'painel_quantidade_historico' => 5,
                'painel_modo_exibicao_nome' => 'COMPLETO',
            ]
        );
    }

    public function test_evento_nova_chamada_contem_texto_de_sintese_de_voz_completo_com_sala_e_profissional(): void
    {
        $this->actingAs($this->coordenador);

        $chamada = ChamadaPainel::create([
            'agendamento_id' => $this->agendamento->id,
            'paciente_id' => $this->paciente->id,
            'paciente_nome' => $this->paciente->nome,
            'sala_id' => $this->sala->id,
            'sala_nome' => $this->sala->nome,
            'profissional_id' => $this->profissional->id,
            'profissional_nome' => $this->profissional->nome,
            'tipo' => 'NOVA',
            'chamado_em' => now(),
            'chamado_por_id' => $this->coordenador->id,
        ]);

        $component = Livewire::test(PainelChamadas::class);

        $component->assertDispatched('nova-chamada-painel', function (string $event, array $params) use ($chamada): bool {
            $dados = $params[0] ?? $params;

            $this->assertSame($chamada->id, $dados['id']);
            $this->assertSame('Carlos Eduardo da Silva', $dados['paciente']);
            $this->assertSame($this->sala->nome, $dados['sala']);
            $this->assertSame($this->profissional->nome, $dados['profissional']);
            $this->assertSame('NOVA', $dados['tipo']);
            $this->assertTrue($dados['vozAtiva']);
            $this->assertSame(0.8, $dados['volume']);

            $esperado = "Paciente Carlos Eduardo da Silva, dirigir-se à sala {$this->sala->nome}, com {$this->profissional->nome}";
            $this->assertSame($esperado, $dados['textoVoz']);

            return true;
        });
    }

    public function test_chamada_repetida_inclui_prefixo_de_atencao_no_texto_de_voz(): void
    {
        $this->actingAs($this->coordenador);

        ChamadaPainel::create([
            'agendamento_id' => $this->agendamento->id,
            'paciente_id' => $this->paciente->id,
            'paciente_nome' => $this->paciente->nome,
            'sala_id' => $this->sala->id,
            'sala_nome' => $this->sala->nome,
            'profissional_id' => $this->profissional->id,
            'profissional_nome' => $this->profissional->nome,
            'tipo' => 'REPETICAO',
            'chamado_em' => now(),
            'chamado_por_id' => $this->coordenador->id,
        ]);

        $component = Livewire::test(PainelChamadas::class);

        $component->assertDispatched('nova-chamada-painel', function (string $event, array $params): bool {
            $dados = $params[0] ?? $params;

            $this->assertSame('REPETICAO', $dados['tipo']);
            $esperado = "Atenção, chamada repetida: Paciente Carlos Eduardo da Silva, dirigir-se à sala {$this->sala->nome}, com {$this->profissional->nome}";
            $this->assertSame($esperado, $dados['textoVoz']);

            return true;
        });
    }

    public function test_texto_de_voz_sem_profissional_quando_nao_informado(): void
    {
        $this->actingAs($this->coordenador);

        ChamadaPainel::create([
            'agendamento_id' => $this->agendamento->id,
            'paciente_id' => $this->paciente->id,
            'paciente_nome' => $this->paciente->nome,
            'sala_id' => $this->sala->id,
            'sala_nome' => $this->sala->nome,
            'profissional_id' => null,
            'profissional_nome' => null,
            'tipo' => 'NOVA',
            'chamado_em' => now(),
            'chamado_por_id' => $this->coordenador->id,
        ]);

        $component = Livewire::test(PainelChamadas::class);

        $component->assertDispatched('nova-chamada-painel', function (string $event, array $params): bool {
            $dados = $params[0] ?? $params;

            $esperado = "Paciente Carlos Eduardo da Silva, dirigir-se à sala {$this->sala->nome}";
            $this->assertSame($esperado, $dados['textoVoz']);
            $this->assertStringNotContainsString(', com', $dados['textoVoz']);

            return true;
        });
    }

    public function test_configuracao_de_voz_e_volume_sao_respeitadas_no_payload(): void
    {
        $this->actingAs($this->coordenador);

        $config = ConfiguracaoEstabelecimento::atual();
        $config->update([
            'painel_voz_ativa' => false,
            'painel_volume' => 60,
        ]);

        ChamadaPainel::create([
            'agendamento_id' => $this->agendamento->id,
            'paciente_id' => $this->paciente->id,
            'paciente_nome' => $this->paciente->nome,
            'sala_id' => $this->sala->id,
            'sala_nome' => $this->sala->nome,
            'tipo' => 'NOVA',
            'chamado_em' => now(),
            'chamado_por_id' => $this->coordenador->id,
        ]);

        $component = Livewire::test(PainelChamadas::class);

        $component->assertDispatched('nova-chamada-painel', function (string $event, array $params): bool {
            $dados = $params[0] ?? $params;

            $this->assertFalse($dados['vozAtiva']);
            $this->assertSame(0.6, $dados['volume']);

            return true;
        });
    }

    public function test_view_renderiza_frase_oficial_de_teste_e_controles_tecnicos(): void
    {
        $this->actingAs($this->coordenador);

        $component = Livewire::test(PainelChamadas::class);

        $component->assertSee('Testar Voz');
        $component->assertSee('Painel Técnico TV');
        $component->assertSee('Teste de áudio do CER Digital. A chamada por voz está funcionando.');
        $component->assertSee('STATUS DE COMPATIBILIDADE TTS');
        $component->assertSee('Web Speech API (Síntese Nativa)');
        $component->assertSee('Motor Local CER Digital (Servidor)');
        $component->assertSee('Destravar Fila');
        $component->assertSee('Recarregar Vozes');
        $component->assertSee('1. Testar Chime');
        $component->assertSee('2. Testar Voz pt-BR (Nativa)');
        $component->assertSee('3. Testar Voz do Sistema');
        $component->assertSee('4. Testar Áudio Local (Servidor)');
    }

    public function test_view_possui_identificacao_de_titanos_fila_fifo_e_motor_local(): void
    {
        $bladePath = resource_path('views/livewire/painel-chamadas.blade.php');
        $conteudo = file_get_contents($bladePath);

        $this->assertNotFalse($conteudo);

        // Identificação específica do ambiente Philips TitanOS / WhaleTV
        $this->assertStringContainsString('TitanOS', $conteudo);
        $this->assertStringContainsString('WhaleTV', $conteudo);
        $this->assertStringContainsString('PU[GK]\d{4}', $conteudo);

        // Fila controlada de reprodução FIFO (evita sobreposição e cancelamentos excessivos)
        $this->assertStringContainsString('filaChamadas', $conteudo);
        $this->assertStringContainsString('enfileirarChamada', $conteudo);
        $this->assertStringContainsString('processarFila', $conteudo);

        // Sincronização por evento real de término do Chime (elimina delay arbitrário)
        $this->assertStringContainsString('osc2.onended', $conteudo);

        // Motor alternativo local do CER Digital (autohospedado e compatível com LGPD)
        $this->assertStringContainsString('reproduzirAudioLocal', $conteudo);
        $this->assertStringContainsString('/painel-chamadas/audio/fala', $conteudo);

        // Normalização e seleção exclusiva de pt-BR (não seleciona en-US)
        $this->assertStringContainsString('normalizarLang', $conteudo);
        $this->assertStringContainsString('pt-br', $conteudo);

        // Ativação universal por controle remoto da TV e teclado
        $this->assertStringContainsString('keydown', $conteudo);

        // Proteção contra Garbage Collection prematuro
        $this->assertStringContainsString('window._cerActiveUtterance', $conteudo);
        $this->assertStringContainsString('this.activeUtterance', $conteudo);
    }
}
