<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Jobs\SincronizarEsusPecJob;
use App\Models\ConfiguracaoEstabelecimento;
use App\Models\ConfiguracaoEsusPec;
use App\Models\DispositivoTv;
use App\Models\HistoricoSincronizacaoEsus;
use App\Models\LogAuditoria;
use App\Models\Paciente;
use App\Models\ProcedimentoSigtap;
use App\Models\Profissional;
use App\Models\Sala;
use App\Models\SolicitacaoPareamentoTv;
use App\Models\User;
use App\Rules\CnsValido;
use App\Services\EsusPecIntegrationService;
use App\Services\TvPairingService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Configurações e Cadastros Base — CER Digital')]
class ConfiguracoesSistema extends Component
{
    use WithFileUploads;
    use WithPagination;

    public ?TemporaryUploadedFile $logoEstabelecimento = null;

    public bool $removerLogoEstabelecimento = false;

    public string $estabelecimento_ibge = '270430';

    public string $estabelecimento_cnes = '2000001';

    // --- Configurações do Painel de Chamadas para TV ---
    public bool $painel_voz_ativa = true;

    public int $painel_volume = 80;

    public int $painel_tempo_destaque_segundos = 15;

    public int $painel_quantidade_historico = 5;

    public string $painel_modo_exibicao_nome = 'COMPLETO';

    public ?string $painel_token_acesso = null;

    public bool $painel_permitir_token_legado = true;

    // --- Gestão e Pareamento de Smart TVs (Coordenação) ---
    public bool $modalAutorizarTvAberto = false;

    public bool $modalRenomearTvAberto = false;

    public bool $modalReautorizarTvAberto = false;

    public string $tv_codigo_ativacao = '';

    public string $tv_nome_dispositivo = '';

    public ?int $tv_renomear_id = null;

    public string $tv_novo_nome = '';

    public ?int $tvReautorizarId = null;

    public string $tvNomeReautorizacao = '';

    public string $tvCodigoReautorizacao = '';

    public ?array $solicitacaoPrevia = null;

    public string $abaAtiva = 'pacientes';

    // --- Filtros de Busca ---
    public string $buscaPaciente = '';

    public int $porPaginaPaciente = 25;

    public string $buscaSala = '';

    public string $buscaProfissional = '';

    public string $buscaSigtap = '';

    public string $filtroAcaoAuditoria = '';

    // --- Controle de Modais ---
    public bool $modalSalaAberto = false;

    public bool $modalPacienteAberto = false;

    public bool $modalProfissionalAberto = false;

    public bool $modalSigtapAberto = false;

    public bool $modalExclusaoAberto = false;

    public string $exclusaoEntidade = '';

    public ?int $exclusaoId = null;

    public string $exclusaoDescricao = '';

    public ?int $logDetalhesId = null;

    // --- Formulário de Sala ---
    public ?int $salaEditandoId = null;

    public string $sala_nome = '';

    public string $sala_tipo = 'CONSULTORIO';

    public bool $sala_ativo = true;

    // --- Formulário de Paciente ---
    public ?int $pacienteEditandoId = null;

    public string $pac_nome = '';

    public string $pac_cns = '';

    public string $pac_cpf = '';

    public string $pac_data_nascimento = '';

    public string $pac_sexo = 'M';

    public string $pac_nome_mae = '';

    public string $pac_municipio_ibge = '270430';

    public string $pac_ubs_referencia = '';

    public string $pac_nome_acs = '';

    public string $pac_telefone_contato = '';

    public string $pac_telefone_secundario = '';

    public string $pac_endereco = '';

    public string $pac_bairro = '';

    public string $pac_cid_principal = '';

    public string $pac_cid_secundario = '';

    /** @var array<int, string> */
    public array $pac_modalidades = [];

    // --- Formulário de Usuário & Profissional ---
    public ?int $usuarioEditandoId = null;

    public string $usr_name = '';

    public string $usr_email = '';

    public string $usr_password = '';

    public string $usr_role = 'terapeuta';

    public bool $vincular_profissional = true;

    public string $prof_cpf = '';

    public string $prof_cns = '';

    public string $prof_conselho = '';

    public string $prof_cbo = '223605';

    public string $prof_especialidade = 'Fisioterapia';

    public string $prof_pin = '';

    // --- Formulário SIGTAP ---
    public ?int $sigtapEditandoId = null;

    public string $sig_codigo = '';

    public string $sig_descricao = '';

    public string $sig_cbos_csv = '223605, 223810, 223905, 251510';

    public int $sig_idade_minima = 0;

    public int $sig_idade_maxima = 130;

    public string $sig_sexo_permitido = 'AMBOS';

    // --- Formulário Meu Perfil (Dados, Senha e PIN) ---
    public string $perfil_nome = '';

    public string $perfil_email = '';

    public string $nova_senha = '';

    public string $novo_pin = '';

    // --- Integração e-SUS APS PEC (Coordenação) ---
    public bool $esus_ativo = false;

    public string $esus_host = 'localhost';

    public int $esus_porta = 5433;

    public string $esus_banco = 'esus';

    public string $esus_usuario = 'esus_leitura';

    public string $esus_senha = '';

    public bool $esus_tem_senha = false;

    public string $esus_endereco_web = 'http://189.89.61.50:8181';

    public string $esus_ultimo_status = 'NAO_TESTADO';

    public ?string $esus_mensagem_status = null;

    public ?string $esus_tipo_banco = null;

    public ?string $esus_tabela_origem = null;

    public int $esus_total_tabelas = 0;

    /** @var array<string, mixed>|null */
    public ?array $esus_diagnostico = null;

    public ?string $esus_ultima_conexao = null;

    public ?string $esus_ultima_sincronizacao = null;

    public ?string $esus_ultima_sincronizacao_status = null;

    public string $esus_modo_sincronizacao = 'AUTO';

    public int $esus_tamanho_lote = 500;

    public int $esus_janela_seguranca_minutos = 1440;

    public ?string $esus_ultimo_checkpoint_em = null;

    public ?int $esus_ultimo_checkpoint_id = null;

    public ?string $esus_coluna_atualizacao = null;

    public ?string $esus_coluna_sequencial = null;

    // --- Progresso em Tempo Real da Sincronização e-SUS PEC ---
    public bool $sincronizandoEsus = false;

    public ?int $sincronizacaoAtivaId = null;

    public string $progressoEtapa = '';

    public int $progressoProcessados = 0;

    public int $progressoTotal = 0;

    public int $progressoPercentual = 0;

    /** @var array<string, int> */
    public array $progressoContadores = [];

    public function mount(?string $secao = null): void
    {
        $user = auth()->user();
        abort_if($user === null, 403);

        $this->perfil_nome = $user->name;
        $this->perfil_email = $user->email;

        if ($user->hasRole('coordenacao')) {
            $configuracao = ConfiguracaoEstabelecimento::atual();
            $this->estabelecimento_ibge = $configuracao->codigo_ibge_padrao;
            $this->estabelecimento_cnes = $configuracao->cnes;
            $this->painel_voz_ativa = (bool) ($configuracao->painel_voz_ativa ?? true);
            $this->painel_volume = (int) ($configuracao->painel_volume ?? 80);
            $this->painel_tempo_destaque_segundos = (int) ($configuracao->painel_tempo_destaque_segundos ?? 15);
            $this->painel_quantidade_historico = (int) ($configuracao->painel_quantidade_historico ?? 5);
            $this->painel_modo_exibicao_nome = (string) ($configuracao->painel_modo_exibicao_nome ?? 'COMPLETO');
            $this->painel_token_acesso = $configuracao->painel_token_acesso;
            $this->painel_permitir_token_legado = (bool) ($configuracao->painel_permitir_token_legado ?? true);
            $this->carregarConfiguracaoEsus();
        }

        $permitidas = $this->abasPermitidas();

        $editarPacienteId = (int) request()->query('editar_paciente');
        if ($editarPacienteId > 0 && in_array('pacientes', $permitidas, true) && $user->hasAnyRole(['recepcao', 'coordenacao'])) {
            $this->abaAtiva = 'pacientes';
            $this->editarPaciente($editarPacienteId);

            return;
        }

        if ($secao !== null) {
            abort_unless(in_array($secao, $permitidas, true), 403);
            $this->abaAtiva = $secao;

            return;
        }

        if ($user->hasRole('coordenacao')) {
            $this->abaAtiva = 'salas';
        } elseif ($user->hasRole('recepcao')) {
            $this->abaAtiva = 'pacientes';
        } elseif ($user->hasRole('faturamento')) {
            $this->abaAtiva = 'sigtap';
        } else {
            $this->abaAtiva = 'perfil';
        }
    }

    public function updatedBuscaPaciente(): void
    {
        $this->resetPage();
    }

    public function updatedPorPaginaPaciente(): void
    {
        $this->resetPage();
    }

    public function selecionarAba(string $aba): void
    {
        $permitidas = $this->abasPermitidas();
        if (in_array($aba, $permitidas, true)) {
            $this->resetValidation();
            $this->abaAtiva = $aba;
            if ($aba === 'pacientes') {
                $this->resetPage();
            }
        }
    }

    /**
     * @return array<int, string>
     */
    public function abasPermitidas(): array
    {
        $user = auth()->user();
        if ($user === null) {
            return [];
        }

        if ($user->hasRole('coordenacao')) {
            return ['pacientes', 'salas', 'profissionais', 'sigtap', 'auditoria', 'estabelecimento', 'esus', 'chamadas', 'perfil'];
        }

        $abas = ['perfil'];
        if ($user->hasRole('recepcao')) {
            array_unshift($abas, 'pacientes');
        }
        if ($user->hasRole('faturamento')) {
            array_unshift($abas, 'sigtap');
        }

        return array_values(array_unique($abas));
    }

    // =========================================================================
    // IDENTIFICAÇÃO INSTITUCIONAL (Coordenação)
    // =========================================================================
    public function salvarIdentificacaoEstabelecimento(): void
    {
        abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);

        $dados = $this->validate([
            'estabelecimento_ibge' => ['required', 'string', 'digits:6'],
            'estabelecimento_cnes' => ['required', 'string', 'digits:7'],
            'logoEstabelecimento' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'removerLogoEstabelecimento' => ['boolean'],
        ]);

        $configuracao = ConfiguracaoEstabelecimento::query()->find(1);
        if ($configuracao === null) {
            $configuracao = new ConfiguracaoEstabelecimento;
            $configuracao->id = 1;
        }

        $antes = [
            'codigo_ibge_padrao' => $configuracao->codigo_ibge_padrao ?? '270430',
            'cnes' => $configuracao->cnes ?? '2000001',
            'logo_path' => $configuracao->logo_path,
        ];
        $logoAnterior = $configuracao->logo_path;
        $novoCaminho = $logoAnterior;

        if ($this->logoEstabelecimento !== null) {
            $novoCaminho = $this->logoEstabelecimento->store('identidade-estabelecimento', 'public');
        } elseif ($this->removerLogoEstabelecimento) {
            $novoCaminho = null;
        }

        $configuracao->codigo_ibge_padrao = $dados['estabelecimento_ibge'];
        $configuracao->cnes = $dados['estabelecimento_cnes'];
        $configuracao->logo_path = $novoCaminho;
        $configuracao->save();

        if ($logoAnterior !== null && $logoAnterior !== $novoCaminho) {
            Storage::disk('public')->delete($logoAnterior);
        }

        LogAuditoria::create([
            'user_id' => auth()->id(),
            'acao' => 'ATUALIZACAO_ESTABELECIMENTO',
            'tabela_afetada' => 'configuracoes_estabelecimento',
            'registro_id' => $configuracao->id,
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'detalhes' => [
                'antes' => $antes,
                'depois' => [
                    'codigo_ibge_padrao' => $configuracao->codigo_ibge_padrao,
                    'cnes' => $configuracao->cnes,
                    'logo_path' => $configuracao->logo_path,
                ],
            ],
            'created_at' => now(),
        ]);

        $this->logoEstabelecimento = null;
        $this->removerLogoEstabelecimento = false;
        $this->resetValidation();

        $this->dispatch('toast', tipo: 'sucesso', titulo: 'Identificação atualizada', mensagem: 'Os dados institucionais foram salvos com sucesso.');
    }

    // =========================================================================
    // PAINEL DE CHAMADAS PARA TV (Coordenação)
    // =========================================================================
    public function salvarConfiguracaoPainelChamadas(): void
    {
        abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);

        $this->validate([
            'painel_voz_ativa' => ['required', 'boolean'],
            'painel_volume' => ['required', 'integer', 'min:0', 'max:100'],
            'painel_tempo_destaque_segundos' => ['required', 'integer', 'min:5', 'max:120'],
            'painel_quantidade_historico' => ['required', 'integer', 'min:1', 'max:15'],
            'painel_modo_exibicao_nome' => ['required', Rule::in(['COMPLETO', 'PRIMEIRO_NOME', 'PRIMEIRO_E_ULTIMO', 'ABREVIADO'])],
            'painel_permitir_token_legado' => ['required', 'boolean'],
        ]);

        $configuracao = ConfiguracaoEstabelecimento::atual();
        $configuracao->update([
            'painel_voz_ativa' => $this->painel_voz_ativa,
            'painel_volume' => $this->painel_volume,
            'painel_tempo_destaque_segundos' => $this->painel_tempo_destaque_segundos,
            'painel_quantidade_historico' => $this->painel_quantidade_historico,
            'painel_modo_exibicao_nome' => $this->painel_modo_exibicao_nome,
            'painel_permitir_token_legado' => $this->painel_permitir_token_legado,
        ]);

        Cache::forget('configuracao_estabelecimento_painel');

        LogAuditoria::create([
            'user_id' => auth()->id(),
            'acao' => 'ATUALIZACAO_CONFIG_PAINEL_CHAMADAS',
            'tabela_afetada' => 'configuracoes_estabelecimento',
            'registro_id' => $configuracao->id,
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'detalhes' => [
                'painel_voz_ativa' => $this->painel_voz_ativa,
                'painel_volume' => $this->painel_volume,
                'painel_tempo_destaque_segundos' => $this->painel_tempo_destaque_segundos,
                'painel_quantidade_historico' => $this->painel_quantidade_historico,
                'painel_modo_exibicao_nome' => $this->painel_modo_exibicao_nome,
                'painel_permitir_token_legado' => $this->painel_permitir_token_legado,
            ],
            'created_at' => now(),
        ]);

        $this->dispatch(
            'toast',
            tipo: 'sucesso',
            titulo: 'Configurações da TV Atualizadas',
            mensagem: 'Os parâmetros de voz, volume e privacidade do painel de chamadas foram salvos.'
        );
    }

    public function regenerarTokenTv(): void
    {
        abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);

        $configuracao = ConfiguracaoEstabelecimento::atual();
        $novoToken = $configuracao->gerarTokenTv();
        $this->painel_token_acesso = $novoToken;
        Cache::forget('configuracao_estabelecimento_painel');

        LogAuditoria::create([
            'user_id' => auth()->id(),
            'acao' => 'REGENERACAO_TOKEN_TV',
            'tabela_afetada' => 'configuracoes_estabelecimento',
            'registro_id' => $configuracao->id,
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'detalhes' => [
                'motivo' => 'Revogação do token anterior e emissão de nova credencial para a TV.',
            ],
            'created_at' => now(),
        ]);

        $this->dispatch(
            'toast',
            tipo: 'alerta',
            titulo: 'Novo Token da TV Gerado',
            mensagem: 'O link transitório foi renovado. Para máxima segurança, prefira parear as TVs por código de ativação.'
        );
    }

    // --- Dispositivos de TV Autorizados (Pareamento Individual) ---
    public function abrirModalAutorizarTv(?string $codigo = null): void
    {
        abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);

        $this->resetValidation();
        $this->tv_codigo_ativacao = $codigo ?? '';
        $this->tv_nome_dispositivo = '';
        $this->solicitacaoPrevia = null;

        if (! empty($codigo)) {
            $this->consultarCodigoTv();
        }

        $this->modalAutorizarTvAberto = true;
    }

    public function fecharModalAutorizarTv(): void
    {
        $this->modalAutorizarTvAberto = false;
        $this->solicitacaoPrevia = null;
        $this->resetValidation();
    }

    public function consultarCodigoTv(): void
    {
        $codNormalizado = SolicitacaoPareamentoTv::normalizarCodigo($this->tv_codigo_ativacao);
        $sol = SolicitacaoPareamentoTv::query()->validas()->where('codigo', $codNormalizado)->first();

        if ($sol !== null) {
            $this->solicitacaoPrevia = [
                'codigo' => $sol->codigo,
                'expira_em' => $sol->expira_em->format('H:i'),
                'ip' => $sol->ip_origem ?? 'Desconhecido',
                'user_agent' => $sol->user_agent ? Str::limit($sol->user_agent, 45) : 'Navegador Web TV',
            ];

            if ($this->tv_nome_dispositivo === '') {
                $this->tv_nome_dispositivo = "Smart TV ({$sol->codigo})";
            }
        } else {
            $this->solicitacaoPrevia = null;
        }
    }

    public function autorizarTv(TvPairingService $service): void
    {
        abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);

        $this->validate([
            'tv_codigo_ativacao' => ['required', 'string', 'min:4', 'max:20'],
            'tv_nome_dispositivo' => ['required', 'string', 'max:100'],
        ]);

        try {
            $dispositivo = $service->autorizarCodigo(
                $this->tv_codigo_ativacao,
                $this->tv_nome_dispositivo,
                auth()->user(),
                request()->ip(),
                request()->userAgent()
            );

            $this->modalAutorizarTvAberto = false;
            $this->solicitacaoPrevia = null;
            $this->tv_codigo_ativacao = '';
            $this->tv_nome_dispositivo = '';

            $this->dispatch(
                'toast',
                tipo: 'sucesso',
                titulo: 'Smart TV Autorizada',
                mensagem: "O televisor '{$dispositivo->nome}' foi autorizado com sucesso e já está conectado ao painel."
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->addError('tv_codigo_ativacao', $e->getMessage());
        }
    }

    public function abrirModalRenomearTv(int $id): void
    {
        abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);

        $this->resetValidation();
        $disp = DispositivoTv::findOrFail($id);
        $this->tv_renomear_id = $disp->id;
        $this->tv_novo_nome = $disp->nome;
        $this->modalRenomearTvAberto = true;
    }

    public function fecharModalRenomearTv(): void
    {
        $this->modalRenomearTvAberto = false;
        $this->resetValidation();
    }

    public function renomearTv(TvPairingService $service): void
    {
        abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);

        $this->validate([
            'tv_novo_nome' => ['required', 'string', 'max:100'],
        ]);

        if ($this->tv_renomear_id === null) {
            return;
        }

        $disp = DispositivoTv::findOrFail($this->tv_renomear_id);
        $service->renomearDispositivo($disp, $this->tv_novo_nome, auth()->user());

        $this->modalRenomearTvAberto = false;

        $this->dispatch(
            'toast',
            tipo: 'sucesso',
            titulo: 'Dispositivo Renomeado',
            mensagem: "O televisor foi renomeado para '{$disp->nome}'."
        );
    }

    public function revogarTv(int $id, TvPairingService $service): void
    {
        abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);

        $disp = DispositivoTv::findOrFail($id);
        $service->revogarDispositivo($disp, auth()->user());

        $this->dispatch(
            'toast',
            tipo: 'alerta',
            titulo: 'Dispositivo Revogado',
            mensagem: "O acesso da TV '{$disp->nome}' foi revogado imediatamente."
        );
    }

    public function abrirModalReautorizarTv(int $id): void
    {
        abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);

        $disp = DispositivoTv::findOrFail($id);
        $this->tvReautorizarId = $disp->id;
        $this->tvNomeReautorizacao = $disp->nome;
        $this->tvCodigoReautorizacao = '';
        $this->resetErrorBag(['tvCodigoReautorizacao']);
        $this->modalReautorizarTvAberto = true;
    }

    public function fecharModalReautorizarTv(): void
    {
        $this->modalReautorizarTvAberto = false;
        $this->tvReautorizarId = null;
        $this->tvCodigoReautorizacao = '';
        $this->resetErrorBag(['tvCodigoReautorizacao']);
    }

    public function reautorizarTv(int $id): void
    {
        $this->abrirModalReautorizarTv($id);
    }

    public function reautorizarTvConfirmado(TvPairingService $service): void
    {
        abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);

        $this->validate([
            'tvCodigoReautorizacao' => ['required', 'string', 'min:6', 'max:12'],
        ], [
            'tvCodigoReautorizacao.required' => 'Informe o novo código de ativação exibido na TV.',
            'tvCodigoReautorizacao.min' => 'O código de ativação deve conter ao menos 6 caracteres.',
        ]);

        if ($this->tvReautorizarId === null) {
            return;
        }

        $disp = DispositivoTv::findOrFail($this->tvReautorizarId);

        try {
            $service->reautorizarDispositivo(
                $disp,
                $this->tvCodigoReautorizacao,
                auth()->user(),
                request()->ip(),
                request()->userAgent()
            );

            $this->fecharModalReautorizarTv();

            $this->dispatch(
                'toast',
                tipo: 'sucesso',
                titulo: 'Dispositivo Reautorizado',
                mensagem: "A TV '{$disp->nome}' foi reconectada e reautorizada com uma nova credencial segura."
            );
        } catch (ValidationException $e) {
            $this->addError('tvCodigoReautorizacao', $e->validator->errors()->first() ?: 'Código de ativação inválido.');
        }
    }

    public function excluirTv(int $id, TvPairingService $service): void
    {
        abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);

        $disp = DispositivoTv::findOrFail($id);
        $service->excluirDispositivo($disp, auth()->user());

        $this->dispatch(
            'toast',
            tipo: 'info',
            titulo: 'Dispositivo Removido',
            mensagem: 'O registro do televisor foi excluído do sistema.'
        );
    }

    // =========================================================================
    // INTEGRAÇÃO e-SUS APS PEC (Coordenação)
    // =========================================================================
    public function carregarConfiguracaoEsus(): void
    {
        $config = ConfiguracaoEsusPec::atual();
        $this->esus_ativo = (bool) $config->ativo;
        $this->esus_host = (string) ($config->host ?: 'localhost');
        $this->esus_porta = (int) ($config->porta ?: 5433);
        $this->esus_banco = (string) ($config->banco ?: 'esus');
        $this->esus_usuario = (string) ($config->usuario ?: 'esus_leitura');
        $this->esus_senha = '';
        $this->esus_tem_senha = $config->temSenha();
        $this->esus_endereco_web = (string) ($config->endereco_web_pec ?: 'http://189.89.61.50:8181');
        $this->esus_ultimo_status = (string) ($config->ultimo_status_conexao ?: 'NAO_TESTADO');
        $this->esus_mensagem_status = $config->mensagem_status_conexao;
        $this->esus_tipo_banco = $config->tipo_banco_detectado;
        $this->esus_tabela_origem = $config->tabela_origem_detectada;
        $this->esus_total_tabelas = (int) ($config->total_tabelas_descobertas ?: 0);
        $this->esus_diagnostico = is_array($config->detalhes_schema) ? $config->detalhes_schema : null;
        $this->esus_ultima_conexao = $config->ultima_conexao_em?->format('d/m/Y H:i:s');
        $this->esus_ultima_sincronizacao = $config->ultima_sincronizacao_em?->format('d/m/Y H:i:s');
        $this->esus_ultima_sincronizacao_status = $config->ultima_sincronizacao_status;
        $this->esus_modo_sincronizacao = (string) ($config->modo_sincronizacao ?: 'AUTO');
        $this->esus_tamanho_lote = (int) ($config->tamanho_lote ?: 500);
        $this->esus_janela_seguranca_minutos = (int) ($config->janela_seguranca_minutos ?: 1440);
        $this->esus_ultimo_checkpoint_em = $config->ultimo_checkpoint_em?->format('d/m/Y H:i:s');
        $this->esus_ultimo_checkpoint_id = $config->ultimo_checkpoint_id;
        $this->esus_coluna_atualizacao = $config->coluna_atualizacao_detectada;
        $this->esus_coluna_sequencial = $config->coluna_sequencial_detectada;

        $emAndamento = HistoricoSincronizacaoEsus::where('status', 'EM_ANDAMENTO')->latest('iniciado_em')->first();
        if ($emAndamento) {
            $this->sincronizandoEsus = true;
            $this->sincronizacaoAtivaId = $emAndamento->id;
            $this->verificarProgressoSincronizacao();
        }
    }

    public function salvarConfiguracaoEsus(): void
    {
        abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);

        $dados = $this->validate([
            'esus_ativo' => ['boolean'],
            'esus_host' => ['required', 'string', 'max:255'],
            'esus_porta' => ['required', 'integer', 'between:1,65535'],
            'esus_banco' => ['required', 'string', 'max:100'],
            'esus_usuario' => ['required', 'string', 'max:100'],
            'esus_senha' => ['nullable', 'string', 'max:255'],
            'esus_endereco_web' => ['nullable', 'url', 'max:255'],
            'esus_modo_sincronizacao' => ['required', 'string', 'in:AUTO,INCREMENTAL,COMPLETA'],
            'esus_tamanho_lote' => ['required', 'integer', 'between:100,2000'],
        ]);

        $config = ConfiguracaoEsusPec::atual();
        $config->ativo = (bool) $dados['esus_ativo'];
        $config->host = trim($dados['esus_host']);
        $config->porta = (int) $dados['esus_porta'];
        $config->banco = trim($dados['esus_banco']);
        $config->usuario = trim($dados['esus_usuario']);
        $config->endereco_web_pec = ! empty($dados['esus_endereco_web']) ? trim((string) $dados['esus_endereco_web']) : 'http://189.89.61.50:8181';
        $config->modo_sincronizacao = $dados['esus_modo_sincronizacao'];
        $config->tamanho_lote = (int) $dados['esus_tamanho_lote'];

        // Atualiza a senha apenas se informada
        if ($this->esus_senha !== '') {
            $config->senha = $this->esus_senha;
        }

        $config->save();

        LogAuditoria::create([
            'user_id' => auth()->id(),
            'acao' => 'ATUALIZACAO_CONFIG_ESUS',
            'tabela_afetada' => 'configuracoes_esus_pec',
            'registro_id' => $config->id,
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'detalhes' => [
                'ativo' => $config->ativo,
                'host' => $config->host,
                'porta' => $config->porta,
                'banco' => $config->banco,
                'usuario' => $config->usuario,
                'senha_atualizada' => $this->esus_senha !== '',
            ],
            'created_at' => now(),
        ]);

        $this->carregarConfiguracaoEsus();
        $this->resetValidation();

        $this->dispatch('toast', tipo: 'sucesso', titulo: 'Parâmetros e-SUS salvos', mensagem: 'As configurações de conexão foram armazenadas com criptografia segura.');
    }

    public function testarConexaoEsus(EsusPecIntegrationService $service): void
    {
        abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);

        $this->validate([
            'esus_host' => ['required', 'string', 'max:255'],
            'esus_porta' => ['required', 'integer', 'between:1,65535'],
            'esus_banco' => ['required', 'string', 'max:100'],
            'esus_usuario' => ['required', 'string', 'max:100'],
        ]);

        $config = ConfiguracaoEsusPec::atual();
        $config->host = trim($this->esus_host);
        $config->porta = (int) $this->esus_porta;
        $config->banco = trim($this->esus_banco);
        $config->usuario = trim($this->esus_usuario);
        if ($this->esus_senha !== '') {
            $config->senha = $this->esus_senha;
        }
        $config->save();

        $resultado = $service->testarConexao($config);
        $this->carregarConfiguracaoEsus();

        if ($resultado['sucesso']) {
            $this->dispatch('toast', tipo: 'sucesso', titulo: 'Conexão e-SUS validada', mensagem: $resultado['mensagem']);
        } else {
            $this->dispatch('toast', tipo: 'erro', titulo: 'Falha na conexão PostgreSQL', mensagem: $resultado['mensagem']);
        }
    }

    public function sincronizarEsusAgora(EsusPecIntegrationService $service, ?string $forcarModo = null): void
    {
        abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);

        $config = ConfiguracaoEsusPec::atual();
        if (! $config->ativo) {
            $this->dispatch('toast', tipo: 'alerta', titulo: 'Integração inativa', mensagem: 'Ative a integração e-SUS APS PEC e salve as configurações antes de sincronizar.');

            return;
        }

        // Blindagem contra execuções simultâneas
        $emAndamento = HistoricoSincronizacaoEsus::where('status', 'EM_ANDAMENTO')->exists();
        if ($emAndamento) {
            $this->sincronizandoEsus = true;
            $this->dispatch('toast', tipo: 'alerta', titulo: 'Sincronização em andamento', mensagem: 'Já existe uma sincronização sendo executada neste momento. Aguarde a conclusão.');

            return;
        }

        $modoParaExecucao = $forcarModo ?: $config->modo_sincronizacao ?: 'AUTO';

        $historico = HistoricoSincronizacaoEsus::create([
            'iniciado_em' => now(),
            'tipo_execucao' => 'MANUAL',
            'status' => 'EM_ANDAMENTO',
            'total_consultado' => 0,
            'novos_pacientes' => 0,
            'pacientes_atualizados' => 0,
            'registros_ignorados' => 0,
            'duplicidades_identificadas' => 0,
            'detalhes' => [
                'etapa' => 'Consultando origem',
                'percentual' => 0,
                'modo_executado' => $modoParaExecucao,
            ],
        ]);

        $this->sincronizandoEsus = true;
        $this->sincronizacaoAtivaId = $historico->id;
        $this->progressoEtapa = 'Consultando origem';
        $this->progressoPercentual = 0;

        if (config('queue.default') === 'sync') {
            $service->sincronizarPacientes('MANUAL', null, $historico->id, null, $forcarModo);
            $this->verificarProgressoSincronizacao();
        } else {
            SincronizarEsusPecJob::dispatch('MANUAL', $historico->id, $forcarModo);
            $this->dispatch('toast', tipo: 'info', titulo: 'Sincronização em andamento', mensagem: 'A importação foi iniciada. O progresso em tempo real é exibido abaixo.');
        }
    }

    public function verificarProgressoSincronizacao(): void
    {
        if (! $this->sincronizacaoAtivaId) {
            $emAndamento = HistoricoSincronizacaoEsus::where('status', 'EM_ANDAMENTO')->latest('iniciado_em')->first();
            if ($emAndamento) {
                $this->sincronizacaoAtivaId = $emAndamento->id;
                $this->sincronizandoEsus = true;
            } else {
                $this->sincronizandoEsus = false;

                return;
            }
        }

        $historico = HistoricoSincronizacaoEsus::find($this->sincronizacaoAtivaId);
        if (! $historico) {
            $this->sincronizandoEsus = false;
            $this->sincronizacaoAtivaId = null;

            return;
        }

        $cache = Cache::get("esus_sync_progresso_{$historico->id}");
        $detalhes = $historico->detalhes ?? [];

        $this->progressoEtapa = (string) ($cache['etapa'] ?? ($detalhes['etapa'] ?? 'Processando registros...'));
        $this->progressoProcessados = (int) ($cache['processados'] ?? ($detalhes['processados'] ?? $historico->total_consultado));
        $this->progressoTotal = (int) ($cache['total'] ?? ($detalhes['total_estimado'] ?? 0));
        $this->progressoPercentual = (int) ($cache['percentual'] ?? ($detalhes['percentual'] ?? 0));

        $contadores = $cache['contadores'] ?? [
            'novos' => $historico->novos_pacientes,
            'atualizados' => $historico->pacientes_atualizados,
            'sem_alteracoes' => $detalhes['pacientes_sem_alteracoes'] ?? 0,
            'duplicidades' => $historico->duplicidades_identificadas,
            'invalidos' => $detalhes['registros_invalidos'] ?? 0,
            'sem_identificador' => $detalhes['sem_identificador'] ?? 0,
        ];
        $this->progressoContadores = $contadores;

        if ($historico->status !== 'EM_ANDAMENTO') {
            $this->sincronizandoEsus = false;
            $this->sincronizacaoAtivaId = null;
            $this->carregarConfiguracaoEsus();

            if ($historico->status === 'SUCESSO') {
                $msg = $detalhes['mensagem_resumo'] ?? sprintf(
                    'Sincronização concluída com sucesso: %d novos pacientes, %d atualizados (%d consultados).',
                    $historico->novos_pacientes,
                    $historico->pacientes_atualizados,
                    $historico->total_consultado
                );
                $this->dispatch('toast', tipo: 'sucesso', titulo: 'Sincronização e-SUS concluída', mensagem: $msg);
            } elseif ($historico->status === 'INTERROMPIDO') {
                $this->dispatch('toast', tipo: 'alerta', titulo: 'Sincronização interrompida', mensagem: $historico->mensagem_erro ?? 'Operação interrompida.');
            } else {
                $this->dispatch('toast', tipo: 'erro', titulo: 'Erro na sincronização', mensagem: $historico->mensagem_erro ?? 'Falha na sincronização.');
            }
        }
    }

    // =========================================================================
    // 1. GESTÃO DE SALAS E ESPAÇOS (Coordenação)
    // =========================================================================
    public function abrirModalNovaSala(): void
    {
        abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);

        $this->resetValidation();
        $this->salaEditandoId = null;
        $this->sala_nome = '';
        $this->sala_tipo = 'CONSULTORIO';
        $this->sala_ativo = true;
        $this->modalSalaAberto = true;
    }

    public function editarSala(int $salaId): void
    {
        abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);

        $this->resetValidation();
        $sala = Sala::findOrFail($salaId);
        $this->salaEditandoId = $sala->id;
        $this->sala_nome = $sala->nome;
        $this->sala_tipo = $sala->tipo;
        $this->sala_ativo = (bool) $sala->ativo;
        $this->modalSalaAberto = true;
    }

    public function fecharModalSala(): void
    {
        $this->modalSalaAberto = false;
        $this->resetValidation();
    }

    public function salvarSala(): void
    {
        abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);

        $dados = $this->validate([
            'sala_nome' => ['required', 'string', 'max:100'],
            'sala_tipo' => ['required', Rule::in(['CONSULTORIO', 'SENSORIAL', 'AVD', 'PEDIATRIA', 'ANEXO'])],
        ]);

        $editando = $this->salaEditandoId !== null;

        Sala::updateOrCreate(
            ['id' => $this->salaEditandoId],
            [
                'nome' => trim($dados['sala_nome']),
                'tipo' => $dados['sala_tipo'],
                'ativo' => $this->sala_ativo,
            ]
        );

        $this->modalSalaAberto = false;
        $this->reset(['salaEditandoId', 'sala_nome']);
        $this->sala_tipo = 'CONSULTORIO';
        $this->sala_ativo = true;

        $mensagem = $editando
            ? 'Os dados da sala foram atualizados com sucesso.'
            : 'Nova sala/espaço terapêutico cadastrado com sucesso.';

        session()->flash('sucesso_config', $mensagem);
        $this->dispatch('toast', tipo: 'sucesso', titulo: $editando ? 'Sala atualizada' : 'Sala cadastrada', mensagem: $mensagem);
    }

    public function alternarStatusSala(int $salaId): void
    {
        abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);

        $sala = Sala::findOrFail($salaId);
        $novoStatus = ! $sala->ativo;
        $sala->update(['ativo' => $novoStatus]);

        $this->dispatch(
            'toast',
            tipo: $novoStatus ? 'sucesso' : 'alerta',
            titulo: $novoStatus ? 'Sala reativada' : 'Sala inativada',
            mensagem: "O espaço '{$sala->nome}' agora está ".($novoStatus ? 'ATIVO na grade.' : 'INATIVO para novos agendamentos.')
        );
    }

    // =========================================================================
    // 2. GESTÃO DE PACIENTES DO CER (Recepção / Coordenação)
    // =========================================================================
    public function abrirModalNovoPaciente(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['recepcao', 'coordenacao']) === true, 403);

        $this->resetValidation();
        $this->reset([
            'pacienteEditandoId',
            'pac_nome',
            'pac_cns',
            'pac_cpf',
            'pac_data_nascimento',
            'pac_nome_mae',
            'pac_ubs_referencia',
            'pac_nome_acs',
            'pac_telefone_contato',
            'pac_telefone_secundario',
            'pac_endereco',
            'pac_bairro',
            'pac_cid_principal',
            'pac_cid_secundario',
            'pac_modalidades',
        ]);
        $this->pac_sexo = 'M';
        $this->pac_municipio_ibge = ConfiguracaoEstabelecimento::atual()->codigo_ibge_padrao;
        $this->modalPacienteAberto = true;
    }

    public function editarPaciente(int $pacienteId): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['recepcao', 'coordenacao']) === true, 403);

        $this->resetValidation();
        $pac = Paciente::findOrFail($pacienteId);

        $this->pacienteEditandoId = $pac->id;
        $this->pac_nome = $pac->nome;
        $this->pac_cns = (string) ($pac->cns ?? '');
        $this->pac_cpf = (string) ($pac->cpf ?? '');
        $this->pac_data_nascimento = $pac->data_nascimento->format('Y-m-d');
        $this->pac_sexo = $pac->sexo;
        $this->pac_nome_mae = (string) ($pac->nome_mae ?? '');
        $this->pac_municipio_ibge = (string) ($pac->municipio_ibge ?? ConfiguracaoEstabelecimento::atual()->codigo_ibge_padrao);
        $this->pac_ubs_referencia = (string) ($pac->ubs_referencia ?? '');
        $this->pac_nome_acs = (string) ($pac->nome_acs ?? '');
        $this->pac_telefone_contato = (string) ($pac->telefone_contato ?? '');
        $this->pac_telefone_secundario = (string) ($pac->telefone_secundario ?? '');
        $this->pac_endereco = (string) ($pac->endereco ?? '');
        $this->pac_bairro = (string) ($pac->bairro ?? '');
        $this->pac_cid_principal = (string) ($pac->cid_principal ?? '');
        $this->pac_cid_secundario = (string) ($pac->cid_secundario ?? '');
        $this->pac_modalidades = is_array($pac->modalidades_reabilitacao) ? $pac->modalidades_reabilitacao : [];

        $this->modalPacienteAberto = true;
    }

    public function fecharModalPaciente(): void
    {
        $this->modalPacienteAberto = false;
        $this->resetValidation();
    }

    public function salvarPaciente(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['recepcao', 'coordenacao']) === true, 403);

        $this->pac_cns = preg_replace('/\D/', '', $this->pac_cns ?? '') ?? '';
        $this->pac_cpf = preg_replace('/\D/', '', $this->pac_cpf ?? '') ?? '';
        $this->pac_municipio_ibge = preg_replace('/\D/', '', $this->pac_municipio_ibge ?? '') ?? '';

        $rules = [
            'pac_nome' => ['required', 'string', 'max:255'],
            'pac_data_nascimento' => ['required', 'date', 'before_or_equal:today'],
            'pac_sexo' => ['required', Rule::in(['M', 'F'])],
            'pac_municipio_ibge' => ['required', 'string', 'digits:6'],
            'pac_nome_mae' => ['nullable', 'string', 'max:255'],
            'pac_ubs_referencia' => ['nullable', 'string', 'max:255'],
            'pac_nome_acs' => ['nullable', 'string', 'max:255'],
            'pac_telefone_contato' => ['nullable', 'string', 'max:20'],
            'pac_telefone_secundario' => ['nullable', 'string', 'max:20'],
            'pac_endereco' => ['nullable', 'string', 'max:255'],
            'pac_bairro' => ['nullable', 'string', 'max:100'],
            'pac_cns' => [
                'nullable',
                'string',
                'digits:15',
                new CnsValido,
                Rule::unique('pacientes', 'cns')->ignore($this->pacienteEditandoId),
            ],
            'pac_cpf' => [
                'nullable',
                'string',
                'digits:11',
                Rule::unique('pacientes', 'cpf')->ignore($this->pacienteEditandoId),
            ],
        ];

        // Se campos clínicos forem fornecidos (ex.: por coordenação ou testes legados)
        if ($this->pac_cid_principal !== '') {
            $rules['pac_cid_principal'] = ['nullable', 'string', 'max:10'];
        }
        if ($this->pac_cid_secundario !== '') {
            $rules['pac_cid_secundario'] = ['nullable', 'string', 'max:10'];
        }
        if ($this->pac_modalidades !== []) {
            $rules['pac_modalidades'] = ['nullable', 'array'];
        }

        $this->validate($rules);

        // Regra de desduplicação do e-SUS/SUS:
        // Se CPF e CNS não forem informados, validar duplicidade por Nome + Data de Nascimento
        if ($this->pac_cpf === '' && $this->pac_cns === '') {
            $duplicado = Paciente::query()
                ->when($this->pacienteEditandoId !== null, fn ($q) => $q->where('id', '!=', $this->pacienteEditandoId))
                ->where('nome', trim($this->pac_nome))
                ->whereDate('data_nascimento', $this->pac_data_nascimento)
                ->exists();

            if ($duplicado) {
                $this->addError('pac_nome', 'Já existe um paciente cadastrado com este mesmo nome e data de nascimento no sistema.');

                return;
            }
        }

        $editando = $this->pacienteEditandoId !== null;
        $pacienteExistente = $editando ? Paciente::find($this->pacienteEditandoId) : null;

        $cidPrincipal = $this->pac_cid_principal !== ''
            ? strtoupper(trim($this->pac_cid_principal))
            : ($pacienteExistente?->cid_principal);

        $cidSecundario = $this->pac_cid_secundario !== ''
            ? strtoupper(trim($this->pac_cid_secundario))
            : ($pacienteExistente?->cid_secundario);

        $modalidades = $this->pac_modalidades !== []
            ? $this->pac_modalidades
            : ($pacienteExistente?->modalidades_reabilitacao);

        Paciente::updateOrCreate(
            ['id' => $this->pacienteEditandoId],
            [
                'nome' => trim($this->pac_nome),
                'cns' => $this->pac_cns !== '' ? $this->pac_cns : null,
                'cpf' => $this->pac_cpf !== '' ? $this->pac_cpf : null,
                'data_nascimento' => $this->pac_data_nascimento,
                'sexo' => $this->pac_sexo,
                'nome_mae' => $this->pac_nome_mae !== '' ? trim($this->pac_nome_mae) : null,
                'municipio_ibge' => $this->pac_municipio_ibge,
                'ubs_referencia' => $this->pac_ubs_referencia !== '' ? trim($this->pac_ubs_referencia) : null,
                'nome_acs' => $this->pac_nome_acs !== '' ? trim($this->pac_nome_acs) : null,
                'telefone_contato' => $this->pac_telefone_contato !== '' ? trim($this->pac_telefone_contato) : null,
                'telefone_secundario' => $this->pac_telefone_secundario !== '' ? trim($this->pac_telefone_secundario) : null,
                'endereco' => $this->pac_endereco !== '' ? trim($this->pac_endereco) : null,
                'bairro' => $this->pac_bairro !== '' ? trim($this->pac_bairro) : null,
                'cid_principal' => $cidPrincipal,
                'cid_secundario' => $cidSecundario,
                'modalidades_reabilitacao' => $modalidades,
                'origem_cadastro' => $pacienteExistente?->origem_cadastro ?? 'MANUAL',
                'ativo' => true,
            ]
        );

        if (! $editando) {
            $this->resetPage();
        }

        Cache::forget('pacientes_lista_ubs_dropdown');

        $this->modalPacienteAberto = false;
        $this->reset([
            'pacienteEditandoId',
            'pac_nome',
            'pac_cns',
            'pac_cpf',
            'pac_data_nascimento',
            'pac_nome_mae',
            'pac_ubs_referencia',
            'pac_nome_acs',
            'pac_telefone_contato',
            'pac_telefone_secundario',
            'pac_endereco',
            'pac_bairro',
            'pac_cid_principal',
            'pac_cid_secundario',
            'pac_modalidades',
        ]);

        $mensagem = $editando
            ? 'Os dados cadastrais do paciente foram atualizados com sucesso.'
            : 'Paciente cadastrado com sucesso e pronto para atendimento.';

        session()->flash('sucesso_config', $mensagem);
        $this->dispatch('toast', tipo: 'sucesso', titulo: $editando ? 'Paciente atualizado' : 'Paciente cadastrado', mensagem: $mensagem);
    }

    public function alternarStatusPaciente(int $pacienteId): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['recepcao', 'coordenacao']) === true, 403);

        $pac = Paciente::findOrFail($pacienteId);
        $novoStatus = ! $pac->ativo;
        $pac->update(['ativo' => $novoStatus]);

        $this->dispatch(
            'toast',
            tipo: $novoStatus ? 'sucesso' : 'alerta',
            titulo: $novoStatus ? 'Paciente ativado' : 'Paciente inativado',
            mensagem: "O cadastro de '{$pac->nome}' foi marcado como ".($novoStatus ? 'ATIVO.' : 'INATIVO.')
        );
    }

    // =========================================================================
    // 3. GESTÃO DE USUÁRIOS E PROFISSIONAIS DE SAÚDE (Coordenação)
    // =========================================================================
    public function abrirModalNovoProfissional(): void
    {
        abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);

        $this->resetValidation();
        $this->reset([
            'usuarioEditandoId',
            'usr_name',
            'usr_email',
            'usr_password',
            'prof_cpf',
            'prof_cns',
            'prof_conselho',
            'prof_pin',
        ]);
        $this->usr_role = 'terapeuta';
        $this->vincular_profissional = true;
        $this->prof_cbo = '223605';
        $this->prof_especialidade = 'Fisioterapia';
        $this->modalProfissionalAberto = true;
    }

    public function editarUsuarioProfissional(int $userId): void
    {
        abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);

        $this->resetValidation();
        $usuario = User::with(['roles', 'profissional'])->findOrFail($userId);

        $this->usuarioEditandoId = $usuario->id;
        $this->usr_name = $usuario->name;
        $this->usr_email = $usuario->email;
        $this->usr_password = '';
        $this->usr_role = (string) ($usuario->roles->first()?->name ?? 'terapeuta');

        if ($usuario->profissional !== null) {
            $this->vincular_profissional = true;
            $this->prof_cpf = $usuario->profissional->cpf;
            $this->prof_cns = $usuario->profissional->cns;
            $this->prof_conselho = $usuario->profissional->registro_conselho;
            $this->prof_cbo = $usuario->profissional->cbo_padrao;
            $this->prof_especialidade = $usuario->profissional->especialidade;
            $this->prof_pin = '';
        } else {
            $this->vincular_profissional = false;
            $this->prof_cpf = '';
            $this->prof_cns = '';
            $this->prof_conselho = '';
            $this->prof_cbo = '223605';
            $this->prof_especialidade = 'Fisioterapia';
            $this->prof_pin = '';
        }

        $this->modalProfissionalAberto = true;
    }

    public function fecharModalProfissional(): void
    {
        $this->modalProfissionalAberto = false;
        $this->resetValidation();
    }

    public function salvarUsuarioProfissional(): void
    {
        abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);

        $this->prof_cpf = preg_replace('/\D/', '', $this->prof_cpf) ?? '';
        $this->prof_cns = preg_replace('/\D/', '', $this->prof_cns) ?? '';

        $editando = $this->usuarioEditandoId !== null;
        $usuarioExistente = $editando ? User::with('profissional')->findOrFail($this->usuarioEditandoId) : null;
        $profissionalExistenteId = $usuarioExistente?->profissional?->id;

        $regras = [
            'usr_name' => ['required', 'string', 'max:255'],
            'usr_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->usuarioEditandoId),
            ],
            'usr_password' => $editando ? ['nullable', 'string', 'min:6'] : ['required', 'string', 'min:6'],
            'usr_role' => ['required', Rule::in(['coordenacao', 'recepcao', 'terapeuta', 'faturamento'])],
        ];

        if ($this->vincular_profissional) {
            $regras['prof_cpf'] = [
                'required',
                'string',
                'digits:11',
                Rule::unique('profissionais', 'cpf')->ignore($profissionalExistenteId),
            ];
            $regras['prof_cns'] = [
                'required',
                'string',
                'digits:15',
                new CnsValido,
                Rule::unique('profissionais', 'cns')->ignore($profissionalExistenteId),
            ];
            $regras['prof_conselho'] = ['required', 'string', 'max:50'];
            $regras['prof_cbo'] = ['required', 'string', 'max:10'];
            $regras['prof_especialidade'] = ['required', 'string', 'max:100'];
            $regras['prof_pin'] = ($editando && $profissionalExistenteId !== null)
                ? ['nullable', 'string', 'min:4', 'max:12']
                : ['required', 'string', 'min:4', 'max:12'];
        }

        $this->validate($regras);

        DB::transaction(function () use ($editando, $usuarioExistente): void {
            if ($editando && $usuarioExistente !== null) {
                $dadosUser = [
                    'name' => trim($this->usr_name),
                    'email' => strtolower(trim($this->usr_email)),
                ];
                if ($this->usr_password !== '') {
                    $dadosUser['password'] = Hash::make($this->usr_password);
                }

                $usuarioExistente->update($dadosUser);
                $usuarioExistente->syncRoles([$this->usr_role]);
                $userAlvo = $usuarioExistente;
            } else {
                $userAlvo = User::create([
                    'name' => trim($this->usr_name),
                    'email' => strtolower(trim($this->usr_email)),
                    'password' => Hash::make($this->usr_password),
                    'ativo' => true,
                ]);
                $userAlvo->syncRoles([$this->usr_role]);
            }

            if ($this->vincular_profissional) {
                $dadosProf = [
                    'nome' => trim($this->usr_name),
                    'cpf' => $this->prof_cpf,
                    'cns' => $this->prof_cns,
                    'registro_conselho' => trim($this->prof_conselho),
                    'cbo_padrao' => trim($this->prof_cbo),
                    'especialidade' => trim($this->prof_especialidade),
                    'ativo' => (bool) $userAlvo->ativo,
                ];

                if ($this->prof_pin !== '') {
                    $dadosProf['pin_assinatura_hash'] = Hash::make($this->prof_pin);
                }

                Profissional::updateOrCreate(
                    ['user_id' => $userAlvo->id],
                    $dadosProf
                );
            }
        });

        $this->modalProfissionalAberto = false;
        $this->reset([
            'usuarioEditandoId',
            'usr_name',
            'usr_email',
            'usr_password',
            'prof_cpf',
            'prof_cns',
            'prof_conselho',
            'prof_pin',
        ]);

        $mensagem = $editando
            ? 'Dados do usuário e credenciais profissionais atualizados com sucesso.'
            : 'Usuário e credenciais profissionais criados com sucesso.';

        session()->flash('sucesso_config', $mensagem);
        $this->dispatch('toast', tipo: 'sucesso', titulo: $editando ? 'Profissional atualizado' : 'Profissional cadastrado', mensagem: $mensagem);
    }

    public function alternarStatusUsuario(int $userId): void
    {
        abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);

        if ($userId === (int) auth()->id()) {
            $this->dispatch('toast', tipo: 'alerta', titulo: 'Ação bloqueada', mensagem: 'Você não pode inativar a sua própria conta de acesso.');

            return;
        }

        $usuario = User::findOrFail($userId);
        $novoStatus = ! $usuario->ativo;
        $usuario->update(['ativo' => $novoStatus]);

        if ($usuario->profissional !== null) {
            $usuario->profissional->update(['ativo' => $novoStatus]);
        }

        $this->dispatch(
            'toast',
            tipo: $novoStatus ? 'sucesso' : 'alerta',
            titulo: $novoStatus ? 'Conta reativada' : 'Conta suspensa',
            mensagem: "O acesso de '{$usuario->name}' foi ".($novoStatus ? 'REATIVADO.' : 'INATIVADO.')
        );
    }

    // =========================================================================
    // 4. PARAMETRIZAÇÃO SIGTAP X CBO (Faturamento / Coordenação)
    // =========================================================================
    public function abrirModalNovoSigtap(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['faturamento', 'coordenacao']) === true, 403);

        $this->resetValidation();
        $this->sigtapEditandoId = null;
        $this->sig_codigo = '';
        $this->sig_descricao = '';
        $this->sig_cbos_csv = '223605, 223810, 223905, 251510';
        $this->sig_idade_minima = 0;
        $this->sig_idade_maxima = 130;
        $this->sig_sexo_permitido = 'AMBOS';
        $this->modalSigtapAberto = true;
    }

    public function editarProcedimentoSigtap(int $sigtapId): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['faturamento', 'coordenacao']) === true, 403);

        $this->resetValidation();
        $proc = ProcedimentoSigtap::findOrFail($sigtapId);

        $this->sigtapEditandoId = $proc->id;
        $this->sig_codigo = $proc->codigo;
        $this->sig_descricao = $proc->descricao;
        $this->sig_cbos_csv = implode(', ', $proc->cbos_permitidos ?? []);
        $this->sig_idade_minima = (int) $proc->idade_minima;
        $this->sig_idade_maxima = (int) $proc->idade_maxima;
        $this->sig_sexo_permitido = $proc->sexo_permitido;
        $this->modalSigtapAberto = true;
    }

    public function fecharModalSigtap(): void
    {
        $this->modalSigtapAberto = false;
        $this->resetValidation();
    }

    public function salvarProcedimentoSigtap(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['faturamento', 'coordenacao']) === true, 403);

        $this->sig_codigo = preg_replace('/\D/', '', $this->sig_codigo) ?? '';

        $this->validate([
            'sig_codigo' => ['required', 'string', 'digits:10'],
            'sig_descricao' => ['required', 'string', 'max:255'],
            'sig_cbos_csv' => ['required', 'string'],
            'sig_idade_minima' => ['required', 'integer', 'min:0', 'max:130'],
            'sig_idade_maxima' => ['required', 'integer', 'min:0', 'max:999', 'gte:sig_idade_minima'],
            'sig_sexo_permitido' => ['required', Rule::in(['AMBOS', 'M', 'F'])],
        ]);

        $cbos = array_values(array_filter(array_map(
            fn (string $item) => preg_replace('/\D/', '', trim($item)) ?? '',
            explode(',', $this->sig_cbos_csv)
        )));

        $editando = $this->sigtapEditandoId !== null;

        if ($editando) {
            $proc = ProcedimentoSigtap::findOrFail($this->sigtapEditandoId);
            $proc->update([
                'codigo' => $this->sig_codigo,
                'descricao' => strtoupper(trim($this->sig_descricao)),
                'cbos_permitidos' => $cbos,
                'idade_minima' => $this->sig_idade_minima,
                'idade_maxima' => $this->sig_idade_maxima,
                'sexo_permitido' => $this->sig_sexo_permitido,
            ]);
        } else {
            ProcedimentoSigtap::updateOrCreate(
                ['codigo' => $this->sig_codigo],
                [
                    'descricao' => strtoupper(trim($this->sig_descricao)),
                    'cbos_permitidos' => $cbos,
                    'idade_minima' => $this->sig_idade_minima,
                    'idade_maxima' => $this->sig_idade_maxima,
                    'sexo_permitido' => $this->sig_sexo_permitido,
                    'ativo' => true,
                ]
            );
        }

        $this->modalSigtapAberto = false;
        $this->reset(['sigtapEditandoId', 'sig_codigo', 'sig_descricao']);

        $mensagem = $editando
            ? 'Procedimento SIGTAP e matriz CBO atualizados com sucesso.'
            : 'Procedimento SIGTAP parametrizado com sucesso.';

        session()->flash('sucesso_config', $mensagem);
        $this->dispatch('toast', tipo: 'sucesso', titulo: 'SIGTAP salvo', mensagem: $mensagem);
    }

    public function alternarStatusSigtap(int $sigtapId): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['faturamento', 'coordenacao']) === true, 403);

        $proc = ProcedimentoSigtap::findOrFail($sigtapId);
        $novoStatus = ! $proc->ativo;
        $proc->update(['ativo' => $novoStatus]);

        $this->dispatch(
            'toast',
            tipo: $novoStatus ? 'sucesso' : 'alerta',
            titulo: $novoStatus ? 'Procedimento ativado' : 'Procedimento suspenso',
            mensagem: "O código {$proc->codigo} foi marcado como ".($novoStatus ? 'ATIVO.' : 'INATIVO.')
        );
    }

    // =========================================================================
    // 5. AUDITORIA: MODAL DE DETALHES JSON (Coordenação)
    // =========================================================================
    public function inspecionarLogAuditoria(int $logId): void
    {
        abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);
        $this->logDetalhesId = $logId;
    }

    public function fecharModalLogAuditoria(): void
    {
        $this->logDetalhesId = null;
    }

    // =========================================================================
    // 6. MEU PERFIL: DADOS, SENHA E PIN DE ASSINATURA (Todos os usuários)
    // =========================================================================
    public function atualizarCredenciaisProprias(): void
    {
        $user = auth()->user();
        abort_if($user === null, 403);

        $this->validate([
            'perfil_nome' => ['required', 'string', 'max:255'],
            'perfil_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'nova_senha' => ['nullable', 'string', 'min:6'],
            'novo_pin' => ['nullable', 'string', 'min:4', 'max:12'],
        ]);

        $dadosUser = [
            'name' => trim($this->perfil_nome),
            'email' => strtolower(trim($this->perfil_email)),
        ];

        if ($this->nova_senha !== '') {
            $dadosUser['password'] = Hash::make($this->nova_senha);
        }

        $user->update($dadosUser);

        if ($user->profissional !== null) {
            $dadosProf = ['nome' => trim($this->perfil_nome)];
            if ($this->novo_pin !== '') {
                $dadosProf['pin_assinatura_hash'] = Hash::make($this->novo_pin);
            }
            $user->profissional->update($dadosProf);
        }

        $this->reset(['nova_senha', 'novo_pin']);
        $mensagem = 'Suas informações de perfil e credenciais de assinatura foram atualizadas.';
        session()->flash('sucesso_config', $mensagem);
        $this->dispatch('toast', tipo: 'sucesso', titulo: 'Perfil atualizado', mensagem: $mensagem);
    }

    // =========================================================================
    // 7. MODAL UNIVERSAL DE EXCLUSÃO COM VALIDAÇÃO DE INTEGRIDADE
    // =========================================================================
    public function confirmarExclusao(string $entidade, int $id): void
    {
        $this->exclusaoEntidade = $entidade;
        $this->exclusaoId = $id;

        if ($entidade === 'sala') {
            abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);
            $sala = Sala::findOrFail($id);
            $this->exclusaoDescricao = "Sala/Espaço: {$sala->nome} ({$sala->tipo})";
        } elseif ($entidade === 'paciente') {
            abort_unless(auth()->user()?->hasAnyRole(['recepcao', 'coordenacao']) === true, 403);
            $pac = Paciente::findOrFail($id);
            $this->exclusaoDescricao = "Paciente: {$pac->nome} (CNS: {$pac->cns})";
        } elseif ($entidade === 'usuario') {
            abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);
            $usr = User::findOrFail($id);
            $this->exclusaoDescricao = "Usuário/Profissional: {$usr->name} ({$usr->email})";
        } elseif ($entidade === 'sigtap') {
            abort_unless(auth()->user()?->hasAnyRole(['faturamento', 'coordenacao']) === true, 403);
            $proc = ProcedimentoSigtap::findOrFail($id);
            $this->exclusaoDescricao = "Procedimento SIGTAP: {$proc->codigo} — {$proc->descricao}";
        } else {
            abort(400);
        }

        $this->modalExclusaoAberto = true;
    }

    public function cancelarExclusao(): void
    {
        $this->modalExclusaoAberto = false;
        $this->exclusaoEntidade = '';
        $this->exclusaoId = null;
        $this->exclusaoDescricao = '';
    }

    public function excluirRegistro(): void
    {
        if ($this->exclusaoId === null || $this->exclusaoEntidade === '') {
            return;
        }

        $id = $this->exclusaoId;
        $entidade = $this->exclusaoEntidade;

        try {
            if ($entidade === 'sala') {
                abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);
                $sala = Sala::findOrFail($id);

                if ($sala->agendamentos()->withTrashed()->exists()) {
                    $this->cancelarExclusao();
                    $this->dispatch(
                        'toast',
                        tipo: 'alerta',
                        titulo: 'Exclusão bloqueada por vínculo',
                        mensagem: "A sala '{$sala->nome}' possui agendamentos vinculados. Inative-a em vez de excluir."
                    );

                    return;
                }

                $nome = $sala->nome;
                $sala->delete();
                $this->cancelarExclusao();
                $this->dispatch('toast', tipo: 'sucesso', titulo: 'Sala excluída', mensagem: "O espaço '{$nome}' foi removido com sucesso.");

                return;
            }

            if ($entidade === 'paciente') {
                abort_unless(auth()->user()?->hasAnyRole(['recepcao', 'coordenacao']) === true, 403);
                $pac = Paciente::findOrFail($id);
                $nome = $pac->nome;
                $pac->delete(); // SoftDelete preserva histórico clínico e faturamento
                $this->cancelarExclusao();
                $this->dispatch('toast', tipo: 'sucesso', titulo: 'Paciente arquivado', mensagem: "O prontuário de '{$nome}' foi arquivado (Soft Delete) com segurança.");

                return;
            }

            if ($entidade === 'usuario') {
                abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);

                if ($id === (int) auth()->id()) {
                    $this->cancelarExclusao();
                    $this->dispatch('toast', tipo: 'erro', titulo: 'Operação não permitida', mensagem: 'Você não pode excluir a sua própria conta logada.');

                    return;
                }

                $usr = User::with('profissional')->findOrFail($id);
                $temVinculos = $usr->logsAuditoria()->exists()
                    || ($usr->profissional !== null && (
                        $usr->profissional->agendamentos()->withTrashed()->exists()
                        || $usr->profissional->evolucoes()->exists()
                        || $usr->profissional->producoesBpai()->exists()
                    ));

                if ($temVinculos) {
                    $this->cancelarExclusao();
                    $this->dispatch(
                        'toast',
                        tipo: 'alerta',
                        titulo: 'Exclusão bloqueada por histórico clínico',
                        mensagem: "O profissional '{$usr->name}' possui evoluções, agendamentos ou logs de auditoria. Utilize o botão de Inativar Acesso."
                    );

                    return;
                }

                $nome = $usr->name;
                DB::transaction(function () use ($usr): void {
                    if ($usr->profissional !== null) {
                        $usr->profissional->delete();
                    }
                    $usr->delete();
                });

                $this->cancelarExclusao();
                $this->dispatch('toast', tipo: 'sucesso', titulo: 'Usuário removido', mensagem: "O cadastro de '{$nome}' foi excluído permanentemente.");

                return;
            }

            if ($entidade === 'sigtap') {
                abort_unless(auth()->user()?->hasAnyRole(['faturamento', 'coordenacao']) === true, 403);
                $proc = ProcedimentoSigtap::findOrFail($id);

                if ($proc->producoesBpai()->exists()) {
                    $this->cancelarExclusao();
                    $this->dispatch(
                        'toast',
                        tipo: 'alerta',
                        titulo: 'Procedimento com produção BPA-I',
                        mensagem: "O código {$proc->codigo} já foi utilizado em linhas de faturamento BPA-I. Inative-o em vez de excluir."
                    );

                    return;
                }

                $codigo = $proc->codigo;
                $proc->delete();
                $this->cancelarExclusao();
                $this->dispatch('toast', tipo: 'sucesso', titulo: 'Procedimento excluído', mensagem: "O procedimento SIGTAP {$codigo} foi excluído.");
            }
        } catch (QueryException) {
            $this->cancelarExclusao();
            $this->dispatch(
                'toast',
                tipo: 'erro',
                titulo: 'Restrição de integridade do banco',
                mensagem: 'Este registro possui vínculos históricos no banco de dados e não pode ser removido fisicamente.'
            );
        }
    }

    public function render(): View
    {
        $user = auth()->user();
        $isCoordenacao = $user?->hasRole('coordenacao') === true;
        $podePacientes = $user?->hasAnyRole(['recepcao', 'coordenacao']) === true;
        $podeSigtap = $user?->hasAnyRole(['faturamento', 'coordenacao']) === true;

        // Otimização de Performance: busca exclusivamente os dados da aba ativa
        $salas = collect();
        if ($isCoordenacao && $this->abaAtiva === 'salas') {
            $salas = Sala::query()
                ->when($this->buscaSala !== '', function ($q): void {
                    $termo = '%'.trim($this->buscaSala).'%';
                    $q->where(fn ($sub) => $sub->where('nome', 'like', $termo)->orWhere('tipo', 'like', $termo));
                })
                ->orderBy('nome')
                ->get();
        }

        $usuarios = collect();
        if ($isCoordenacao && $this->abaAtiva === 'profissionais') {
            $usuarios = User::query()
                ->with(['roles', 'profissional'])
                ->when($this->buscaProfissional !== '', function ($q): void {
                    $termo = '%'.trim($this->buscaProfissional).'%';
                    $q->where(function ($sub) use ($termo): void {
                        $sub->where('name', 'like', $termo)
                            ->orWhere('email', 'like', $termo)
                            ->orWhereHas('profissional', fn ($p) => $p->where('cns', 'like', $termo)->orWhere('cbo_padrao', 'like', $termo)->orWhere('especialidade', 'like', $termo));
                    });
                })
                ->orderBy('name')
                ->get();
        }

        $pacientes = collect();
        if ($podePacientes && $this->abaAtiva === 'pacientes') {
            $termo = trim($this->buscaPaciente);
            $termoDigitos = preg_replace('/\D/', '', $termo) ?? '';

            $itensPorPagina = in_array($this->porPaginaPaciente, [15, 25, 50, 100], true)
                ? $this->porPaginaPaciente
                : 25;

            // Restringe projeção apenas às colunas renderizadas na tabela, evitando SELECT * pesado
            $colunas = [
                'id',
                'nome',
                'cpf',
                'cns',
                'data_nascimento',
                'sexo',
                'nome_mae',
                'cid_principal',
                'cid_secundario',
                'modalidades_reabilitacao',
                'ubs_referencia',
                'nome_acs',
                'telefone_contato',
                'telefone_secundario',
                'ativo',
            ];

            $query = Paciente::query()->select($colunas);

            if ($termo !== '') {
                $somenteDigitos = $termoDigitos !== '' && strlen(preg_replace('/[\s\.\-\/]/', '', $termo)) === strlen($termoDigitos);

                $query->where(function (Builder $sub) use ($termo, $termoDigitos, $somenteDigitos): void {
                    // 1. Termo numérico (CPF ou CNS): busca exata ou prefixo indexado
                    if ($somenteDigitos) {
                        if (strlen($termoDigitos) === 11) {
                            $sub->where('cpf', $termoDigitos)->orWhere('cns', 'like', $termoDigitos.'%');

                            return;
                        }
                        if (strlen($termoDigitos) === 15) {
                            $sub->where('cns', $termoDigitos);

                            return;
                        }
                        if (strlen($termoDigitos) >= 3) {
                            $sub->where('cpf', 'like', $termoDigitos.'%')->orWhere('cns', 'like', $termoDigitos.'%');

                            return;
                        }
                    }

                    // 2. Termo textual: prioriza prefixo do nome e CID
                    $sub->where('nome', 'like', $termo.'%')
                        ->orWhere('cid_principal', 'like', $termo.'%');

                    if (strlen($termoDigitos) >= 3) {
                        $sub->orWhere('cpf', 'like', $termoDigitos.'%')
                            ->orWhere('cns', 'like', $termoDigitos.'%');
                    }

                    // 3. Suporte a palavras intermediárias no MySQL via FULLTEXT ou fallback indexável
                    $driver = DB::connection()->getDriverName();
                    if ($driver === 'mysql' && mb_strlen($termo) >= 3) {
                        $termoFulltext = preg_replace('/[+\-><()~*\"@]+/', ' ', $termo) ?? $termo;
                        $palavras = array_filter(explode(' ', trim($termoFulltext)));
                        if ($palavras !== []) {
                            $expressao = implode(' ', array_map(fn (string $p): string => '+'.$p.'*', $palavras));
                            $sub->orWhereRaw('MATCH(nome) AGAINST(? IN BOOLEAN MODE)', [$expressao]);
                        }
                    } elseif ($driver !== 'mysql') {
                        $sub->orWhere('nome', 'like', '%'.$termo.'%');
                    }
                });
            }

            $pacientes = $query->latest('id')->paginate($itensPorPagina);
        }

        $procedimentosSigtap = collect();
        if ($podeSigtap && $this->abaAtiva === 'sigtap') {
            $procedimentosSigtap = ProcedimentoSigtap::query()
                ->when($this->buscaSigtap !== '', function ($q): void {
                    $termo = '%'.trim($this->buscaSigtap).'%';
                    $q->where(fn ($sub) => $sub->where('codigo', 'like', $termo)->orWhere('descricao', 'like', $termo));
                })
                ->orderBy('codigo')
                ->get();
        }

        $logsAuditoria = collect();
        $logInspecionado = null;
        if ($isCoordenacao && $this->abaAtiva === 'auditoria') {
            $logsAuditoria = LogAuditoria::query()
                ->with('user')
                ->when($this->filtroAcaoAuditoria !== '', fn ($q) => $q->where('acao', $this->filtroAcaoAuditoria))
                ->latest('created_at')
                ->limit(50)
                ->get();

            if ($this->logDetalhesId !== null) {
                $logInspecionado = LogAuditoria::with('user')->find($this->logDetalhesId);
            }
        }

        $historicoEsus = collect();
        if ($isCoordenacao && $this->abaAtiva === 'esus') {
            $historicoEsus = HistoricoSincronizacaoEsus::query()
                ->latest('iniciado_em')
                ->limit(20)
                ->get();
        }

        $dispositivosTv = collect();
        $solicitacoesTvPendentes = collect();
        if ($isCoordenacao && $this->abaAtiva === 'chamadas') {
            $dispositivosTv = DispositivoTv::query()
                ->with(['autorizadoPor', 'revogadoPor'])
                ->orderByDesc('created_at')
                ->get();

            $solicitacoesTvPendentes = SolicitacaoPareamentoTv::query()
                ->validas()
                ->orderByDesc('created_at')
                ->get();
        }

        // Carrega lista de UBS exclusivamente sob demanda (aba pacientes ou modal aberto) com cache de 1 hora
        $listaUbs = [];
        if ($podePacientes && ($this->abaAtiva === 'pacientes' || $this->modalPacienteAberto)) {
            $listaUbs = Cache::remember('pacientes_lista_ubs_dropdown', 3600, function (): array {
                $ubsDoBanco = Paciente::query()
                    ->whereNotNull('ubs_referencia')
                    ->where('ubs_referencia', '!=', '')
                    ->distinct()
                    ->pluck('ubs_referencia')
                    ->toArray();

                $padroes = [
                    'UBS Centro / PSF Central',
                    'UBS Dr. José Ferreira',
                    'UBS Santa Luzia',
                    'UBS Bela Vista',
                    'UBS São José',
                    'USF Esperança',
                    'UBS Zona Rural',
                ];

                return array_values(array_unique(array_filter(array_merge($padroes, $ubsDoBanco))));
            });
        }

        return view('livewire.configuracoes-sistema', [
            'abasPermitidas' => $this->abasPermitidas(),
            'salas' => $salas,
            'usuarios' => $usuarios,
            'pacientes' => $pacientes,
            'procedimentosSigtap' => $procedimentosSigtap,
            'logsAuditoria' => $logsAuditoria,
            'logInspecionado' => $logInspecionado,
            'historicoEsus' => $historicoEsus,
            'listaUbs' => $listaUbs,
            'dispositivosTv' => $dispositivosTv,
            'solicitacoesTvPendentes' => $solicitacoesTvPendentes,
        ]);
    }
}
