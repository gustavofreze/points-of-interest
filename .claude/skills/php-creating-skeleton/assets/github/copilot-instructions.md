# Copilot instructions

Read `CLAUDE.md` at the repository root and the rule files in `.claude/rules/` before producing any code, documentation,
or commit message. They are the single source of truth. When editing a file, every rule whose `paths:` frontmatter glob
matches that file is in effect and must be followed strictly.

`CLAUDE.md` has a dividing line across it. Above the line is this project, in two lines. Below it is the portable
toolkit, which carries nothing of this project and answers to no other repository.

A `<token>` in a rule or a skill is a reference resolved as you read the sentence, from the source named in `CLAUDE.md`
section "Reading the project", which lists where each value is read from. Nothing is declared, so a token you cannot
resolve from a file in the repository names a capability this project does not have. A `<token>` in a code block or a
file template is a hole to fill.
