# Changelog

All notable changes to this project are documented in this file.

## [1.0.0] - 2026-07-14

### Added

- Initial release of Telegram Storage Gateway.
- Web UI (`index.php`) with drag & drop, multi-file upload, per-file
  progress bars, dark mode, and copyable results.
- REST API (`api/index.php`) accepting `POST multipart/form-data` with a
  `file` field, returning a normalized JSON response.
- Automatic Telegram upload method selection (`sendPhoto`, `sendVideo`,
  `sendAudio`, `sendVoice`, `sendDocument`) based on server-detected MIME
  type, with `sendDocument` as the fallback.
- Server-side validation: upload errors, file size, filename safety,
  allowed/blocked extensions, allowed/blocked MIME types.
- Centralized configuration via `config.php`.
- JSON-line file logging with configurable, automatic retention
  (age- and size-based, with optional gzip compression before deletion).
- No database, no persistent local file storage, no external dependencies.
