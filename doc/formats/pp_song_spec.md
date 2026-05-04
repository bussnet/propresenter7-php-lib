# ProPresenter 7 `.pro` File Format Specification

**Version:** 1.1  
**Target Audience:** AI agents, automated parsers, developers  
**Proto Source:** greyshirtguy/ProPresenter7-Proto Proto 19beta (MIT License)

---

## 1. Overview

### File Format
- **Extension:** `.pro`
- **Binary Format:** Protocol Buffers (Google protobuf v3)
- **Top-level Message:** `rv.data.Presentation` (defined in `presentation.proto`)
- **Proto Definitions:** greyshirtguy/ProPresenter7-Proto Proto 19beta (MIT)

### Known Limitations
- **Binary Fidelity:** Round-trip decode→encode fails on all reference files. Proto definitions are incomplete; unknown fields are lost during serialization.
- **Workaround:** Preserve original binary data if exact binary reproduction is required.

### File Validity
- **Empty files (0 bytes):** Invalid. Throw exception.
- **Songs without arrangements:** Valid. 17 out of 169 reference files have no arrangements.
- **Non-song presentations:** Files like ANKUENDIGUNGEN, MODERATION, THEMA have groups/slides but may lack text elements.

---

## 2. Song Structure

### Hierarchy Diagram

```
Presentation (rv.data.Presentation)
├── name (string, field 1)
├── uuid (rv.data.UUID, field 5)
├── cue_groups[] (rv.data.Presentation.CueGroup, field 12) ← Groups
│   ├── group (rv.data.Group, field 1)
│   │   ├── name (string, field 2)
│   │   ├── uuid (rv.data.UUID, field 1)
│   │   └── color (rv.data.Color, field 3) [optional]
│   └── cue_identifiers[] (rv.data.UUID, field 2) ← Slide UUID references
├── cues[] (rv.data.Cue, field 13) ← Slides
│   ├── uuid (rv.data.UUID, field 1)
│   ├── name (string, field 2) ← Optional slide label/title
│   └── actions[] (rv.data.Action, field 10)
│       ├── actions[0] slide (type=11)
│       │   └── slide (rv.data.Action.SlideType, field 23)
│       │       └── presentation (rv.data.PresentationSlide, field 2)
│       │           └── base_slide (rv.data.Slide, field 1)
│       │               └── elements[] (rv.data.Slide.Element, field 1)
│       │                   └── element (rv.data.Graphics.Element, field 1)
│       │                       ├── name (string, field 2) ← Label like "Orginal", "Deutsch"
│       │                       └── text (rv.data.Graphics.Text, field 13)
│       │                           └── rtf_data (bytes, field 3) ← RTF-encoded text
│       ├── actions[n] media (type=2) [optional]
│       │   └── media (rv.data.Action.MediaType, field 20)
│       │       └── element (rv.data.Media, field 5)
│       └── actions[n] macro (type=23) [optional]
│           └── macro (rv.data.Action.MacroType, field 40)
└── arrangements[] (rv.data.Presentation.Arrangement, field 11)
    ├── name (string, field 2)
    ├── uuid (rv.data.UUID, field 1)
    └── group_identifiers[] (rv.data.UUID, field 3) ← Group UUID references
```

### Navigation Paths

**To access slide text:**
```
Presentation
  → cues[i]
    → actions[0]
      → slide
        → presentation
          → base_slide
            → elements[j]
              → element
                → text.rtf_data
```

**To access group metadata:**
```
Presentation
  → cue_groups[i]
    → group
      → name, uuid, color
```

**To access arrangement order:**
```
Presentation
  → arrangements[i]
    → group_identifiers[]
```

---

## 3. Fields Reference

### Presentation (rv.data.Presentation)

| Field Path | Protobuf Type | Field Number | Description |
|------------|---------------|--------------|-------------|
| `application_info` | `rv.data.ApplicationInfo` | 1 | Platform and application version info |
| `uuid` | `rv.data.UUID` | 2 | Unique identifier for the presentation |
| `name` | `string` | 3 | Song title (e.g., "Amazing Grace") |
| `last_date_used` | `rv.data.Timestamp` | 4 | Last date the song was used |
| `last_modified_date` | `rv.data.Timestamp` | 5 | Last modification date |
| `category` | `string` | 6 | Optional category label |
| `notes` | `string` | 7 | Optional notes |
| `background` | `rv.data.Background` | 8 | Background color/image |
| `selected_arrangement` | `rv.data.UUID` | 10 | UUID of the currently selected arrangement |
| `arrangements[]` | `rv.data.Presentation.Arrangement` | 11 | Array of arrangements |
| `cue_groups[]` | `rv.data.Presentation.CueGroup` | 12 | Array of groups (song parts) |
| `cues[]` | `rv.data.Cue` | 13 | Array of slides |
| `ccli` | `rv.data.Presentation.CCLI` | 14 | CCLI licensing metadata |
| `timeline` | `rv.data.Presentation.Timeline` | 17 | Timeline with duration |
| `music_key` | `string` | 22 | Music key (rarely used) |
| `music` | `rv.data.Presentation.Music` | 23 | Music key scale data |

### Presentation.CCLI

CCLI (Christian Copyright Licensing International) metadata. Present in 157 out of 168 reference files.

| Field Path | Protobuf Type | Field Number | Description |
|------------|---------------|--------------|-------------|
| `author` | `string` | 1 | Song author(s) (e.g., "Joel Houston, Matt Crocker") |
| `artist_credits` | `string` | 2 | Artist credits (rarely used) |
| `song_title` | `string` | 3 | CCLI song title |
| `publisher` | `string` | 4 | Publisher (e.g., "2012 Hillsong Music Publishing") |
| `copyright_year` | `uint32` | 5 | Copyright year (e.g., 2012) |
| `song_number` | `uint32` | 6 | CCLI song number (e.g., 6428767) |
| `display` | `bool` | 7 | Whether to display CCLI info |
| `album` | `string` | 8 | Album name (rarely used) |
| `artwork` | `bytes` | 9 | Album artwork (rarely used) |
### Presentation.CueGroup

| Field Path | Protobuf Type | Field Number | Description |
|------------|---------------|--------------|-------------|
| `group` | `rv.data.Group` | 1 | Group metadata (name, uuid, color) |
| `cue_identifiers[]` | `rv.data.UUID` | 2 | Array of slide UUIDs in this group |

### Group (rv.data.Group)

| Field Path | Protobuf Type | Field Number | Description |
|------------|---------------|--------------|-------------|
| `uuid` | `rv.data.UUID` | 1 | Unique identifier for the group |
| `name` | `string` | 2 | Display name (e.g., "Verse 1", "Chorus") |
| `color` | `rv.data.Color` | 3 | Optional RGBA color (float values 0.0-1.0) |

### Presentation.Arrangement

| Field Path | Protobuf Type | Field Number | Description |
|------------|---------------|--------------|-------------|
| `uuid` | `rv.data.UUID` | 1 | Unique identifier for the arrangement |
| `name` | `string` | 2 | Arrangement name (e.g., "normal", "test2") |
| `group_identifiers[]` | `rv.data.UUID` | 3 | Ordered array of group UUIDs |

### Cue (rv.data.Cue)

| Field Path | Protobuf Type | Field Number | Description |
|------------|---------------|--------------|-------------|
| `uuid` | `rv.data.UUID` | 1 | Unique identifier for the slide |
| `name` | `string` | 2 | Optional slide label/title shown in UI |
| `actions[]` | `rv.data.Action` | 10 | Array of actions (slide action at index 0, optional media/macro actions after it) |

### Action (rv.data.Action)

| Field Path | Protobuf Type | Field Number | Description |
|------------|---------------|--------------|-------------|
| `type` | `rv.data.Action.ActionType` | 9 | Action type enum (`11` slide, `2` media, `23` macro) |
| `slide` | `rv.data.Action.SlideType` | 23 | Slide data (oneof field) |
| `media` | `rv.data.Action.MediaType` | 20 | Media/image action payload (oneof field) |
| `macro` | `rv.data.Action.MacroType` | 40 | Macro action payload (oneof field) |

### Action.SlideType

| Field Path | Protobuf Type | Field Number | Description |
|------------|---------------|--------------|-------------|
| `presentation` | `rv.data.PresentationSlide` | 2 | Presentation slide (oneof field) |

### PresentationSlide (rv.data.PresentationSlide)

| Field Path | Protobuf Type | Field Number | Description |
|------------|---------------|--------------|-------------|
| `base_slide` | `rv.data.Slide` | 1 | Base slide containing elements |

### Slide (rv.data.Slide)

| Field Path | Protobuf Type | Field Number | Description |
|------------|---------------|--------------|-------------|
| `elements[]` | `rv.data.Slide.Element` | 1 | Array of slide elements |

### Slide.Element

| Field Path | Protobuf Type | Field Number | Description |
|------------|---------------|--------------|-------------|
| `element` | `rv.data.Graphics.Element` | 1 | Graphics element wrapper |

### Graphics.Element

| Field Path | Protobuf Type | Field Number | Description |
|------------|---------------|--------------|-------------|
| `uuid` | `rv.data.UUID` | 1 | Unique identifier for the element |
| `name` | `string` | 2 | User-defined label (e.g., "Orginal", "Deutsch") |
| `text` | `rv.data.Graphics.Text` | 13 | Text data (optional) |

### Graphics.Text

| Field Path | Protobuf Type | Field Number | Description |
|------------|---------------|--------------|-------------|
| `rtf_data` | `bytes` | 3 | RTF-encoded text content |

---

## 4. Groups

### Definition
Groups represent song parts (Verse 1, Verse 2, Chorus, Bridge, Ending, etc.). They define logical sections of a song.

### Characteristics
- **Names:** User-defined strings. Not standardized. Examples: "Verse 1", "Strophe 1", "Refrain", "Ending".
- **Slide References:** Each group contains an ordered array of slide UUIDs (`cue_identifiers`).
- **Color:** Optional RGBA color (float values 0.0-1.0 for red, green, blue, alpha).
- **Special Groups:** COPYRIGHT, BLANK — treated as regular groups (no special handling required).

### Example (Test.pro)
- **Verse 1** → 1 slide
- **Verse 2** → 1 slide
- **Chorus** → 2 slides
- **Ending** → 1 slide

### Access Pattern
```php
foreach ($presentation->getCueGroups() as $cueGroup) {
    $group = $cueGroup->getGroup();
    $name = $group->getName();
    $uuid = $group->getUuid()->getString();
    $slideUuids = [];
    foreach ($cueGroup->getCueIdentifiers() as $uuid) {
        $slideUuids[] = $uuid->getString();
    }
}
```

---

## 5. Slides

### Definition
Slides are individual presentation frames. Each slide can contain multiple elements (text, shapes, media).

### Navigation Path
```
Cue → actions[0] → slide → presentation → base_slide → elements[]
```

### Text Elements
- **Location:** `base_slide.elements[]` contains `Slide.Element` wrappers.
- **Graphics Element:** Each `Slide.Element` wraps a `Graphics.Element`.
- **Text Data:** `Graphics.Element.text.rtf_data` contains RTF-encoded text.
- **Element Name:** `Graphics.Element.name` is a user-defined label (e.g., "Orginal", "Deutsch").

### Slides Without Text
Some slides contain only media (images, videos) or shapes. These slides have `elements[]` with no `text` field set.

### Slide Labels (Cue.name)
- **Location:** `Cue.name`
- **Meaning:** Optional title/label for a slide in ProPresenter UI.
- **Example:** `Seniorennachmittag März.jpg`

### Media/Image Actions
- **Detection:** Any cue action where `Action.type == ACTION_TYPE_MEDIA (2)`.
- **Path:** `Cue.actions[n].media.element`
- **URL:** `media.element.url.absolute_string` (typically `file:///...`)
- **Format:** `media.element.metadata.format` (e.g., `JPG`)
- **Image Marker:** `media.element.image` oneof is set (`ImageTypeProperties`)
- **Text Relation:** Image slides often still include `actions[0]` slide action with an empty `base_slide.elements[]`.

### Macro Actions
- **Detection:** Any cue action where `Action.type == ACTION_TYPE_MACRO (23)`.
- **Path:** `Cue.actions[n].macro.identification`
- **Macro Name/UUID:** `identification.parameter_name`, `identification.parameter_uuid.string`
- **Collection Name/UUID:** `identification.parent_collection.parameter_name`, `identification.parent_collection.parameter_uuid.string`
- **Observed Collection:** `--MAIN--` with UUID `8D02FC57-83F8-4042-9B90-81C229728426` in sample files.

### UUID References
Groups reference slides by UUID. Use `Cue.uuid` to match slides to group references.

### Example (Test.pro)
- **5 slides total**
- **Chorus group** → 2 slides (UUIDs referenced in `cue_identifiers`)

---

## 6. Arrangements

### Definition
Arrangements define the order and selection of groups for a presentation. They specify which groups appear and in what sequence.

### Characteristics
- **Group References:** Ordered array of group UUIDs (`group_identifiers`).
- **Repetition:** The same group UUID can appear multiple times (e.g., Chorus repeated 3 times).
- **Optional:** Songs may have 0 or more arrangements.
- **No Arrangements:** 17 out of 169 reference files have no arrangements. This is valid.

### Example (Test.pro)
- **Arrangement "normal":** Verse 1 → Chorus → Verse 2 → Chorus → Ending
- **Arrangement "test2":** Verse 1 → Verse 2 → Chorus

### Access Pattern
```php
foreach ($presentation->getArrangements() as $arrangement) {
    $name = $arrangement->getName();
    $groupUuids = [];
    foreach ($arrangement->getGroupIdentifiers() as $uuid) {
        $groupUuids[] = $uuid->getString();
    }
}
```

---

## 7. Translations

### Definition
Multiple `elements[]` per slide represent multiple text layers. The first element is the original text; subsequent elements are translations.

### Characteristics
- **Element Count:** 1 element = no translation. 2+ elements = translation present.
- **Element Names:** User-defined labels (e.g., "Orginal", "Deutsch", "Text", "Text 2").
- **Label Patterns:** 3 known patterns observed:
  1. "Orginal" / "Deutsch"
  2. "Text" / "Text 2"
  3. No specific naming (generic labels)
- **Not Standardized:** Element names are arbitrary strings. Do NOT assume fixed labels.

### Detection
```php
$textElements = [];
foreach ($baseSlide->getElements() as $slideElement) {
    $graphicsElement = $slideElement->getElement();
    if ($graphicsElement !== null && $graphicsElement->hasText()) {
        $textElements[] = $graphicsElement;
    }
}

$hasTranslation = count($textElements) >= 2;
$originalText = $textElements[0]->getText()->getRtfData();
$translationText = $textElements[1]->getText()->getRtfData() ?? null;
```

### Example (Test.pro)
- **Slide 1:** 2 text elements → "Orginal" (German), "Deutsch" (English translation)
- **Element Names:** User-defined, not standardized

---

## 8. Edge Cases

### Empty Files
- **Size:** 0 bytes
- **Validity:** Invalid
- **Action:** Throw exception

### Songs Without Arrangements
- **Frequency:** 17 out of 169 reference files
- **Validity:** Valid
- **Behavior:** `arrangements[]` is empty. Groups and slides still exist.

### Non-Song Presentations
- **Examples:** ANKUENDIGUNGEN, MODERATION, THEMA
- **Characteristics:** Have groups and slides but may lack text elements.
- **Validity:** Valid

### Slides Without Text
- **Characteristics:** `elements[]` contains shapes, media, or other non-text elements.
- **Detection:** `Graphics.Element.hasText()` returns false.
- **Validity:** Valid

### COPYRIGHT and BLANK Groups
- **Treatment:** Regular groups (no special handling required).
- **Validity:** Valid

---

## 9. RTF Text Format

### Format Variant
- **Type:** Apple CocoaRTF 2761
- **Encoding:** Windows-1252 (ANSI codepage 1252)

### Structure
```
{\rtf1\ansi\ansicpg1252\cocoartf2761
{\fonttbl\f0\fswiss\fcharset0 Helvetica;}
{\colortbl;\red255\green255\blue255;}
{\*\expandedcolortbl;;}
\pard\tx560\tx1120\tx1680\tx2240\tx2800\tx3360\tx3920\tx4480\tx5040\tx5600\tx6160\tx6720\pardirnatural\partightenfactor0

\f0\fs96 \cf1 \CocoaLigature0 TEXT STARTS HERE}
```

### Text Extraction
- **Text Start:** After `\CocoaLigature0 ` (space after 0 is the delimiter).
- **Soft Returns:** `\` + newline character = line break within slide.
- **Paragraph Breaks:** `\par` = paragraph break.

### Character Encoding

#### Windows-1252 Hex Escapes
- **Format:** `\'xx` where `xx` is a hex byte value.
- **Examples:**
  - `\'fc` → ü (U+00FC)
  - `\'f6` → ö (U+00F6)
  - `\'e4` → ä (U+00E4)
  - `\'df` → ß (U+00DF)

#### Unicode Escapes
- **Format:** `\uN?` where `N` is a decimal codepoint, `?` is an ANSI fallback character.
- **Examples:**
  - `\u8364?` → € (U+20AC)
  - `\u8220?` → " (U+201C)
  - `\u8221?` → " (U+201D)
- **Negative Values:** RTF uses signed 16-bit integers. Negative values are converted: `codepoint + 65536`.

### Control Words
- **Format:** `\word[N]` followed by space or non-alpha character.
- **Common Words:**
  - `\par` → paragraph break
  - `\CocoaLigature0` → text start marker
  - `\f0`, `\fs96`, `\cf1` → formatting (font, size, color)
- **Delimiter:** Space after control word is consumed (not part of text).

### Escaped Characters
- `\{` → `{`
- `\}` → `}`
- `\\` → `\` (or soft return in ProPresenter context)

### Example RTF
```rtf
{\rtf1\ansi\ansicpg1252\cocoartf2761
{\fonttbl\f0\fswiss\fcharset0 Helvetica;}
{\colortbl;\red255\green255\blue255;}
{\*\expandedcolortbl;;}
\pard\tx560\pardirnatural\partightenfactor0

\f0\fs96 \cf1 \CocoaLigature0 Gro\'dfe Gnade\
Amazing Grace}
```

**Plain Text Output:**
```
Große Gnade
Amazing Grace
```

---

## 10. PHP Parser Usage

### Installation
```bash
composer require propresenter/parser
```

### Read a Song
```php
use ProPresenter\Parser\ProFileReader;

$song = ProFileReader::read('path/to/song.pro');
```

### Access Song Metadata
```php
// Song name and UUID
$name = $song->getName();  // "Amazing Grace"
$uuid = $song->getUuid();  // "A1B2C3D4-..."

// CCLI metadata
$author = $song->getCcliAuthor();          // "Joel Houston, Matt Crocker"
$title = $song->getCcliSongTitle();        // "Oceans (Where Feet May Fail)"
$publisher = $song->getCcliPublisher();    // "2012 Hillsong Music Publishing"
$year = $song->getCcliCopyrightYear();     // 2012
$number = $song->getCcliSongNumber();      // 6428767
$display = $song->getCcliDisplay();        // true
$credits = $song->getCcliArtistCredits();  // ""
$album = $song->getCcliAlbum();            // ""

// Other metadata
$category = $song->getCategory();          // ""
$notes = $song->getNotes();                // ""
$selectedArr = $song->getSelectedArrangementUuid();  // "uuid-string"

// Groups, Slides, Arrangements
$groups = $song->getGroups();              // Group[]
$slides = $song->getSlides();              // Slide[]
$arrangements = $song->getArrangements();  // Arrangement[]
```

### Access Groups
```php
foreach ($song->getGroups() as $group) {
    $name = $group->getName();        // "Verse 1"
    $uuid = $group->getUuid();        // "E5F6G7H8-..."
    $color = $group->getColor();      // ['r' => 1.0, 'g' => 0.0, 'b' => 0.0, 'a' => 1.0] or null
    $slideUuids = $group->getSlideUuids();  // ["uuid1", "uuid2", ...]
}
```

### Access Slides
```php
foreach ($song->getSlides() as $slide) {
    $uuid = $slide->getUuid();
    $plainText = $slide->getPlainText();  // Extracted from first text element
    
    // Check for translation
    if ($slide->hasTranslation()) {
        $translation = $slide->getTranslation();
        $translatedText = $translation->getPlainText();
    }
    
    // Access all text elements
    foreach ($slide->getTextElements() as $textElement) {
        $name = $textElement->getName();        // "Orginal", "Deutsch", etc.
        $rtf = $textElement->getRtfData();      // Raw RTF bytes
        $plain = $textElement->getPlainText();  // Extracted plain text
    }
}
```

### Access Arrangements
```php
foreach ($song->getArrangements() as $arrangement) {
    $name = $arrangement->getName();  // "normal"
    $groupUuids = $arrangement->getGroupUuids();  // ["uuid1", "uuid2", "uuid1", ...]
    
    // Resolve groups
    $groups = $song->getGroupsForArrangement($arrangement);
    foreach ($groups as $group) {
        echo $group->getName() . "\n";
    }
}
```

### Access Slides for a Group
```php
$group = $song->getGroupByName("Chorus");
$slides = $song->getSlidesForGroup($group);

foreach ($slides as $slide) {
    echo $slide->getPlainText() . "\n";
}
```

### Modify and Write
```php
use ProPresenter\Parser\ProFileWriter;

// Modify song metadata
$song->setName("New Song Title");
$song->setCategory("Worship");
$song->setNotes("Use acoustic intro");

// Modify CCLI metadata
$song->setCcliAuthor("Author Name");
$song->setCcliSongTitle("Song Title");
$song->setCcliPublisher("Publisher");
$song->setCcliCopyrightYear(2024);
$song->setCcliSongNumber(12345);
$song->setCcliDisplay(true);

// Modify group
$group = $song->getGroupByName("Verse 1");
$group->setName("Strophe 1");

// Write to file
ProFileWriter::write($song, 'output.pro');
```

### Error Handling
```php
try {
    $song = ProFileReader::read('song.pro');
} catch (\RuntimeException $e) {
    // File not found, empty file, or invalid protobuf
    echo "Error: " . $e->getMessage();
}
```

### Example: Extract All Text
```php
$song = ProFileReader::read('song.pro');

foreach ($song->getGroups() as $group) {
    echo "Group: " . $group->getName() . "\n";
    
    $slides = $song->getSlidesForGroup($group);
    foreach ($slides as $slide) {
        echo "  Original: " . $slide->getPlainText() . "\n";
        
        if ($slide->hasTranslation()) {
            echo "  Translation: " . $slide->getTranslation()->getPlainText() . "\n";
        }
    }
}
```

### Example: Create Arrangement
```php
$song = ProFileReader::read('song.pro');

// Get group UUIDs
$verse1 = $song->getGroupByName("Verse 1");
$chorus = $song->getGroupByName("Chorus");
$verse2 = $song->getGroupByName("Verse 2");

// Create new arrangement
$arrangement = new Arrangement(new \Rv\Data\Presentation\Arrangement());
$arrangement->setName("custom");
$arrangement->setGroupUuids([
    $verse1->getUuid(),
    $chorus->getUuid(),
    $verse2->getUuid(),
    $chorus->getUuid(),
]);

// Add to song (requires direct protobuf access)
$song->getPresentation()->getArrangements()[] = $arrangement->getProto();

ProFileWriter::write($song, 'output.pro');
```

### Generate a New Song
```php
use ProPresenter\Parser\ProFileGenerator;

$song = ProFileGenerator::generate(
    'Amazing Grace',
    [
        [
            'name' => 'Verse 1',
            'color' => [0.13, 0.59, 0.95, 1.0],
            'slides' => [
                ['text' => 'Amazing grace, how sweet the sound'],
                ['text' => 'That saved a wretch like me'],
            ],
        ],
        [
            'name' => 'Chorus',
            'color' => [0.95, 0.27, 0.27, 1.0],
            'slides' => [
                ['text' => 'I once was lost, but now am found'],
            ],
        ],
    ],
    [
        ['name' => 'normal', 'groupNames' => ['Verse 1', 'Chorus', 'Verse 1']],
    ],
    [
        'author' => 'John Newton',
        'song_title' => 'Amazing Grace',
        'copyright_year' => 1779,
    ],
);

// Write to file
ProFileGenerator::generateAndWrite('output.pro', 'Amazing Grace', $groups, $arrangements, $ccli);
```

### Generate a Song with Translations
```php
$song = ProFileGenerator::generate(
    'Oceans',
    [
        [
            'name' => 'Verse 1',
            'color' => [0.13, 0.59, 0.95, 1.0],
            'slides' => [
                [
                    'text' => 'You call me out upon the waters',
                    'translation' => 'Du rufst mich auf das Wasser',
                ],
            ],
        ],
    ],
    [
        ['name' => 'normal', 'groupNames' => ['Verse 1']],
    ],
);
```

---

## Appendix: Test.pro Structure

### Groups (4)
1. **Verse 1** → 2 slides
2. **Verse 2** → 1 slide
3. **Chorus** → 1 slide
4. **Ending** → 1 slide

### Slides (5)
- Slides 1-2: Verse 1 text (2 text elements each: "Orginal", "Deutsch")
- Slide 3: Verse 2 text (2 text elements)
- Slide 4: Chorus text (2 text elements)
- Slide 5: Ending text (2 text elements, with translations)

### Arrangements (2)
1. **normal:** Chorus → Verse 1 → Chorus → Verse 2 → Chorus
2. **test2:** Verse 1 → Chorus → Verse 2 → Chorus

---

## Appendix: Reference Statistics

- **Total Files:** 169
- **Parseable Files:** 168
- **Empty Files:** 1 (invalid)
- **Files Without Arrangements:** 17 (valid)
- **Files With CCLI Data:** 157 out of 168
- **Binary Fidelity:** 0 files pass round-trip decode→encode (proto definitions incomplete)

---

## Appendix: Proto Field Numbers Quick Reference

| Message | Field | Number |
|---------|-------|--------|
| Presentation | application_info | 1 |
| Presentation | uuid | 2 |
| Presentation | name | 3 |
| Presentation | last_date_used | 4 |
| Presentation | last_modified_date | 5 |
| Presentation | category | 6 |
| Presentation | notes | 7 |
| Presentation | selected_arrangement | 10 |
| Presentation | arrangements | 11 |
| Presentation | cue_groups | 12 |
| Presentation | cues | 13 |
| Presentation | ccli | 14 |
| Presentation | timeline | 17 |
| Presentation | music_key | 22 |
| Presentation | music | 23 |
| Presentation.CCLI | author | 1 |
| Presentation.CCLI | artist_credits | 2 |
| Presentation.CCLI | song_title | 3 |
| Presentation.CCLI | publisher | 4 |
| Presentation.CCLI | copyright_year | 5 |
| Presentation.CCLI | song_number | 6 |
| Presentation.CCLI | display | 7 |
| Presentation.CCLI | album | 8 |
| Presentation.CCLI | artwork | 9 |
| CueGroup | group | 1 |
| CueGroup | cue_identifiers | 2 |
| Group | uuid | 1 |
| Group | name | 2 |
| Group | color | 3 |
| Arrangement | uuid | 1 |
| Arrangement | name | 2 |
| Arrangement | group_identifiers | 3 |
| Cue | uuid | 1 |
| Cue | actions | 10 |
| Action | slide | 23 |
| Action.SlideType | presentation | 2 |
| PresentationSlide | base_slide | 1 |
| Slide | elements | 1 |
| Slide.Element | element | 1 |
| Graphics.Element | uuid | 1 |
| Graphics.Element | name | 2 |
| Graphics.Element | text | 13 |
| Graphics.Text | rtf_data | 3 |

---

**End of Specification**
