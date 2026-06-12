#!/usr/bin/env bash
# update_fillable.sh
# Atualiza o $fillable de uma Model Laravel com base na migration de criação da tabela
#
# Uso:
#   ./update_fillable.sh User
#   ./update_fillable.sh ProductCategory
#
# Variáveis de ambiente opcionais:
#   MIGRATIONS_DIR=database/migrations  (padrão)
#   MODELS_DIR=app/Models               (padrão)

set -euo pipefail

MIGRATIONS_DIR="${MIGRATIONS_DIR:-database/migrations}"
MODELS_DIR="${MODELS_DIR:-app/Models}"

BOLD='\033[1m'
GREEN='\033[0;32m'
CYAN='\033[0;36m'
YELLOW='\033[0;33m'
RED='\033[0;31m'
RESET='\033[0m'

usage() {
    echo "Uso: $0 NomeDaModel"
    echo "Exemplo: $0 User"
    echo "         $0 ProductCategory"
    exit 1
}

to_snake_case() {
    echo "$1" | sed 's/\([A-Z]\)/_\1/g' | sed 's/^_//' | tr '[:upper:]' '[:lower:]'
}

to_plural() {
    local word="$1"
    # consoante + y → ies (category, modality, policy, company...)
    if [[ "$word" =~ [^aeiou]y$ ]]; then
        echo "${word%y}ies"
    else
        case "$word" in
            *fe)               echo "${word%fe}ves" ;;
            *lf)               echo "${word%lf}lves" ;;
            *s|*x|*z|*ch|*sh) echo "${word}es" ;;
            *)                 echo "${word}s" ;;
        esac
    fi
}

[ $# -lt 1 ] && usage

MODEL_NAME="$1"
SNAKE=$(to_snake_case "$MODEL_NAME")
TABLE=$(to_plural "$SNAKE")

echo -e "${BOLD}Model:${RESET}  ${MODEL_NAME}"
echo -e "${BOLD}Tabela:${RESET} ${TABLE}"
echo ""

# ── Localizar migration ───────────────────────────────────────────────────────

MIGRATION=$(find "$MIGRATIONS_DIR" -maxdepth 1 -name "*create_${TABLE}_table*.php" | sort | tail -1)

if [ -z "$MIGRATION" ]; then
    echo -e "${RED}✗ Migration não encontrada para a tabela '${TABLE}' em ${MIGRATIONS_DIR}${RESET}"
    echo -e "  Esperado algo como: create_${TABLE}_table.php"
    exit 1
fi

echo -e "${CYAN}Migration:${RESET} $(basename "$MIGRATION")"

# ── Extrair colunas ───────────────────────────────────────────────────────────

COLS=$(grep -oP '\$table->\w+\(\K'"'"'[^'"'"']+'"'"'' "$MIGRATION" 2>/dev/null \
    | tr -d "'" \
    | grep -vP '^(id|created_at|updated_at|deleted_at)$' || true)

if [ -z "$COLS" ]; then
    echo -e "${RED}✗ Nenhuma coluna encontrada na migration.${RESET}"
    exit 1
fi

echo ""
echo -e "${BOLD}Colunas extraídas:${RESET}"
echo "$COLS" | sed "s/^/  • /"
echo ""

# ── Localizar model ───────────────────────────────────────────────────────────

MODEL_FILE="${MODELS_DIR}/${MODEL_NAME}.php"

if [ ! -f "$MODEL_FILE" ]; then
    echo -e "${RED}✗ Model não encontrada: ${MODEL_FILE}${RESET}"
    exit 1
fi

echo -e "${CYAN}Model:${RESET} ${MODEL_FILE}"

# ── Atualizar $fillable via python ────────────────────────────────────────────

ACTION=$(python3 - "$MODEL_FILE" "$MODEL_NAME" "$COLS" << 'PYEOF'
import re, sys

model_file = sys.argv[1]
model_name = sys.argv[2]
cols       = [c for c in sys.argv[3].splitlines() if c.strip()]

fillable = "    protected $fillable = [\n"
for col in cols:
    fillable += f"        '{col}',\n"
fillable += "    ];"

with open(model_file, "r") as f:
    content = f.read()

pattern = r'[ \t]*protected\s+\$fillable\s*=\s*\[.*?\];'
if re.search(pattern, content, re.DOTALL):
    content = re.sub(pattern, fillable, content, flags=re.DOTALL)
    print("substituído")
else:
    content = re.sub(r'(class\s+\w+[^{]*\{\s*\n)', r'\1' + fillable + '\n\n', content)
    print("inserido")

with open(model_file, "w") as f:
    f.write(content)
PYEOF
)

echo -e "${GREEN}✓ \$fillable ${ACTION} com sucesso.${RESET}"
echo ""
echo -e "${BOLD}Resultado:${RESET}"
echo "    protected \$fillable = ["
echo "$COLS" | sed "s/^/        '/;s/$/',/"
echo "    ];"