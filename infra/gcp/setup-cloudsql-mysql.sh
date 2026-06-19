#!/usr/bin/env bash
# Délègue au MySQL partagé LL-IT (ll-it-mysql).
exec "$(cd "$(dirname "${BASH_SOURCE[0]}")/../../../infra/shared-mysql" && pwd)/setup-gcp.sh" "$@"
