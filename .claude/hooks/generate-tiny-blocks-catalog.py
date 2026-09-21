#!/usr/bin/env python3
"""Tiny-blocks catalog generator.

Dual mode: as a PostToolUse hook it regenerates the catalog after a composer.json
edit, reading the payload from stdin. As a CLI it regenerates the target given as an
argument, or the default catalog when invoked from a terminal.
"""

import json
import re
import sys
import urllib.request
from dataclasses import dataclass
from functools import cache
from pathlib import Path
from typing import Final

from hooklib import php
from hooklib.core import project_root, stdin_file_path

VENDOR: Final = "tiny-blocks"
CATALOG_RELATIVE: Final = ".claude/skills/php-reusing-tiny-blocks/references/catalog.md"
COMPOSER_NAME: Final = "composer.json"

LIST_URL: Final = f"https://packagist.org/packages/list.json?vendor={VENDOR}"
METADATA_URL: Final = "https://repo.packagist.org/p2/{name}.json"
TIMEOUT_SECONDS: Final = 30

# The generator owns only the region between these markers; everything else is preserved.
BEGIN_MARKER: Final = "<!-- tiny-blocks:auto -->"
END_MARKER: Final = "<!-- /tiny-blocks:auto -->"
GENERATED_NOTE: Final = (
    f"<!-- Generated from https://packagist.org/packages/{VENDOR}/, do not edit by hand. -->"
)
BLOCK: Final = re.compile(re.escape(BEGIN_MARKER) + r".*?" + re.escape(END_MARKER), re.DOTALL)
HEADING: Final = re.compile(r"^# .*$", re.MULTILINE)

TABLE_HEADERS: Final = ("Package", "Latest version", "Description")


# --- Types --------------------------------------------------------------------


@dataclass(frozen=True)
class Package:
    """One vendor package at its latest stable release."""

    name: str
    version: str
    description: str

    def cells(self) -> tuple[str, str, str]:
        """The package as the three Markdown table cells, unpadded."""
        return f"`{self.name}`", self.version, sanitize(text=self.description)


# --- Packagist ----------------------------------------------------------------


def sanitize(text: str) -> str:
    """One-line, pipe-escaped cell text, or an em-dash when empty."""
    collapsed = " ".join(text.split()).replace("|", "\\|")
    return collapsed or "—"


@cache
def user_agent() -> str:
    """The courtesy user agent, named after the consuming repository's own Composer vendor.

    Derived and never configured, so a reusing organization identifies itself to Packagist as
    itself instead of inheriting whoever wrote this file. Cached because it is read once per
    HTTP request and the manifest does not change mid-run."""
    vendor = php.own_vendor()
    return f"{vendor}-tiny-blocks-catalog-generator" if vendor else "tiny-blocks-catalog-generator"


def fetch_json(url: str) -> dict:
    """The JSON body at a URL, with a courtesy user agent and a timeout."""
    request = urllib.request.Request(url=url, headers={"User-Agent": user_agent()})
    with urllib.request.urlopen(request, timeout=TIMEOUT_SECONDS) as response:
        return json.load(response)


def vendor_package_names() -> list[str]:
    """Every package name published under the vendor, sorted."""
    return sorted(fetch_json(url=LIST_URL).get("packageNames") or [])


def latest_package(name: str) -> Package | None:
    """The latest stable release of one package, or None when only dev releases exist."""
    releases = (fetch_json(url=METADATA_URL.format(name=name)).get("packages") or {}).get(name) or []
    for release in releases:
        version = release.get("version", "")

        if version.startswith("dev-"):
            continue
        return Package(name=name, version=version, description=release.get("description") or "")
    return None


def collect_packages() -> tuple[list[Package], list[str]]:
    """The latest release of every vendor package, plus names that could not be read."""
    packages, failures = [], []
    for name in vendor_package_names():
        try:
            package = latest_package(name=name)
        except (OSError, ValueError) as error:
            failures.append(f"{name}: {error}")
            continue

        if package is None:
            failures.append(f"{name}: no stable release")
            continue
        packages.append(package)
    return packages, failures


# --- Rendering ----------------------------------------------------------------


def render_row(cells: tuple[str, ...], widths: list[int]) -> str:
    """One Markdown table row with every cell padded to its column width."""
    return "| " + " | ".join(cell.ljust(width) for cell, width in zip(cells, widths)) + " |"


def render_table(packages: list[Package]) -> str:
    """The package table, every column padded to its widest cell."""
    rows = [package.cells() for package in sorted(packages, key=lambda package: package.name)]
    widths = [max(map(len, column)) for column in zip(TABLE_HEADERS, *rows)]
    separator = "|" + "|".join("-" * (width + 2) for width in widths) + "|"
    body = [render_row(cells=row, widths=widths) for row in rows]
    return "\n".join([render_row(cells=TABLE_HEADERS, widths=widths), separator, *body])


def render_block(packages: list[Package]) -> str:
    """The managed block: markers, provenance note, and the package table."""
    table = render_table(packages=packages)
    return f"{BEGIN_MARKER}\n{GENERATED_NOTE}\n\n{table}\n{END_MARKER}"


def splice(existing: str, block: str) -> str:
    """The file with the managed block replaced in place, or inserted after the H1."""
    if BLOCK.search(existing):
        return BLOCK.sub(lambda _: block, existing, count=1)
    heading = HEADING.search(existing)

    if heading:
        return f"{existing[:heading.end()]}\n\n{block}\n{existing[heading.end():]}"
    return f"{block}\n\n{existing}" if existing.strip() else f"{block}\n"


# --- Shell --------------------------------------------------------------------


def resolve_target(arguments: list[str]) -> Path:
    """The catalog path from a positional argument, or the default under the project root."""
    paths = [argument for argument in arguments if not argument.startswith("-")]
    return Path(paths[0]) if paths else project_root() / CATALOG_RELATIVE


def composer_edited() -> bool:
    """Whether the PostToolUse stdin payload reports an edit to composer.json."""
    path = stdin_file_path()
    return path is not None and path.name == COMPOSER_NAME


def regenerate(target: Path) -> int:
    """Rewrite the managed block of one catalog file from Packagist."""
    if not target.is_file():
        print(f"tiny-blocks catalog: {target} not found", file=sys.stderr)
        return 1
    try:
        packages, failures = collect_packages()
    except (OSError, ValueError) as error:
        print(f"tiny-blocks catalog: cannot reach Packagist, {error}", file=sys.stderr)
        return 1
    target.write_text(
        splice(existing=target.read_text(encoding="utf-8"), block=render_block(packages=packages)),
        encoding="utf-8",
    )

    for failure in failures:
        print(f"tiny-blocks catalog: skipped {failure}", file=sys.stderr)
    return 0


def main() -> int:
    arguments = sys.argv[1:]

    if arguments or sys.stdin.isatty():
        return regenerate(target=resolve_target(arguments=arguments))

    if not composer_edited():
        return 0
    target = project_root() / CATALOG_RELATIVE
    return regenerate(target=target) if target.is_file() else 0


if __name__ == "__main__":
    sys.exit(main())
