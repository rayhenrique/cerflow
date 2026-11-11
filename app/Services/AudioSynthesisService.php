<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AudioSynthesisService
{
    /**
     * Diretório de cache para arquivos de áudio sintetizados.
     */
    protected string $cachePath;

    public function __construct()
    {
        $this->cachePath = storage_path('app/tts_cache');
        if (! File::isDirectory($this->cachePath)) {
            File::makeDirectory($this->cachePath, 0755, true, true);
        }
    }

    /**
     * Identifica qual driver TTS está disponível no servidor local.
     * Retorna: 'piper' | 'espeak' | 'sapi' | 'none'
     */
    public function driverDisponivel(): string
    {
        // 1. Piper (se configurado/instalado na máquina)
        if ($this->comandoExiste('piper')) {
            return 'piper';
        }

        // 2. espeak-ng ou espeak (padrão em servidores Linux/Debian/Ubuntu)
        if ($this->comandoExiste('espeak-ng') || $this->comandoExiste('espeak')) {
            return 'espeak';
        }

        // 3. SAPI (Windows nativo através de PowerShell)
        if (PHP_OS_FAMILY === 'Windows') {
            return 'sapi';
        }

        return 'none';
    }

    /**
     * Retorna relatório detalhado de status do motor TTS no servidor.
     *
     * @return array{disponivel: bool, driver: string, descricao: string, instrucao: ?string}
     */
    public function status(): array
    {
        $driver = $this->driverDisponivel();

        return match ($driver) {
            'piper' => [
                'disponivel' => true,
                'driver' => 'piper',
                'descricao' => 'Piper Neural TTS (Alta Qualidade Local)',
                'instrucao' => null,
            ],
            'espeak' => [
                'disponivel' => true,
                'driver' => 'espeak',
                'descricao' => 'eSpeak NG Local (Linux / VPS)',
                'instrucao' => null,
            ],
            'sapi' => [
                'disponivel' => true,
                'driver' => 'sapi',
                'descricao' => 'Microsoft SAPI pt-BR (Windows Local)',
                'instrucao' => null,
            ],
            default => [
                'disponivel' => false,
                'driver' => 'none',
                'descricao' => 'Nenhum motor TTS instalado no servidor host',
                'instrucao' => 'Em servidores Linux/VPS, instale o espeak-ng com: sudo apt update && sudo apt install -y espeak-ng',
            ],
        };
    }

    /**
     * Sintetiza o texto para áudio WAV em formato PCM.
     * Utiliza cache em disco por hash para evitar reprocessamento idêntico.
     */
    public function sintetizar(string $texto): ?string
    {
        $textoSanitizado = $this->sanitizarTexto($texto);
        if ($textoSanitizado === '') {
            return null;
        }

        $hash = md5($textoSanitizado.'_'.$this->driverDisponivel());
        $arquivoCache = $this->cachePath.DIRECTORY_SEPARATOR."{$hash}.wav";

        if (File::exists($arquivoCache) && File::size($arquivoCache) > 100) {
            return File::get($arquivoCache);
        }

        $driver = $this->driverDisponivel();
        $gerou = match ($driver) {
            'piper' => $this->sintetizarPiper($textoSanitizado, $arquivoCache),
            'espeak' => $this->sintetizarEspeak($textoSanitizado, $arquivoCache),
            'sapi' => $this->sintetizarSapi($textoSanitizado, $arquivoCache),
            default => false,
        };

        if ($gerou && File::exists($arquivoCache) && File::size($arquivoCache) > 100) {
            return File::get($arquivoCache);
        }

        // Se nenhum motor TTS de sistema estiver instalado, gera um tom PCM/WAV de chamada suave
        // para garantir que o cliente HTTP receba áudio válido sem quebrar o player da TV
        $fallbackWav = $this->gerarTomAvisoWav();
        File::put($arquivoCache, $fallbackWav);

        return $fallbackWav;
    }

    /**
     * Sanitiza o texto de anúncio de chamada clínica.
     */
    protected function sanitizarTexto(string $texto): string
    {
        $limpo = trim(strip_tags($texto));
        // Remove quebras de linha e caracteres de controle
        $limpo = preg_replace('/[\r\n\t]+/', ' ', $limpo) ?? $limpo;
        $limpo = preg_replace('/\s+/', ' ', $limpo) ?? $limpo;

        return Str::limit($limpo, 300, '');
    }

    /**
     * Síntese via Microsoft SAPI no Windows.
     */
    protected function sintetizarSapi(string $texto, string $destinoWav): bool
    {
        try {
            $psScript = '$s = New-Object -ComObject SAPI.SpVoice; '
                .'$stream = New-Object -ComObject SAPI.SpFileStream; '
                .'$stream.Open("'.str_replace('\\', '/', $destinoWav).'", 3, $false); '
                .'foreach ($v in $s.GetVoices()) { if ($v.GetDescription() -like "*pt-BR*" -or $v.GetDescription() -like "*Maria*" -or $v.GetDescription() -like "*Daniel*") { $s.Voice = $v; break; } }; '
                .'$s.AudioOutputStream = $stream; '
                .'$s.Speak("'.str_replace('"', '\"', $texto).'"); '
                .'$stream.Close();';

            $bytes = mb_convert_encoding($psScript, 'UTF-16LE', 'UTF-8');
            $b64 = base64_encode($bytes);

            $cmd = 'powershell.exe -NoProfile -NonInteractive -EncodedCommand '.$b64;
            exec($cmd, $saida, $codigoRetorno);

            return $codigoRetorno === 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Síntese via eSpeak / eSpeak NG no Linux.
     */
    protected function sintetizarEspeak(string $texto, string $destinoWav): bool
    {
        try {
            $binario = $this->comandoExiste('espeak-ng') ? 'espeak-ng' : 'espeak';
            // Configura voz pt-br com velocidade 140 (natural para recepção)
            $cmd = sprintf(
                '%s -v pt-br -s 140 -w %s %s',
                escapeshellcmd($binario),
                escapeshellarg($destinoWav),
                escapeshellarg($texto)
            );

            exec($cmd, $saida, $codigoRetorno);

            return $codigoRetorno === 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Síntese via Piper Neural TTS no Linux/Servidor.
     */
    protected function sintetizarPiper(string $texto, string $destinoWav): bool
    {
        try {
            $modelo = config('services.piper.model', storage_path('app/piper/pt_BR-faber-medium.onnx'));
            if (! File::exists($modelo)) {
                return false;
            }

            $cmd = sprintf(
                'echo %s | piper --model %s --output_file %s',
                escapeshellarg($texto),
                escapeshellarg($modelo),
                escapeshellarg($destinoWav)
            );

            exec($cmd, $saida, $codigoRetorno);

            return $codigoRetorno === 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Gera um arquivo WAV básico de 16kHz mono (tom suave de chamada clínica)
     * caso o servidor não tenha nenhum sintetizador TTS instalado.
     */
    public function gerarTomAvisoWav(): string
    {
        $sampleRate = 16000;
        $duration = 1.0;
        $numSamples = (int) ($sampleRate * $duration);

        $data = '';
        for ($i = 0; $i < $numSamples; $i++) {
            $t = $i / $sampleRate;
            // Dois tons simultâneos: 587 Hz (Ré 5) e 880 Hz (Lá 5) com decaimento exponencial
            $amplitude = exp(-3.0 * $t);
            $sample = (sin(2 * M_PI * 587.33 * $t) * 0.5 + sin(2 * M_PI * 880.00 * $t) * 0.5) * $amplitude;
            $intSample = (int) max(-32767, min(32767, $sample * 24000));
            $data .= pack('v', $intSample < 0 ? $intSample + 65536 : $intSample);
        }

        $dataLength = strlen($data);
        $riffLength = 36 + $dataLength;

        // Cabeçalho RIFF WAV 16-bit PCM Mono
        $header = 'RIFF'
            .pack('V', $riffLength)
            .'WAVE'
            .'fmt '
            .pack('V', 16)      // Tamanho do chunk fmt
            .pack('v', 1)       // PCM
            .pack('v', 1)       // Canais: 1 (Mono)
            .pack('V', $sampleRate) // Frequência
            .pack('V', $sampleRate * 2) // Byte rate
            .pack('v', 2)       // Block align
            .pack('v', 16)      // Bits por sample
            .'data'
            .pack('V', $dataLength);

        return $header.$data;
    }

    /**
     * Verifica se um comando executável existe no PATH do sistema operacional.
     */
    protected function comandoExiste(string $comando): bool
    {
        $verificador = PHP_OS_FAMILY === 'Windows' ? 'where' : 'which';
        $cmd = sprintf('%s %s 2>&1', $verificador, escapeshellarg($comando));
        exec($cmd, $saida, $codigo);

        return $codigo === 0;
    }
}
