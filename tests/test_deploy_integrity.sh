#!/usr/bin/env bash
set -euo pipefail

# ==============================================================================
# Bateria de Testes Automatizados de Integridade do deploy.sh
# Executa o PRÓPRIO deploy.sh em ambientes isolados com comandos simulados (mocks)
# ==============================================================================

ROOT_DIR="$(pwd)"
DEPLOY_SCRIPT="${ROOT_DIR}/deploy.sh"
TMP_BASE="$(mktemp -d 2>/dev/null || mktemp -d -t 'cer_deploy_test_XXXXXX')"
trap 'rm -rf "${TMP_BASE}"' EXIT

PASSED_COUNT=0
TOTAL_COUNT=7

echo "=================================================================="
echo "TESTES DE INTEGRIDADE E HARDENING DO DEPLOY.SH (EXECUÇÃO REAL)"
echo "=================================================================="

# Função auxiliar para preparar ambiente isolado com mocks
preparar_ambiente() {
    local test_name="$1"
    local test_dir="${TMP_BASE}/${test_name}"
    mkdir -p "${test_dir}/bin" "${test_dir}/storage" "${test_dir}/bootstrap/cache"
    cp "${DEPLOY_SCRIPT}" "${test_dir}/deploy.sh"
    chmod +x "${test_dir}/deploy.sh"

    # Mock de php
    cat << 'EOF' > "${test_dir}/bin/php"
#!/usr/bin/env bash
exit 0
EOF
    chmod +x "${test_dir}/bin/php"

    # Mock de composer
    cat << 'EOF' > "${test_dir}/bin/composer"
#!/usr/bin/env bash
exit 0
EOF
    chmod +x "${test_dir}/bin/composer"

    # Mock de npm
    cat << 'EOF' > "${test_dir}/bin/npm"
#!/usr/bin/env bash
exit 0
EOF
    chmod +x "${test_dir}/bin/npm"

    # Mock padrão de git (repassa para o git do sistema por padrão)
    cat << 'EOF' > "${test_dir}/bin/git"
#!/usr/bin/env bash
if [ "${1:-}" = "config" ] || [ "${1:-}" = "fetch" ] || [ "${1:-}" = "checkout" ] || [ "${1:-}" = "reset" ]; then
    exit 0
fi
if [ "${1:-}" = "ls-files" ]; then
    # Por padrão, .env não está rastreado (retorna 1)
    exit 1
fi
exec git "$@"
EOF
    chmod +x "${test_dir}/bin/git"

    echo "${test_dir}"
}

# ----------------------------------------------------------------------
# Cenário 1: .env ausente
# ----------------------------------------------------------------------
echo -n "Cenário 1: .env ausente... "
TEST_DIR=$(preparar_ambiente "cenario1")
cd "${TEST_DIR}"
export PATH="${TEST_DIR}/bin:${PATH}"

set +e
OUTPUT_1=$(./deploy.sh 2>&1)
STATUS_1=$?
set -e

if [ ${STATUS_1} -ne 0 ] && echo "${OUTPUT_1}" | grep -q "ERRO CRÍTICO: Arquivo de configuração .env não encontrado"; then
    echo "APROVADO (Interrompeu com código ${STATUS_1})"
    PASSED_COUNT=$((PASSED_COUNT + 1))
else
    echo "FALHOU! Status: ${STATUS_1}, Output: ${OUTPUT_1}"
    exit 1
fi

# ----------------------------------------------------------------------
# Cenário 2: APP_KEY vazia
# ----------------------------------------------------------------------
echo -n "Cenário 2: APP_KEY vazia... "
TEST_DIR=$(preparar_ambiente "cenario2")
cd "${TEST_DIR}"
export PATH="${TEST_DIR}/bin:${PATH}"

cat << 'EOF' > .env
APP_NAME="CER Digital"
APP_ENV=production
APP_KEY=""
EOF

set +e
OUTPUT_2=$(./deploy.sh 2>&1)
STATUS_2=$?
set -e

if [ ${STATUS_2} -ne 0 ] && echo "${OUTPUT_2}" | grep -q "ERRO CRÍTICO: Variável APP_KEY ausente ou vazia"; then
    echo "APROVADO (Interrompeu com código ${STATUS_2})"
    PASSED_COUNT=$((PASSED_COUNT + 1))
else
    echo "FALHOU! Status: ${STATUS_2}, Output: ${OUTPUT_2}"
    exit 1
fi

# ----------------------------------------------------------------------
# Cenário 3: .env rastreado pelo Git
# ----------------------------------------------------------------------
echo -n "Cenário 3: .env rastreado pelo Git... "
TEST_DIR=$(preparar_ambiente "cenario3")
cd "${TEST_DIR}"
export PATH="${TEST_DIR}/bin:${PATH}"

cat << 'EOF' > .env
APP_NAME="CER Digital"
APP_ENV=production
APP_KEY="base64:SegredoValido12345678901234567890="
EOF

# Simula que o git reporta que o .env está rastreado no repositório
cat << 'EOF' > "${TEST_DIR}/bin/git"
#!/usr/bin/env bash
if [ "${1:-}" = "ls-files" ] && [ "${2:-}" = "--error-unmatch" ] && [ "${3:-}" = ".env" ]; then
    echo ".env"
    exit 0 # Rastreado!
fi
exit 0
EOF
chmod +x "${TEST_DIR}/bin/git"

set +e
OUTPUT_3=$(./deploy.sh 2>&1)
STATUS_3=$?
set -e

if [ ${STATUS_3} -ne 0 ] && echo "${OUTPUT_3}" | grep -q "ERRO CRÍTICO DE SEGURANÇA: O arquivo .env está sendo rastreado pelo Git"; then
    echo "APROVADO (Interrompeu com código ${STATUS_3})"
    PASSED_COUNT=$((PASSED_COUNT + 1))
else
    echo "FALHOU! Status: ${STATUS_3}, Output: ${OUTPUT_3}"
    exit 1
fi

# ----------------------------------------------------------------------
# Cenário 4: .env removido durante a sincronização Git
# ----------------------------------------------------------------------
echo -n "Cenário 4: .env removido durante o Git (restauração com sucesso)... "
TEST_DIR=$(preparar_ambiente "cenario4")
cd "${TEST_DIR}"
export PATH="${TEST_DIR}/bin:${PATH}"

ORIGINAL_SECRET="base64:SegredoOriginalImutavelDoCenario4_1234567890="
cat << EOF > .env
APP_NAME="CER Digital"
APP_ENV=production
APP_KEY=${ORIGINAL_SECRET}
EOF

# Mock do git que apaga o .env na chamada de reset
cat << 'EOF' > "${TEST_DIR}/bin/git"
#!/usr/bin/env bash
if [ "${1:-}" = "ls-files" ]; then
    exit 1 # Não rastreado
fi
if [ "${1:-}" = "reset" ]; then
    rm -f .env
fi
exit 0
EOF
chmod +x "${TEST_DIR}/bin/git"

set +e
OUTPUT_4=$(./deploy.sh 2>&1)
STATUS_4=$?
set -e

if [ ${STATUS_4} -eq 0 ] && echo "${OUTPUT_4}" | grep -q "Arquivo .env restaurado com sucesso" && grep -q "${ORIGINAL_SECRET}" .env; then
    echo "APROVADO (Restaurou .env e concluiu com sucesso)"
    PASSED_COUNT=$((PASSED_COUNT + 1))
else
    echo "FALHOU! Status: ${STATUS_4}, Output: ${OUTPUT_4}"
    exit 1
fi

# ----------------------------------------------------------------------
# Cenário 5: .env sobrescrito com conteúdo adulterado mantendo o arquivo existente
# ----------------------------------------------------------------------
echo -n "Cenário 5: .env sobrescrito mantendo arquivo (restauração via SHA-256)... "
TEST_DIR=$(preparar_ambiente "cenario5")
cd "${TEST_DIR}"
export PATH="${TEST_DIR}/bin:${PATH}"

ORIGINAL_SECRET_5="base64:SegredoOriginalImutavelDoCenario5_1234567890="
cat << EOF > .env
APP_NAME="CER Digital"
APP_ENV=production
APP_KEY=${ORIGINAL_SECRET_5}
EOF

# Mock do git que sobrescreve o .env com novos dados adulterados (arquivo continua existindo!)
cat << 'EOF' > "${TEST_DIR}/bin/git"
#!/usr/bin/env bash
if [ "${1:-}" = "ls-files" ]; then
    exit 1 # Não rastreado
fi
if [ "${1:-}" = "reset" ]; then
    echo "APP_NAME=Adulterado" > .env
    echo "APP_KEY=base64:ChaveAdulteradaIncorreta=" >> .env
fi
exit 0
EOF
chmod +x "${TEST_DIR}/bin/git"

set +e
OUTPUT_5=$(./deploy.sh 2>&1)
STATUS_5=$?
set -e

if [ ${STATUS_5} -eq 0 ] && echo "${OUTPUT_5}" | grep -q "Arquivo .env restaurado com sucesso" && grep -q "${ORIGINAL_SECRET_5}" .env; then
    echo "APROVADO (Detectou divergência no SHA-256, restaurou .env original e concluiu)"
    PASSED_COUNT=$((PASSED_COUNT + 1))
else
    echo "FALHOU! Status: ${STATUS_5}, Output: ${OUTPUT_5}"
    exit 1
fi

# ----------------------------------------------------------------------
# Cenário 6: APP_KEY alterada/esvaziada durante a sincronização
# ----------------------------------------------------------------------
echo -n "Cenário 6: APP_KEY esvaziada durante sincronização (restauração segura)... "
TEST_DIR=$(preparar_ambiente "cenario6")
cd "${TEST_DIR}"
export PATH="${TEST_DIR}/bin:${PATH}"

ORIGINAL_SECRET_6="base64:SegredoOriginalImutavelDoCenario6_1234567890="
cat << EOF > .env
APP_NAME="CER Digital"
APP_ENV=production
APP_KEY=${ORIGINAL_SECRET_6}
EOF

# Mock do git que esvazia a APP_KEY
cat << 'EOF' > "${TEST_DIR}/bin/git"
#!/usr/bin/env bash
if [ "${1:-}" = "ls-files" ]; then
    exit 1 # Não rastreado
fi
if [ "${1:-}" = "reset" ]; then
    echo "APP_KEY=" > .env
fi
exit 0
EOF
chmod +x "${TEST_DIR}/bin/git"

set +e
OUTPUT_6=$(./deploy.sh 2>&1)
STATUS_6=$?
set -e

if [ ${STATUS_6} -eq 0 ] && echo "${OUTPUT_6}" | grep -q "Arquivo .env restaurado com sucesso" && grep -q "${ORIGINAL_SECRET_6}" .env; then
    echo "APROVADO (Validou novamente APP_KEY, restaurou e concluiu)"
    PASSED_COUNT=$((PASSED_COUNT + 1))
else
    echo "FALHOU! Status: ${STATUS_6}, Output: ${OUTPUT_6}"
    exit 1
fi

# ----------------------------------------------------------------------
# Cenário 7: Deploy normal sem alteração do .env
# ----------------------------------------------------------------------
echo -n "Cenário 7: Deploy normal sem alteração do .env... "
TEST_DIR=$(preparar_ambiente "cenario7")
cd "${TEST_DIR}"
export PATH="${TEST_DIR}/bin:${PATH}"

ORIGINAL_SECRET_7="base64:SegredoOriginalDoCenario7_1234567890="
cat << EOF > .env
APP_NAME="CER Digital"
APP_ENV=production
APP_KEY=${ORIGINAL_SECRET_7}
EOF

# Git normal (sem alterar nada)
cat << 'EOF' > "${TEST_DIR}/bin/git"
#!/usr/bin/env bash
if [ "${1:-}" = "ls-files" ]; then
    exit 1 # Não rastreado
fi
exit 0
EOF
chmod +x "${TEST_DIR}/bin/git"

set +e
OUTPUT_7=$(./deploy.sh 2>&1)
STATUS_7=$?
set -e

if [ ${STATUS_7} -eq 0 ] && echo "${OUTPUT_7}" | grep -q "Deploy concluído com sucesso" && grep -q "${ORIGINAL_SECRET_7}" .env && [ ! -f .env.deploy_guard.bak ]; then
    if echo "${OUTPUT_7}" | grep -q "${ORIGINAL_SECRET_7}"; then
        echo "FALHOU! A chave vazou no log de saída!"
        exit 1
    fi
    echo "APROVADO (Deploy bem-sucedido, .env intacto, backup removido e segredo preservado)"
    PASSED_COUNT=$((PASSED_COUNT + 1))
else
    echo "FALHOU! Status: ${STATUS_7}, Output: ${OUTPUT_7}"
    exit 1
fi

echo "=================================================================="
echo "RESULTADO: ${PASSED_COUNT} DE ${TOTAL_COUNT} CENÁRIOS EXECUTADOS E APROVADOS COM SUCESSO!"
echo "=================================================================="
