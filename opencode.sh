#!/usr/bin/env bash
cd "$(dirname "$0")" || exit 1
set -a
source "$(dirname "$0")/agents.env"
set +a
exec opencode "$@"
