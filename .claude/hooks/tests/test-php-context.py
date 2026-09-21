#!/usr/bin/env python3
"""Regression suite for PHP context resolution in the hooks.

Run it from anywhere: python3 .claude/hooks/tests/test-php-context.py
Exit 0 means every check passed. Exit 1 lists the failures.

Every section carries a positive control, because a suite that only proves the hooks stay
quiet proves nothing: a hook that never fires passes every negative test.

What it pins down:
  A. Identity still resolves in this repository, from absolute AND relative paths, since a
     hook receives whichever the caller passed.
  B. A path outside the project root resolves to no manifest, so a foreign file never
     inherits this project's PSR-4 prefix and gets reported against a namespace it never
     claimed.
  C. A manifest below the project root is found, which is the case the upward walk exists
     for and the case the root-only check used to miss in silence.
  D. A repository with no manifest gates every PHP check off without output.
  E. A malformed or partial manifest degrades to a skip, never a traceback.
  F. The real tree is still clean.
"""
import subprocess
import sys
import tempfile
from pathlib import Path

HOOKS = Path(__file__).resolve().parent.parent
REPO = HOOKS.parent.parent
sys.path.insert(0, str(HOOKS))

from hooklib import php  # noqa: E402

FOREIGN = '''<?php

namespace Acme\\Other\\Application\\Domain\\Models;

use Foreign\\Library\\Helper;

final readonly class Thing
{
}
'''

failures = []


def check(label, actual, expected):
    ok = actual == expected
    print(f"  {'PASS' if ok else 'FAIL'}  {label}")
    if not ok:
        print(f"        expected {expected!r}")
        print(f"        actual   {actual!r}")
        failures.append(label)


def run_hook(hook, path, project_dir, cwd):
    proc = subprocess.run(
        [sys.executable, '-B', str(HOOKS / f'{hook}.py'), str(path)],
        capture_output=True, text=True, cwd=str(cwd),
        env={'CLAUDE_PROJECT_DIR': str(project_dir), 'PATH': '/usr/bin:/bin', 'HOME': '/tmp'},
    )
    return proc.returncode, (proc.stdout + proc.stderr).strip()


print("=== A. in-tree resolution still works (positive control) ===")
import json
import os

os.environ['CLAUDE_PROJECT_DIR'] = str(REPO)
os.chdir(REPO)

# Expected values are derived from this repository's own manifest, never written here, so the
# suite travels with the toolkit instead of asserting one organization's identity. Each one is
# read a DIFFERENT way than the implementation reads it, so the assertion cannot be a tautology.
manifest = json.loads((REPO / 'composer.json').read_text(encoding='utf-8'))
src_prefix = next(p for p, t in manifest['autoload']['psr-4'].items() if t in ('src/', 'src'))
vendor = manifest['name'].split('/', 1)[0]
print(f"  (derived from composer.json: vendor {vendor!r}, src prefix {src_prefix!r})")

check("absolute src path resolves the whole root namespace",
      php.nearest_src_prefix(REPO / 'src'), src_prefix)
check("RELATIVE src path resolves it too (paths arrive relative)",
      php.nearest_src_prefix(Path('src')), src_prefix)
check("own_vendor matches the manifest name",
      php.own_vendor(), vendor)
check("context_present true for an in-tree php file",
      php.context_present(Path('src')), True)

# The property that matters, stated independently of how the code computes it: the allow-prefix
# must be the WHOLE root namespace, so a package sharing only its first segment stays foreign.
first_segment = src_prefix.split('\\', 1)[0] + '\\'
if first_segment != src_prefix:
    foreign = first_segment + 'SomeVendorPackage\\Thing'
    check("a package sharing only the first namespace segment is NOT own code",
          foreign.startswith(php.nearest_src_prefix(Path('src'))), False)
else:
    print("  SKIP  single-segment root namespace, the first segment IS the whole prefix")

print()
print("=== B. out-of-tree paths no longer inherit this project's identity ===")
with tempfile.TemporaryDirectory() as tmp:
    out = Path(tmp) / 'outside/src/Application/Domain/Models'
    out.mkdir(parents=True)
    (out / 'Thing.php').write_text(FOREIGN)

    check("nearest_manifest returns None for an out-of-tree file",
          php.nearest_manifest(out / 'Thing.php'), None)
    check("context_present false for an out-of-tree file",
          php.context_present(out / 'Thing.php'), False)
    check("nearest_src_prefix empty for an out-of-tree file (restores the diagnostic)",
          php.nearest_src_prefix(out / 'Thing.php'), '')

    rc, msg = run_hook('check-php-domain-imports', out / 'Thing.php', REPO, REPO)
    check("domain-imports exits 0 on an out-of-tree file", rc, 0)
    check("domain-imports says nothing about it", msg, '')

    rc, msg = run_hook('check-php-hexagonal-layers', out / 'Thing.php', REPO, REPO)
    check("hexagonal-layers exits 0 on an out-of-tree file", rc, 0)

print()
print("=== C. the bug the refactor was for: manifest below the project root ===")
with tempfile.TemporaryDirectory() as tmp:
    mono = Path(tmp) / 'mono'
    svc = mono / 'services/thing'
    (svc / 'src/Application/Domain/Models').mkdir(parents=True)
    (svc / 'composer.json').write_text(
        '{"name":"acme/thing","autoload":{"psr-4":{"Acme\\\\Thing\\\\":"src/"}}}')
    target = svc / 'src/Application/Domain/Models/Thing.php'
    target.write_text(FOREIGN)

    rc, msg = run_hook('check-php-domain-imports', target, mono, mono)
    check("domain-imports CATCHES the violation (exit 2)", rc, 2)
    check("and binds the sub-root's WHOLE root namespace, not the parent's and not a segment of it",
          'only Acme\\Thing\\* ' in msg, True)

print()
print("=== D. non-PHP project gates off silently ===")
with tempfile.TemporaryDirectory() as tmp:
    go = Path(tmp) / 'goproj'
    (go / 'src/Application/Domain/Models').mkdir(parents=True)
    (go / 'go.mod').write_text('module example.com/x\n')
    target = go / 'src/Application/Domain/Models/Thing.php'
    target.write_text(FOREIGN)

    rc, msg = run_hook('check-php-domain-imports', target, go, go)
    check("domain-imports exits 0 in a non-PHP repo", rc, 0)
    check("and says nothing", msg, '')

print()
print("=== E. malformed and partial manifests degrade, never crash ===")
with tempfile.TemporaryDirectory() as tmp:
    for name, body in [
        ('broken', '{ not json'),
        ('noautoload', '{"name":"acme/x"}'),
        ('nosrcmap', '{"name":"acme/x","autoload":{"psr-4":{"Acme\\\\X\\\\":"lib/"}}}'),
        ('noname', '{"autoload":{"psr-4":{"Acme\\\\X\\\\":"src/"}}}'),
    ]:
        proj = Path(tmp) / name
        (proj / 'src/Application/Domain/Models').mkdir(parents=True)
        (proj / 'composer.json').write_text(body)
        target = proj / 'src/Application/Domain/Models/Thing.php'
        target.write_text(FOREIGN)
        rc, msg = run_hook('check-php-domain-imports', target, proj, proj)
        check(f"domain-imports survives a {name} manifest (exit 0 or 2, no traceback)",
              rc in (0, 2) and 'Traceback' not in msg, True)
        rc, msg = run_hook('check-php-hexagonal-layers', target, proj, proj)
        check(f"hexagonal-layers survives a {name} manifest",
              rc in (0, 2) and 'Traceback' not in msg, True)

print()
print("=== F. the real tree is still clean (positive control) ===")
tracked = subprocess.run(['git', '-C', str(REPO), 'ls-files', 'src/*.php', 'tests/*.php'],
                         capture_output=True, text=True).stdout.split()
print(f"  ({len(tracked)} tracked php files)")
for hook in ('check-php-domain-imports', 'check-php-hexagonal-layers', 'check-php-member-ordering'):
    proc = subprocess.run([sys.executable, '-B', str(HOOKS / f'{hook}.py'), *tracked],
                          capture_output=True, text=True, cwd=str(REPO),
                          env={'CLAUDE_PROJECT_DIR': str(REPO), 'PATH': '/usr/bin:/bin', 'HOME': '/tmp'})
    check(f"{hook} clean on the real tree", (proc.returncode, proc.stderr.strip()), (0, ''))

print()
print("=" * 62)
print("FAILURES:", failures or "none")
sys.exit(1 if failures else 0)
