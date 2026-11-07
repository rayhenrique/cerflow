<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\LogAuditoria;
use App\Models\Paciente;
use App\Models\ProcedimentoSigtap;
use App\Models\Profissional;
use App\Models\Sala;
use App\Models\User;
use App\Rules\CnsValido;
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Configurações e Cadastros Base — CER Digital')]
class ConfiguracoesSistema extends Component
{
    public string $abaAtiva = 'pacientes';

    // --- Filtros de Busca ---
    public string $buscaPaciente = '';

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

    public string $pac_cid_principal = '';

    public string $pac_cid_secundario = '';

    public string $pac_telefone_contato = '';

    public string $pac_endereco = '';

    public string $pac_bairro = '';

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

    public function mount(?string $secao = null): void
    {
        $user = auth()->user();
        abort_if($user === null, 403);

        $this->perfil_nome = $user->name;
        $this->perfil_email = $user->email;

        $permitidas = $this->abasPermitidas();

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

    public function selecionarAba(string $aba): void
    {
        $permitidas = $this->abasPermitidas();
        if (in_array($aba, $permitidas, true)) {
            $this->resetValidation();
            $this->abaAtiva = $aba;
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
            return ['pacientes', 'salas', 'profissionais', 'sigtap', 'auditoria', 'perfil'];
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
            'pac_cid_principal',
            'pac_cid_secundario',
            'pac_telefone_contato',
            'pac_endereco',
            'pac_bairro',
            'pac_modalidades',
        ]);
        $this->pac_sexo = 'M';
        $this->pac_municipio_ibge = '270430';
        $this->modalPacienteAberto = true;
    }

    public function editarPaciente(int $pacienteId): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['recepcao', 'coordenacao']) === true, 403);

        $this->resetValidation();
        $pac = Paciente::findOrFail($pacienteId);

        $this->pacienteEditandoId = $pac->id;
        $this->pac_nome = $pac->nome;
        $this->pac_cns = $pac->cns;
        $this->pac_cpf = (string) ($pac->cpf ?? '');
        $this->pac_data_nascimento = $pac->data_nascimento->format('Y-m-d');
        $this->pac_sexo = $pac->sexo;
        $this->pac_nome_mae = $pac->nome_mae;
        $this->pac_municipio_ibge = $pac->municipio_ibge;
        $this->pac_cid_principal = $pac->cid_principal;
        $this->pac_cid_secundario = (string) ($pac->cid_secundario ?? '');
        $this->pac_telefone_contato = (string) ($pac->telefone_contato ?? '');
        $this->pac_endereco = (string) ($pac->endereco ?? '');
        $this->pac_bairro = (string) ($pac->bairro ?? '');
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

        $this->pac_cns = preg_replace('/\D/', '', $this->pac_cns) ?? '';
        $this->pac_cpf = preg_replace('/\D/', '', $this->pac_cpf) ?? '';
        $this->pac_municipio_ibge = preg_replace('/\D/', '', $this->pac_municipio_ibge) ?? '';

        $this->validate([
            'pac_nome' => ['required', 'string', 'max:255'],
            'pac_cns' => [
                'required',
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
            'pac_data_nascimento' => ['required', 'date', 'before_or_equal:today'],
            'pac_sexo' => ['required', Rule::in(['M', 'F'])],
            'pac_nome_mae' => ['required', 'string', 'max:255'],
            'pac_municipio_ibge' => ['required', 'string', 'digits:6'],
            'pac_cid_principal' => ['required', 'string', 'max:10'],
            'pac_cid_secundario' => ['nullable', 'string', 'max:10'],
            'pac_telefone_contato' => ['nullable', 'string', 'max:20'],
            'pac_endereco' => ['nullable', 'string', 'max:255'],
            'pac_bairro' => ['nullable', 'string', 'max:100'],
            'pac_modalidades' => ['nullable', 'array'],
        ]);

        $editando = $this->pacienteEditandoId !== null;

        Paciente::updateOrCreate(
            ['id' => $this->pacienteEditandoId],
            [
                'nome' => trim($this->pac_nome),
                'cns' => $this->pac_cns,
                'cpf' => $this->pac_cpf !== '' ? $this->pac_cpf : null,
                'data_nascimento' => $this->pac_data_nascimento,
                'sexo' => $this->pac_sexo,
                'nome_mae' => trim($this->pac_nome_mae),
                'municipio_ibge' => $this->pac_municipio_ibge,
                'cid_principal' => strtoupper(trim($this->pac_cid_principal)),
                'cid_secundario' => $this->pac_cid_secundario !== '' ? strtoupper(trim($this->pac_cid_secundario)) : null,
                'telefone_contato' => $this->pac_telefone_contato !== '' ? trim($this->pac_telefone_contato) : null,
                'endereco' => $this->pac_endereco !== '' ? trim($this->pac_endereco) : null,
                'bairro' => $this->pac_bairro !== '' ? trim($this->pac_bairro) : null,
                'modalidades_reabilitacao' => $this->pac_modalidades,
                'ativo' => true,
            ]
        );

        $this->modalPacienteAberto = false;
        $this->reset([
            'pacienteEditandoId',
            'pac_nome',
            'pac_cns',
            'pac_cpf',
            'pac_data_nascimento',
            'pac_nome_mae',
            'pac_cid_principal',
            'pac_cid_secundario',
            'pac_telefone_contato',
            'pac_endereco',
            'pac_bairro',
            'pac_modalidades',
        ]);

        $mensagem = $editando
            ? 'Os dados cadastrais do paciente foram atualizados com sucesso.'
            : 'Paciente cadastrado com sucesso e pronto para agendamento.';

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

        $salas = collect();
        if ($isCoordenacao) {
            $salas = Sala::query()
                ->when($this->buscaSala !== '', function ($q): void {
                    $termo = '%'.trim($this->buscaSala).'%';
                    $q->where(fn ($sub) => $sub->where('nome', 'like', $termo)->orWhere('tipo', 'like', $termo));
                })
                ->orderBy('nome')
                ->get();
        }

        $usuarios = collect();
        if ($isCoordenacao) {
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
        if ($podePacientes) {
            $pacientes = Paciente::query()
                ->when($this->buscaPaciente !== '', function ($q): void {
                    $termo = '%'.trim($this->buscaPaciente).'%';
                    $q->where(fn ($sub) => $sub->where('nome', 'like', $termo)->orWhere('cns', 'like', $termo)->orWhere('cpf', 'like', $termo)->orWhere('cid_principal', 'like', $termo));
                })
                ->latest('id')
                ->limit(50)
                ->get();
        }

        $procedimentosSigtap = collect();
        if ($podeSigtap) {
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
        if ($isCoordenacao) {
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

        return view('livewire.configuracoes-sistema', [
            'abasPermitidas' => $this->abasPermitidas(),
            'salas' => $salas,
            'usuarios' => $usuarios,
            'pacientes' => $pacientes,
            'procedimentosSigtap' => $procedimentosSigtap,
            'logsAuditoria' => $logsAuditoria,
            'logInspecionado' => $logInspecionado,
        ]);
    }
}
