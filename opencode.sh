#!/usr/bin/env bash
cd "$(dirname "$0")" || exit 1
set -a
set +a
exec opencode "$@"
