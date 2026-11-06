# Database Schema - CER Flow (MySQL 8.0+)

## 1. Diagrama de Relacionamentos (ERD Textual)

```
users (1) ───────────────────────── (1) profissionais
  │                                           │
  │ (1)                                       │ (1)
  ▼ (N)                                       ▼ (N)
logs_auditoria                       agendamento_profissionais (N)
                                              ▲
                                              │ (M)
salas (1) ─────────────── (N) agendamentos ───┘
                                  │
                                  │ (1)
                                  ▼ (N)
pacientes (1) ─────────── (N) evolucoes_clinicas ──── (1) ─── producoes_bpai
  │                               │                             │
  │ (1)                           │ (1)                         │ (N)
  │                               ▼ (N)                         ▼
  └──────────────────────── evolucao_adendos           procedimentos_sigtap (1)
```

---

## 2. Ordem Estrita de Criação das Tabelas

Para evitar qualquer erro de integridade referencial (`Foreign Key Constraint Failure`), as tabelas devem ser executadas/migradas rigorosamente nesta ordem:
1. `users`
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

---

## 3. Script SQL DDL Completo (MySQL 8.0+)

```sql
-- 1. TABELA DE USUÁRIOS DO SISTEMA
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    ativo BOOLEAN DEFAULT TRUE,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABELA DE SALAS E ESPAÇOS FÍSICOS
CREATE TABLE salas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    tipo VARCHAR(50) NOT NULL, -- CONSULTORIO, SENSORIAL, AVD, PEDIATRIA, ANEXO
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABELA DE PACIENTES / USUÁRIOS DO CER
CREATE TABLE pacientes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cns VARCHAR(15) NOT NULL UNIQUE,
    cpf VARCHAR(11) NULL UNIQUE,
    nome VARCHAR(255) NOT NULL,
    data_nascimento DATE NOT NULL,
    sexo ENUM('M', 'F') NOT NULL,
    nome_mae VARCHAR(255) NOT NULL,
    municipio_ibge VARCHAR(6) NOT NULL,
    endereco VARCHAR(255) NULL,
    bairro VARCHAR(100) NULL,
    telefone_contato VARCHAR(20) NULL,
    cid_principal VARCHAR(10) NOT NULL,
    cid_secundario VARCHAR(10) NULL,
    modalidades_reabilitacao JSON NULL, -- Ex: ["FISICA", "INTELECTUAL", "AUDITIVA", "VISUAL"]
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    INDEX idx_pacientes_cns (cns),
    INDEX idx_pacientes_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABELA DE PROCEDIMENTOS SIGTAP PARAMETRIZADOS
CREATE TABLE procedimentos_sigtap (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(10) NOT NULL UNIQUE,
    descricao VARCHAR(255) NOT NULL,
    cbos_permitidos JSON NOT NULL, -- Ex: ["223605", "223810", "223905", "251510"]
    idade_minima INT UNSIGNED DEFAULT 0,
    idade_maxima INT UNSIGNED DEFAULT 999,
    sexo_permitido ENUM('M', 'F', 'AMBOS') DEFAULT 'AMBOS',
    ativo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    INDEX idx_procedimentos_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABELA DE PROFISSIONAIS DE SAÚDE
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

-- 6. TABELA DE AGENDAMENTOS (GRADE)
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

-- 7. TABELA PIVÔ AGENDAMENTO - PROFISSIONAIS (SUPORTE A CO-ATENDIMENTO)
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

-- 8. TABELA DE EVOLUÇÕES CLÍNICAS (PEP)
CREATE TABLE evolucoes_clinicas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agendamento_id BIGINT UNSIGNED NOT NULL,
    paciente_id BIGINT UNSIGNED NOT NULL,
    profissional_id BIGINT UNSIGNED NOT NULL,
    queixa_estado_atual TEXT NOT NULL,
    conduta_terapeutica TEXT NOT NULL,
    intercorrencias TEXT NULL,
    assinado_em TIMESTAMP NOT NULL,
    assinatura_hash VARCHAR(64) NOT NULL, -- SHA-256 (paciente + profissional + timestamp + texto)
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

-- 9. TABELA DE ADENDOS RETIFICADORES (PÓS-24 HORAS)
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

-- 10. TABELA DE PRODUÇÃO PARA O BPA-i (SIA/SUS)
CREATE TABLE producoes_bpai (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evolucao_id BIGINT UNSIGNED NOT NULL,
    paciente_id BIGINT UNSIGNED NOT NULL,
    profissional_id BIGINT UNSIGNED NOT NULL,
    procedimento_id BIGINT UNSIGNED NOT NULL,
    cbo_executante VARCHAR(10) NOT NULL,
    cid_atendimento VARCHAR(10) NOT NULL,
    data_atendimento DATE NOT NULL,
    competencia VARCHAR(6) NOT NULL, -- Formato: YYYYMM (ex: 202610)
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

-- 11. TABELA DE LOGS DE AUDITORIA E SEGURANÇA (LGPD / CFM)
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
```