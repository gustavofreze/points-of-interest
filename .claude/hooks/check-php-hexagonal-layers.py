#!/usr/bin/env python3
"""Hexagonal layer check for the service hexagon under src/.

Dependencies point inward only, and the CQRS read side stays segregated from the write
side. Tests cross layers freely and are out of scope.
"""

import re
import sys
from dataclasses import dataclass
from pathlib import Path
from typing import Final

from hooklib import php
from hooklib.core import Violation, read_unit, run_check

# Root files directly under src/ are declarative bootstrap that wires every layer.
ROOT_FILE_PATTERN: Final = re.compile(r"(^|/)src/[^/]+\.php$")

# Layer detection by path. Domain is matched before Application because it nests inside it.
LAYER_PATTERNS: Final = (
    ("domain", re.compile(r"(^|/)src/Application/Domain/")),
    ("application", re.compile(r"(^|/)src/Application/")),
    ("driver", re.compile(r"(^|/)src/Driver/")),
    ("driven", re.compile(r"(^|/)src/Driven/")),
    ("query", re.compile(r"(^|/)src/Query/")),
)


@dataclass(frozen=True)
class Namespaces:
    """Own-namespace prefixes per layer, derived from the service PSR-4 root."""

    own: str

    @property
    def domain(self) -> str:
        """The domain namespace, the only own-namespace the domain layer may import from."""
        return f"{self.own}Application\\Domain\\"

    @property
    def application(self) -> str:
        """The application namespace prefix."""
        return f"{self.own}Application\\"

    @property
    def driver(self) -> str:
        """The driver namespace prefix."""
        return f"{self.own}Driver\\"

    @property
    def driven(self) -> str:
        """The driven namespace prefix."""
        return f"{self.own}Driven\\"

    @property
    def query(self) -> str:
        """The query namespace prefix."""
        return f"{self.own}Query\\"


def layer_of(path: str) -> str:
    """The hexagon layer of the file, or an empty string for a root or out-of-layer file."""
    if ROOT_FILE_PATTERN.search(path):
        return ""
    for name, pattern in LAYER_PATTERNS:
        if pattern.search(path):
            return name
    return ""


def forbidden(layer: str, imported: str, namespaces: Namespaces) -> str:
    """The reason an own-namespace import is forbidden from this layer, empty when allowed."""
    if layer == "domain" and not imported.startswith(namespaces.domain):
        return (
            f"domain imports `{imported}`, the domain depends on no other layer, "
            f"only {namespaces.domain}* is allowed here"
        )
    if layer == "application" and (
        imported.startswith(namespaces.driver)
        or imported.startswith(namespaces.driven)
        or imported.startswith(namespaces.query)
    ):
        return (
            f"application imports `{imported}`, dependencies point inward, the application "
            f"never reaches out to Driver, Driven, or the Query read side"
        )
    if layer == "driver" and imported.startswith(namespaces.driven):
        return (
            f"driver imports `{imported}` from the Driven layer, a Driver adapter depends only on "
            f"Application ports and Application or Domain exceptions, never on a Driven type"
        )
    if layer == "driver" and imported.startswith(namespaces.query):
        return (
            f"driver imports `{imported}` from the Query read side, the write and read sides "
            f"are segregated (CQRS)"
        )
    if layer == "driven" and imported.startswith(namespaces.query):
        return (
            f"driven imports `{imported}` from the Query read side, the write and read sides "
            f"are segregated (CQRS)"
        )
    if layer == "query" and (
        imported.startswith(namespaces.application)
        or imported.startswith(namespaces.driver)
        or imported.startswith(namespaces.driven)
    ):
        return (
            f"query imports `{imported}` from the write side, each read slice is self-contained "
            f"and never reuses Application, Driver, or Driven code (CQRS)"
        )
    return ""


def hexagonal_violations(
    path: str,
    source: php.Source,
    namespaces: Namespaces,
) -> tuple[Violation, ...]:
    """The layer-coupling violations for one file, considering own-namespace imports only."""
    layer = layer_of(path)
    if not layer:
        return ()
    violations = []
    for match in php.USE_STATEMENT.finditer(source.clean):
        imported = match.group(1).lstrip("\\")

        if not imported.startswith(namespaces.own):
            continue

        message = forbidden(layer=layer, imported=imported, namespaces=namespaces)

        if not message:
            continue

        violations.append(Violation(
            line=source.line_of(match.start()),
            path=path,
            message=message,
        ))
    return tuple(violations)


def in_scope(path: Path) -> bool:
    """Whether the path is a PHP source under src/ with a PHP context above it."""
    return (
        bool(php.SRC_PATTERN.search(path.as_posix()))
        and path.is_file()
        and php.context_present(path)
    )


def file_violations(path: Path) -> tuple[Violation, ...]:
    """The hexagonal violations for one file."""
    own = php.nearest_src_prefix(path)
    if not own:
        print(
            f"{path.as_posix()}: cannot resolve the service namespace from composer.json "
            f"(autoload.psr-4 mapping to src/), hexagonal conformance not checked",
            file=sys.stderr,
        )
        return ()
    unit = read_unit(path)
    return hexagonal_violations(unit.path, php.Source.from_php(unit.text), Namespaces(own=own))


def main() -> int:
    return run_check(in_scope, file_violations)


if __name__ == "__main__":
    sys.exit(main())
