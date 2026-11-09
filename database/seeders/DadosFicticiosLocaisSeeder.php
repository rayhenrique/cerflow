<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Agendamento;
use App\Models\ChamadaPainel;
use App\Models\ConfiguracaoEstabelecimento;
use App\Models\EvolucaoAdendo;
use App\Models\EvolucaoClinica;
use App\Models\LogAuditoria;
use App\Models\Paciente;
use App\Models\ProcedimentoSigtap;
use App\Models\ProducaoBpai;
use App\Models\Profissional;
use App\Models\Sala;
use App\Models\User;
use App\Rules\CnsValido;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class DadosFicticiosLocaisSeeder extends Seeder
{
    /**
     * Executa a população de dados fictícios para testes locais de todos os módulos.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('BLOQUEIO DE SEGURANÇA: Este seeder contém apenas dados fictícios e NÃO pode ser executado em ambiente de PRODUÇÃO.');

            return;
        }

        $this->command?->info('Iniciando inserção de dados fictícios para testes locais...');

        // 1. Garantir Roles e Permissões básicas
        $this->call([
            RoleAndPermissionSeeder::class,
            ProcedimentoSigtapSeeder::class,
            SalaSeeder::class,
        ]);

        // 2. Estabelecimento e Configurações
        $this->configurarEstabelecimento();

        // 3. Usuários do Sistema para Todos os Perfis
        $usuarios = $this->criarUsuarios();

        // 4. Profissionais de Saúde com PIN de Assinatura
        $profissionais = $this->criarProfissionais($usuarios);

        // 5. Pacientes Fictícios com Patologias do CER
        $pacientes = $this->criarPacientes();

        // 6. Salas e Procedimentos SIGTAP
        $salas = Sala::query()->where('ativo', true)->get()->keyBy('id');
        $procedimentos = ProcedimentoSigtap::query()->where('ativo', true)->get()->keyBy('codigo');

        // 7. Agendamentos (Hoje, Semana e Histórico Mensal)
        $agendamentos = $this->criarAgendamentos($pacientes, $profissionais, $salas);

        // 8. Chamadas do Painel de TV (Painel / Smart TV)
        $this->criarChamadasPainelTv($agendamentos, $usuarios);

        // 9. Prontuário Multiprofissional PEP (Evoluções e Adendos)
        $evolucoes = $this->criarEvolucoesClinicas($agendamentos, $pacientes, $profissionais, $procedimentos);

        // 10. Faturamento SUS (Produção BPA-i)
        $this->criarProducoesBpai($evolucoes, $procedimentos);

        // 11. Logs de Auditoria
        $this->criarLogsAuditoria($usuarios);

        // 12. Limpar todos os caches
        Cache::flush();

        $this->command?->info('Dados fictícios gerados com sucesso!');
        $this->command?->table(
            ['Perfil', 'Email', 'Senha', 'PIN Assinatura PEP'],
            [
                ['Coordenação', 'coordenacao@cerflow.gov.br', 'password', '1234'],
                ['Recepção', 'recepcao@cerflow.gov.br', 'password', '—'],
                ['Fisioterapia', 'fisioterapeuta@cerflow.gov.br', 'password', '1234'],
                ['Fonoaudiologia', 'fonoaudiologo@cerflow.gov.br', 'password', '1234'],
                ['Terapia Ocupacional', 'terapeuta.ocupacional@cerflow.gov.br', 'password', '1234'],
                ['Psicologia', 'psicologo@cerflow.gov.br', 'password', '1234'],
                ['Médico Neurologista', 'medico@cerflow.gov.br', 'password', '1234'],
                ['Faturamento SUS', 'faturamento@cerflow.gov.br', 'password', '—'],
            ]
        );
    }

    private function configurarEstabelecimento(): void
    {
        $config = ConfiguracaoEstabelecimento::first();
        if (! $config) {
            $config = new ConfiguracaoEstabelecimento;
        }

        $config->fill([
            'cnes' => '2000001',
            'codigo_ibge_padrao' => '270430', // Maceió/AL
            'painel_voz_ativa' => true,
            'painel_volume' => 85,
            'painel_tempo_destaque_segundos' => 15,
            'painel_quantidade_historico' => 5,
            'painel_modo_exibicao_nome' => 'COMPLETO',
            'painel_token_acesso' => 'TV-CER-DIGITAL-TOKEN-DEMO',
        ]);
        $config->save();
    }

    /**
     * @return array<string, User>
     */
    private function criarUsuarios(): array
    {
        $senhaHash = Hash::make('password');

        $lista = [
            'coordenacao' => [
                'name' => 'Dra. Beatriz Albuquerque (Coordenação)',
                'email' => 'coordenacao@cerflow.gov.br',
                'role' => 'coordenacao',
            ],
            'recepcao' => [
                'name' => 'Mariana Costa (Recepção)',
                'email' => 'recepcao@cerflow.gov.br',
                'role' => 'recepcao',
            ],
            'fisioterapeuta' => [
                'name' => 'Dra. Camila Rocha (Fisioterapia)',
                'email' => 'fisioterapeuta@cerflow.gov.br',
                'role' => 'terapeuta',
            ],
            'fonoaudiologo' => [
                'name' => 'Dr. Lucas Martins (Fonoaudiologia)',
                'email' => 'fonoaudiologo@cerflow.gov.br',
                'role' => 'terapeuta',
            ],
            'terapeuta_ocupacional' => [
                'name' => 'Dra. Juliana Mendes (Terapia Ocupacional)',
                'email' => 'terapeuta.ocupacional@cerflow.gov.br',
                'role' => 'terapeuta',
            ],
            'psicologo' => [
                'name' => 'Dr. Rafael Silveira (Psicologia)',
                'email' => 'psicologo@cerflow.gov.br',
                'role' => 'terapeuta',
            ],
            'medico' => [
                'name' => 'Dr. Marcos Vinicius (Neuropediatra)',
                'email' => 'medico@cerflow.gov.br',
                'role' => 'terapeuta',
            ],
            'faturamento' => [
                'name' => 'Carlos Eduardo (Faturamento SUS)',
                'email' => 'faturamento@cerflow.gov.br',
                'role' => 'faturamento',
            ],
        ];

        $usuarios = [];
        foreach ($lista as $chave => $dados) {
            $user = User::updateOrCreate(
                ['email' => $dados['email']],
                [
                    'name' => $dados['name'],
                    'password' => $senhaHash,
                    'ativo' => true,
                    'email_verified_at' => now(),
                    'ultima_versao_novidades_vista' => '1.17.1',
                ]
            );

            $user->syncRoles([$dados['role']]);
            $usuarios[$chave] = $user;
        }

        return $usuarios;
    }

    /**
     * @param  array<string, User>  $usuarios
     * @return array<string, Profissional>
     */
    private function criarProfissionais(array $usuarios): array
    {
        $pinHash = Hash::make('1234');

        $listaProfissionais = [
            'fisioterapeuta' => [
                'user_key' => 'fisioterapeuta',
                'nome' => 'Dra. Camila Rocha',
                'cpf' => $this->gerarCpfValido('111222333'),
                'cns' => CnsValido::gerarDefinitivo('11122233301'),
                'registro_conselho' => 'CREFITO 23412-F/AL',
                'cbo_padrao' => '223605',
                'especialidade' => 'Fisioterapia Neurofuncional',
            ],
            'fonoaudiologo' => [
                'user_key' => 'fonoaudiologo',
                'nome' => 'Dr. Lucas Martins',
                'cpf' => $this->gerarCpfValido('222333444'),
                'cns' => CnsValido::gerarDefinitivo('22233344401'),
                'registro_conselho' => 'CRFa 4-8921/AL',
                'cbo_padrao' => '223810',
                'especialidade' => 'Fonoaudiologia e Linguagem',
            ],
            'terapeuta_ocupacional' => [
                'user_key' => 'terapeuta_ocupacional',
                'nome' => 'Dra. Juliana Mendes',
                'cpf' => $this->gerarCpfValido('333444555'),
                'cns' => CnsValido::gerarDefinitivo('33344455501'),
                'registro_conselho' => 'CREFITO 19834-TO/AL',
                'cbo_padrao' => '223905',
                'especialidade' => 'Terapia Ocupacional e Integração Sensorial',
            ],
            'psicologo' => [
                'user_key' => 'psicologo',
                'nome' => 'Dr. Rafael Silveira',
                'cpf' => $this->gerarCpfValido('444555666'),
                'cns' => CnsValido::gerarDefinitivo('44455566601'),
                'registro_conselho' => 'CRP 15/04321',
                'cbo_padrao' => '251510',
                'especialidade' => 'Psicologia Clínica e Comportamental',
            ],
            'medico' => [
                'user_key' => 'medico',
                'nome' => 'Dr. Marcos Vinicius',
                'cpf' => $this->gerarCpfValido('555666777'),
                'cns' => CnsValido::gerarDefinitivo('55566677701'),
                'registro_conselho' => 'CRM 6543/AL',
                'cbo_padrao' => '225112',
                'especialidade' => 'Neurologia Pediátrica',
            ],
        ];

        $profissionais = [];
        foreach ($listaProfissionais as $chave => $dados) {
            $user = $usuarios[$dados['user_key']];

            $prof = Profissional::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nome' => $dados['nome'],
                    'cpf' => $dados['cpf'],
                    'cns' => $dados['cns'],
                    'registro_conselho' => $dados['registro_conselho'],
                    'cbo_padrao' => $dados['cbo_padrao'],
                    'especialidade' => $dados['especialidade'],
                    'pin_assinatura_hash' => $pinHash,
                    'ativo' => true,
                ]
            );

            $profissionais[$chave] = $prof;
        }

        return $profissionais;
    }

    /**
     * @return array<int, Paciente>
     */
    private function criarPacientes(): array
    {
        $dadosPacientes = [
            [
                'nome' => 'Pedro Henrique de Souza',
                'cpf_base' => '701111222',
                'data_nascimento' => '2019-04-12',
                'sexo' => 'M',
                'nome_mae' => 'Maria Aparecida de Souza',
                'ubs_referencia' => 'UBS Centro',
                'nome_acs' => 'Carla Pereira',
                'cid_principal' => 'F84.0', // TEA
                'cid_secundario' => 'F80.0',
                'modalidades' => ['INTELECTUAL'],
                'endereco' => 'Rua do Sol, 120, Centro',
                'bairro' => 'Centro',
                'telefone' => '82988112233',
            ],
            [
                'nome' => 'Maria Clara Santos',
                'cpf_base' => '702222333',
                'data_nascimento' => '2021-08-20',
                'sexo' => 'F',
                'nome_mae' => 'Juliana dos Santos',
                'ubs_referencia' => 'UBS Benedito Bentes',
                'nome_acs' => 'João Silva',
                'cid_principal' => 'G80.0', // Paralisia Cerebral Espástica
                'cid_secundario' => null,
                'modalidades' => ['FISICA', 'INTELECTUAL'],
                'endereco' => 'Av. Pratagy, 450, Qd 12',
                'bairro' => 'Benedito Bentes',
                'telefone' => '82988223344',
            ],
            [
                'nome' => 'José Carlos de Oliveira',
                'cpf_base' => '703333444',
                'data_nascimento' => '1963-02-14',
                'sexo' => 'M',
                'nome_mae' => 'Francisca de Oliveira',
                'ubs_referencia' => 'UBS Jacintinho',
                'nome_acs' => 'Renata Barros',
                'cid_principal' => 'I69.4', // Sequelas de AVC Hemiparesia
                'cid_secundario' => 'I10',
                'modalidades' => ['FISICA'],
                'endereco' => 'Travessa São José, 78',
                'bairro' => 'Jacintinho',
                'telefone' => '82988334455',
            ],
            [
                'nome' => 'Ana Beatriz de Lima',
                'cpf_base' => '704444555',
                'data_nascimento' => '2017-11-05',
                'sexo' => 'F',
                'nome_mae' => 'Roberta de Lima',
                'ubs_referencia' => 'UBS Tabuleiro',
                'nome_acs' => 'Marcos Lima',
                'cid_principal' => 'Q90.9', // Síndrome de Down
                'cid_secundario' => null,
                'modalidades' => ['INTELECTUAL', 'FISICA'],
                'endereco' => 'Rua Rotary, 310',
                'bairro' => 'Tabuleiro dos Martins',
                'telefone' => '82988445566',
            ],
            [
                'nome' => 'Arthur Gabriel Silva',
                'cpf_base' => '705555666',
                'data_nascimento' => '2020-03-18',
                'sexo' => 'M',
                'nome_mae' => 'Patricia da Silva',
                'ubs_referencia' => 'UBS Ponta Grossa',
                'nome_acs' => 'Patricia Ramos',
                'cid_principal' => 'H90.3', // Perda Auditiva Bilateral
                'cid_secundario' => null,
                'modalidades' => ['AUDITIVA'],
                'endereco' => 'Rua Formosa, 89',
                'bairro' => 'Ponta Grossa',
                'telefone' => '82988556677',
            ],
            [
                'nome' => 'Davi Lucas Ferreira',
                'cpf_base' => '706666777',
                'data_nascimento' => '2018-09-22',
                'sexo' => 'M',
                'nome_mae' => 'Vanessa Ferreira',
                'ubs_referencia' => 'UBS Trapiche',
                'nome_acs' => 'Lucia Neves',
                'cid_principal' => 'F83', // Atraso Global do Desenvolvimento
                'cid_secundario' => null,
                'modalidades' => ['FISICA', 'INTELECTUAL'],
                'endereco' => 'Av. Siqueira Campos, 230',
                'bairro' => 'Trapiche da Barra',
                'telefone' => '82988667788',
            ],
            [
                'nome' => 'Helena Vitória Moreira',
                'cpf_base' => '707777888',
                'data_nascimento' => '2015-12-10',
                'sexo' => 'F',
                'nome_mae' => 'Camila Moreira',
                'ubs_referencia' => 'UBS Farol',
                'nome_acs' => 'Diego Torres',
                'cid_principal' => 'Q05.9', // Mielomeningocele
                'cid_secundario' => 'N31.9',
                'modalidades' => ['FISICA'],
                'endereco' => 'Rua Dom Santino, 45',
                'bairro' => 'Farol',
                'telefone' => '82988778899',
            ],
            [
                'nome' => 'Laura Sofia Albuquerque',
                'cpf_base' => '708888999',
                'data_nascimento' => '2022-06-30',
                'sexo' => 'F',
                'nome_mae' => 'Denise Albuquerque',
                'ubs_referencia' => 'UBS Jatiúca',
                'nome_acs' => 'Fabiana Costa',
                'cid_principal' => 'Q02', // Microcefalia
                'cid_secundario' => null,
                'modalidades' => ['FISICA', 'INTELECTUAL'],
                'endereco' => 'Rua São Paulo, 102',
                'bairro' => 'Jatiúca',
                'telefone' => '82988889900',
            ],
            [
                'nome' => 'Miguel Alves Ramos',
                'cpf_base' => '709999000',
                'data_nascimento' => '1980-05-15',
                'sexo' => 'M',
                'nome_mae' => 'Tereza Alves Ramos',
                'ubs_referencia' => 'UBS Cruz das Almas',
                'nome_acs' => 'Gilberto Dias',
                'cid_principal' => 'G82.2', // Lesão Medular Paraplegia
                'cid_secundario' => null,
                'modalidades' => ['FISICA'],
                'endereco' => 'Av. Brigadeiro Eduardo Gomes, 560',
                'bairro' => 'Cruz das Almas',
                'telefone' => '82988990011',
            ],
            [
                'nome' => 'Sofia Rodrigues Melo',
                'cpf_base' => '710111222',
                'data_nascimento' => '2016-01-25',
                'sexo' => 'F',
                'nome_mae' => 'Tatiana Rodrigues',
                'ubs_referencia' => 'UBS Mangabeiras',
                'nome_acs' => 'Claudia Vieira',
                'cid_principal' => 'H54.2', // Baixa Visão / Visão Subnormal
                'cid_secundario' => null,
                'modalidades' => ['VISUAL'],
                'endereco' => 'Rua João Davino, 340',
                'bairro' => 'Mangabeiras',
                'telefone' => '82988001122',
            ],
            [
                'nome' => 'Bernardo Silva Duarte',
                'cpf_base' => '711222333',
                'data_nascimento' => '1997-09-08',
                'sexo' => 'M',
                'nome_mae' => 'Marta Silva Duarte',
                'ubs_referencia' => 'UBS Benedito Bentes',
                'nome_acs' => 'João Silva',
                'cid_principal' => 'Z89.5', // Amputação Transtibial
                'cid_secundario' => null,
                'modalidades' => ['FISICA'],
                'endereco' => 'Conjunto Guaxuma, Bloco 4',
                'bairro' => 'Benedito Bentes',
                'telefone' => '82988113355',
            ],
            [
                'nome' => 'Valentina Guimarães Pires',
                'cpf_base' => '712333444',
                'data_nascimento' => '2019-10-14',
                'sexo' => 'F',
                'nome_mae' => 'Luciana Guimarães',
                'ubs_referencia' => 'UBS Tabuleiro',
                'nome_acs' => 'Marcos Lima',
                'cid_principal' => 'G80.3', // Paralisia Cerebral Discinética
                'cid_secundario' => null,
                'modalidades' => ['FISICA', 'INTELECTUAL'],
                'endereco' => 'Rua Santa Luzia, 88',
                'bairro' => 'Tabuleiro dos Martins',
                'telefone' => '82988224466',
            ],
        ];

        $pacientes = [];
        foreach ($dadosPacientes as $item) {
            $cpf = $this->gerarCpfValido($item['cpf_base']);
            $cns = CnsValido::gerarDefinitivo($item['cpf_base'].'01');

            $paciente = Paciente::updateOrCreate(
                ['cpf' => $cpf],
                [
                    'nome' => $item['nome'],
                    'cns' => $cns,
                    'data_nascimento' => $item['data_nascimento'],
                    'sexo' => $item['sexo'],
                    'nome_mae' => $item['nome_mae'],
                    'municipio_ibge' => '270430',
                    'ubs_referencia' => $item['ubs_referencia'],
                    'nome_acs' => $item['nome_acs'],
                    'telefone_contato' => $item['telefone'],
                    'endereco' => $item['endereco'],
                    'bairro' => $item['bairro'],
                    'cid_principal' => $item['cid_principal'],
                    'cid_secundario' => $item['cid_secundario'],
                    'modalidades_reabilitacao' => $item['modalidades'],
                    'origem_cadastro' => 'MANUAL',
                    'ativo' => true,
                ]
            );

            $pacientes[] = $paciente;
        }

        return $pacientes;
    }

    /**
     * @param  array<int, Paciente>  $pacientes
     * @param  array<string, Profissional>  $profissionais
     * @param  Collection<int, Sala>  $salas
     * @return array<int, Agendamento>
     */
    private function criarAgendamentos(array $pacientes, array $profissionais, $salas): array
    {
        $hoje = CarbonImmutable::now();
        $agendamentosCriados = [];

        $diasMapeados = [
            1 => 'SEGUNDA',
            2 => 'TERCA',
            3 => 'QUARTA',
            4 => 'QUINTA',
            5 => 'SEXTA',
        ];

        // -------------------------------------------------------------
        // A. AGENDAMENTOS PARA HOJE (Para testar Recepção e Chamadas TV)
        // -------------------------------------------------------------
        $gradeHoje = [
            // Status PRESENTE (prontos para serem chamados na TV pelo terapeuta ou recepção)
            [
                'paciente_idx' => 0, // Pedro Henrique
                'prof' => $profissionais['terapeuta_ocupacional'],
                'sala_id' => 1, // Sala Integração Sensorial
                'hora_inicio' => '08:00',
                'hora_fim' => '08:30',
                'status' => 'PRESENTE',
                'tipo' => 'INDIVIDUAL',
            ],
            [
                'paciente_idx' => 1, // Maria Clara
                'prof' => $profissionais['fisioterapeuta'],
                'sala_id' => 6, // Ginásio Fisioterapia Pediátrica
                'hora_inicio' => '08:30',
                'hora_fim' => '09:00',
                'status' => 'PRESENTE',
                'tipo' => 'INDIVIDUAL',
            ],
            [
                'paciente_idx' => 2, // José Carlos
                'prof' => $profissionais['psicologo'],
                'sala_id' => 3, // Consultório Fono/Psico
                'hora_inicio' => '09:00',
                'hora_fim' => '09:30',
                'status' => 'PRESENTE',
                'tipo' => 'INDIVIDUAL',
            ],
            // Status AGENDADO (prontos para a Recepção testar "Confirmar Presença")
            [
                'paciente_idx' => 3, // Ana Beatriz
                'prof' => $profissionais['fonoaudiologo'],
                'sala_id' => 3,
                'hora_inicio' => '09:30',
                'hora_fim' => '10:00',
                'status' => 'AGENDADO',
                'tipo' => 'INDIVIDUAL',
            ],
            [
                'paciente_idx' => 4, // Arthur Gabriel
                'prof' => $profissionais['fonoaudiologo'],
                'sala_id' => 3,
                'hora_inicio' => '10:00',
                'hora_fim' => '10:30',
                'status' => 'AGENDADO',
                'tipo' => 'INDIVIDUAL',
            ],
            [
                'paciente_idx' => 5, // Davi Lucas
                'prof' => $profissionais['medico'],
                'sala_id' => 5, // Consultório Avaliação
                'hora_inicio' => '10:30',
                'hora_fim' => '11:00',
                'status' => 'AGENDADO',
                'tipo' => 'INDIVIDUAL',
            ],
            // Status ATENDIDO (atendimentos concluídos hoje com evolução no PEP)
            [
                'paciente_idx' => 6, // Helena Vitória
                'prof' => $profissionais['fisioterapeuta'],
                'sala_id' => 6,
                'hora_inicio' => '14:00',
                'hora_fim' => '14:30',
                'status' => 'ATENDIDO',
                'tipo' => 'INDIVIDUAL',
            ],
            [
                'paciente_idx' => 7, // Laura Sofia
                'prof' => $profissionais['terapeuta_ocupacional'],
                'prof_segundo' => $profissionais['fisioterapeuta'],
                'sala_id' => 1,
                'hora_inicio' => '14:30',
                'hora_fim' => '15:15',
                'status' => 'ATENDIDO',
                'tipo' => 'COMPARTILHADO',
            ],
            // Faltas (para testar estatísticas do dia e justificativas)
            [
                'paciente_idx' => 8, // Miguel Alves
                'prof' => $profissionais['fisioterapeuta'],
                'sala_id' => 7, // Ginásio Adulto
                'hora_inicio' => '15:00',
                'hora_fim' => '15:30',
                'status' => 'FALTA_JUSTIFICADA',
                'tipo' => 'INDIVIDUAL',
                'obs' => 'Genitora informou consulta médica hospitalar.',
            ],
            [
                'paciente_idx' => 9, // Sofia Rodrigues
                'prof' => $profissionais['fisioterapeuta'],
                'sala_id' => 7,
                'hora_inicio' => '15:30',
                'hora_fim' => '16:00',
                'status' => 'FALTA_INJUSTIFICADA',
                'tipo' => 'INDIVIDUAL',
            ],
        ];

        $diaSemanaEnumHoje = $diasMapeados[$hoje->dayOfWeekIso] ?? 'SEGUNDA';

        foreach ($gradeHoje as $item) {
            $paciente = $pacientes[$item['paciente_idx']];

            $agendamento = Agendamento::create([
                'paciente_id' => $paciente->id,
                'sala_id' => $item['sala_id'],
                'data_atendimento' => $hoje->toDateString(),
                'hora_inicio' => $item['hora_inicio'],
                'hora_fim' => $item['hora_fim'],
                'dia_semana' => $diaSemanaEnumHoje,
                'tipo_atendimento' => $item['tipo'],
                'status' => $item['status'],
                'recorrente' => true,
                'observacao' => $item['obs'] ?? null,
            ]);

            $agendamento->profissionais()->sync([
                $item['prof']->id => ['papel' => 'PRINCIPAL'],
            ]);

            if (isset($item['prof_segundo'])) {
                $agendamento->profissionais()->attach($item['prof_segundo']->id, ['papel' => 'COMPARTILHADO']);
            }

            $agendamentosCriados[] = $agendamento;
        }

        // -------------------------------------------------------------
        // B. AGENDAMENTOS PARA A GRADE DA SEMANA (Segunda a Sexta)
        // -------------------------------------------------------------
        $inicioSemana = $hoje->startOfWeek();
        for ($diaIso = 1; $diaIso <= 5; $diaIso++) {
            $dataDia = $inicioSemana->addDays($diaIso - 1);
            $nomeDia = $diasMapeados[$diaIso];

            // Pular se coincidir com hoje para não duplicar horários
            if ($dataDia->toDateString() === $hoje->toDateString()) {
                continue;
            }

            $slots = [
                ['hora' => '08:00', 'pac_idx' => 0, 'prof_key' => 'fisioterapeuta', 'sala_id' => 6],
                ['hora' => '08:30', 'pac_idx' => 1, 'prof_key' => 'terapeuta_ocupacional', 'sala_id' => 1],
                ['hora' => '09:00', 'pac_idx' => 2, 'prof_key' => 'fonoaudiologo', 'sala_id' => 3],
                ['hora' => '09:30', 'pac_idx' => 3, 'prof_key' => 'psicologo', 'sala_id' => 3],
                ['hora' => '10:00', 'pac_idx' => 4, 'prof_key' => 'medico', 'sala_id' => 5],
                ['hora' => '14:00', 'pac_idx' => 5, 'prof_key' => 'fisioterapeuta', 'sala_id' => 7],
                ['hora' => '14:30', 'pac_idx' => 6, 'prof_key' => 'terapeuta_ocupacional', 'sala_id' => 1],
                ['hora' => '15:00', 'pac_idx' => 7, 'prof_key' => 'fonoaudiologo', 'sala_id' => 3],
            ];

            foreach ($slots as $slot) {
                $paciente = $pacientes[$slot['pac_idx']];
                $prof = $profissionais[$slot['prof_key']];
                $horaInicio = $slot['hora'];
                $horaFim = CarbonImmutable::createFromFormat('H:i', $horaInicio)->addMinutes(30)->format('H:i');

                // Dias passados = ATENDIDO, dias futuros = AGENDADO
                $status = $dataDia->isPast() ? 'ATENDIDO' : 'AGENDADO';

                $ag = Agendamento::create([
                    'paciente_id' => $paciente->id,
                    'sala_id' => $slot['sala_id'],
                    'data_atendimento' => $dataDia->toDateString(),
                    'hora_inicio' => $horaInicio,
                    'hora_fim' => $horaFim,
                    'dia_semana' => $nomeDia,
                    'tipo_atendimento' => 'INDIVIDUAL',
                    'status' => $status,
                    'recorrente' => true,
                ]);

                $ag->profissionais()->sync([
                    $prof->id => ['papel' => 'PRINCIPAL'],
                ]);

                $agendamentosCriados[] = $ag;
            }
        }

        // -------------------------------------------------------------
        // C. HISTÓRICO DO MÊS PASSADO E RECENTE (Para Dashboard e Gráficos)
        // -------------------------------------------------------------
        $mesAtual = $hoje->startOfMonth();
        $mesPassado = $hoje->subMonth()->startOfMonth();

        for ($i = 1; $i <= 30; $i++) {
            $dataHist = $i <= 15
                ? $mesPassado->addDays($i)
                : $mesAtual->addDays($i - 15);

            if ($dataHist->isWeekend() || $dataHist->isFuture()) {
                continue;
            }

            $diaIso = $dataHist->dayOfWeekIso;
            $nomeDia = $diasMapeados[$diaIso] ?? 'SEGUNDA';
            $pac = $pacientes[$i % count($pacientes)];
            $profKeys = array_keys($profissionais);
            $prof = $profissionais[$profKeys[$i % count($profKeys)]];

            $statusHist = 'ATENDIDO';
            if ($i % 7 === 0) {
                $statusHist = 'FALTA_INJUSTIFICADA';
            } elseif ($i % 9 === 0) {
                $statusHist = 'FALTA_JUSTIFICADA';
            }

            $agHist = Agendamento::create([
                'paciente_id' => $pac->id,
                'sala_id' => ($i % 8) + 1,
                'data_atendimento' => $dataHist->toDateString(),
                'hora_inicio' => sprintf('%02d:00', 8 + ($i % 8)),
                'hora_fim' => sprintf('%02d:30', 8 + ($i % 8)),
                'dia_semana' => $nomeDia,
                'tipo_atendimento' => ($i % 5 === 0) ? 'COMPARTILHADO' : 'INDIVIDUAL',
                'status' => $statusHist,
                'recorrente' => true,
                'created_at' => $dataHist->subDays(7),
                'updated_at' => $dataHist,
            ]);

            $agHist->profissionais()->sync([
                $prof->id => ['papel' => 'PRINCIPAL'],
            ]);

            $agendamentosCriados[] = $agHist;
        }

        return $agendamentosCriados;
    }

    /**
     * @param  array<int, Agendamento>  $agendamentos
     * @param  array<string, User>  $usuarios
     */
    private function criarChamadasPainelTv(array $agendamentos, array $usuarios): void
    {
        $agendamentosHoje = array_filter(
            $agendamentos,
            fn (Agendamento $ag) => $ag->data_atendimento->toDateString() === now()->toDateString()
        );

        $agendamentosHoje = array_values($agendamentosHoje);
        if (count($agendamentosHoje) === 0) {
            return;
        }

        $chamador = $usuarios['recepcao'] ?? $usuarios['coordenacao'];

        // 1. Chamada ativa em destaque na TV (status: CHAMANDO)
        $primeiro = $agendamentosHoje[0];
        $primeiroProf = $primeiro->profissionais->first();

        ChamadaPainel::create([
            'agendamento_id' => $primeiro->id,
            'paciente_id' => $primeiro->paciente_id,
            'sala_id' => $primeiro->sala_id,
            'profissional_id' => $primeiroProf?->id,
            'chamado_por_id' => $chamador->id,
            'paciente_nome' => $primeiro->paciente->nome,
            'sala_nome' => $primeiro->sala->nome,
            'profissional_nome' => $primeiroProf?->nome ?? 'Equipe Multiprofissional',
            'tipo' => 'CHAMADA',
            'status' => 'CHAMANDO',
            'chamado_em' => now()->subMinutes(2),
        ]);

        // 2. Histórico recente de chamadas na TV (status: CONCLUIDA)
        $historico = array_slice($agendamentosHoje, 1, 4);
        $minutosAtras = [12, 28, 45, 62];

        foreach ($historico as $idx => $ag) {
            $prof = $ag->profissionais->first();
            $tempo = now()->subMinutes($minutosAtras[$idx] ?? 30);

            ChamadaPainel::create([
                'agendamento_id' => $ag->id,
                'paciente_id' => $ag->paciente_id,
                'sala_id' => $ag->sala_id,
                'profissional_id' => $prof?->id,
                'chamado_por_id' => $chamador->id,
                'paciente_nome' => $ag->paciente->nome,
                'sala_nome' => $ag->sala->nome,
                'profissional_nome' => $prof?->nome ?? 'Equipe Multiprofissional',
                'tipo' => 'CHAMADA',
                'status' => 'CONCLUIDA',
                'chamado_em' => $tempo,
            ]);
        }
    }

    /**
     * @param  array<int, Agendamento>  $agendamentos
     * @param  array<int, Paciente>  $pacientes
     * @param  array<string, Profissional>  $profissionais
     * @param  Collection<string, ProcedimentoSigtap>  $procedimentos
     * @return array<int, EvolucaoClinica>
     */
    private function criarEvolucoesClinicas(array $agendamentos, array $pacientes, array $profissionais, $procedimentos): array
    {
        $evolucoes = [];

        $atendidos = array_filter(
            $agendamentos,
            fn (Agendamento $ag) => $ag->status === 'ATENDIDO'
        );

        foreach ($atendidos as $ag) {
            $prof = $ag->profissionais->first();
            if (! $prof) {
                continue;
            }

            $horasDiff = $ag->data_atendimento->diffInHours(now());
            $assinadoEm = $ag->data_atendimento->setTime(10, 30);
            $bloqueado = $horasDiff >= 24;

            $evolucao = EvolucaoClinica::create([
                'agendamento_id' => $ag->id,
                'paciente_id' => $ag->paciente_id,
                'profissional_id' => $prof->id,
                'queixa_estado_atual' => 'Paciente comparece à sessão acompanhado pelo responsável. Mostra-se cooperativo e responsivo aos estímulos terapêuticos propostos. Responsável relata boa evolução domiciliar.',
                'conduta_terapeutica' => 'Realizado plano terapêutico individualizado com treino de estabilidade motora, equilíbrio e atividades proprioceptivas. Trabalhado engajamento em tarefas funcionais e orientações para casa.',
                'intercorrencias' => 'Sessão transcorreu sem intercorrências ou queixas de dor.',
                'assinado_em' => $assinadoEm,
                'assinatura_hash' => hash('sha256', "EVOLUCAO_{$ag->id}_{$prof->id}_{$assinadoEm->toIso8601String()}"),
                'bloqueado_edicao' => $bloqueado,
            ]);

            // Se for atendimento antigo, adicionar um adendo clínico de demonstração
            if ($bloqueado && count($evolucoes) === 0) {
                EvolucaoAdendo::create([
                    'evolucao_id' => $evolucao->id,
                    'profissional_id' => $prof->id,
                    'justificativa' => 'Orientação complementar realizada após contato telefônico com a UBS de referência.',
                    'texto_adendo' => 'Adendo: Realizado alinhamento com a equipe da UBS sobre a dispensação da órtese suropodálica do paciente.',
                    'assinado_em' => $assinadoEm->addHours(2),
                ]);
            }

            $evolucoes[] = $evolucao;
        }

        return $evolucoes;
    }

    /**
     * @param  array<int, EvolucaoClinica>  $evolucoes
     * @param  Collection<string, ProcedimentoSigtap>  $procedimentos
     */
    private function criarProducoesBpai(array $evolucoes, $procedimentos): void
    {
        $compAtual = now()->format('Ym');
        $compPassada = now()->subMonth()->format('Ym');

        // Mapear procedimentos padrão
        $procReabFisica = $procedimentos->get('0301070075') ?? $procedimentos->first();
        $procReabTea = $procedimentos->get('0301070113') ?? $procedimentos->first();
        $procFono = $procedimentos->get('0301040044') ?? $procedimentos->first();
        $procPsico = $procedimentos->get('0301080178') ?? $procedimentos->first();

        $listaProc = [$procReabFisica, $procReabTea, $procFono, $procPsico];

        foreach ($evolucoes as $idx => $evo) {
            $proc = $listaProc[$idx % count($listaProc)];
            $comp = $evo->assinado_em->format('Ym');

            $status = 'VALIDADO';
            $msgInconsistencia = null;
            $lote = null;

            if ($idx === 1) {
                $status = 'PENDENTE';
            } elseif ($idx === 2) {
                $status = 'INCONSISTENTE';
                $msgInconsistencia = 'Idade do paciente fora do limite cadastrado na tabela de procedimentos SIGTAP para este procedimento.';
            } elseif ($comp === $compPassada) {
                $status = 'EXPORTADO';
                $lote = "LOTE-BPAI-{$compPassada}-001";
            }

            ProducaoBpai::create([
                'evolucao_id' => $evo->id,
                'paciente_id' => $evo->paciente_id,
                'profissional_id' => $evo->profissional_id,
                'procedimento_id' => $proc->id,
                'cbo_executante' => $evo->profissional->cbo_padrao ?? '223605',
                'cid_atendimento' => $evo->paciente->cid_principal ?? 'F84.0',
                'data_atendimento' => $evo->assinado_em->toDateString(),
                'competencia' => $comp,
                'status_exportacao' => $status,
                'mensagem_inconsistencia' => $msgInconsistencia,
                'lote_exportacao_id' => $lote,
            ]);
        }
    }

    /**
     * @param  array<string, User>  $usuarios
     */
    private function criarLogsAuditoria(array $usuarios): array
    {
        $logs = [
            ['acao' => 'LOGIN', 'tabela' => 'users', 'detalhes' => ['mensagem' => 'Autenticação bem-sucedida no sistema']],
            ['acao' => 'ACESSO_PEP', 'tabela' => 'prontuarios', 'detalhes' => ['mensagem' => 'Visualização de prontuário multiprofissional']],
            ['acao' => 'CHECKIN_RECEPCAO', 'tabela' => 'agendamentos', 'detalhes' => ['mensagem' => 'Paciente marcou presença na recepção']],
            ['acao' => 'CHAMADA_PAINEL_EMITIDA', 'tabela' => 'chamadas_painel', 'detalhes' => ['mensagem' => 'Chamada sonora enviada para TV do saguão']],
            ['acao' => 'EVOLUCAO_ASSINADA', 'tabela' => 'evolucoes_clinicas', 'detalhes' => ['mensagem' => 'Assinatura digital via PIN']],
            ['acao' => 'EXPORTACAO_BPAI', 'tabela' => 'producoes_bpai', 'detalhes' => ['mensagem' => 'Geração de arquivo de lote magnético SIA/SUS']],
        ];

        $criados = [];
        $dias = [0, 1, 2, 3, 4];
        foreach ($logs as $idx => $item) {
            $userKeys = array_keys($usuarios);
            $user = $usuarios[$userKeys[$idx % count($userKeys)]];

            $criados[] = LogAuditoria::create([
                'user_id' => $user->id,
                'acao' => $item['acao'],
                'tabela_afetada' => $item['tabela'],
                'registro_id' => $idx + 1,
                'ip_address' => '127.0.0.1',
                'detalhes' => $item['detalhes'],
                'created_at' => now()->subDays($dias[$idx % count($dias)])->subHours($idx * 2),
            ]);
        }

        return $criados;
    }

    /**
     * Gera um CPF válido com cálculo matemático real dos dígitos verificadores (Módulo 11).
     */
    private function gerarCpfValido(string $base9): string
    {
        $base9 = str_pad(substr(preg_replace('/\D/', '', $base9) ?? '', 0, 9), 9, '0', STR_PAD_RIGHT);

        $d1 = 0;
        for ($i = 0; $i < 9; $i++) {
            $d1 += ((int) $base9[$i]) * (10 - $i);
        }
        $r1 = $d1 % 11;
        $dig1 = $r1 < 2 ? 0 : 11 - $r1;

        $base10 = $base9.$dig1;
        $d2 = 0;
        for ($i = 0; $i < 10; $i++) {
            $d2 += ((int) $base10[$i]) * (11 - $i);
        }
        $r2 = $d2 % 11;
        $dig2 = $r2 < 2 ? 0 : 11 - $r2;

        return $base10.$dig2;
    }
}
