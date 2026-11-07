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
                'versao' => '1.1.3',
                'data' => '06/11/2026',
                'titulo' => 'Correção de Assets Estáticos do Livewire 4 no Nginx/CloudPanel e Fechamento Instantâneo do Modal',
                'tipo' => 'Patch Release',
                'destaques' => [
                    'Publicação estática de public/vendor/livewire (livewire.min.js) evitando erro 404 na regra de arquivos .js do Nginx no CloudPanel.',
                    'Consumo atômico da sessão (session()->pull) no Modal de Novidades da Versão e fechamento instantâneo via Alpine.js + tecla ESC.',
                    'Remoção de instância duplicada do Alpine.js em resources/js/app.js em favor do bundle nativo do Livewire 4.',
                ],
            ],
            [
                'versao' => '1.1.2',
                'data' => '06/11/2026',
                'titulo' => 'Padronização PSR-12 dos Componentes de Versão e Revisão do Guia de Deploy CloudPanel',
                'tipo' => 'Patch Release',
                'destaques' => [
                    'Padronização PSR-12 nos componentes Livewire de controle de versão (ModalNovidadesVersao e AjudaSobre).',
                    'Sincronização das referências de tag SemVer no guia de deploy para CloudPanel (cer.kltecnologia.com).',
                ],
            ],
            [
                'versao' => '1.1.1',
                'data' => '06/11/2026',
                'titulo' => 'Documentação de Produção CloudPanel (cer.kltecnologia.com) e Licenciamento Proprietário KL Tecnologia',
                'tipo' => 'Patch Release',
                'destaques' => [
                    'Adicionado guia completo deploy.md parametrizado para o ambiente CloudPanel na Hostinger (cer.kltecnologia.com / PHP 8.5 / MySQL cer).',
                    'Atualização institucional do README.md detalhando os 5 módulos operacionais do CER Flow.',
                    'Definição formal de propriedade intelectual e licença proprietária exclusiva da KL Tecnologia (kltecnologia.com).',
                ],
            ],
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
