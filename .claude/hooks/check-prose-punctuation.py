#!/usr/bin/env python3
"""Prose punctuation check for Markdown prose, shell comments, and PHP comments.

Flags the em-dash, the en-dash, the spaced double hyphen, and (Markdown only) the
semicolon used as clause separators. Fenced code, table rows, and inline code are
exempt.
"""

import re
import sys
from pathlib import Path
from typing import Final

from hooklib import php
from hooklib.core import FileUnit, Violation, read_unit, run_check

# Any Markdown file is in scope. PHP sources are in scope under src/ or tests/. Shell
# scripts are in scope wherever they ship, including the skeleton asset templates.
MARKDOWN_PATTERN: Final = re.compile(r"\.md$")
SHELL_PATTERN: Final = re.compile(r"\.sh$")
SHELL_COMMENT: Final = re.compile(r"^[ \t]*#.*$", re.MULTILINE)

# Prohibited prose punctuation as clause separators. Em-dash U+2014, en-dash U+2013,
# spaced double hyphen, and (Markdown only) the semicolon.
PROSE_PUNCTUATION: Final = re.compile(r"[\u2014\u2013]| -- |;")
PROSE_DASHES: Final = re.compile(r"[\u2014\u2013]| -- ")
FENCE: Final = re.compile(r"^\s*```")
INLINE_CODE: Final = re.compile(r"`[^`]*`")


def is_markdown(path: str) -> bool:
    """Whether the path is a Markdown file, routed to the Markdown prose check."""
    return bool(MARKDOWN_PATTERN.search(path))


def markdown_violations(unit: FileUnit) -> tuple[Violation, ...]:
    """Markdown prose violations. Fenced code and table rows are exempt."""
    violations = []
    in_fence = False
    for number, line in enumerate(unit.text.split("\n"), start=1):
        if FENCE.match(line):
            in_fence = not in_fence
            continue

        if in_fence or "|" in line:
            continue

        if PROSE_PUNCTUATION.search(INLINE_CODE.sub("", line)):
            violations.append(Violation(
                line=number,
                path=unit.path,
                message=(
                    "prohibited prose punctuation (`;`, em-dash, en-dash, or ` -- `), "
                    "split the sentence or use a comma, colon, or parentheses"
                ),
            ))
    return tuple(violations)


def comment_violations(unit: FileUnit) -> tuple[Violation, ...]:
    """PHP comment violations. The `;` is not checked here."""
    violations = []
    for match in php.COMMENTS.finditer(unit.text):
        if PROSE_DASHES.search(INLINE_CODE.sub("", match.group(0))):
            violations.append(Violation(
                line=unit.text.count("\n", 0, match.start()) + 1,
                path=unit.path,
                message=(
                    "prohibited prose punctuation (em-dash, en-dash, or ` -- `) in a "
                    "comment"
                ),
            ))
    return tuple(violations)


def shell_comment_violations(unit: FileUnit) -> tuple[Violation, ...]:
    """Shell comment violations. The `;` is not checked here, it is Bash syntax."""
    violations = []
    for match in SHELL_COMMENT.finditer(unit.text):
        if PROSE_DASHES.search(INLINE_CODE.sub("", match.group(0))):
            violations.append(Violation(
                line=unit.text.count("\n", 0, match.start()) + 1,
                path=unit.path,
                message=(
                    "prohibited prose punctuation (em-dash, en-dash, or ` -- `) in a "
                    "comment"
                ),
            ))
    return tuple(violations)


def punctuation_violations(unit: FileUnit) -> tuple[Violation, ...]:
    """Punctuation violations for one file, routed by kind: Markdown, shell, or PHP comments."""
    if is_markdown(unit.path):
        return markdown_violations(unit)

    if SHELL_PATTERN.search(unit.path):
        return shell_comment_violations(unit)
    return comment_violations(unit)


def in_scope(path: Path) -> bool:
    """Whether the path is any Markdown or shell file, or a PHP source when PHP context exists."""
    posix = path.as_posix()

    if MARKDOWN_PATTERN.search(posix):
        return path.is_file()

    if SHELL_PATTERN.search(posix):
        return path.is_file()

    if not php.context_present(path):
        return False
    return bool(php.SRC_TESTS_PATTERN.search(posix)) and path.is_file()


def file_violations(path: Path) -> tuple[Violation, ...]:
    """The punctuation violations for one file."""
    return punctuation_violations(read_unit(path))


def main() -> int:
    return run_check(in_scope, file_violations)


if __name__ == "__main__":
    sys.exit(main())
