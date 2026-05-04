# Architectural Decisions

## Decisions Made

### Proto Version Choice
- **Decision**: Use greyshirtguy/ProPresenter7-Proto, Proto 19beta (dumped from ProPresenter v19 beta build 318767123)
- **Reason**: Latest available proto schema; field numbers compatible with our reference files; covers new ProPresenter 19 features
- **Retained extras**: `calendar.proto`, `keyMappings.proto` (not present in 19beta upstream but extracted from PP binaries to support our `parse-calendar` and `parse-key-mappings` tools); `analyticsCapture/Update/WHMStore.proto` retained from the prior 7.16.2 set for backward compatibility
- **History**: Originally adopted v7.16.2 (Metis analysis + typed decode validation in T2); upgraded to Proto 19beta on 2026-05-04

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
