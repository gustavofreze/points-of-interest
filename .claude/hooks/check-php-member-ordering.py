#!/usr/bin/env python3
"""Member ordering check for PHP declarations and call sites.

Parameters order by tier (required, defaulted, variadic), then length and alphabet,
with semantic pairs kept adjacent. Named arguments at call sites follow the same order.
"""

import re
import sys
from dataclasses import dataclass
from pathlib import Path
from typing import Final

from hooklib import php
from hooklib.core import Violation, read_unit, run_check

# Semantic pairs (exhaustive): natural order wins between the two members.
SEMANTIC_PAIRS: Final = (
    ("start", "end"),
    ("from", "to"),
    ("startAt", "endAt"),
    ("createdAt", "updatedAt"),
    ("before", "after"),
    ("min", "max"),
    ("code", "body"),
    ("params", "types"),
    ("accumulator", "initial"),
)

# Each member maps to (first, second, position); the pair sorts as a unit at the lead key.
PAIR_MEMBER: Final = {
    member: (first, second, position)
    for first, second in SEMANTIC_PAIRS
    for position, member in enumerate((first, second))
}

FUNCTION_DECLARATION: Final = re.compile(r"\bfunction\s+&?(\w+)\s*\(")

# Any identifier directly followed by an argument list: function calls, method calls,
# static calls, `new <ClassName>(...)`, and attributes. Declarations match too, but their
# parameter pieces carry no top-level `name:` and are filtered out by NAMED_ARGUMENT.
CALL_SITE: Final = re.compile(r"\b(\w+)\s*\(")

# A named argument at the start of one top-level piece, never a `::` static access.
NAMED_ARGUMENT: Final = re.compile(r"^\s*([A-Za-z_]\w*)\s*:(?!:)")

PARAMETER: Final = re.compile(r"\$(\w+)")
VARIADIC: Final = re.compile(r"\.\.\.\s*\$")
# A default is a lone `=`, never `=>` nor a comparison (`==`, `!=`, `<=`, `>=`).
DEFAULT_ASSIGNMENT: Final = re.compile(r"(?<![=!<>])=(?![=>])")

# Data-provider attribute or annotation, naming the test method it feeds so that method is exempt.
DATA_PROVIDER_REFERENCE: Final = re.compile(
    r"#\[\s*(?:\\?\w+\\)*DataProvider\s*\(\s*['\"](\w+)['\"]"
    r"|@dataProvider\s+(\w+)"
)

# PSR-15 process/handle and the inbound-port handle: parameter order fixed by the contract.
INTERFACE_FIXED_METHODS: Final = frozenset({"handle", "process"})


# --- Ordering -------------------------------------------------------------------


@dataclass(frozen=True)
class Ordering:
    """Sole owner of the parameter-ordering logic for one variant."""

    pair_member: dict[str, tuple[str, str, int]]

    def sorted(self, names: list[str]) -> list[str]:
        """The names in the required order."""
        present = set(names)
        return sorted(names, key=lambda name: self.key(name, present))

    def key(self, name: str, present: set[str]) -> tuple[int, str, int]:
        """Length, then alphabet, with both members of a present pair at the lead key."""
        entry = self.pair_member.get(name)
        if entry is not None:
            first, second, position = entry
            partner = second if position == 0 else first
            if partner in present:
                return (len(first), first, position)
        return (len(name), name, 0)


PARAMETER_ORDERING: Final = Ordering(pair_member=PAIR_MEMBER)


# Parameter ordering tiers: required first, then defaulted, then a variadic.
REQUIRED_TIER: Final = 0
DEFAULT_TIER: Final = 1
VARIADIC_TIER: Final = 2


@dataclass(frozen=True)
class Parameter:
    """One declared parameter: its identifier and its ordering tier."""

    name: str
    tier: int


@dataclass(frozen=True)
class ParameterList:
    """One declaration's parameters in source order."""

    line: int
    owner: str
    params: list[Parameter]

    @property
    def names(self) -> list[str]:
        return [parameter.name for parameter in self.params]

    def in_tier(self, tier: int) -> list[str]:
        return [parameter.name for parameter in self.params if parameter.tier == tier]

    def required(self) -> list[str]:
        ordered = PARAMETER_ORDERING.sorted(self.in_tier(REQUIRED_TIER))
        ordered += PARAMETER_ORDERING.sorted(self.in_tier(DEFAULT_TIER))
        return ordered + self.in_tier(VARIADIC_TIER)

    def out_of_order(self) -> bool:
        return len(self.params) >= 2 and self.names != self.required()


# --- Structure ----------------------------------------------------------------


def bracket_spans(text: str) -> dict[int, int]:
    """Every opening bracket position mapped to its closing position."""
    spans: dict[int, int] = {}
    stack: list[int] = []

    for position, character in enumerate(text):
        if character in "([{":
            stack.append(position)

        if character in ")]}" and stack:
            spans[stack.pop()] = position

    return spans


def top_level_pieces(text: str) -> list[str]:
    """The comma-separated pieces of a list, ignoring nested separators."""
    pieces, depth, current = [], 0, []
    for character in text:
        if character in "([{":
            depth += 1

        if character in ")]}":
            depth -= 1

        if character == "," and depth == 0:
            pieces.append("".join(current))
            current = []
            continue
        current.append(character)

    tail = "".join(current).strip()

    if tail:
        pieces.append(tail)

    return pieces


# --- Extraction ---------------------------------------------------------------


def declared_signatures(source: php.Source) -> list[ParameterList]:
    """Every function or method declaration with its parameters."""
    signatures = []
    spans = bracket_spans(source.clean)

    for match in FUNCTION_DECLARATION.finditer(source.clean):
        open_paren = source.clean.index("(", match.end() - 1)
        inner = source.clean[open_paren + 1: spans.get(open_paren, len(source.clean))]
        params = [
            parameter_of(piece)
            for piece in top_level_pieces(inner)
            if PARAMETER.search(piece)
        ]

        signatures.append(ParameterList(
            line=source.line_of(match.start()),
            owner=match.group(1),
            params=params,
        ))
    return signatures


def parameter_of(piece: str) -> Parameter:
    """One parameter's identifier and ordering tier from its declaration piece."""
    name = PARAMETER.findall(piece)[-1]

    if VARIADIC.search(piece):
        return Parameter(name=name, tier=VARIADIC_TIER)

    if DEFAULT_ASSIGNMENT.search(piece):
        return Parameter(name=name, tier=DEFAULT_TIER)
    return Parameter(name=name, tier=REQUIRED_TIER)


def provider_consumer_lines(text: str, source: php.Source) -> set[int]:
    """Declaration lines of test methods a data provider feeds (their params are exempt)."""
    clean = source.clean
    lines = set()
    for reference in DATA_PROVIDER_REFERENCE.finditer(text):
        method = FUNCTION_DECLARATION.search(clean, reference.end())
        if not method:
            continue
        lines.add(source.line_of(method.start()))
    return lines


# --- Checks -------------------------------------------------------------------


def parameter_violations(path: str, text: str, source: php.Source) -> tuple[Violation, ...]:
    """Parameter ordering on every declaration, skipping the ones a data provider feeds and
    the interface-mandated signatures whose parameter order is fixed by the contract."""
    exempt = provider_consumer_lines(text, source)
    return tuple(
        Violation(
            line=signature.line,
            path=path,
            message=(
                f"parameter order in `{signature.owner}()` is "
                f"({', '.join(signature.names)}), "
                f"required ({', '.join(signature.required())})"
            ),
        )

        for signature in declared_signatures(source)
        if signature.line not in exempt
        and signature.owner not in INTERFACE_FIXED_METHODS
        and signature.out_of_order()
    )


def call_site_violations(path: str, source: php.Source) -> tuple[Violation, ...]:
    """Named-argument ordering at call sites, skipping variadic-spread calls and the
    interface-mandated methods whose parameter order is fixed by the contract."""
    clean = source.clean
    spans = bracket_spans(clean)
    violations = []
    for match in CALL_SITE.finditer(clean):
        owner = match.group(1)

        if owner in INTERFACE_FIXED_METHODS:
            continue
        open_paren = clean.index("(", match.end() - 1)
        inner = clean[open_paren + 1: spans.get(open_paren, open_paren + 1)]
        pieces = top_level_pieces(inner)

        if any(VARIADIC.search(piece) for piece in pieces):
            continue
        names = [named.group(1) for named in map(NAMED_ARGUMENT.match, pieces) if named]
        required = PARAMETER_ORDERING.sorted(names)

        if len(names) >= 2 and names != required:
            violations.append(Violation(
                line=source.line_of(match.start()),
                path=path,
                message=(
                    f"named-argument order in call to `{owner}(...)` is "
                    f"({', '.join(names)}), required ({', '.join(required)})"
                ),
            ))
    return tuple(violations)


# --- Shell --------------------------------------------------------------------


def in_scope(path: Path) -> bool:
    """Whether the path is a PHP source under src/ or tests/ with a PHP context above it."""
    return (
        bool(php.SRC_TESTS_PATTERN.search(path.as_posix()))
        and path.is_file()
        and php.context_present(path)
    )


def file_violations(path: Path) -> tuple[Violation, ...]:
    """The ordering violations for one file: declared parameters and call-site
    named arguments."""
    unit = read_unit(path)
    source = php.Source.from_php(unit.text)
    return (
        parameter_violations(unit.path, unit.text, source)
        + call_site_violations(unit.path, source)
    )


def main() -> int:
    return run_check(in_scope, file_violations)


if __name__ == "__main__":
    sys.exit(main())
