# Issues & Gotchas

(Agents will log problems encountered here)

- 2026-03-01 task-2 edge case: `Du machst alles neu_ver2025-05-11-4.pro` is 0 bytes; `protoc --decode rv.data.Presentation` returns empty output (no decoded fields).
- 2026-03-01 task-6 fidelity failure: `Rv\Data\Presentation::mergeFromString()->serializeToString()` is not byte-preserving for current generated schema/runtime (`169/169` mismatches, including `Test.pro` with `length_delta=-18`, first mismatch at byte `1205`), so unknown/opaque binary data is still being transformed or dropped.
- 2026-03-01 task-7: no new parser blockers found; UTF-8 filename handling is stable when using raw PHP filesystem functions (`is_file`, `filesize`, `file_get_contents`).

- 2026-03-01 task-2 test gotcha: `unzip` may render UTF-8 filenames with replacement characters; entry-comparison tests normalize names before asserting equality with `ZipArchive` listing.

- 2026-03-01 21:23:59 - Generated header color values deserialize with float precision drift; fixed by assertEqualsWithDelta in generator interoperability test.
