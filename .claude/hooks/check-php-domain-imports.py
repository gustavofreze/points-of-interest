#!/usr/bin/env python3
"""Domain imports check for the hexagon's domain layer.

Outside the Commons/ wrappers, the domain imports only own-vendor namespaces and
unqualified names.
"""

import re
import sys
from pathlib import Path
from typing import Final

from hooklib import php
from hooklib.core import Violation, read_unit, run_check

# Domain layer (the strict import restriction applies here, outside the Commons/ wrappers).
DOMAIN_PATTERN: Final = re.compile(r"(^|/)src/Application/Domain/.+\.php$")
DOMAIN_COMMONS_PATTERN: Final = re.compile(r"(^|/)src/Application/Domain/\w+/Commons/")


def domain_import_violations(path: str, source: php.Source, prefix: str) -> tuple[Violation, ...]:
    """Outside Commons/, only own-vendor and unqualified names may be imported."""
    if not DOMAIN_PATTERN.search(path) or DOMAIN_COMMONS_PATTERN.search(path):
        return ()
    violations = []
    for match in php.USE_STATEMENT.finditer(source.clean):
        imported = match.group(1).lstrip("\\")

        if "\\" not in imported:
            continue

        if imported.startswith(prefix):
            continue

        vendor = imported.split("\\", 1)[0]
        violations.append(Violation(
            line=source.line_of(match.start()),
            path=path,
            message=(
                f"domain imports `{imported}` outside Commons/, only {prefix}* and "
                f"unqualified names are allowed here, wrap `{vendor}\\*` under Commons/"
            ),
        ))
    return tuple(violations)


def in_scope(path: Path) -> bool:
    """Whether the path is a PHP source under src/ or tests/ with a PHP context above it."""
    return (
        bool(php.SRC_TESTS_PATTERN.search(path.as_posix()))
        and path.is_file()
        and php.context_present(path)
    )


def file_violations(path: Path) -> tuple[Violation, ...]:
    """The domain-import violations for one file, against the root namespace of its own manifest.

    Resolving the prefix per file (never from a configured value) is what lets the check travel to
    any project unedited. The prefix is the WHOLE PSR-4 root mapped to src/, never its first
    segment: a package published under the same first segment is still a third-party package, and
    allowing it would let a vendor dependency reach straight into the domain layer."""
    prefix = php.nearest_src_prefix(path)

    if not prefix:
        print(
            f"{path.as_posix()}: cannot resolve the root namespace from composer.json "
            f"(autoload.psr-4 mapping to src/), domain-imports not checked",
            file=sys.stderr,
        )
        return ()
    unit = read_unit(path)
    return domain_import_violations(unit.path, php.Source.from_php(unit.text), prefix)


def main() -> int:
    return run_check(in_scope, file_violations)


if __name__ == "__main__":
    sys.exit(main())
