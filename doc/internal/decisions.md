# Architectural Decisions

## Decisions Made

### Proto Version Choice
- **Decision**: Use greyshirtguy/ProPresenter7-Proto v7.16.2
- **Reason**: Field numbers match Test.pro raw decode perfectly
- **Source**: Metis analysis + typed decode validation in T2

### RTF Handling
- **Getters**: Plain text only (via RtfExtractor)
- **Internal**: Raw RTF preserved for round-trip integrity
- **Write**: Template-clone approach (preserve formatting, swap text only)

### Scope Boundaries
- **IN**: Read+write existing content, parse all reference files
- **OUT**: Creating new slides/groups from scratch, Laravel integration, playlist formats

- 2026-03-01 task-2 autoload decision: added `GPBMetadata\` => `generated/GPBMetadata/` to `composer.json` so generated `Rv\Data` classes can initialize descriptor metadata at runtime.

- 2026-03-01 task-2 ZIP64 repair strategy: patch archive headers in-memory only (no recompression), applying deterministic EOCD/ZIP64 size corrections before any `ZipArchive` access.

- 2026-03-01 21:23:59 - ProPlaylist integration tests use temp files via tempnam() tracked in class state and cleaned in tearDown() to guarantee cleanup across all test methods.
