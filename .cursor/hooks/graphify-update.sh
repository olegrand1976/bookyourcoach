#!/usr/bin/env bash
# PostToolUse: refresh graphify AST graph after code-mutating tools.
# Fail-open: never block the agent on graphify errors.

set +e
export PATH="${HOME}/.local/bin:/usr/local/bin:${PATH}"

input=$(cat)

root=$(GRAPHIFY_HOOK_INPUT="$input" python3 - <<'PY'
import json, os, time
from pathlib import Path

try:
    data = json.loads(os.environ.get("GRAPHIFY_HOOK_INPUT") or "")
except Exception:
    print("")
    raise SystemExit(0)

roots = data.get("workspace_roots") or []
root = roots[0] if roots else ""
if not root:
    # Project hooks usually run with cwd = project root
    root = os.getcwd()

root_path = Path(root)
if not (root_path / "graphify-out" / "graph.json").is_file():
    print("")
    raise SystemExit(0)

stamp_dir = Path.home() / ".cursor" / "hooks" / ".graphify-update-stamps"
stamp_dir.mkdir(parents=True, exist_ok=True)
key = stamp_dir / (root.replace("/", "_").lstrip("_") + ".ts")
now = time.time()
try:
    if key.is_file() and now - float(key.read_text().strip() or "0") < 10:
        print("")
        raise SystemExit(0)
except SystemExit:
    raise
except Exception:
    pass
try:
    key.write_text(str(now))
except Exception:
    pass

print(root)
PY
)

if [[ -z "${root}" ]]; then
  exit 0
fi

if ! command -v graphify >/dev/null 2>&1; then
  exit 0
fi

(
  cd "$root" && graphify update . >/dev/null 2>&1
) &
disown 2>/dev/null || true

exit 0

