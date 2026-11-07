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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Configurações e Cadastros Base — CER Flow')]
class ConfiguracoesSistema extends Component
{
    public string $abaAtiva = 'pacientes';

    // --- Formulário de Sala ---
    public ?int $salaEditandoId = null;

    public string $sala_nome = '';

    public string $sala_tipo = 'CONSULTORIO';

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
    public string $sig_codigo = '';

    public string $sig_descricao = '';

    public string $sig_cbos_csv = '223605, 223810, 223905, 251510';

    public int $sig_idade_minima = 0;

    public int $sig_idade_maxima = 130;

    public string $sig_sexo_permitido = 'AMBOS';

    // --- Formulário Meu Perfil (Senha e PIN) ---
    public string $nova_senha = '';

    public string $novo_pin = '';

    public function mount(): void
    {
        $user = auth()->user();
        abort_if($user === null, 403);

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
            return ['salas', 'profissionais', 'pacientes', 'sigtap', 'auditoria', 'perfil'];
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
    public function salvarSala(): void
    {
        abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);

        $dados = $this->validate([
            'sala_nome' => ['required', 'string', 'max:100'],
            'sala_tipo' => ['required', Rule::in(['CONSULTORIO', 'SENSORIAL', 'AVD', 'PEDIATRIA', 'ANEXO'])],
        ]);

        Sala::updateOrCreate(
            ['id' => $this->salaEditandoId],
            [
                'nome' => trim($dados['sala_nome']),
                'tipo' => $dados['sala_tipo'],
                'ativo' => true,
            ]
        );

        $this->reset(['salaEditandoId', 'sala_nome']);
        $this->sala_tipo = 'CONSULTORIO';
        session()->flash('sucesso_config', 'Sala/espaço salvo com sucesso.');
    }

    public function alternarStatusSala(int $salaId): void
    {
        abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);

        $sala = Sala::findOrFail($salaId);
        $sala->update(['ativo' => ! $sala->ativo]);
    }

    // =========================================================================
    // 2. GESTÃO DE PACIENTES DO CER (Recepção / Coordenação)
    // =========================================================================
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

        session()->flash('sucesso_config', 'Paciente cadastrado/atualizado com sucesso.');
    }

    // =========================================================================
    // 3. GESTÃO DE USUÁRIOS E PROFISSIONAIS DE SAÚDE (Coordenação)
    // =========================================================================
    public function salvarUsuarioProfissional(): void
    {
        abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);

        $this->prof_cpf = preg_replace('/\D/', '', $this->prof_cpf) ?? '';
        $this->prof_cns = preg_replace('/\D/', '', $this->prof_cns) ?? '';

        $regras = [
            'usr_name' => ['required', 'string', 'max:255'],
            'usr_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'usr_password' => ['required', 'string', 'min:6'],
            'usr_role' => ['required', Rule::in(['coordenacao', 'recepcao', 'terapeuta', 'faturamento'])],
        ];

        if ($this->vincular_profissional) {
            $regras['prof_cpf'] = ['required', 'string', 'digits:11', 'unique:profissionais,cpf'];
            $regras['prof_cns'] = ['required', 'string', 'digits:15', new CnsValido, 'unique:profissionais,cns'];
            $regras['prof_conselho'] = ['required', 'string', 'max:50'];
            $regras['prof_cbo'] = ['required', 'string', 'max:10'];
            $regras['prof_especialidade'] = ['required', 'string', 'max:100'];
            $regras['prof_pin'] = ['required', 'string', 'min:4', 'max:12'];
        }

        $this->validate($regras);

        DB::transaction(function (): void {
            $novoUser = User::create([
                'name' => trim($this->usr_name),
                'email' => strtolower(trim($this->usr_email)),
                'password' => Hash::make($this->usr_password),
                'ativo' => true,
            ]);

            $novoUser->syncRoles([$this->usr_role]);

            if ($this->vincular_profissional) {
                Profissional::create([
                    'user_id' => $novoUser->id,
                    'nome' => trim($this->usr_name),
                    'cpf' => $this->prof_cpf,
                    'cns' => $this->prof_cns,
                    'registro_conselho' => trim($this->prof_conselho),
                    'cbo_padrao' => trim($this->prof_cbo),
                    'especialidade' => trim($this->prof_especialidade),
                    'pin_assinatura_hash' => Hash::make($this->prof_pin),
                    'ativo' => true,
                ]);
            }
        });

        $this->reset([
            'usr_name',
            'usr_email',
            'usr_password',
            'prof_cpf',
            'prof_cns',
            'prof_conselho',
            'prof_pin',
        ]);

        session()->flash('sucesso_config', 'Usuário e credenciais profissionais criados com sucesso.');
    }

    public function alternarStatusUsuario(int $userId): void
    {
        abort_unless(auth()->user()?->hasRole('coordenacao') === true, 403);

        if ($userId === (int) auth()->id()) {
            return;
        }

        $usuario = User::findOrFail($userId);
        $novoStatus = ! $usuario->ativo;
        $usuario->update(['ativo' => $novoStatus]);

        if ($usuario->profissional !== null) {
            $usuario->profissional->update(['ativo' => $novoStatus]);
        }
    }

    // =========================================================================
    // 4. PARAMETRIZAÇÃO SIGTAP X CBO (Faturamento / Coordenação)
    // =========================================================================
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

        $this->reset(['sig_codigo', 'sig_descricao']);
        session()->flash('sucesso_config', 'Procedimento SIGTAP parametrizado com sucesso.');
    }

    // =========================================================================
    // 5. MEU PERFIL: SENHA E PIN DE ASSINATURA (Todos os usuários)
    // =========================================================================
    public function atualizarCredenciaisProprias(): void
    {
        $user = auth()->user();
        abort_if($user === null, 403);

        $this->validate([
            'nova_senha' => ['nullable', 'string', 'min:6'],
            'novo_pin' => ['nullable', 'string', 'min:4', 'max:12'],
        ]);

        if ($this->nova_senha !== '') {
            $user->update(['password' => Hash::make($this->nova_senha)]);
        }

        if ($this->novo_pin !== '' && $user->profissional !== null) {
            $user->profissional->update([
                'pin_assinatura_hash' => Hash::make($this->novo_pin),
            ]);
        }

        $this->reset(['nova_senha', 'novo_pin']);
        session()->flash('sucesso_config', 'Suas credenciais de acesso/assinatura foram atualizadas.');
    }

    public function render(): View
    {
        $user = auth()->user();
        $isCoordenacao = $user?->hasRole('coordenacao') === true;
        $podePacientes = $user?->hasAnyRole(['recepcao', 'coordenacao']) === true;
        $podeSigtap = $user?->hasAnyRole(['faturamento', 'coordenacao']) === true;

        return view('livewire.configuracoes-sistema', [
            'abasPermitidas' => $this->abasPermitidas(),
            'salas' => $isCoordenacao ? Sala::query()->orderBy('nome')->get() : collect(),
            'usuarios' => $isCoordenacao ? User::query()->with(['roles', 'profissional'])->orderBy('name')->get() : collect(),
            'pacientes' => $podePacientes ? Paciente::query()->latest('id')->limit(30)->get() : collect(),
            'procedimentosSigtap' => $podeSigtap ? ProcedimentoSigtap::query()->orderBy('codigo')->get() : collect(),
            'logsAuditoria' => $isCoordenacao ? LogAuditoria::query()->with('user')->latest('created_at')->limit(40)->get() : collect(),
        ]);
    }
}
