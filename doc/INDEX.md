# ProPresenter 7 PHP API — Documentation

> Comprehensive reference for the [ProPresenter 7 PHP API](../README.md) library.
>
> Each document is self-contained — open the one that matches your task. If you don't know which to load, search [keywords.md](keywords.md) or scan the table below.

## Quick Navigation

| Need | Load |
|------|------|
| Parse/modify `.pro` song files | [api/song.md](api/song.md) |
| Parse/modify `.proplaylist` files | [api/playlist.md](api/playlist.md) |
| Parse/modify `.probundle` files | [api/bundle.md](api/bundle.md) |
| Read/write the global `Macros` file | [api/macros.md](api/macros.md) |
| Read/write the global `Labels` file | [api/labels.md](api/labels.md) |
| Read/write the global `Groups` file | [api/groups.md](api/groups.md) |
| Read/write the global `ClearGroups` file | [api/clear-groups.md](api/clear-groups.md) |
| Read/write the global `CCLI` file | [api/ccli.md](api/ccli.md) |
| Read/write the global `Messages` file | [api/messages.md](api/messages.md) |
| Read/write the global `Timers` file | [api/timers.md](api/timers.md) |
| Read/write the global `Stage` file | [api/stage.md](api/stage.md) |
| Read/write the global `Workspace` file | [api/workspace.md](api/workspace.md) |
| Read/write the global `Props` file | [api/props.md](api/props.md) |
| Read/write the global `TestPatterns` file | [api/test-patterns.md](api/test-patterns.md) |
| Read/write the global `Calendar` file | [api/calendar.md](api/calendar.md) |
| Read/write the global `KeyMappings` file | [api/key-mappings.md](api/key-mappings.md) |
| Read/write the global `CommunicationDevices` JSON file | [api/communication-devices.md](api/communication-devices.md) |
| Read/write theme folders (Theme + Assets/) | [api/theme.md](api/theme.md) |
| Understand `.pro` binary format | [formats/pp_song_spec.md](formats/pp_song_spec.md) |
| Understand `.proplaylist` format | [formats/pp_playlist_spec.md](formats/pp_playlist_spec.md) |
| Understand `.probundle` format | [formats/pp_bundle_spec.md](formats/pp_bundle_spec.md) |
| Add new documentation | [CONTRIBUTING.md](CONTRIBUTING.md) |
| Search by keyword | [keywords.md](keywords.md) |

---

## Table of Contents

### File Format Specifications
- [formats/pp_song_spec.md](formats/pp_song_spec.md) — ProPresenter 7 `.pro` file format (protobuf structure, RTF handling, field reference)
- [formats/pp_playlist_spec.md](formats/pp_playlist_spec.md) — ProPresenter 7 `.proplaylist` file format (ZIP64 container, item types)
- [formats/pp_bundle_spec.md](formats/pp_bundle_spec.md) — ProPresenter 7 `.probundle` file format (ZIP container, media assets)

### PHP API Documentation
#### Document containers
- [api/song.md](api/song.md) — Song parser API (read, modify, generate `.pro` files)
- [api/playlist.md](api/playlist.md) — Playlist parser API (read, modify, generate `.proplaylist` files)
- [api/bundle.md](api/bundle.md) — Bundle parser API (read, write `.probundle` files with media)
- [api/theme.md](api/theme.md) — Theme bundle API (folder with `Theme` proto + `Assets/`)

#### Global library files (read + write)
- [api/macros.md](api/macros.md) — `Macros` library (macros + collections, with editable accessors)
- [api/labels.md](api/labels.md) — `Labels` library (named slide labels with optional UI colors)
- [api/groups.md](api/groups.md) — `Groups` library (named library groups with UUID, color, hot keys)
- [api/clear-groups.md](api/clear-groups.md) — `ClearGroups` library (clear-action groups)
- [api/ccli.md](api/ccli.md) — `CCLI` settings (license, display behaviour, copyright template)
- [api/messages.md](api/messages.md) — `Messages` library (lower-third / overlay messages)
- [api/timers.md](api/timers.md) — `Timers` library (timer definitions + clock format)
- [api/stage.md](api/stage.md) — `Stage` document (stage display layouts)
- [api/workspace.md](api/workspace.md) — `Workspace` document (screens, looks, masks, audio/video inputs)
- [api/props.md](api/props.md) — `Props` document (prop cues + transition)
- [api/test-patterns.md](api/test-patterns.md) — `TestPatterns` document (currently selected pattern + saved overrides)
- [api/calendar.md](api/calendar.md) — `Calendar` document (scheduled events firing macros)
- [api/key-mappings.md](api/key-mappings.md) — `KeyMappings` document (custom hot-key bindings)
- [api/communication-devices.md](api/communication-devices.md) — `CommunicationDevices` JSON list (MIDI / serial / OSC bindings)

### Internal Reference
- [internal/learnings.md](internal/learnings.md) — Development learnings and conventions discovered
- [internal/decisions.md](internal/decisions.md) — Architectural decisions and rationale
- [internal/issues.md](internal/issues.md) — Known issues and edge cases

### Meta
- [keywords.md](keywords.md) — Searchable keyword index
- [CONTRIBUTING.md](CONTRIBUTING.md) — How to document new features

---

## Directory Structure

```
doc/
├── INDEX.md           ← You are here (main entry point)
├── keywords.md        ← Keyword search index
├── CONTRIBUTING.md    ← Documentation guidelines
├── formats/           ← File format specifications
│   ├── pp_song_spec.md
│   ├── pp_playlist_spec.md
│   └── pp_bundle_spec.md
├── api/               ← PHP API documentation
│   ├── song.md
│   ├── playlist.md
│   ├── bundle.md
│   ├── theme.md
│   ├── macros.md
│   ├── labels.md
│   ├── groups.md
│   ├── clear-groups.md
│   ├── ccli.md
│   ├── messages.md
│   ├── timers.md
│   ├── stage.md
│   ├── workspace.md
│   ├── props.md
│   ├── test-patterns.md
│   ├── calendar.md
│   ├── key-mappings.md
│   └── communication-devices.md
└── internal/          ← Development notes (optional context)
    ├── learnings.md
    ├── decisions.md
    └── issues.md
```

---

## What to Read for Each Task

| Task | Read |
|------|------|
| Parse a song file | [api/song.md](api/song.md) |
| Generate a new playlist | [api/playlist.md](api/playlist.md) |
| Read or write a `.probundle` | [api/bundle.md](api/bundle.md) |
| Edit a global library file (Macros, Labels, Groups, …) | [api/<library>.md](api/) |
| Round-trip a theme folder with assets | [api/theme.md](api/theme.md) + [api/bundle.md](api/bundle.md) |
| Debug protobuf parsing issues | [formats/pp_song_spec.md](formats/pp_song_spec.md) §2–5 |
| Understand translation handling | [api/song.md](api/song.md) (Translations) + [formats/pp_song_spec.md](formats/pp_song_spec.md) §7 |
| Fix ZIP64 issues | [formats/pp_playlist_spec.md](formats/pp_playlist_spec.md) §4 + [formats/pp_bundle_spec.md](formats/pp_bundle_spec.md) §4 |
| Check internal notes / known bugs | [internal/issues.md](internal/issues.md), [internal/learnings.md](internal/learnings.md) |

---

## Project Overview

For installation, getting started, and a high-level feature tour see the top-level [README](../README.md).

In short, this library covers:

- **Songs** (`.pro`) — protobuf files with lyrics, groups, slides, arrangements, translations
- **Playlists** (`.proplaylist`) — ZIP64 archives with embedded songs and media
- **Bundles** (`.probundle`) — ZIP archives bundling a single song with its media assets
- **Themes** — folder with a `Theme` protobuf and an `Assets/` directory
- **Global library files** — `Macros`, `Labels`, `Groups`, `ClearGroups`, `CCLI`, `Messages`, `Timers`, `Stage`, `Workspace`, `Props`, `TestPatterns`, `Calendar`, `KeyMappings`, `CommunicationDevices` (JSON)

### CLI Tools

Every supported file type ships with an inspector script in [`bin/`](../bin/). Examples:

```bash
php bin/parse-song.php     path/to/song.pro
php bin/parse-playlist.php path/to/playlist.proplaylist
php bin/parse-theme.php    path/to/ThemeFolder
php bin/parse-macros.php   path/to/Macros
# … and one for every other global library file
```

See the [README](../README.md#cli-tools) for the full list.
