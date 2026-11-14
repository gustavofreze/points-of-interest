#!/usr/bin/env python3
"""Regression suite for the Markdown layout hook.

Run it from anywhere: python3 .claude/hooks/tests/test-markdown-layout.py
Exit 0 means every check passed. Exit 1 lists the failures.

Every section carries a positive control, because a suite that only proves the hook stays
quiet proves nothing: a hook that never fires passes every negative test.

What it pins down:
  A. Each layout rule fires on its own violation, and a clean README stays quiet.
  B. What is not prose is exempt: fenced code, table rows, headings, HTML, and a line no
     wrap can shorten.
  C. --fix repairs every mechanical violation, changes nothing but whitespace, and leaves
     a file the check accepts. Running it twice changes nothing more.
  D. Only a README.md is in scope, from argv and from the stdin payload alike.
  E. The real tree is still clean.
"""
import json
import subprocess
import sys
import tempfile
from pathlib import Path

HOOKS = Path(__file__).resolve().parent.parent
REPO = HOOKS.parent.parent
HOOK = HOOKS / 'check-markdown-layout.py'
ENVIRONMENT = {'CLAUDE_PROJECT_DIR': str(REPO), 'PATH': '/usr/bin:/bin', 'HOME': '/tmp'}

WORD = 'word '
LONG_LINE = (WORD * 30).strip()
SPLIT_SPAN = f"{(WORD * 22).strip()} **two\nwords** and the rest of the sentence."
AFTER_OPEN = "A sentence that breaks right after an opening parenthesis (\nlike this one) does."
BEFORE_CLOSE = "A sentence that breaks right before a closing parenthesis (like this one\n) does."
CLEAN = "# Title\n\nA short paragraph with `inline code` and a [link](https://example.com).\n"

EXEMPT = '\n'.join([
    '# ' + LONG_LINE,
    '',
    '| ' + LONG_LINE + ' |',
    '',
    '```',
    LONG_LINE,
    '```',
    '',
    '<div id="' + 'x' * 130 + '"></div>',
    '',
    '[![Badge](https://example.com/' + 'a' * 120 + ')](https://example.com)',
    '',
    '![An image with spaces in its text](https://example.com/' + 'a' * 120 + '.svg)',
    '',
])

failures = []


def check(label, actual, expected):
    ok = actual == expected
    print(f"  {'PASS' if ok else 'FAIL'}  {label}")
    if not ok:
        print(f"        expected {expected!r}")
        print(f"        actual   {actual!r}")
        failures.append(label)


def run_hook(*arguments, payload=None):
    proc = subprocess.run(
        [sys.executable, '-B', str(HOOK), *[str(argument) for argument in arguments]],
        capture_output=True, text=True, cwd=str(REPO), env=ENVIRONMENT, input=payload,
    )
    return proc.returncode, (proc.stdout + proc.stderr).strip()


def squashed(text):
    return ''.join(''.join(line.lstrip(' >').split()) for line in text.split('\n'))


with tempfile.TemporaryDirectory() as tmp:
    readme = Path(tmp) / 'README.md'

    print("=== A. each rule fires, a clean file stays quiet ===")
    readme.write_text(CLEAN)
    check("a clean README passes in silence", run_hook(readme), (0, ''))
    for label, body, expected in [
        ("a line over the wrap column", LONG_LINE, "over the 120-column wrap"),
        ("an inline span split across a break", SPLIT_SPAN, "inline span split across a line break"),
        ("a break after an opening delimiter", AFTER_OPEN, "ends on an opening delimiter"),
        ("a break before a closing delimiter", BEFORE_CLOSE, "starts on a closing delimiter"),
    ]:
        readme.write_text(f"# Title\n\n{body}\n")
        rc, message = run_hook(readme)
        check(f"{label} is reported (positive control)", (rc, expected in message), (2, True))

    print()
    print("=== B. what is not prose is exempt ===")
    readme.write_text(EXEMPT)
    check("headings, tables, fences, HTML and unbreakable lines pass", run_hook(readme), (0, ''))

    print()
    print("=== C. --fix repairs, and only moves whitespace ===")
    for label, body in [
        ("a line over the wrap column", LONG_LINE),
        ("an inline span split across a break", SPLIT_SPAN),
        ("a break after an opening delimiter", AFTER_OPEN),
        ("a break before a closing delimiter", BEFORE_CLOSE),
        ("a long list item", f"- {LONG_LINE}"),
        ("a long quoted line", f"> {LONG_LINE}"),
    ]:
        before = f"# Title\n\n{body}\n"
        readme.write_text(before)
        rc, _ = run_hook('--fix', readme)
        after = readme.read_text()
        check(f"--fix repairs {label}", (rc, run_hook(readme)), (0, (0, '')))
        check(f"--fix on {label} moves whitespace only", squashed(after), squashed(before))
        run_hook('--fix', readme)
        check(f"--fix on {label} is idempotent", readme.read_text(), after)

    readme.write_text(f"# Title\n\n- {LONG_LINE}\n")
    run_hook('--fix', readme)
    continuation = readme.read_text().split('\n')[3]
    check("a wrapped list item continues under its text", continuation.startswith('  word'), True)

    print()
    print("=== D. only a README.md is in scope ===")
    other = Path(tmp) / 'NOTES.md'
    other.write_text(f"# Title\n\n{LONG_LINE}\n")
    check("another Markdown file is ignored", run_hook(other), (0, ''))
    readme.write_text(f"# Title\n\n{LONG_LINE}\n")
    payload = json.dumps({'tool_input': {'file_path': str(readme)}})
    rc, message = run_hook(payload=payload)
    check("the stdin payload reaches the check (positive control)", rc, 2)
    payload = json.dumps({'tool_input': {'file_path': str(other)}})
    check("the stdin payload of another file is ignored", run_hook(payload=payload), (0, ''))

print()
print("=== E. the real tree is still clean (positive control) ===")
tracked = subprocess.run(['git', '-C', str(REPO), 'ls-files', 'README.md', '**/README.md'],
                         capture_output=True, text=True).stdout.split()
print(f"  ({len(tracked)} tracked README files)")
check("at least one README is tracked", bool(tracked), True)
check("the hook is clean on the real tree", run_hook(*tracked), (0, ''))

print()
print("=" * 62)
print("FAILURES:", failures or "none")
sys.exit(1 if failures else 0)
