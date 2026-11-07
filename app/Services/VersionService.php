<?php

declare(strict_types=1);

namespace App\Services;

class VersionService
{
    /**
     * Retorna a versão SemVer atual lida do arquivo VERSION na raiz do projeto.
     */
    public function versaoAtual(): string
    {
        $caminho = base_path('VERSION');
        if (is_file($caminho)) {
            $conteudo = trim((string) file_get_contents($caminho));
            if ($conteudo !== '') {
                return $conteudo;
            }
        }

        return '1.1.0';
    }

    /**
     * Retorna os dados da versão mais recente para exibição no modal pós-login.
     *
     * @return array{versao: string, data: string, titulo: string, tipo: string, destaques: array<int, string>}
     */
    public function releaseAtual(): array
    {
        $historico = $this->historicoVersoes();

        return $historico[0];
    }

    /**
     * Retorna a linha do tempo completa de evolução das versões do CER Flow.
     *
     * @return array<int, array{versao: string, data: string, titulo: string, tipo: string, destaques: array<int, string>}>
     */
    public function historicoVersoes(): array
    {
        return [
            [
                'versao' => '1.1.0',
                'data' => '06/11/2026',
                'titulo' => 'Módulo de Ajuda & Sobre e Comunicado Automático de Versão no Login',
                'tipo' => 'Minor Release',
                'destaques' => [
                    'Modal automático de Novidades da Versão exibido a cada login com os destaques da release ativa.',
                    'Novo módulo Ajuda & Sobre com manual operacional rápido por perfil (Recepção, Terapeuta, Faturamento e Coordenação).',
                    'Linha do tempo interativa com o histórico completo de evolução das versões (SemVer) e especificações técnicas do ambiente.',
                ],
            ],
            [
                'versao' => '1.0.1',
                'data' => '06/11/2026',
                'titulo' => 'Automação de Deploy para VPS Hostinger (CloudPanel)',
                'tipo' => 'Patch Release',
                'destaques' => [
                    'Adicionado script deploy.sh com suporte a deploy contínuo por branch ou tag SemVer.',
                    'Automação de modo de manutenção, migrations, limpeza de cache do Spatie Permission e build Vite.',
                    'Documentação completa de provisionamento CloudPanel + MySQL 8.0+ no README.md.',
                ],
            ],
            [
                'versao' => '1.0.0',
                'data' => '06/11/2026',
                'titulo' => 'Lançamento Inicial do MVP CER Flow (Grade, Recepção, PEP Multi e BPA-i)',
                'tipo' => 'Major Release',
                'destaques' => [
                    'Grade Semanal reativa em slots de 30 minutos por sala e profissional, com suporte a Atendimento Compartilhado e horário cativo.',
                    'Painel de Recepção com check-in em 1 clique e alerta visual automático de absenteísmo (3 faltas consecutivas ou 5 no mês).',
                    'Prontuário Eletrônico Multiprofissional (PEP) com assinatura eletrônica via PIN (SHA-256), trava legal de 24 horas e Termos de Adendo.',
                    'Pré-auditoria em memória e exportação magnética posicional do BPA-i (Header 01#BPA# + linhas Tipo 03 de 350 colunas) padrão DATASUS.',
                ],
            ],
        ];
    }
}
