# Coverage and mutation read-order procedure

Read this when verifying that a suite reaches 100% coverage and a fully killed mutation run. None of it is a per-file
invariant, so it is NOT path-injected. The invariants (100% line and branch coverage, every mutant killed, refactor
rather than work around the tool) live in `php-testing` (§ Coverage and mutation). This file holds the report-reading
procedure that used to sit inside that rule.

## Determining coverage and mutation status

To determine whether coverage is 100% and all mutants are killed, read the report files directly. Do NOT guess, infer
from test output, or claim success without reading these files.

- **Coverage report**: `reports/coverage.txt`. Inspect the totals line and any per-class or per-line entries below 100%.
- **Mutation summary**: `reports/infection/logs/infection-summary.log`. Reports totals by category. A successful run
  requires `Total` to equal `Killed by Test Framework` plus `Killed by Static Analysis`, with all other counters
  (`Errored`, `Syntax Errors`, `Escaped`, `Timed Out`, `Skipped`, `Ignored`, `Not Covered`) at zero. Any non-zero value
  outside the two `Killed` categories means the task is incomplete.
- **Mutation details**: `reports/infection/logs/infection-text.log`. Per-mutant breakdown listing each escaped or
  uncovered mutation with its file, line, and applied mutator. Use this file to locate the specific test that must be
  added or strengthened.

When reviewing testing work, always open `reports/coverage.txt` and `reports/infection/logs/infection-summary.log`
before declaring the task complete. The suite runs only through `make tests` (direct `php` and `phpunit` invocations are
blocked), and `reports/` is gitignored, so on a fresh clone the files do not exist until `make tests` has run. If
`reports/` is missing or stale, run `make tests` first.

## Read order (keep context small)

The command stdout from `make tests` is not a source of truth and must never be used to determine coverage or mutation
status. Progress bars and the mutant diff are suppressed at the command level for this reason. Read the report files in
this order, pulling the minimum into context:

1. **`reports/infection/logs/infection-summary.log` first.** Smallest file, and the gate. Apply the pass criterion
   already defined above (`Total` equals the two `Killed` categories, every other counter zero).
2. **`reports/infection/logs/infection-text.log` only when the summary is not green.** The large per-mutant file. Open
   it solely to locate the file, line, and mutator behind a non-zero `Escaped`, `Not Covered`, `Timed Out`, `Errored`,
   or `Syntax Errors`.
3. **`reports/coverage.txt` filtered.** Inspect the totals line and the entries below 100% only. Never read the whole
   file into context when a filter on the sub-100% lines is enough.
