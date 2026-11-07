<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LogAuditoria;
use App\Models\ProducaoBpai;
use App\Models\User;
use App\Rules\CnsValido;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BpaiExportService
{
    public const CNES_PADRAO = '2000001';

    /**
     * RF4.2 - Validador pré-exportação de inconsistências em memória para uma competência (YYYYMM).
     *
     * @return array{
     *     competencia: string,
     *     total: int,
     *     validados: Collection<int, ProducaoBpai>,
     *     inconsistentes: Collection<int, ProducaoBpai>
     * }
     */
    public function auditarCompetencia(string $competencia, bool $persistirStatus = true): array
    {
        $producoes = ProducaoBpai::query()
            ->with(['paciente', 'profissional', 'procedimento', 'evolucao'])
            ->porCompetencia($competencia)
            ->orderBy('data_atendimento')
            ->orderBy('id')
            ->get();

        $validados = collect();
        $inconsistentes = collect();

        foreach ($producoes as $producao) {
            $erros = $this->validarRegistroEmMemoria($producao);

            if ($erros === []) {
                $novoStatus = $producao->status_exportacao === 'EXPORTADO' ? 'EXPORTADO' : 'VALIDADO';
                $producao->status_exportacao = $novoStatus;
                $producao->mensagem_inconsistencia = null;

                if ($persistirStatus && $producao->isDirty(['status_exportacao', 'mensagem_inconsistencia'])) {
                    $producao->save();
                }

                $validados->push($producao);
            } else {
                $mensagem = implode('; ', $erros);
                $producao->status_exportacao = 'INCONSISTENTE';
                $producao->mensagem_inconsistencia = $mensagem;

                if ($persistirStatus && $producao->isDirty(['status_exportacao', 'mensagem_inconsistencia'])) {
                    $producao->save();
                }

                $inconsistentes->push($producao);
            }
        }

        return [
            'competencia' => $competencia,
            'total' => $producoes->count(),
            'validados' => $validados,
            'inconsistentes' => $inconsistentes,
        ];
    }

    /**
     * Avalia em memória todas as regras do SIA/SUS (CNS, idade, sexo, CBO x SIGTAP e CID-10).
     *
     * @return array<int, string>
     */
    public function validarRegistroEmMemoria(ProducaoBpai $producao): array
    {
        $erros = [];
        $paciente = $producao->paciente;
        $profissional = $producao->profissional;
        $procedimento = $producao->procedimento;

        if ($paciente === null) {
            $erros[] = 'Paciente não encontrado';

            return $erros;
        }

        if (! CnsValido::validar((string) $paciente->cns)) {
            $erros[] = 'CNS do paciente inválido ou fora do padrão de 15 dígitos';
        }

        if ($paciente->data_nascimento === null) {
            $erros[] = 'Data de nascimento do paciente ausente';
        }

        if (preg_match('/^\d{6}$/', (string) $paciente->municipio_ibge) !== 1) {
            $erros[] = 'Código IBGE do município inválido (exige 6 dígitos)';
        }

        if ($profissional === null) {
            $erros[] = 'Profissional executante não encontrado';

            return $erros;
        }

        if (preg_match('/^\d{15}$/', (string) $profissional->cns) !== 1) {
            $erros[] = 'CNS do profissional inválido (exige 15 dígitos)';
        }

        if ($procedimento === null) {
            $erros[] = 'Procedimento SIGTAP não encontrado';

            return $erros;
        }

        if (preg_match('/^\d{10}$/', (string) $procedimento->codigo) !== 1) {
            $erros[] = 'Código do procedimento SIGTAP deve conter 10 dígitos';
        }

        if (! $procedimento->permiteCbo((string) $producao->cbo_executante)) {
            $erros[] = sprintf(
                'CBO %s incompatível com o procedimento SIGTAP %s',
                $producao->cbo_executante,
                $procedimento->codigo
            );
        }

        if ($paciente->data_nascimento !== null && $producao->data_atendimento !== null) {
            $idade = (int) $paciente->data_nascimento->diffInYears($producao->data_atendimento);
            if ($idade < (int) $procedimento->idade_minima || $idade > (int) $procedimento->idade_maxima) {
                $erros[] = sprintf(
                    'Idade do paciente (%d anos) incompatível com a faixa do procedimento (%d a %d anos)',
                    $idade,
                    $procedimento->idade_minima,
                    $procedimento->idade_maxima
                );
            }
        }

        if (
            $procedimento->sexo_permitido !== 'AMBOS'
            && $paciente->sexo !== $procedimento->sexo_permitido
        ) {
            $erros[] = sprintf(
                'Sexo do paciente (%s) incompatível com o procedimento (%s)',
                $paciente->sexo,
                $procedimento->sexo_permitido
            );
        }

        return $erros;
    }

    /**
     * RF4.3 - Formatador do layout posicional oficial DATASUS (Header 01#BPA# + linhas tipo 03).
     *
     * @param  ?Collection<int, ProducaoBpai>  $producoesValidadas
     */
    public function gerarConteudoPosicional(
        string $competencia,
        ?Collection $producoesValidadas = null,
        ?string $cnes = null
    ): string {
        $cnes ??= ConfiguracaoEstabelecimento::atual()->cnes;

        if ($producoesValidadas === null) {
            $auditoria = $this->auditarCompetencia($competencia, true);
            $producoesValidadas = $auditoria['validados'];
        }

        $linhasTipo03 = [];
        $folhaAtual = 1;
        $sequenciaAtual = 0;
        $cnsProfissionalAnterior = null;

        $ordenadas = $producoesValidadas->sortBy([
            fn (ProducaoBpai $p) => (string) $p->profissional?->cns,
            fn (ProducaoBpai $p) => (string) $p->data_atendimento?->format('Ymd'),
            fn (ProducaoBpai $p) => $p->id,
        ])->values();

        $somaControle = 0;

        foreach ($ordenadas as $producao) {
            $cnsProf = (string) $producao->profissional->cns;

            if ($cnsProfissionalAnterior !== null && $cnsProfissionalAnterior !== $cnsProf) {
                $folhaAtual++;
                $sequenciaAtual = 1;
            } else {
                $sequenciaAtual++;
                if ($sequenciaAtual > 20) {
                    $folhaAtual++;
                    $sequenciaAtual = 1;
                }
            }

            $cnsProfissionalAnterior = $cnsProf;

            $codigoProcedimentoNum = (int) $producao->procedimento->codigo;
            $somaControle += $codigoProcedimentoNum + 1;

            $linhasTipo03[] = $this->formatarLinhaTipo03(
                producao: $producao,
                competencia: $competencia,
                cnes: $cnes,
                folha: $folhaAtual,
                sequencia: $sequenciaAtual
            );
        }

        $totalLinhas = count($linhasTipo03);
        $totalFolhas = $totalLinhas > 0 ? $folhaAtual : 0;
        $campoControle = (string) (($somaControle % 1111) + 1111);

        $header = $this->formatarLinhaHeader(
            competencia: $competencia,
            totalLinhas: $totalLinhas,
            totalFolhas: $totalFolhas,
            campoControle: $campoControle
        );

        return implode("\r\n", array_merge([$header], $linhasTipo03))."\r\n";
    }

    /**
     * Formata a linha 01 de cabeçalho oficial (#BPA#): 132 posições + CRLF.
     */
    public function formatarLinhaHeader(
        string $competencia,
        int $totalLinhas,
        int $totalFolhas,
        string $campoControle,
        string $orgaoOrigem = 'SECRETARIA MUNICIPAL DE SAUDE',
        string $siglaOrgao = 'SMS',
        string $cgcCpfPrestador = '00000000000191',
        string $orgaoDestino = 'SECRETARIA ESTADUAL DE SAUDE',
        string $indicadorDestino = 'E',
        string $versaoSistema = 'CERFLOW1.0'
    ): string {
        return '01'
            .'#BPA#'
            .$this->padNum($competencia, 6)
            .$this->padNum((string) $totalLinhas, 6)
            .$this->padNum((string) $totalFolhas, 6)
            .$this->padNum($campoControle, 4)
            .$this->padAlfa($orgaoOrigem, 30)
            .$this->padAlfa($siglaOrgao, 6)
            .$this->padNum($cgcCpfPrestador, 14)
            .$this->padAlfa($orgaoDestino, 40)
            .$this->padAlfa($indicadorDestino, 1)
            .$this->padAlfa($versaoSistema, 10);
    }

    /**
     * Formata uma linha posicional tipo 03 (BPA Individualizado - SIA/SUS): 350 posições exatas.
     */
    public function formatarLinhaTipo03(
        ProducaoBpai $producao,
        string $competencia,
        string $cnes,
        int $folha,
        int $sequencia
    ): string {
        $paciente = $producao->paciente;
        $profissional = $producao->profissional;
        $procedimento = $producao->procedimento;

        $dataAtendimento = $producao->data_atendimento->format('Ymd');
        $dataNascimento = $paciente->data_nascimento->format('Ymd');
        $idade = (int) $paciente->data_nascimento->diffInYears($producao->data_atendimento);
        $cidLimpo = strtoupper(preg_replace('/[^A-Z0-9]/i', '', (string) $producao->cid_atendimento) ?? '');
        $telefoneLimpo = preg_replace('/\D/', '', (string) $paciente->telefone_contato) ?? '';
        $cpfLimpo = preg_replace('/\D/', '', (string) $paciente->cpf) ?? '';

        return '03'                                                         // 001-002 (02) Tipo de linha BPA-i
            .$this->padNum($cnes, 7)                                        // 003-009 (07) CNES
            .$this->padNum($competencia, 6)                                 // 010-015 (06) Competência AAAAMM
            .$this->padNum((string) $profissional->cns, 15)                 // 016-030 (15) CNS do Profissional
            .$this->padAlfa((string) $producao->cbo_executante, 6)          // 031-036 (06) CBO do Profissional
            .$this->padNum($dataAtendimento, 8)                             // 037-044 (08) Data do Atendimento AAAAMMDD
            .$this->padNum((string) $folha, 3)                              // 045-047 (03) Número da Folha
            .$this->padNum((string) $sequencia, 2)                          // 048-049 (02) Sequência da Linha na Folha
            .$this->padNum((string) $procedimento->codigo, 10)              // 050-059 (10) Procedimento SIGTAP
            .$this->padNum((string) $paciente->cns, 15)                     // 060-074 (15) CNS do Paciente
            .$this->padAlfa((string) $paciente->sexo, 1)                    // 075-075 (01) Sexo (M/F)
            .$this->padNum((string) $paciente->municipio_ibge, 6)           // 076-081 (06) Código IBGE Município
            .$this->padAlfa($cidLimpo, 4)                                   // 082-085 (04) CID-10 Principal
            .$this->padNum((string) $idade, 3)                              // 086-088 (03) Idade na data do atendimento
            .$this->padNum('1', 6)                                          // 089-094 (06) Quantidade produzida
            .$this->padNum('01', 2)                                         // 095-096 (02) Caráter de atendimento (01 Eletivo)
            .$this->padAlfa('', 13)                                         // 097-109 (13) Número de autorização
            .$this->padAlfa('BPA', 3)                                       // 110-112 (03) Origem das informações
            .$this->padAlfa((string) $paciente->nome, 30)                   // 113-142 (30) Nome do paciente
            .$this->padNum($dataNascimento, 8)                              // 143-150 (08) Data de nascimento AAAAMMDD
            .$this->padNum('99', 2)                                         // 151-152 (02) Raça/Cor (99 Sem informação)
            .$this->padAlfa('', 4)                                          // 153-156 (04) Etnia
            .$this->padNum('010', 3)                                        // 157-159 (03) Nacionalidade (010 Brasileira)
            .$this->padAlfa('135', 3)                                       // 160-162 (03) Código do Serviço (135 Reabilitação)
            .$this->padAlfa('', 3)                                          // 163-165 (03) Código da Classificação
            .$this->padAlfa('', 8)                                          // 166-173 (08) Código da Sequência da Equipe
            .$this->padAlfa('', 4)                                          // 174-177 (04) Código da Área da Equipe
            .$this->padAlfa('', 14)                                         // 178-191 (14) CNPJ
            .$this->padAlfa('', 8)                                          // 192-199 (08) CEP do Paciente
            .$this->padNum('081', 3)                                        // 200-202 (03) Código de Logradouro
            .$this->padAlfa((string) ($paciente->endereco ?? ''), 30)       // 203-232 (30) Endereço do Paciente
            .$this->padAlfa('', 10)                                         // 233-242 (10) Complemento
            .$this->padAlfa('S/N', 5)                                       // 243-247 (05) Número
            .$this->padAlfa((string) ($paciente->bairro ?? ''), 30)         // 248-277 (30) Bairro
            .$this->padAlfa($telefoneLimpo, 11)                             // 278-288 (11) Telefone
            .$this->padAlfa('', 40)                                         // 289-328 (40) E-mail
            .$this->padAlfa('', 10)                                         // 329-338 (10) INE
            .$this->padAlfa($cpfLimpo, 12);                                 // 339-350 (12) CPF / Reservado
    }

    /**
     * Gera o StreamedResponse para download do arquivo magnético oficial .txt do BPA-i.
     */
    public function exportarStream(
        string $competencia,
        ?User $usuarioExecutor = null,
        ?string $ipAddress = null,
        string $cnes = self::CNES_PADRAO
    ): StreamedResponse {
        $cnes ??= ConfiguracaoEstabelecimento::atual()->cnes;
        abort_unless(preg_match('/^\\d{7}$/', $cnes) === 1, 422, 'Configure um CNES válido de 7 dígitos em Configurações > Identificação do Estabelecimento antes de exportar o BPA-i.');

        $auditoria = $this->auditarCompetencia($competencia, true);
        $validados = $auditoria['validados'];
        $loteId = sprintf('BPA-%s-%s', $competencia, strtoupper(Str::random(6)));

        if ($validados->isNotEmpty()) {
            ProducaoBpai::query()
                ->whereIn('id', $validados->pluck('id')->all())
                ->update([
                    'status_exportacao' => 'EXPORTADO',
                    'lote_exportacao_id' => $loteId,
                ]);
        }

        $conteudo = $this->gerarConteudoPosicional($competencia, $validados, $cnes);

        if ($usuarioExecutor !== null) {
            LogAuditoria::create([
                'user_id' => $usuarioExecutor->id,
                'acao' => 'EXPORTACAO_BPA',
                'tabela_afetada' => 'producoes_bpai',
                'registro_id' => null,
                'ip_address' => $ipAddress ?? '127.0.0.1',
                'detalhes' => [
                    'competencia' => $competencia,
                    'lote_exportacao_id' => $loteId,
                    'total_exportados' => $validados->count(),
                    'total_inconsistentes' => $auditoria['inconsistentes']->count(),
                ],
                'created_at' => now(),
            ]);
        }

        $nomeArquivo = sprintf('BPA_I_%s.txt', $competencia);

        return response()->streamDownload(function () use ($conteudo): void {
            echo $conteudo;
        }, $nomeArquivo, [
            'Content-Type' => 'text/plain; charset=ISO-8859-1',
        ]);
    }

    private function padNum(string $valor, int $tamanho): string
    {
        $limpo = preg_replace('/\D/', '', $valor) ?? '';

        return str_pad(substr($limpo, 0, $tamanho), $tamanho, '0', STR_PAD_LEFT);
    }

    private function padAlfa(string $valor, int $tamanho): string
    {
        $ascii = strtoupper(Str::ascii($valor));
        $ascii = preg_replace('/[\r\n\t]+/', ' ', $ascii) ?? '';

        return str_pad(substr($ascii, 0, $tamanho), $tamanho, ' ', STR_PAD_RIGHT);
    }
}
