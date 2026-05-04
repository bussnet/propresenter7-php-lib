# ProPresenter 7 `.probundle` File Format Specification

**Version:** 1.0  
**Target Audience:** AI agents, automated parsers, developers  
**Proto Source:** greyshirtguy/ProPresenter7-Proto Proto 19beta (MIT License)

---

## 1. Overview

### File Format
- **Extension:** `.probundle`
- **Container Format:** Standard ZIP archive (PKZIP 2.0+, default deflate compression)
- **Binary Format:** Protocol Buffers (Google protobuf v3) for the embedded `.pro` file
- **Top-level Message:** `rv.data.Presentation` (defined in `presentation.proto`)
- **Proto Definitions:** greyshirtguy/ProPresenter7-Proto Proto 19beta (MIT)
- **Predecessor:** Pro6 `.pro6x` format

### Container Structure
- **Archive Type:** Standard ZIP with deflate compression (default)
- **ZIP64 EOCD Quirk:** ProPresenter 7 exports have the same 98-byte EOCD discrepancy as `.proplaylist` files
- **Entry Layout (library output — flat, portable):**
  - Media files as **flat filenames** at ZIP root (e.g., `background.png`)
  - Single `.pro` file at root (filename only, no directory)
  - Protobuf uses `ROOT_CURRENT_RESOURCE` to resolve media relative to the bundle
- **Entry Layout (PP7 export — absolute paths):**
  - Media files at **absolute paths with leading `/`** (e.g., `/Users/me/Downloads/Media/image.png`)
  - Single `.pro` file at root

### Purpose
A `.probundle` packages a single ProPresenter presentation (`.pro` file) together with all its referenced media assets (images, videos, audio) into a single portable archive. This enables sharing presentations between machines without losing media references.

### File Validity
- **Empty files (0 bytes):** Invalid. Throw exception.
- **Archives without `.pro`:** Invalid. Throw exception.
- **Bundles without media:** Valid. A presentation with no media actions produces a ZIP containing only the `.pro` file.

---

## 2. Archive Structure

### Library Output (Flat — Portable)

```
background.png    <-- Media file (flat filename, no directories)
SongName.pro      <-- Protobuf-encoded presentation
```

Media entries use **flat filenames only** (no directories, no absolute paths). The `.pro` protobuf references media via `ROOT_CURRENT_RESOURCE`, which PP7 resolves relative to the bundle. This makes bundles fully portable across machines.

### PP7 Export (Absolute Paths)

```
/Users/me/Downloads/pp-test/Media/background.png   <-- Absolute path with leading /
SongName.pro                                         <-- Protobuf-encoded presentation
```

PP7's own exports use absolute filesystem paths as ZIP entry names. The reader handles both formats.

### Entry Order
- **Media files first**, then the `.pro` file last
- ProPresenter does not enforce order, but this matches PP7 export behavior

### Compression
- **ProPresenter exports:** Standard deflate compression
- **Writer output:** Standard deflate compression (ZipArchive defaults)
- **No special attributes needed:** Standard permissions, no forced store compression

---

## 3. Protobuf Content (`.pro` File)

### Media URL Format

#### Bundle-Relative (Library Output — Portable)

For bundles, media references use `ROOT_CURRENT_RESOURCE` with just the filename. PP7 resolves this relative to the bundle itself.

```protobuf
message URL {
  string absolute_string = 1;       // "background.png" (just the filename)
  LocalRelativePath local = 4;      // ROOT_CURRENT_RESOURCE + filename
  Platform platform = 5;            // PLATFORM_MACOS
}
```

```
URL.absolute_string = "background.png"
URL.local.root      = ROOT_CURRENT_RESOURCE (12)
URL.local.path      = "background.png"
URL.platform        = PLATFORM_MACOS
```

Both `url` and `image.file.localUrl` use the same structure.

#### Absolute Paths (PP7 Export / Standalone `.pro`)

PP7's own exports and standalone `.pro` files use absolute `file:///` URLs with filesystem-based root mappings:

```
URL.absolute_string = "file:///Users/me/Downloads/pp-test/Media/background.png"
URL.local.root      = ROOT_USER_DOWNLOADS (4)
URL.local.path      = "pp-test/Media/background.png"
URL.platform        = PLATFORM_MACOS
```

#### LocalRelativePath

```protobuf
message LocalRelativePath {
  Root root = 1;     // Enum mapping to macOS directory or bundle context
  string path = 2;   // Relative path from root
}
```

### Root Type Mappings

| Root Enum | Value | macOS Directory |
|-----------|-------|-----------------|
| `ROOT_UNKNOWN` | 0 | Unknown |
| `ROOT_BOOT_VOLUME` | 1 | `/` (fallback) |
| `ROOT_USER_HOME` | 2 | `~/` |
| `ROOT_USER_DOCUMENTS` | 3 | `~/Documents/` |
| `ROOT_USER_DOWNLOADS` | 4 | `~/Downloads/` |
| `ROOT_USER_MUSIC` | 5 | `~/Music/` |
| `ROOT_USER_PICTURES` | 6 | `~/Pictures/` |
| `ROOT_USER_VIDEOS` | 7 | `~/Movies/` |
| `ROOT_USER_APP_SUPPORT` | 8 | `~/Library/Application Support/` |
| `ROOT_SHARED` | 9 | `/Users/Shared/` |
| `ROOT_SHOW` | 10 | ProPresenter library directory |
| `ROOT_USER_DESKTOP` | 11 | `~/Desktop/` |
| **`ROOT_CURRENT_RESOURCE`** | **12** | **Relative to current bundle/document** |

### Media Metadata

| Field | Expected Value | Notes |
|-------|---------------|-------|
| `Metadata.format` | Lowercase: `"png"`, `"jpg"`, `"mp4"` | PP7 uses lowercase |
| `Action.type` | `ACTION_TYPE_MEDIA` | Media action type |
| `MediaType.layer_type` | `LAYER_TYPE_FOREGROUND` | Default for slide media |

---

## 4. ZIP64 EOCD Quirk

### Issue
ProPresenter 7 exports `.probundle` files with the same ZIP64 EOCD bug as `.proplaylist` files: a 98-byte discrepancy between the ZIP64 EOCD locator offset and the actual EOCD position.

### Workaround
The reader applies `Zip64Fixer` before opening the archive. This searches backward from the end of file for the ZIP64 EOCD signature (`0x06064b50`) and corrects the offset.

### Writer Behavior
The writer produces standard ZIPs without the bug. PHP's `ZipArchive` creates clean archives that PP7 imports without issues.

---

## 5. Differences from `.proplaylist`

| Aspect | `.proplaylist` | `.probundle` (library) | `.probundle` (PP7 export) |
|--------|---------------|----------------------|--------------------------|
| **Purpose** | Playlist with multiple songs | Single presentation with media | Single presentation with media |
| **Compression** | Store only (method 0) | Deflate (default) | Deflate |
| **Metadata entry** | `data` file (protobuf `rv.data.Playlist`) | None (`.pro` file IS the data) | None |
| **Song entries** | Multiple `.pro` files | Single `.pro` file | Single `.pro` file |
| **Media paths** | Absolute minus leading `/` | **Flat filenames** | Absolute with leading `/` |
| **Media URL root** | Filesystem-based roots | `ROOT_CURRENT_RESOURCE (12)` | Filesystem-based roots |
| **ZIP64** | Always ZIP64 | Standard ZIP | ZIP64 |

---

## 6. Edge Cases

### Bundles Without Media
- **Valid.** Archive contains only the `.pro` file.
- **Use case:** Sharing a lyrics-only presentation.

### Multiple Media Files
- **Valid.** Each media file gets its own ZIP entry (flat filename).
- **Deduplication:** Same filename stored once.

### Non-Image Media
- **Videos** (`.mp4`, `.mov`): Same URL format, different `Metadata.format`.
- **Audio** (`.mp3`, `.wav`): Same pattern, `MediaType.audio` field used.

### Case Sensitivity
- `.pro` file detection is case-insensitive (`.pro`, `.Pro`, `.PRO`).
- Media format strings should be **lowercase** to match PP7 behavior.

---

## 7. Reverse-Engineering Evidence

### Reference Files
- **TestBild.probundle:** Generated by this library, verified importable by PP7 with image found
- **RestBildExportFromPP.probundle:** Exported by PP7 after import, used as comparison reference

### Key Discoveries
1. **`ROOT_CURRENT_RESOURCE` (12) enables portable bundles:** PP7 resolves this root relative to the bundle, so media stored as flat filenames in the ZIP are found on any machine
2. **`URL.local` is required:** PP7 cannot find media without the `LocalRelativePath` in `URL.local`
3. **Flat filenames work:** ZIP entries like `image.png` (no directories) with `ROOT_CURRENT_RESOURCE` in the protobuf — PP7 finds the media
4. **Lowercase format:** PP7 uses lowercase format strings (`"png"` not `"PNG"`)
5. **Standard ZIP is fine:** PP7 imports standard deflate-compressed ZIPs without issues
6. **ZIP64 EOCD bug:** PP7 exports have the same 98-byte offset quirk as `.proplaylist` files
7. **PP7 exports use absolute paths:** PP7's own exports use `file:///` absolute paths — but these only work on the same machine. The library uses `ROOT_CURRENT_RESOURCE` for portability instead.

### What Didn't Work (Rejected Approaches)
- **`file:///filename.png` with `ROOT_BOOT_VOLUME`:** PP7 cannot resolve bare filenames with filesystem roots
- **`file:///Users/.../filename.png` with flat ZIP entry:** PP7 needs the URL root to match the ZIP structure
- **`ROOT_SHOW` with bare filename:** PP7 looks in its library dir, not the bundle
- **Missing `URL.local`:** PP7 shows "image not found" without `LocalRelativePath`
- **Uppercase format:** `"PNG"` works but doesn't match PP7's own output
- **Forced store compression / 000 permissions:** Unnecessary hacks that don't affect import

---

## Appendix: PP7 Export vs Library Output

### PP7 Export Characteristics (informational only)
- ZIP64 format with 98-byte EOCD offset bug
- Store compression (method 0)
- File permissions set to `0000`
- These are PP7 artifacts — the library reader handles them, the writer doesn't reproduce them

### Library Output Characteristics
- Standard ZIP (PKZIP 2.0+)
- Deflate compression (ZipArchive default)
- Normal file permissions
- PP7 imports these without issues

---

**End of Specification**
