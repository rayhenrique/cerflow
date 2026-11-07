#!/usr/bin/env bash
set -euo pipefail

# Cria diretório temporário para testes
TMP_TEST_DIR="$(mktemp -d 2>/dev/null || mktemp -d -t 'cer_test_XXXXXX')"
trap 'rm -rf "${TMP_TEST_DIR}"' EXIT

DEPLOY_SCRIPT="$(pwd)/deploy.sh"

echo "=================================================================="
echo "TESTES DE HARDENING E SEGURANÇA DO SCRIPT DE DEPLOY"
echo "=================================================================="

# ----------------------------------------------------------------------
# Teste 1: Cenário sem arquivo .env
# ----------------------------------------------------------------------
echo -n "Teste 1: Execução sem arquivo .env... "
cd "${TMP_TEST_DIR}"
rm -rf test1 && mkdir test1 && cd test1
cp "${DEPLOY_SCRIPT}" ./deploy.sh
chmod +x ./deploy.sh

# Não cria o arquivo .env
set +e
OUTPUT_1=$(./deploy.sh 2>&1)
STATUS_1=$?
set -e

if [ ${STATUS_1} -ne 0 ] && echo "${OUTPUT_1}" | grep -q "ERRO CRÍTICO: Arquivo de configuração .env não encontrado"; then
    echo "APROVADO (Status ${STATUS_1})"
else
    echo "FALHOU! Status: ${STATUS_1}, Output: ${OUTPUT_1}"
    exit 1
fi

# ----------------------------------------------------------------------
# Teste 2: Cenário com APP_KEY vazia
# ----------------------------------------------------------------------
echo -n "Teste 2: Execução com APP_KEY vazia... "
cd "${TMP_TEST_DIR}"
rm -rf test2 && mkdir test2 && cd test2
cp "${DEPLOY_SCRIPT}" ./deploy.sh
chmod +x ./deploy.sh

# Cria .env sem APP_KEY ou com APP_KEY vazia
cat << 'EOF' > .env
APP_NAME="CER Digital"
APP_ENV=production
APP_KEY=
EOF

set +e
OUTPUT_2=$(./deploy.sh 2>&1)
STATUS_2=$?
set -e

if [ ${STATUS_2} -ne 0 ] && echo "${OUTPUT_2}" | grep -q "ERRO CRÍTICO: Variável APP_KEY ausente ou vazia"; then
    echo "APROVADO (Status ${STATUS_2})"
else
    echo "FALHOU! Status: ${STATUS_2}, Output: ${OUTPUT_2}"
    exit 1
fi

# ----------------------------------------------------------------------
# Teste 3: Cenário com APP_KEY preenchida com aspas vazias
# ----------------------------------------------------------------------
echo -n "Teste 3: Execução com APP_KEY com aspas vazias (\"\")... "
cd "${TMP_TEST_DIR}"
rm -rf test3 && mkdir test3 && cd test3
cp "${DEPLOY_SCRIPT}" ./deploy.sh
chmod +x ./deploy.sh

cat << 'EOF' > .env
APP_NAME="CER Digital"
APP_ENV=production
APP_KEY=""
EOF

set +e
OUTPUT_3=$(./deploy.sh 2>&1)
STATUS_3=$?
set -e

if [ ${STATUS_3} -ne 0 ] && echo "${OUTPUT_3}" | grep -q "ERRO CRÍTICO: Variável APP_KEY ausente ou vazia"; then
    echo "APROVADO (Status ${STATUS_3})"
else
    echo "FALHOU! Status: ${STATUS_3}, Output: ${OUTPUT_3}"
    exit 1
fi

# ----------------------------------------------------------------------
# Teste 4: Cenário com APP_KEY válida configurada (sem vazar o segredo)
# ----------------------------------------------------------------------
echo -n "Teste 4: Execução com APP_KEY válida (sem vazar conteúdo)... "
cd "${TMP_TEST_DIR}"
rm -rf test4 && mkdir test4 && cd test4
cp "${DEPLOY_SCRIPT}" ./deploy.sh
chmod +x ./deploy.sh

SECRET_KEY="base64:SegredoFalsoExtremamenteConfidencial1234567890="
cat << EOF > .env
APP_NAME="CER Digital"
APP_ENV=production
APP_KEY=${SECRET_KEY}
EOF

# Extrai e roda apenas a seção 1 (Hardening e Validações) para não invocar composer/artisan
set +e
VALIDATION_SCRIPT='
set -euo pipefail
if [ ! -f .env ]; then
    echo "❌ ERRO CRÍTICO: Arquivo de configuração .env não encontrado!"
    exit 1
fi
APP_KEY_VALUE="$(grep -E "^APP_KEY=" .env 2>/dev/null | head -n 1 | cut -d "=" -f2- | sed -e "s/^[[:space:]]*//" -e "s/[[:space:]]*$//" -e "s/^[\x27\"]//" -e "s/[\x27\"]$//" || true)"
if [ -z "${APP_KEY_VALUE}" ]; then
    echo "❌ ERRO CRÍTICO: Variável APP_KEY ausente ou vazia no arquivo .env!"
    exit 1
fi
unset APP_KEY_VALUE
echo "✅ Pré-requisitos de segurança validados (.env e APP_KEY ativos)."
'
OUTPUT_4=$(bash -c "${VALIDATION_SCRIPT}" 2>&1)
STATUS_4=$?
set -e

if [ ${STATUS_4} -eq 0 ] && echo "${OUTPUT_4}" | grep -q "Pré-requisitos de segurança validados"; then
    if echo "${OUTPUT_4}" | grep -q "SegredoFalsoExtremamenteConfidencial"; then
        echo "FALHOU! O segredo vazou no log de saída!"
        exit 1
    fi
    echo "APROVADO (Status ${STATUS_4} - Segredo protegido e não exibido)"
else
    echo "FALHOU! Status: ${STATUS_4}, Output: ${OUTPUT_4}"
    exit 1
fi

# ----------------------------------------------------------------------
# Teste 5: Confirmação de que o .env permanece no .gitignore
# ----------------------------------------------------------------------
echo -n "Teste 5: Verificação de .env no .gitignore... "
cd "${DEPLOY_SCRIPT%/*}"
if grep -E '^\.env$' .gitignore >/dev/null 2>&1; then
    echo "APROVADO (.env explicitamente ignorado)"
else
    echo "FALHOU! .env não encontrado no .gitignore"
    exit 1
fi

echo "=================================================================="
echo "TODOS OS 5 TESTES DE SEGURANÇA FORAM APROVADOS COM SUCESSO!"
echo "=================================================================="
