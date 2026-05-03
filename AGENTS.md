# ProPresenter Parser — Agent Instructions

## Documentation

All project documentation lives in `doc/`. Load only what you need.

**Start here:** Read `doc/INDEX.md` for the table of contents and quick navigation.

### How to Find What You Need

1. Check `doc/INDEX.md` for the topic overview
2. Use `doc/keywords.md` to search by keyword
3. Load the specific doc file — don't load everything

### Common Tasks

| Task | Load |
|------|------|
| Parse/modify `.pro` song files | `doc/api/song.md` |
| Parse/modify `.proplaylist` files | `doc/api/playlist.md` |
| Parse/modify `.probundle` files | `doc/api/bundle.md` |
| Read/write the global `Macros` file | `doc/api/macros.md` |
| Read/write the global `Labels` file | `doc/api/labels.md` |
| Read/write the global `Groups` file | `doc/api/groups.md` |
| Read/write the global `ClearGroups` file | `doc/api/clear-groups.md` |
| Read/write the global `CCLI` file | `doc/api/ccli.md` |
| Read/write the global `Messages` file | `doc/api/messages.md` |
| Read/write the global `Timers` file | `doc/api/timers.md` |
| Read/write the global `Stage` file | `doc/api/stage.md` |
| Read/write the global `Workspace` file | `doc/api/workspace.md` |
| Read/write the global `Props` file | `doc/api/props.md` |
| Read/write the global `TestPatterns` file | `doc/api/test-patterns.md` |
| Read/write the global `Calendar` file | `doc/api/calendar.md` |
| Read/write the global `KeyMappings` file | `doc/api/key-mappings.md` |
| Read/write the `CommunicationDevices` JSON file | `doc/api/communication-devices.md` |
| Read/write a theme folder (Theme + Assets/) | `doc/api/theme.md` |
| Understand `.pro` binary format | `doc/formats/pp_song_spec.md` |
| Understand `.proplaylist` binary format | `doc/formats/pp_playlist_spec.md` |
| Understand `.probundle` binary format | `doc/formats/pp_bundle_spec.md` |
| Debug or troubleshoot | `doc/internal/issues.md` |
| Add new documentation | `doc/CONTRIBUTING.md` |

### Structure

```
doc/
├── INDEX.md           ← Start here (TOC + navigation)
├── keywords.md        ← Keyword search index
├── CONTRIBUTING.md    ← How to document new things
├── formats/           ← Binary file format specs
│   ├── pp_song_spec.md
│   ├── pp_playlist_spec.md
│   └── pp_bundle_spec.md
├── api/               ← PHP API docs (read/write/generate)
│   ├── song.md
│   ├── playlist.md
│   ├── bundle.md
│   ├── macros.md
│   └── labels.md
└── internal/          ← Dev notes (learnings, decisions, issues)
    ├── learnings.md
    ├── decisions.md
    └── issues.md
```

## Project Overview

PHP tools for parsing, modifying, and generating ProPresenter 7 files:

- **Songs** (`.pro`) — Protobuf-encoded presentation files with lyrics, groups, slides, arrangements, translations
- **Playlists** (`.proplaylist`) — ZIP64 archives containing playlist metadata and embedded songs
- **Bundles** (`.probundle`) — ZIP archives containing a single presentation with embedded media assets
- **Themes** (folder with `Theme` + `Assets/`) — Template document plus media used as a slide theme
- **Global library files** (no extension) — `Macros`, `Labels`, `Groups`, `ClearGroups`, `CCLI`, `Messages`, `Timers`, `Stage`, `Workspace`, `Props`, `TestPatterns`, `Calendar`, `KeyMappings` (protobuf) and `CommunicationDevices` (JSON)

### CLI Tools

```bash
# Songs / playlists / bundles
php bin/parse-song.php path/to/song.pro
php bin/parse-playlist.php path/to/playlist.proplaylist

# Global library files (one parser per type)
php bin/parse-macros.php path/to/Macros
php bin/parse-labels.php path/to/Labels
php bin/parse-groups.php path/to/Groups
php bin/parse-clear-groups.php path/to/ClearGroups
php bin/parse-ccli.php path/to/CCLI
php bin/parse-messages.php path/to/Messages
php bin/parse-timers.php path/to/Timers
php bin/parse-stage.php path/to/Stage
php bin/parse-workspace.php path/to/Workspace
php bin/parse-props.php path/to/Props
php bin/parse-test-patterns.php path/to/TestPatterns
php bin/parse-calendar.php path/to/Calendar
php bin/parse-key-mappings.php path/to/KeyMappings
php bin/parse-communication-devices.php path/to/CommunicationDevices

# Theme folder
php bin/parse-theme.php path/to/ThemeFolder
```

### Key Source Files

All PHP source code is in `src/`. Generated protobuf classes are in `generated/`. Tests are in `tests/`.
