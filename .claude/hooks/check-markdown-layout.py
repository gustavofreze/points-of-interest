#!/usr/bin/env python3
"""Markdown layout check for README files.

Flags what a column-only reformat breaks: a prose line over the wrap column, a line break
that renders as a stray space beside a delimiter, and an inline span (code, emphasis, or
link) split across a line break. Fenced code, table rows, headings, and HTML lines are
exempt, and so is a line no wrap can shorten, such as a badge or an image with a long URL.

Run it with --fix to re-wrap the offending blocks:

    python3 .claude/hooks/check-markdown-layout.py --fix [file ...]

With no file it covers every README.md of the repository. A block whose rewrap would
change more than whitespace is left untouched and reported.
"""

import re
import sys
from pathlib import Path
from typing import Final, Iterator

from hooklib.core import FileUnit, Violation, project_root, read_unit, run_check

MAX_COLUMNS: Final = 120

README_PATTERN: Final = re.compile(r"(^|/)README\.md$")
IGNORED_DIRECTORIES: Final = frozenset({".git", "node_modules", "reports", "vendor"})

FENCE: Final = re.compile(r"^\s*(?:>\s*)*```")
LINK_SPAN: Final = re.compile(r"\[[^\]]*\]\([^)]*\)")
LIST_ITEM: Final = re.compile(r"^\s*(?:>\s*)*(?:[-*+]|\d+\.)\s")
HARD_BREAK: Final = re.compile(r"(?: {2,}|\\)$")
LINE_PREFIX: Final = re.compile(r"^[\s>]*")
LINE_MARKER: Final = re.compile(r"^(\s*)((?:>\s*)*)((?:[-*+]|\d+\.)\s+)?")

# Headings, horizontal rules, table rows, and HTML lines are never prose.
NOT_PROSE: Final = re.compile(r"^\s*(#{1,6}\s|---\s*$|\||<)")

# A break between an opening delimiter and the word it opens, or before a closing one,
# renders as a stray space: "see (\nthe guide)" reads "( the guide)".
BREAK_AFTER_OPEN: Final = re.compile(r"[(\[{]\s*$")
BREAK_BEFORE_CLOSE: Final = re.compile(r"^\s*(?:>\s*)*[)\]},.;:%!?]")

Block = tuple[int, list[str]]


def marker_of(line: str) -> str:
    """The indent, blockquote markers, and list marker that open a line."""
    match = LINE_MARKER.match(line)
    return (match.group(1) or "") + (match.group(2) or "") + (match.group(3) or "")


def closing_bold(text: str, start: int) -> int:
    """Offset of the `**` closing a bold span, skipping the star of a nested italic."""
    offset = text.find("**", start)
    while offset != -1 and text[offset + 2:offset + 3] == "*":
        offset = text.find("**", offset + 1)
    return offset


def spans(text: str) -> Iterator[tuple[int, str]]:
    """Every inline span as (offset, span), outermost first. Inline code is opaque."""
    index = 0
    while index < len(text):
        end = -1
        if text[index] == "`":
            close = text.find("`", index + 1)
            end = close + 1 if close != -1 else -1
        elif text.startswith("**", index):
            close = closing_bold(text, index + 2)
            end = close + 2 if close != -1 else -1
        elif text[index] == "*" and text[index + 1:index + 2].strip():
            close = text.find("*", index + 1)
            end = close + 1 if close != -1 else -1
        elif text[index] == "[":
            match = LINK_SPAN.match(text, index)
            end = match.end() if match else -1

        if end > index:
            yield index, text[index:end]
            index = end
            continue
        index += 1


def prose_blocks(text: str) -> Iterator[Block]:
    """Every prose paragraph or list item as (first line number, lines)."""
    block: list[str] = []
    start = 0
    in_fence = False
    for number, line in enumerate(text.split("\n"), start=1):
        if FENCE.match(line):
            in_fence = not in_fence
            if block:
                yield start, block
                block = []
            continue

        if in_fence or not line.strip().strip(">") or NOT_PROSE.match(line):
            if block:
                yield start, block
                block = []
            continue

        quoted = line.lstrip().startswith(">")
        opens_quote = quoted and bool(block) and not block[0].lstrip().startswith(">")
        after_hard_break = bool(block) and bool(HARD_BREAK.search(block[-1]))

        if block and (LIST_ITEM.match(line) or opens_quote or after_hard_break):
            yield start, block
            block = []

        if not block:
            start = number
        block.append(line)

    if block:
        yield start, block


def joined(block: list[str]) -> tuple[str, str, list[int]]:
    """The block as one logical line, its continuation prefix, and where each line starts in it."""
    marker = marker_of(block[0])
    continuation = LINE_PREFIX.match(block[1]).group(0) if len(block) > 1 else marker
    parts = [block[0][len(marker):]] + [LINE_PREFIX.sub("", line) for line in block[1:]]
    offsets = []
    position = 0
    for part in parts:
        offsets.append(position)
        position += len(part) + 1
    return " ".join(parts), continuation, offsets


def unbreakable(line: str) -> bool:
    """Whether the line holds a single token, which no wrap can shorten."""
    return len(tokens_of(line[len(marker_of(line)):], MAX_COLUMNS)) <= 1


def block_violations(path: str, start: int, block: list[str]) -> Iterator[Violation]:
    """The layout violations of one prose block."""
    for index, line in enumerate(block):
        if len(line) > MAX_COLUMNS and not unbreakable(line):
            yield Violation(
                line=start + index,
                path=path,
                message=f"line is {len(line)} columns, over the {MAX_COLUMNS}-column wrap",
            )

        if index + 1 < len(block) and BREAK_AFTER_OPEN.search(line):
            yield Violation(
                line=start + index,
                path=path,
                message="line ends on an opening delimiter, the break renders as a space",
            )

        if index and BREAK_BEFORE_CLOSE.match(line):
            yield Violation(
                line=start + index,
                path=path,
                message="line starts on a closing delimiter, the break renders as a space",
            )

    text, continuation, offsets = joined(block)
    budget = MAX_COLUMNS - len(continuation)
    for offset, span in spans(text):
        straddles = any(offset < position < offset + len(span) for position in offsets)

        if straddles and len(span) <= budget:
            flat = " ".join(span.split())
            yield Violation(
                line=start + sum(position <= offset for position in offsets) - 1,
                path=path,
                message=f"inline span split across a line break: '{flat[:60]}'",
            )


def layout_violations(unit: FileUnit) -> tuple[Violation, ...]:
    """The layout violations of one Markdown file."""
    return tuple(
        violation
        for start, block in prose_blocks(unit.text)
        for violation in block_violations(unit.path, start, block)
    )


def tokens_of(text: str, budget: int) -> list[str]:
    """The text split on spaces, keeping whole every link and every span that fits on one line."""
    protected = [
        (offset, offset + len(span))
        for offset, span in spans(text)
        if " " in span and (len(span) <= budget or span.startswith("["))
    ]
    tokens = []
    current = ""
    for index, character in enumerate(text):
        if character == " " and not any(first <= index < last for first, last in protected):
            if current:
                tokens.append(current)
                current = ""
            continue
        current += character

    if current:
        tokens.append(current)
    return tokens


def continuation_of(block: list[str], marker: str) -> str:
    """The prefix every line after the first carries, never a second list marker."""
    if len(block) > 1:
        return LINE_PREFIX.match(block[1]).group(0)

    quoted = re.match(r"^(\s*)((?:>\s*)*)", marker).group(0)
    return quoted + " " * (len(marker) - len(quoted))


def rewrapped(block: list[str]) -> list[str]:
    """The block re-wrapped at the wrap column, respecting every layout rule."""
    marker = marker_of(block[0])
    continuation = continuation_of(block, marker)
    parts = [block[0][len(marker):]] + [LINE_PREFIX.sub("", line) for line in block[1:]]
    text = parts[0]
    for part in parts[1:]:
        glued = BREAK_AFTER_OPEN.search(text) or BREAK_BEFORE_CLOSE.match(part)
        text += part if glued else " " + part

    lines = []
    prefix = marker
    line = marker
    for token in tokens_of(text, MAX_COLUMNS - len(continuation)):
        candidate = line + token if line == prefix else line + " " + token

        if len(candidate) <= MAX_COLUMNS or line == prefix:
            line = candidate
            continue

        lines.append(line)
        prefix = continuation
        line = continuation + token

    lines.append(line)
    return lines


def squashed(lines: list[str]) -> str:
    """Everything but whitespace and line prefixes, which is all a rewrap may change."""
    return re.sub(r"\s+", "", "".join(LINE_PREFIX.sub("", line) for line in lines))


def fix_file(path: Path) -> tuple[int, list[int]]:
    """Re-wraps every offending block. Returns the blocks rewritten and the lines refused."""
    unit = read_unit(path)
    offending = {violation.line for violation in layout_violations(unit)}

    if not offending:
        return 0, []

    lines = unit.text.split("\n")
    edits = []
    refused = []
    for start, block in prose_blocks(unit.text):
        if not offending & set(range(start, start + len(block))):
            continue

        replacement = rewrapped(block)

        if replacement == block:
            continue

        if squashed(replacement) != squashed(block):
            refused.append(start)
            continue

        edits.append((start, len(block), replacement))

    for start, length, replacement in reversed(edits):
        lines[start - 1:start - 1 + length] = replacement

    if edits:
        path.write_text("\n".join(lines), encoding="utf-8")
    return len(edits), refused


def repository_readmes() -> list[Path]:
    """Every README.md of the repository, outside the dependency and report directories."""
    root = project_root()
    return sorted(
        path
        for path in root.rglob("README.md")
        if not IGNORED_DIRECTORIES & set(path.relative_to(root).parts)
    )


def run_fix(arguments: list[str]) -> int:
    """Re-wraps the given files, or every README.md, then reports what is left."""
    paths = [Path(argument) for argument in arguments] or repository_readmes()
    remaining = 0
    for path in paths:
        if not in_scope(path):
            print(f"{path}: not a README.md, skipped", file=sys.stderr)
            continue

        fixed, refused = fix_file(path)
        left = layout_violations(read_unit(path))
        remaining += len(left) + len(refused)

        for line in refused:
            message = "not re-wrapped, it would change more than whitespace"
            print(f"{path}:{line}: {message}", file=sys.stderr)

        for violation in left:
            print(violation, file=sys.stderr)

        if fixed:
            print(f"{path}: {fixed} block{'s' if fixed != 1 else ''} re-wrapped")

    return 1 if remaining else 0


def in_scope(path: Path) -> bool:
    """Whether the path is a README.md that exists."""
    return bool(README_PATTERN.search(path.as_posix())) and path.is_file()


def file_violations(path: Path) -> tuple[Violation, ...]:
    """The layout violations for one file."""
    return layout_violations(read_unit(path))


def main() -> int:
    arguments = sys.argv[1:]

    if "--fix" in arguments:
        return run_fix([argument for argument in arguments if argument != "--fix"])
    return run_check(in_scope, file_violations)


if __name__ == "__main__":
    sys.exit(main())
