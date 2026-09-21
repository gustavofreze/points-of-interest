"""PHP language module: scope patterns, lexical blanking, use-statement scanning, and
namespace derivation from composer.json.

This module is the language seam. Entry points take everything PHP-specific from here,
so a later language module (TypeScript, for example) can sit beside it without touching
hooklib.core. Callers gate on context_present() to no-op silently outside a PHP repo.
"""

import json
import re
from dataclasses import dataclass
from pathlib import Path
from typing import Final

from hooklib.core import project_root

# In-scope patterns for PHP sources.
SRC_TESTS_PATTERN: Final = re.compile(r"(^|/)(src|tests)/.+\.php$")
SRC_PATTERN: Final = re.compile(r"(^|/)src/.+\.php$")

# The lexical grammar: every PHP construct that must not be scanned as code.
LITERALS: Final = re.compile(
    r"""
      /\*.*?\*/                                  # block comment
    | //[^\n]*                                   # line comment
    | \#(?!\[)[^\n]*                             # hash comment, never a #[ attribute
    | <<<[ \t]*(?P<quote>['"]?)(?P<label>\w+)(?P=quote)[^\n]*\n
      .*?\n[ \t]*(?P=label)\b                    # heredoc and nowdoc body
    | '(?:\\.|[^'\\])*'                          # single-quoted string
    | "(?:\\.|[^"\\])*"                          # double-quoted string
    """,
    re.DOTALL | re.MULTILINE | re.VERBOSE,
)

# PHP comments alone, for prose checks that scan only comment text.
COMMENTS: Final = re.compile(r"/\*.*?\*/|//[^\n]*|#(?!\[)[^\n]*", re.DOTALL)

# One use statement and the imported name.
USE_STATEMENT: Final = re.compile(r"^[ \t]*use\s+(\\?[\w\\]+)", re.MULTILINE)

# A src mapping in composer.json points at one of these targets.
SRC_ROOTS: Final = frozenset({"src/", "src"})


@dataclass(frozen=True)
class Source:
    """PHP source with literals blanked out at their original positions."""

    clean: str

    @staticmethod
    def blanked(literal: re.Match[str]) -> str:
        """The matched literal as spaces, newlines preserved."""
        return re.sub(r"[^\n]", " ", literal.group(0))

    @classmethod
    def from_php(cls, text: str) -> "Source":
        """A source with every comment, string, and heredoc blanked out."""
        return cls(clean=LITERALS.sub(cls.blanked, text))

    def line_of(self, index: int) -> int:
        """The 1-based line number of a character index."""
        return self.clean.count("\n", 0, index) + 1


def nearest_manifest(start: Path | None = None) -> Path | None:
    """The nearest composer.json at or above a path, bounded by the project root, or None.

    Walking upward from the edited file (rather than testing the project root alone) is what
    lets the checks travel: a repository that keeps its manifest below the root still resolves
    its PHP context instead of silently disabling every PHP check.

    The walk stops at the project root and never substitutes the root manifest for a path
    outside it. A file the project does not contain is not governed by the project's rules, and
    binding this project's PSR-4 prefix to a foreign file would report violations against a
    namespace that file never claimed."""
    root = project_root().resolve()
    base = (start if start is not None else root).resolve()
    base = base if base.is_dir() else base.parent
    lineage = [base, *base.parents]

    if root not in lineage:
        return None

    for directory in lineage[:lineage.index(root) + 1]:
        manifest = directory / "composer.json"

        if manifest.is_file():
            return manifest
    return None


def context_present(start: Path | None = None) -> bool:
    """Whether the PHP language context exists: a composer.json at or above the path."""
    return nearest_manifest(start) is not None


def own_vendor(start: Path | None = None) -> str | None:
    """The Composer vendor segment (the part of `name` before the slash) of the nearest
    manifest, or None when it cannot be derived. The one identity value a hook may need, and
    it is always read from the repository, never from a declaration."""
    manifest = nearest_manifest(start)

    if manifest is None:
        return None
    try:
        name = json.loads(manifest.read_text(encoding="utf-8"))["name"]
    except (OSError, ValueError, KeyError):
        return None
    vendor = name.split("/", 1)[0] if isinstance(name, str) else ""
    return vendor or None


def nearest_src_prefix(start: Path) -> str:
    """The PSR-4 prefix mapped to src/, read from the nearest composer.json, empty when
    unresolved. Reading it per file lets the check travel to any service unedited."""
    manifest = nearest_manifest(start)

    if manifest is None:
        return ""
    try:
        autoload = json.loads(manifest.read_text(encoding="utf-8")).get("autoload") or {}
    except (OSError, ValueError):
        return ""
    for prefix, target in (autoload.get("psr-4") or {}).items():
        if target in SRC_ROOTS:
            return prefix
    return ""
