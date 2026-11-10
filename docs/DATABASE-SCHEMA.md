# Database Schema — CER Digital (MySQL 8.0+)

Documentação técnica oficial da estrutura de banco de dados do **CER Digital**, cobrindo o núcleo clínico, recepção e agendamento, prontuário multiprofissional (PEP), faturamento BPA-i (SIA/SUS), integrações com e-SUS APS PEC, painel audiovisual de TV e trilha de auditoria LGPD.

---

## 1. Diagrama de Relacionamentos (ERD Textual)

```
users (1) ──────────────────────────────── (1) profissionais
  │                                                │
  │ (1)                                            │ (1)
  ▼ (N)                                            ▼ (N)
logs_auditoria                            agendamento_profissionais (N)
                                                   ▲
                                                   │ (M)
salas (1) ────────────────── (N) agendamentos ─────┘
  │                                │
  │ (1)                            │ (1)
  ▼ (N)                            ▼ (N)
chamadas_painel (N) ◄──────────── pacientes (1) ── (N) evolucoes_clinicas ─── (1) ── producoes_bpai
                                   │                    │                             │
                                   │ (1)                │ (1)                         │ (N)
                                   ▼ (N)                ▼ (N)                         ▼
                               (histórico)      evolucao_adendos          procedimentos_sigtap (1)

configuracoes_estabelecimento (1)
dispositivos_tv (1) ◄─────────────── (N) solicitacoes_pareamento_tv
configuracoes_esus_pec (1) ────────── (N) historico_sincronizacoes_esus
```

---

## 2. Ordem Estrita de Execução das Migrações

Para garantir a integridade referencial sem violação de chaves estrangeiras (`Foreign Key Constraint Failure`), as tabelas devem ser criadas rigorosamente nesta ordem:

1. `users` & `cache` / `jobs`
2. `salas`
3. `pacientes`
4. `procedimentos_sigtap`
5. `profissionais`
6. `agendamentos`
7. `agendamento_profissionais`
8. `evolucoes_clinicas`
9. `evolucao_adendos`
10. `producoes_bpai`
11. `logs_auditoria`
12. `permissions`, `roles`, `model_has_roles`, etc. (Spatie Permission)
13. `configuracoes_estabelecimento`
14. `configuracoes_esus_pec`
15. `historico_sincronizacoes_esus`
16. `chamadas_painel`
17. `dispositivos_tv`
18. `solicitacoes_pareamento_tv`

---

## 3. DDL Completo das Tabelas (MySQL 8.0+)

```sql
-- ========================================================
-- 1. TABELA DE USUÁRIOS DO SISTEMA
-- ========================================================
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    ultima_versao_novidades_vista VARCHAR(20) NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 2. TABELA DE SALAS E ESPAÇOS FÍSICOS TERAPÊUTICOS
-- ========================================================
CREATE TABLE salas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    tipo VARCHAR(50) NOT NULL, -- CONSULTORIO, SENSORIAL, AVD, PEDIATRIA, ANEXO
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 3. TABELA DE PACIENTES / USUÁRIOS DO CER
-- ========================================================
CREATE TABLE pacientes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cns VARCHAR(15) NULL UNIQUE,
    cpf VARCHAR(11) NULL UNIQUE,
    rg VARCHAR(20) NULL,
    orgao_emissor_rg VARCHAR(20) NULL,
    nome VARCHAR(255) NOT NULL,
    data_nascimento DATE NOT NULL,
    sexo ENUM('M', 'F') NOT NULL,
    nome_mae VARCHAR(255) NOT NULL,
    nome_responsavel VARCHAR(255) NULL,
    telefone_responsavel VARCHAR(20) NULL,
    municipio_ibge VARCHAR(6) NOT NULL,
    bairro VARCHAR(100) NULL,
    logradouro VARCHAR(255) NULL,
    numero VARCHAR(20) NULL,
    complemento VARCHAR(100) NULL,
    cep VARCHAR(8) NULL,
    telefone_contato VARCHAR(20) NULL,
    ubs_referencia VARCHAR(255) NULL,
    nome_acs VARCHAR(255) NULL,
    origem_cadastro ENUM('MANUAL', 'ESUS_PEC') DEFAULT 'MANUAL',
    id_externo_esus BIGINT UNSIGNED NULL,
    cid_principal VARCHAR(10) NOT NULL,
    cid_secundario VARCHAR(10) NULL,
    modalidades_reabilitacao JSON NULL, -- ["FISICA", "INTELECTUAL", "AUDITIVA", "VISUAL"]
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    INDEX idx_pacientes_cns (cns),
    INDEX idx_pacientes_cpf (cpf),
    INDEX idx_pacientes_nome (nome),
    INDEX idx_pacientes_busca_multi (nome, cpf, cns)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 4. TABELA DE PROCEDIMENTOS SIGTAP PARAMETRIZADOS
-- ========================================================
CREATE TABLE procedimentos_sigtap (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(10) NOT NULL UNIQUE,
    descricao VARCHAR(255) NOT NULL,
    cbos_permitidos JSON NOT NULL, -- ["223605", "223810", "223905", "251510"]
    idade_minima INT UNSIGNED DEFAULT 0,
    idade_maxima INT UNSIGNED DEFAULT 999,
    sexo_permitido ENUM('M', 'F', 'AMBOS') DEFAULT 'AMBOS',
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_procedimentos_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 5. TABELA DE PROFISSIONAIS DE SAÚDE
-- ========================================================
CREATE TABLE profissionais (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    nome VARCHAR(255) NOT NULL,
    cpf VARCHAR(11) NOT NULL UNIQUE,
    cns VARCHAR(15) NOT NULL UNIQUE,
    registro_conselho VARCHAR(50) NOT NULL,
    cbo_padrao VARCHAR(10) NOT NULL,
    especialidade VARCHAR(100) NOT NULL,
    pin_assinatura_hash VARCHAR(255) NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_profissionais_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_profissionais_cns (cns),
    INDEX idx_profissionais_cbo (cbo_padrao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 6. TABELA DE AGENDAMENTOS (AGENDA OPERACIONAL & GRADE)
-- ========================================================
CREATE TABLE agendamentos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paciente_id BIGINT UNSIGNED NOT NULL,
    sala_id BIGINT UNSIGNED NOT NULL,
    data_atendimento DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    dia_semana ENUM('SEGUNDA', 'TERCA', 'QUARTA', 'QUINTA', 'SEXTA') NOT NULL,
    tipo_atendimento ENUM('INDIVIDUAL', 'COMPARTILHADO', 'TRABALHO_INTERNO') DEFAULT 'INDIVIDUAL',
    status ENUM('AGENDADO', 'PRESENTE', 'ATENDIDO', 'FALTA_JUSTIFICADA', 'FALTA_INJUSTIFICADA', 'CANCELADO') DEFAULT 'AGENDADO',
    chegada_em TIMESTAMP NULL,
    recorrente BOOLEAN DEFAULT TRUE,
    observacao VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    CONSTRAINT fk_agendamentos_paciente FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE RESTRICT,
    CONSTRAINT fk_agendamentos_sala FOREIGN KEY (sala_id) REFERENCES salas(id) ON DELETE RESTRICT,
    INDEX idx_agendamentos_data_hora (data_atendimento, hora_inicio),
    INDEX idx_agendamentos_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 7. TABELA PIVÔ AGENDAMENTO - PROFISSIONAIS (CO-ATENDIMENTO)
-- ========================================================
CREATE TABLE agendamento_profissionais (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agendamento_id BIGINT UNSIGNED NOT NULL,
    profissional_id BIGINT UNSIGNED NOT NULL,
    papel ENUM('PRINCIPAL', 'COMPARTILHADO') DEFAULT 'PRINCIPAL',
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_agend_prof_agendamento FOREIGN KEY (agendamento_id) REFERENCES agendamentos(id) ON DELETE CASCADE,
    CONSTRAINT fk_agend_prof_profissional FOREIGN KEY (profissional_id) REFERENCES profissionais(id) ON DELETE RESTRICT,
    CONSTRAINT unq_agendamento_profissional UNIQUE (agendamento_id, profissional_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 8. TABELA DE EVOLUÇÕES CLÍNICAS (PEP MULTIPROFISSIONAL)
-- ========================================================
CREATE TABLE evolucoes_clinicas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agendamento_id BIGINT UNSIGNED NOT NULL,
    paciente_id BIGINT UNSIGNED NOT NULL,
    profissional_id BIGINT UNSIGNED NOT NULL,
    queixa_estado_atual TEXT NOT NULL,
    conduta_terapeutica TEXT NOT NULL,
    intercorrencias TEXT NULL,
    assinado_em TIMESTAMP NOT NULL,
    assinatura_hash VARCHAR(64) NOT NULL, -- Hash SHA-256 (64 caracteres hex)
    bloqueado_edicao BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_evolucoes_agendamento FOREIGN KEY (agendamento_id) REFERENCES agendamentos(id) ON DELETE RESTRICT,
    CONSTRAINT fk_evolucoes_paciente FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE RESTRICT,
    CONSTRAINT fk_evolucoes_profissional FOREIGN KEY (profissional_id) REFERENCES profissionais(id) ON DELETE RESTRICT,
    CONSTRAINT unq_agendamento_profissional_evolucao UNIQUE (agendamento_id, profissional_id),
    INDEX idx_evolucoes_paciente (paciente_id),
    INDEX idx_evolucoes_assinado_em (assinado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 9. TABELA DE TERMOS DE ADENDO (RETIFICAÇÃO PÓS-24 HORAS)
-- ========================================================
CREATE TABLE evolucao_adendos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evolucao_id BIGINT UNSIGNED NOT NULL,
    profissional_id BIGINT UNSIGNED NOT NULL,
    justificativa TEXT NOT NULL,
    texto_adendo TEXT NOT NULL,
    assinado_em TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_adendos_evolucao FOREIGN KEY (evolucao_id) REFERENCES evolucoes_clinicas(id) ON DELETE CASCADE,
    CONSTRAINT fk_adendos_profissional FOREIGN KEY (profissional_id) REFERENCES profissionais(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 10. TABELA DE PRODUÇÃO PARA O BPA-i (SIA/SUS)
-- ========================================================
CREATE TABLE producoes_bpai (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evolucao_id BIGINT UNSIGNED NOT NULL,
    paciente_id BIGINT UNSIGNED NOT NULL,
    profissional_id BIGINT UNSIGNED NOT NULL,
    procedimento_id BIGINT UNSIGNED NOT NULL,
    cbo_executante VARCHAR(10) NOT NULL,
    cid_atendimento VARCHAR(10) NOT NULL,
    data_atendimento DATE NOT NULL,
    competencia VARCHAR(6) NOT NULL, -- AAAAMM (ex: 202611)
    status_exportacao ENUM('PENDENTE', 'VALIDADO', 'INCONSISTENTE', 'EXPORTADO') DEFAULT 'PENDENTE',
    mensagem_inconsistencia TEXT NULL,
    lote_exportacao_id VARCHAR(50) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_bpai_evolucao FOREIGN KEY (evolucao_id) REFERENCES evolucoes_clinicas(id) ON DELETE RESTRICT,
    CONSTRAINT fk_bpai_paciente FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE RESTRICT,
    CONSTRAINT fk_bpai_profissional FOREIGN KEY (profissional_id) REFERENCES profissionais(id) ON DELETE RESTRICT,
    CONSTRAINT fk_bpai_procedimento FOREIGN KEY (procedimento_id) REFERENCES procedimentos_sigtap(id) ON DELETE RESTRICT,
    INDEX idx_bpai_competencia (competencia),
    INDEX idx_bpai_status (status_exportacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 11. TABELA DE LOGS DE AUDITORIA E SEGURANÇA (LGPD)
-- ========================================================
CREATE TABLE logs_auditoria (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    acao VARCHAR(100) NOT NULL, -- ACESSO_PEP, ASSINATURA_EVOLUCAO, EXPORTACAO_BPA
    tabela_afetada VARCHAR(50) NOT NULL,
    registro_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NOT NULL,
    detalhes JSON NULL,
    created_at TIMESTAMP NOT NULL,
    CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_logs_user_acao (user_id, acao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 12. TABELA DE CONFIGURAÇÕES DO ESTABELECIMENTO
-- ========================================================
CREATE TABLE configuracoes_estabelecimento (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cnes VARCHAR(7) NOT NULL DEFAULT '0000000',
    razao_social VARCHAR(255) NOT NULL DEFAULT 'Centro Especializado em Reabilitação',
    nome_fantasia VARCHAR(255) NOT NULL DEFAULT 'CER Digital',
    municipio_ibge VARCHAR(6) NOT NULL DEFAULT '270430',
    uf VARCHAR(2) NOT NULL DEFAULT 'AL',
    logotipo_path VARCHAR(255) NULL,
    telefone VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    responsavel_nome VARCHAR(255) NULL,
    painel_chamadas_ativo BOOLEAN DEFAULT TRUE,
    painel_chamadas_voz_ativa BOOLEAN DEFAULT TRUE,
    painel_chamadas_som_ativo BOOLEAN DEFAULT TRUE,
    painel_chamadas_velocidade_voz DECIMAL(3,2) DEFAULT 1.00,
    painel_chamadas_tom_voz DECIMAL(3,2) DEFAULT 1.00,
    painel_chamadas_volume DECIMAL(3,2) DEFAULT 1.00,
    painel_chamadas_repeticoes INT UNSIGNED DEFAULT 1,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 13. TABELA DE CONFIGURAÇÕES DO E-SUS APS PEC (POSTGRESQL)
-- ========================================================
CREATE TABLE configuracoes_esus_pec (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    host VARCHAR(255) NOT NULL,
    port INT UNSIGNED NOT NULL DEFAULT 5432,
    database VARCHAR(100) NOT NULL DEFAULT 'esus',
    username VARCHAR(100) NOT NULL,
    password_encrypted TEXT NOT NULL,
    ssl_mode VARCHAR(20) NOT NULL DEFAULT 'prefer',
    ativo BOOLEAN DEFAULT TRUE,
    ultimo_teste_em TIMESTAMP NULL,
    status_conexao VARCHAR(50) DEFAULT 'NAO_TESTADO',
    mensagem_erro_conexao TEXT NULL,
    ultima_sincronizacao_em TIMESTAMP NULL,
    total_registros_importados INT UNSIGNED DEFAULT 0,
    total_registros_atualizados INT UNSIGNED DEFAULT 0,
    sincronizacao_incremental_ativa BOOLEAN DEFAULT TRUE,
    checkpoint_ultima_atualizacao TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 14. TABELA DE HISTÓRICO DE SINCRONIZAÇÕES E-SUS PEC
-- ========================================================
CREATE TABLE historico_sincronizacoes_esus (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    configuracao_id BIGINT UNSIGNED NOT NULL,
    iniciado_em TIMESTAMP NOT NULL,
    finalizado_em TIMESTAMP NULL,
    tipo_execucao ENUM('MANUAL', 'AUTOMATICA', 'SIMULACAO', 'INCREMENTAL') DEFAULT 'MANUAL',
    status ENUM('EM_ANDAMENTO', 'SUCESSO', 'ERRO') DEFAULT 'EM_ANDAMENTO',
    registros_lidos INT UNSIGNED DEFAULT 0,
    registros_importados INT UNSIGNED DEFAULT 0,
    registros_atualizados INT UNSIGNED DEFAULT 0,
    registros_ignorados INT UNSIGNED DEFAULT 0,
    mensagem_erro TEXT NULL,
    detalhes_execucao JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_hist_esus_config FOREIGN KEY (configuracao_id) REFERENCES configuracoes_esus_pec(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 15. TABELA DE CHAMADAS DO PAINEL DE TV
-- ========================================================
CREATE TABLE chamadas_painel (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paciente_id BIGINT UNSIGNED NOT NULL,
    agendamento_id BIGINT UNSIGNED NULL,
    profissional_id BIGINT UNSIGNED NULL,
    sala_id BIGINT UNSIGNED NULL,
    nome_paciente VARCHAR(255) NOT NULL,
    local_chamada VARCHAR(100) NOT NULL,
    profissional_nome VARCHAR(255) NULL,
    status ENUM('PENDENTE', 'CHAMANDO', 'CONCLUIDA', 'CANCELADA') DEFAULT 'PENDENTE',
    origem ENUM('RECEPCAO', 'CONSULTORIO', 'MANUAL') DEFAULT 'RECEPCAO',
    chamado_em TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_chamadas_paciente FOREIGN KEY (paciente_id) REFERENCES pacientes(id) ON DELETE CASCADE,
    CONSTRAINT fk_chamadas_agendamento FOREIGN KEY (agendamento_id) REFERENCES agendamentos(id) ON DELETE SET NULL,
    CONSTRAINT fk_chamadas_profissional FOREIGN KEY (profissional_id) REFERENCES profissionais(id) ON DELETE SET NULL,
    CONSTRAINT fk_chamadas_sala FOREIGN KEY (sala_id) REFERENCES salas(id) ON DELETE SET NULL,
    INDEX idx_chamadas_status (status, chamado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 16. TABELA DE DISPOSITIVOS DE TV PAREADOS
-- ========================================================
CREATE TABLE dispositivos_tv (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    identificador_unico VARCHAR(64) NOT NULL UNIQUE,
    nome VARCHAR(100) NOT NULL,
    localizacao VARCHAR(100) NULL,
    autorizado BOOLEAN DEFAULT TRUE,
    ultimo_acesso_em TIMESTAMP NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_dispositivos_tv_ativo (autorizado, ultimo_acesso_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- 17. TABELA DE SOLICITAÇÕES DE PAREAMENTO DE TV
-- ========================================================
CREATE TABLE solicitacoes_pareamento_tv (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo_pareamento VARCHAR(6) NOT NULL UNIQUE,
    identificador_temporario VARCHAR(64) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    status ENUM('PENDENTE', 'AUTORIZADO', 'EXPIRADO', 'REJEITADO') DEFAULT 'PENDENTE',
    dispositivo_tv_id BIGINT UNSIGNED NULL,
    expira_em TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    CONSTRAINT fk_pareamento_tv_dispositivo FOREIGN KEY (dispositivo_tv_id) REFERENCES dispositivos_tv(id) ON DELETE SET NULL,
    INDEX idx_pareamento_tv_status_expira (status, expira_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```
