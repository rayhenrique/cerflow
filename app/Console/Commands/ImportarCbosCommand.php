<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Cbo;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportarCbosCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'cer:importar-cbos
                            {caminho? : Caminho do arquivo CSV de ocupações da CBO do MTE}
                            {--dry-run : Executa em modo de simulação sem persistir alterações no banco de dados}
                            {--atualizar-titulos : Atualiza títulos de CBOs já existentes no banco quando divergirem do arquivo oficial}
                            {--fonte=Ministério do Trabalho e Emprego (MTE) - CBO2002 : Fonte oficial de referência}
                            {--versao=CBO/2002-Ocupações : Versão de referência do catálogo}';

    /**
     * @var string
     */
    protected $description = 'Importa o catálogo oficial de ocupações da CBO (MTE) a partir de arquivo CSV em lote com validação de formato e integridade';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $atualizarTitulos = (bool) $this->option('atualizar-titulos');
        $fonte = (string) $this->option('fonte');
        $versao = (string) $this->option('versao');

        $this->info('================================================================');
        $this->info('  CER DIGITAL — IMPORTAÇÃO OFICIAL DO CATÁLOGO DE CBOs (MTE)');
        $this->info('================================================================');

        if ($dryRun) {
            $this->warn('⚠️  MODO DE SIMULAÇÃO ATIVO (--dry-run). Nenhuma gravação será realizada.');
        }

        // 1. Resolução do caminho do arquivo CSV
        $caminhoInformado = $this->argument('caminho');
        $caminhoArquivo = $this->resolverCaminhoArquivo($caminhoInformado);

        if ($caminhoArquivo === null || ! is_file($caminhoArquivo)) {
            $this->error('Arquivo CSV não encontrado!');
            $this->line('Informe o caminho do arquivo oficial ou posicione o arquivo em:');
            $this->line('  • database/data/cbo2002_ocupacoes.csv');
            $this->line('  • storage/app/cbo2002_ocupacoes.csv');

            return self::FAILURE;
        }

        $this->line("Arquivo de origem: <info>{$caminhoArquivo}</info>");
        $this->line('Tamanho do arquivo: <info>'.number_format(filesize($caminhoArquivo) / 1024, 2).' KB</info>');

        // 2. Abertura do arquivo para leitura com validação de cabeçalho e delimitador
        $handle = fopen($caminhoArquivo, 'r');
        if ($handle === false) {
            $this->error("Não foi possível abrir o arquivo para leitura: {$caminhoArquivo}");

            return self::FAILURE;
        }

        $primeiraLinha = fgets($handle);
        if ($primeiraLinha === false) {
            fclose($handle);
            $this->error('O arquivo CSV informado está vazio.');

            return self::FAILURE;
        }

        // Detecta encoding e delimitador
        $primeiraLinhaUtf8 = $this->converterParaUtf8($primeiraLinha);
        $delimitador = $this->detectarDelimitador($primeiraLinhaUtf8);
        $dadosPrimeiraLinha = str_getcsv($primeiraLinhaUtf8, $delimitador);

        // Verifica se a primeira linha é cabeçalho (ex.: CODIGO;TITULO ou code;name)
        $primeiraLinhaEhCabecalho = false;
        $colCodigo = 0;
        $colTitulo = 1;

        if (count($dadosPrimeiraLinha) >= 2) {
            $c0 = mb_strtolower(trim($dadosPrimeiraLinha[0]));
            $c1 = mb_strtolower(trim($dadosPrimeiraLinha[1]));

            if (str_contains($c0, 'cod') || str_contains($c0, 'cbo') || str_contains($c0, 'code')) {
                $primeiraLinhaEhCabecalho = true;
                $colCodigo = 0;
                $colTitulo = 1;
            } elseif (str_contains($c1, 'cod') || str_contains($c1, 'cbo') || str_contains($c1, 'code')) {
                $primeiraLinhaEhCabecalho = true;
                $colCodigo = 1;
                $colTitulo = 0;
            }
        }

        // Se não for cabeçalho, reposiciona o ponteiro para o início do arquivo
        if (! $primeiraLinhaEhCabecalho) {
            rewind($handle);
        }

        $this->line("Delimitador detectado: '<info>{$delimitador}</info>' | Cabeçalho detectado: <info>".($primeiraLinhaEhCabecalho ? 'SIM' : 'NÃO').'</info>');

        // 3. Processamento em Lotes (Chunks)
        $totalLinhasLidas = 0;
        $cbosCriados = 0;
        $cbosAtualizados = 0;
        $cbosPreservados = 0;
        $linhasRejeitadas = 0;
        $motivosRejeicao = [];
        $codigosProcessadosNoArquivo = [];

        $tamanhoLote = 250;
        $loteAtual = [];

        $agora = now();
        $inicioTempo = microtime(true);

        DB::beginTransaction();

        try {
            while (($linhaRaw = fgets($handle)) !== false) {
                $totalLinhasLidas++;
                $linha = trim($this->converterParaUtf8($linhaRaw));
                if ($linha === '') {
                    continue;
                }

                $colunas = str_getcsv($linha, $delimitador);
                if (count($colunas) < 2) {
                    $linhasRejeitadas++;
                    $motivosRejeicao[] = "Linha {$totalLinhasLidas}: Menos de 2 colunas válidas ('{$linha}').";

                    continue;
                }

                $codigoBruto = trim((string) ($colunas[$colCodigo] ?? ''));
                $tituloBruto = trim((string) ($colunas[$colTitulo] ?? ''));

                // Normalização do código: somente dígitos, preservando zeros à esquerda com 6 caracteres
                $digitos = preg_replace('/\D/', '', $codigoBruto) ?? '';
                if ($digitos === '' || strlen($digitos) < 4 || strlen($digitos) > 6) {
                    $linhasRejeitadas++;
                    $motivosRejeicao[] = "Linha {$totalLinhasLidas}: Código inválido ('{$codigoBruto}'). Requer de 4 a 6 dígitos.";

                    continue;
                }

                $codigoFinal = str_pad($digitos, 6, '0', STR_PAD_LEFT);

                if ($tituloBruto === '') {
                    $linhasRejeitadas++;
                    $motivosRejeicao[] = "Linha {$totalLinhasLidas}: Título em branco para o código {$codigoFinal}.";

                    continue;
                }

                // Deduplicação dentro do próprio arquivo CSV
                if (isset($codigosProcessadosNoArquivo[$codigoFinal])) {
                    continue;
                }
                $codigosProcessadosNoArquivo[$codigoFinal] = true;

                $loteAtual[$codigoFinal] = $tituloBruto;

                if (count($loteAtual) >= $tamanhoLote) {
                    $this->processarLote($loteAtual, $atualizarTitulos, $fonte, $versao, $agora, $cbosCriados, $cbosAtualizados, $cbosPreservados);
                    $loteAtual = [];
                }
            }

            // Processa o lote remanescente
            if (count($loteAtual) > 0) {
                $this->processarLote($loteAtual, $atualizarTitulos, $fonte, $versao, $agora, $cbosCriados, $cbosAtualizados, $cbosPreservados);
                $loteAtual = [];
            }

            fclose($handle);

            if ($dryRun) {
                DB::rollBack();
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            fclose($handle);
            DB::rollBack();
            $this->error("Erro durante a importação: {$e->getMessage()}");

            return self::FAILURE;
        }

        $duracao = round(microtime(true) - $inicioTempo, 2);

        // 4. Exibição do Relatório Estatístico de Importação
        $this->newLine();
        $this->table(
            ['Métrica da Importação Oficial MTE', 'Resultado'],
            [
                ['Linhas processadas no arquivo', $totalLinhasLidas],
                ['Ocupações únicas válidas no arquivo', count($codigosProcessadosNoArquivo)],
                ['Novos CBOs criados no banco', $cbosCriados],
                ['CBOs existentes com título atualizado', $cbosAtualizados],
                ['CBOs existentes preservados / verificados', $cbosPreservados],
                ['Linhas rejeitadas / inválidas', $linhasRejeitadas],
                ['Tempo total de processamento', "{$duracao} segundos"],
            ]
        );

        if (count($motivosRejeicao) > 0) {
            $this->newLine();
            $this->warn('Amostra de linhas rejeitadas:');
            foreach (array_slice($motivosRejeicao, 0, 8) as $motivo) {
                $this->line("  • {$motivo}");
            }
            if (count($motivosRejeicao) > 8) {
                $this->line('  • ... e mais '.(count($motivosRejeicao) - 8).' linhas com inconsistências.');
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->warn('Simulação concluída com sucesso (--dry-run). Nenhuma modificação foi gravada no banco de dados.');
        } else {
            $this->info("✅ Importação oficial concluída com sucesso! {$cbosCriados} novos CBOs adicionados e {$cbosPreservados} verificados.");
        }

        return self::SUCCESS;
    }

    /**
     * Processa um lote de CBOs no banco de dados de forma segura e idempotente.
     *
     * @param  array<string, string>  $lote
     */
    private function processarLote(
        array $lote,
        bool $atualizarTitulos,
        string $fonte,
        string $versao,
        Carbon $agora,
        int &$criados,
        int &$atualizados,
        int &$preservados
    ): void {
        $codigos = array_keys($lote);
        $existentes = Cbo::query()->whereIn('codigo', $codigos)->get()->keyBy('codigo');

        foreach ($lote as $codigo => $tituloOficial) {
            $cboExistente = $existentes->get($codigo);

            if ($cboExistente === null) {
                Cbo::create([
                    'codigo' => $codigo,
                    'titulo_oficial' => $tituloOficial,
                    'ativo' => true,
                    'fonte_referencia' => $fonte,
                    'versao_referencia' => $versao,
                    'verificado_em' => $agora,
                ]);
                $criados++;
            } else {
                $alterou = false;

                // Atualiza título somente se solicitado pela flag e se houver divergência
                if ($atualizarTitulos && $cboExistente->titulo_oficial !== $tituloOficial) {
                    $cboExistente->titulo_oficial = $tituloOficial;
                    $alterou = true;
                    $atualizados++;
                } else {
                    $preservados++;
                }

                // Atualiza metadados oficiais e data de verificação sem desativar a ocupação
                if ($cboExistente->verificado_em === null) {
                    $cboExistente->verificado_em = $agora;
                    $cboExistente->fonte_referencia = $fonte;
                    $cboExistente->versao_referencia = $versao;
                    $alterou = true;
                }

                if ($alterou) {
                    $cboExistente->save();
                }
            }
        }
    }

    private function resolverCaminhoArquivo(?string $caminho): ?string
    {
        if ($caminho !== null && $caminho !== '') {
            if (is_file($caminho)) {
                return $caminho;
            }
            $basePath = base_path($caminho);
            if (is_file($basePath)) {
                return $basePath;
            }
        }

        $candidatos = [
            base_path('database/data/cbo2002_ocupacoes.csv'),
            storage_path('app/cbo2002_ocupacoes.csv'),
            base_path('CBO2002 - Ocupacao.csv'),
        ];

        foreach ($candidatos as $cand) {
            if (is_file($cand)) {
                return $cand;
            }
        }

        return null;
    }

    private function converterParaUtf8(string $texto): string
    {
        // Remove BOM se presente
        $bom = pack('H*', 'EFBBBF');
        $texto = preg_replace("/^{$bom}/", '', $texto) ?? $texto;

        if (mb_check_encoding($texto, 'UTF-8')) {
            return $texto;
        }

        $convertido = mb_convert_encoding($texto, 'UTF-8', ['ISO-8859-1', 'Windows-1252', 'UTF-8']);

        return is_string($convertido) ? $convertido : $texto;
    }

    private function detectarDelimitador(string $linha): string
    {
        $pontosEVirgulas = substr_count($linha, ';');
        $virgulas = substr_count($linha, ',');
        $tabs = substr_count($linha, "\t");

        if ($pontosEVirgulas >= $virgulas && $pontosEVirgulas >= $tabs) {
            return ';';
        }
        if ($virgulas >= $tabs) {
            return ',';
        }

        return "\t";
    }
}
