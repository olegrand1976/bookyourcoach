#!/usr/bin/env bash
# Installe schedulers prod BYC (7:45–18h) via infra partagée.
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(cd "${SCRIPT_DIR}/../../.." && pwd)"
exec "${ROOT}/infra/gcp/setup-prod-scaling.sh"
