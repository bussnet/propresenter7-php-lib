# Documentation Guidelines

> How to maintain and extend the `doc/` directory.
>
> These rules keep the documentation easy to skim for both humans and AI assistants. For general project contribution rules (issues, PRs, tests), see the top-level [README](../README.md#contributing).

## Principles

1. **Self-contained docs.** Each file should be useful on its own. Cross-link generously, but don't force readers to load three other files just to understand one.
2. **One topic per file.** Don't merge unrelated topics — create a new file instead.
3. **Keyword-searchable.** Every new doc must be added to [keywords.md](keywords.md).
4. **Code examples over prose.** Show the API call, not a paragraph explaining it.
5. **Keep it current.** When you change code, update the corresponding doc in the same PR.

---

## Directory Structure

```
doc/
├── INDEX.md           ← Main entry point (TOC + quick nav)
├── keywords.md        ← Keyword search index (MUST update)
├── CONTRIBUTING.md    ← This file
├── formats/           ← File format specifications (binary/protocol level)
├── api/               ← PHP API documentation (how to use the code)
└── internal/          ← Development notes (learnings, decisions, issues)
```

### When to use which directory

| Directory | Content | Example |
|-----------|---------|---------|
| `formats/` | Binary file format specs, protobuf structure, encoding details | `pp_song_spec.md`, `pp_playlist_spec.md` |
| `api/` | PHP class usage, method signatures, code examples | `song.md`, `playlist.md` |
| `internal/` | Dev notes that help debug or understand history | `learnings.md`, `decisions.md`, `issues.md` |

---

## Adding a New Document

### Step 1: Create the file

Place it in the correct directory based on content type (see above).

### Step 2: Update INDEX.md

Add a line to the Table of Contents section under the appropriate heading:

```markdown
### File Format Specifications
- [formats/new_format_spec.md](formats/new_format_spec.md) -- Description
```

### Step 3: Update keywords.md

Add entries for EVERY searchable keyword in your doc:

```markdown
## New Category

| Keyword | Document |
|---------|----------|
| keyword1 | [path/to/doc.md](path/to/doc.md) |
| keyword2 | [path/to/doc.md](path/to/doc.md) Section N |
```

If your keywords fit existing categories, add them there instead.

### Step 4: Cross-reference

If your doc relates to existing docs, add a "See Also" section at the bottom:

```markdown
## See Also

- [Related Doc](../path/to/related.md) -- Why it's related
```

---

## Document Template

```markdown
# Title

> One-line summary of what this doc covers.

## Quick Reference

\`\`\`php
// Most common usage pattern (2-5 lines)
\`\`\`

---

## Section 1

Content with code examples.

---

## Section 2

More content.

---

## Key Files

| File | Purpose |
|------|---------|
| `path/to/file.php` | What it does |

---

## See Also

- [Related Doc](../path/to/related.md)
```

---

## Style Guide

### Headings
- `#` for document title (one per file)
- `##` for main sections
- `###` for subsections
- Use `---` horizontal rules between major sections

### Code Blocks
- Always specify language: ` ```php `, ` ```bash `, ` ```markdown `
- Show complete, runnable examples (not fragments)
- Include the `use` statements on first occurrence

### Tables
- Use tables for reference data (fields, files, options)
- Left-align text columns, include header row

### Tone
- Direct. No filler words.
- "Use X to do Y" not "You can use X to do Y"
- Show code first, explain after (only if needed)

---

## Updating Existing Docs

When modifying the codebase:

1. **New PHP method** → Update the corresponding `api/*.md`
2. **New file format discovery** → Update `formats/*.md`
3. **Architecture change** → Update `internal/decisions.md`
4. **Bug/gotcha found** → Update `internal/issues.md`
5. **Any change** → Check `keywords.md` for new keywords

---

## Tips for AI Assistants

The docs are also designed to be easy for AI coding assistants to navigate. The conventions matter:

1. Read [INDEX.md](INDEX.md) first to understand what's available.
2. Identify which docs match your task — [keywords.md](keywords.md) maps topics to files.
3. Load **only** the relevant docs; the codebase is large.
4. The `Quick Reference` section at the top of every API doc is the highest-signal starting point.

### Loading patterns

| Task | Load |
|------|------|
| Parse a song | [api/song.md](api/song.md) |
| Fix protobuf parsing | [formats/pp_song_spec.md](formats/pp_song_spec.md) |
| Create a playlist | [api/playlist.md](api/playlist.md) |
| Debug ZIP issues | [formats/pp_playlist_spec.md](formats/pp_playlist_spec.md) + [internal/issues.md](internal/issues.md) |
| Add a new feature | The relevant `api/*.md` + this file |
