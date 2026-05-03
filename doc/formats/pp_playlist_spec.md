# ProPresenter 7 `.proplaylist` File Format Specification

**Version:** 1.0  
**Target Audience:** AI agents, automated parsers, developers  
**Proto Source:** greyshirtguy/ProPresenter7-Proto v7.16.2 (MIT License)

---

## 1. Overview

### File Format
- **Extension:** `.proplaylist`
- **Container Format:** ZIP64 archive (PKZIP 4.5+)
- **Compression:** Store only (no deflate compression)
- **Binary Format:** Protocol Buffers (Google protobuf v3)
- **Top-level Message:** `rv.data.Playlist` (defined in `playlist.proto`)
- **Proto Definitions:** greyshirtguy/ProPresenter7-Proto v7.16.2 (MIT)

### Container Structure
- **Archive Type:** ZIP64 with store compression (compression method 0)
- **ZIP64 EOCD Quirk:** 98-byte discrepancy between ZIP64 EOCD locator offset and actual EOCD position
- **Entry Layout:**
  - `data` file at root (protobuf binary)
  - `.pro` song files at root (filename only, no directory structure)
  - Media files at original absolute paths (minus leading `/`)

### Known Limitations
- **Binary Fidelity:** Round-trip decode→encode fails on all reference files. Proto definitions are incomplete; unknown fields are lost during serialization.
- **Workaround:** Preserve original binary data if exact binary reproduction is required.

### File Validity
- **Empty files (0 bytes):** Invalid. Throw exception.
- **Playlists without items:** Valid. Empty playlists are allowed.
- **Deduplication:** Same `.pro` file stored once; media files deduplicated by path.

---

## 2. Playlist Structure

### Hierarchy Diagram

```
PlaylistDocument (ZIP64 archive)
├── data (protobuf binary)
│   └── Playlist (rv.data.Playlist) ← Root container named "PLAYLIST"
│       ├── name (string, field 2) = "PLAYLIST"
│       ├── uuid (rv.data.UUID, field 1)
│       ├── type (rv.data.Playlist.Type, field 3) = TYPE_PLAYLIST (1)
│       └── playlists (rv.data.Playlist.PlaylistArray, field 12)
│           └── playlists[] (rv.data.Playlist) ← Actual named playlist
│               ├── name (string, field 2) ← User-defined name
│               ├── uuid (rv.data.UUID, field 1)
│               ├── type (rv.data.Playlist.Type, field 3) = TYPE_PLAYLIST (1)
│               └── items (rv.data.Playlist.PlaylistItems, field 13)
│                   └── items[] (rv.data.PlaylistItem)
│                       ├── uuid (rv.data.UUID, field 1)
│                       ├── name (string, field 2)
│                       └── ItemType (oneof)
│                           ├── header (field 3) ← Section divider
│                           ├── presentation (field 4) ← Song reference
│                           ├── cue (field 5) ← Inline cue
│                           ├── planning_center (field 6) ← PCO integration
│                           └── placeholder (field 8) ← Empty slot
├── *.pro files (song files, deduplicated)
└── media files (images/videos at original absolute paths)
```

### Navigation Paths

**To access playlist items:**
```
PlaylistDocument (ZIP)
  → data (protobuf)
    → Playlist (root "PLAYLIST")
      → playlists.playlists[0] (actual playlist)
        → items.items[]
          → ItemType (oneof)
```

**To access presentation references:**
```
PlaylistItem
  → presentation
    → document_path (URL)
    → arrangement (UUID)
    → arrangement_name (string)
    → user_music_key (MusicKeyScale)
```

**To access header dividers:**
```
PlaylistItem
  → header
    → color (Color)
    → actions[] (Action)
```

---

## 3. Fields Reference

### Playlist (rv.data.Playlist)

| Field Path | Protobuf Type | Field Number | Description |
|------------|---------------|--------------|-------------|
| `uuid` | `rv.data.UUID` | 1 | Unique identifier for the playlist |
| `name` | `string` | 2 | Playlist name (root is always "PLAYLIST") |
| `type` | `rv.data.Playlist.Type` | 3 | Playlist type (always TYPE_PLAYLIST = 1) |
| `expanded` | `bool` | 4 | UI expansion state |
| `targeted_layer_uuid` | `rv.data.UUID` | 5 | Target layer UUID |
| `smart_directory_path` | `rv.data.URL` | 6 | Smart playlist directory path |
| `hot_key` | `rv.data.HotKey` | 7 | Keyboard shortcut |
| `cues[]` | `rv.data.Cue` | 8 | Array of cues (not used in observed files) |
| `children[]` | `rv.data.Playlist` | 9 | Array of child playlists (deprecated) |
| `timecode_enabled` | `bool` | 10 | Timecode synchronization enabled |
| `timing` | `rv.data.Playlist.TimingType` | 11 | Timing type (NONE, TIMECODE, TIME_OF_DAY) |
| `playlists` | `rv.data.Playlist.PlaylistArray` | 12 | Child playlists (oneof ChildrenType) |
| `items` | `rv.data.Playlist.PlaylistItems` | 13 | Playlist items (oneof ChildrenType) |
| `smart_directory` | `rv.data.Playlist.FolderDirectory` | 14 | Smart folder config (oneof LinkData) |
| `pco_plan` | `rv.data.PlanningCenterPlan` | 15 | Planning Center plan (oneof LinkData) |
| `startup_info` | `rv.data.Playlist.StartupInfo` | 16 | Startup trigger configuration |

### Playlist.PlaylistArray

| Field Path | Protobuf Type | Field Number | Description |
|------------|---------------|--------------|-------------|
| `playlists[]` | `rv.data.Playlist` | 1 | Array of child playlists |

### Playlist.PlaylistItems

| Field Path | Protobuf Type | Field Number | Description |
|------------|---------------|--------------|-------------|
| `items[]` | `rv.data.PlaylistItem` | 1 | Array of playlist items |

### PlaylistItem (rv.data.PlaylistItem)

| Field Path | Protobuf Type | Field Number | Description |
|------------|---------------|--------------|-------------|
| `uuid` | `rv.data.UUID` | 1 | Unique identifier for the item |
| `name` | `string` | 2 | Item display name |
| `tags[]` | `rv.data.UUID` | 7 | Array of tag UUIDs |
| `is_hidden` | `bool` | 9 | Whether item is hidden in UI |
| `header` | `rv.data.PlaylistItem.Header` | 3 | Section divider (oneof ItemType) |
| `presentation` | `rv.data.PlaylistItem.Presentation` | 4 | Song reference (oneof ItemType) |
| `cue` | `rv.data.Cue` | 5 | Inline cue (oneof ItemType) |
| `planning_center` | `rv.data.PlaylistItem.PlanningCenter` | 6 | PCO integration (oneof ItemType) |
| `placeholder` | `rv.data.PlaylistItem.Placeholder` | 8 | Empty slot (oneof ItemType) |

### PlaylistItem.Header

| Field Path | Protobuf Type | Field Number | Description |
|------------|---------------|--------------|-------------|
| `color` | `rv.data.Color` | 1 | RGBA color (float values 0.0-1.0) |
| `actions[]` | `rv.data.Action` | 2 | Array of actions (rarely used) |

### PlaylistItem.Presentation

| Field Path | Protobuf Type | Field Number | Description |
|------------|---------------|--------------|-------------|
| `document_path` | `rv.data.URL` | 1 | Path to .pro file (URL format) |
| `arrangement` | `rv.data.UUID` | 2 | Arrangement UUID |
| `content_destination` | `rv.data.Action.ContentDestination` | 3 | Content destination layer |
| `user_music_key` | `rv.data.MusicKeyScale` | 4 | User-selected music key |
| `arrangement_name` | `string` | 5 | Arrangement name (UNDOCUMENTED) |

### PlaylistItem.PlanningCenter

| Field Path | Protobuf Type | Field Number | Description |
|------------|---------------|--------------|-------------|
| `item` | `rv.data.PlanningCenterPlan.PlanItem` | 1 | PCO plan item reference |
| `linked_data` | `rv.data.PlaylistItem` | 2 | Linked playlist item |

### PlaylistItem.Placeholder

| Field Path | Protobuf Type | Field Number | Description |
|------------|---------------|--------------|-------------|
| `linked_data` | `rv.data.PlaylistItem` | 1 | Linked playlist item |

---

## 4. ZIP64 Container Format

### Archive Structure
- **Format:** ZIP64 (PKZIP 4.5+)
- **Compression:** Store only (compression method 0, no deflate)
- **Entries:**
  1. `data` file at root (protobuf binary)
  2. `.pro` song files at root (filename only)
  3. Media files at original absolute paths (minus leading `/`)

### ZIP64 EOCD Quirk
- **Issue:** 98-byte discrepancy between ZIP64 EOCD locator offset and actual EOCD position
- **Observed Pattern:** ZIP64 EOCD locator points to offset that is 98 bytes before actual EOCD record
- **Workaround:** Search backward from end of file for ZIP64 EOCD signature (`0x06064b50`)

### Entry Layout Example
```
data                                    ← Protobuf binary
Test.pro                                ← Song file (filename only)
Oceans.pro                              ← Song file (filename only)
Users/me/Pictures/slide.jpg             ← Media file (absolute path minus leading /)
Users/me/Videos/intro.mp4               ← Media file (absolute path minus leading /)
```

### Deduplication Rules
- **Song Files:** Same `.pro` file stored once (by filename)
- **Media Files:** Deduplicated by absolute path
- **Example:** If 3 playlist items reference `Oceans.pro`, only 1 copy is stored in ZIP

---

## 5. Playlist Items

### Definition
Playlist items represent individual entries in a playlist. Each item has a type (header, presentation, cue, planning_center, placeholder) defined by the `ItemType` oneof field.

### Item Types

#### Header (Field 3)
- **Purpose:** Section divider with color
- **Usage:** Visual separator in playlist UI
- **Fields:** `color` (RGBA), `actions[]` (rarely used)
- **Example:** "Worship Set", "Announcements", "Offering"

#### Presentation (Field 4)
- **Purpose:** Reference to a `.pro` song file
- **Usage:** Most common item type
- **Fields:**
  - `document_path` (URL) — Path to `.pro` file
  - `arrangement` (UUID) — Arrangement UUID
  - `arrangement_name` (string) — Arrangement name (e.g., "normal", "bene", "test2")
  - `user_music_key` (MusicKeyScale) — User-selected music key
  - `content_destination` (ContentDestination) — Target layer
- **Example:** Reference to "Oceans.pro" with arrangement "normal"

#### Cue (Field 5)
- **Purpose:** Inline cue (not observed in reference files)
- **Usage:** Embedded cue without external `.pro` file
- **Fields:** Full `rv.data.Cue` message

#### PlanningCenter (Field 6)
- **Purpose:** Planning Center Online integration
- **Usage:** Link to PCO plan item
- **Fields:** `item` (PlanItem), `linked_data` (PlaylistItem)
- **Note:** Not in scope for this specification

#### Placeholder (Field 8)
- **Purpose:** Empty slot in playlist
- **Usage:** Reserve space for future item
- **Fields:** `linked_data` (PlaylistItem)

### Access Pattern
```php
foreach ($playlist->getItems() as $item) {
    $uuid = $item->getUuid();
    $name = $item->getName();
    
    if ($item->hasPresentation()) {
        $presentation = $item->getPresentation();
        $path = $presentation->getDocumentPath()->getAbsoluteString();
        $arrangementName = $presentation->getArrangementName();
        $arrangementUuid = $presentation->getArrangement()->getString();
    } elseif ($item->hasHeader()) {
        $header = $item->getHeader();
        $color = $header->getColor();
    } elseif ($item->hasPlaceholder()) {
        // Empty slot
    }
}
```

---

## 6. URL Format

### URL Structure
ProPresenter uses `rv.data.URL` messages with root type and relative path components.

### Root Types
- **ROOT_USER_HOME (2):** User home directory (`~/`)
- **ROOT_SHOW (10):** ProPresenter library directory

### Path Construction
- **Format:** `root_type` + `relative_path`
- **Example (ROOT_USER_HOME):**
  - Root: `ROOT_USER_HOME (2)`
  - Relative: `Music/ProPresenter/Oceans.pro`
  - Absolute: `file:///Users/username/Music/ProPresenter/Oceans.pro`
- **Example (ROOT_SHOW):**
  - Root: `ROOT_SHOW (10)`
  - Relative: `Oceans.pro`
  - Absolute: `file:///Users/username/Library/Application Support/RenewedVision/ProPresenter/Oceans.pro`

### Media File Paths
- **Storage:** Original absolute path minus leading `/`
- **Example:**
  - Original: `file:///Users/me/Pictures/slide.jpg`
  - ZIP entry: `Users/me/Pictures/slide.jpg`

---

## 7. Protobuf Structure

### Root Container
- **Message:** `rv.data.Playlist`
- **Name:** Always "PLAYLIST"
- **Type:** Always `TYPE_PLAYLIST (1)`
- **Children:** `playlists` field (PlaylistArray)

### Actual Playlist
- **Location:** `playlists.playlists[0]`
- **Name:** User-defined (e.g., "Gottesdienst", "Sunday Service")
- **Type:** Always `TYPE_PLAYLIST (1)`
- **Children:** `items` field (PlaylistItems)

### Nested Structure
```
Playlist (root "PLAYLIST")
  → playlists (PlaylistArray, field 12)
    → playlists[] (Playlist)
      → items (PlaylistItems, field 13)
        → items[] (PlaylistItem)
```

### Example (TestPlaylist.proplaylist)
```
Playlist {
  name: "PLAYLIST"
  type: TYPE_PLAYLIST (1)
  playlists: {
    playlists: [
      {
        name: "TestPlaylist"
        type: TYPE_PLAYLIST (1)
        items: {
          items: [
            { name: "Worship", header: { color: {...} } },
            { name: "Oceans", presentation: { document_path: {...}, arrangement_name: "normal" } },
            { name: "Amazing Grace", presentation: { document_path: {...}, arrangement_name: "bene" } },
          ]
        }
      }
    ]
  }
}
```

---

## 8. Known Constants

### Application Info
- **Platform:** macOS 14.8.3
- **Application:** ProPresenter v20
- **Observed in:** All reference files

### Playlist Type
- **Root Playlist:** Always `TYPE_PLAYLIST (1)`
- **Child Playlists:** Always `TYPE_PLAYLIST (1)`
- **Other Types:** `TYPE_GROUP (2)`, `TYPE_SMART (3)`, `TYPE_ROOT (4)` not observed in reference files

### Root Name
- **Value:** Always "PLAYLIST"
- **Purpose:** Container for actual named playlists

### Arrangement Name (Field 5)
- **Status:** UNDOCUMENTED in community proto
- **Observed Values:** "normal", "bene", "test2", "Gottesdienst", etc.
- **Purpose:** Human-readable arrangement name (complements arrangement UUID)
- **Frequency:** Present in every `PlaylistItem.Presentation` in reference files

---

## 9. Edge Cases

### Empty Playlists
- **Items:** 0 items
- **Validity:** Valid
- **Behavior:** `items.items[]` is empty array

### Playlists Without Presentations
- **Items:** Only headers and placeholders
- **Validity:** Valid
- **Example:** Template playlists with section dividers

### Missing Arrangement Name
- **Field:** `arrangement_name` (field 5)
- **Behavior:** Empty string or not set
- **Validity:** Valid (fallback to arrangement UUID)

### Duplicate Song References
- **Scenario:** Same `.pro` file referenced multiple times
- **ZIP Storage:** Single copy of `.pro` file
- **Playlist Items:** Multiple `PlaylistItem.Presentation` entries with same `document_path`

### Media Files
- **Storage:** Original absolute paths (minus leading `/`)
- **Deduplication:** By absolute path
- **Example:** `Users/me/Pictures/slide.jpg` stored once even if referenced in multiple songs

---

## 10. Reverse-Engineering Evidence

### Reference Files
- **TestPlaylist.proplaylist:** 4 ZIP entries, 3 items (1 header, 2 presentations)
- **Gottesdienst.proplaylist:** 14MB, 25+ items, multiple media files
- **Gottesdienst 2.proplaylist:** 10MB, similar structure
- **Gottesdienst 3.proplaylist:** 16MB, largest reference file

### Key Discoveries
1. **ZIP64 EOCD Quirk:** 98-byte offset discrepancy in all files
2. **Store Compression:** No deflate compression (method 0)
3. **Arrangement Name:** Field 5 on `PlaylistItem.Presentation` is undocumented but present in all files
4. **Root Container:** Always named "PLAYLIST" with `TYPE_PLAYLIST (1)`
5. **Deduplication:** Same `.pro` file stored once, media files deduplicated by path

### Observed Patterns
- **Color Values:** RGBA floats (e.g., `[0.95, 0.27, 0.27, 1.0]` for red)
- **UUID Format:** Standard UUID strings (e.g., `A1B2C3D4-E5F6-G7H8-I9J0-K1L2M3N4O5P6`)
- **Arrangement Names:** User-defined strings (e.g., "normal", "bene", "test2", "Gottesdienst")
- **Media Paths:** Absolute file URLs (e.g., `file:///Users/me/Pictures/slide.jpg`)

---

## Appendix: Proto Field Numbers Quick Reference

| Message | Field | Number |
|---------|-------|--------|
| Playlist | uuid | 1 |
| Playlist | name | 2 |
| Playlist | type | 3 |
| Playlist | expanded | 4 |
| Playlist | targeted_layer_uuid | 5 |
| Playlist | smart_directory_path | 6 |
| Playlist | hot_key | 7 |
| Playlist | cues | 8 |
| Playlist | children | 9 |
| Playlist | timecode_enabled | 10 |
| Playlist | timing | 11 |
| Playlist | playlists | 12 |
| Playlist | items | 13 |
| Playlist | smart_directory | 14 |
| Playlist | pco_plan | 15 |
| Playlist | startup_info | 16 |
| PlaylistArray | playlists | 1 |
| PlaylistItems | items | 1 |
| PlaylistItem | uuid | 1 |
| PlaylistItem | name | 2 |
| PlaylistItem | header | 3 |
| PlaylistItem | presentation | 4 |
| PlaylistItem | cue | 5 |
| PlaylistItem | planning_center | 6 |
| PlaylistItem | tags | 7 |
| PlaylistItem | placeholder | 8 |
| PlaylistItem | is_hidden | 9 |
| PlaylistItem.Header | color | 1 |
| PlaylistItem.Header | actions | 2 |
| PlaylistItem.Presentation | document_path | 1 |
| PlaylistItem.Presentation | arrangement | 2 |
| PlaylistItem.Presentation | content_destination | 3 |
| PlaylistItem.Presentation | user_music_key | 4 |
| PlaylistItem.Presentation | arrangement_name | 5 |
| PlaylistItem.PlanningCenter | item | 1 |
| PlaylistItem.PlanningCenter | linked_data | 2 |
| PlaylistItem.Placeholder | linked_data | 1 |

---

**End of Specification**
