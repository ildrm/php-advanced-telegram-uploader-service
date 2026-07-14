# Telegram Storage Gateway

A small, framework-free PHP application that lets users and other
applications upload files directly to Telegram through a Telegram Bot.
Telegram is the storage backend — this application is only a gateway. It
never becomes a storage provider itself: it does not use a database, and it
never keeps a permanent copy of an uploaded file.

## Features

- **Web UI** — drag & drop or pick multiple files, see per-file upload
  progress, dark mode, and copyable results (file ID, JSON response).
- **REST API** — `POST` a file, get back normalized JSON. Built for
  automation from other applications.
- **Automatic upload method selection** — images go through `sendPhoto`,
  videos through `sendVideo`, audio through `sendAudio`, voice notes
  through `sendVoice`, and everything else through `sendDocument`.
- **Server-side validation** — file size, filename safety, and
  allow/block lists for both extensions and real (server-detected) MIME
  types.
- **No database, no persistent storage** — the only local file is PHP's
  own temporary upload, which is deleted immediately after the file is
  relayed to Telegram (or after a failed attempt).
- **Structured logging** with automatic, configurable retention — no cron
  job required.
- **Zero dependencies** — pure PHP 8.2+, no Composer packages, no
  frameworks. Deploy by uploading the files.

## Requirements

- PHP 8.2 or later
- PHP extensions: `curl`, `fileinfo`, `json` (all bundled with PHP by
  default)
- A web server (Apache, LiteSpeed, or Nginx)
- A Telegram bot token and a destination chat id

## Installation

1. Upload the entire project directory to your web host, or clone it
   there.
2. Create a Telegram bot by messaging [@BotFather](https://t.me/BotFather)
   on Telegram and note the bot token it gives you.
3. Find the chat id the bot should upload files into (a DM with the bot,
   a group, or a channel the bot is a member/admin of). Sending `/start`
   to the bot and checking `getUpdates`, or adding the bot to a group and
   using a helper bot like `@getidsbot`, both work.
4. Edit `config.php` and fill in `telegram.bot_token` and
   `telegram.chat_id`.
5. Make sure the `logs/` directory is writable by the web server.
6. Visit `index.php` in a browser to use the web UI, or `POST` to
   `api/index.php` for the REST API.

No `composer install`, no database setup, and no build step are required.

### Apache / LiteSpeed

The bundled `.htaccess` files already block direct access to `config.php`,
`src/`, and `logs/`, and raise PHP's upload limits under `mod_php`. Nothing
further is required as long as `AllowOverride` is enabled for the
directory.

### Nginx

Nginx does not read `.htaccess`. Add equivalent rules to your server
block:

```nginx
location ~ ^/(src|logs)/ {
    deny all;
}

location = /config.php {
    deny all;
}

client_max_body_size 55m;
```

### PHP-FPM hosts

If your host runs PHP-FPM (common with Nginx, and increasingly with
Apache too), `php_value` directives inside `.htaccess` are ignored or
cause errors. Set the equivalent values in `php.ini`, a pool
configuration, or a `.user.ini` file placed in the project root instead:

```ini
upload_max_filesize = 50M
post_max_size = 55M
max_execution_time = 120
```

Keep these values at or above `upload.max_file_size` in `config.php`,
otherwise PHP itself will reject large uploads before this application
ever sees them.

## Configuration

All configuration lives in `config.php`. Every value is documented inline
in that file. The most important ones:

| Key | Purpose |
|---|---|
| `telegram.bot_token` | The bot token from BotFather. |
| `telegram.chat_id` | The single, fixed destination chat for every upload. |
| `upload.max_file_size` | Maximum accepted upload size, in bytes. Telegram bots can upload up to 50 MB directly — see the [Telegram Bot API docs](https://core.telegram.org/bots/api). |
| `upload.allowed_extensions` / `blocked_extensions` | Extension allow/block lists (block always wins). |
| `upload.allowed_mime_types` / `blocked_mime_types` | Same, matched against the file's real, server-detected MIME type — never the client-supplied one. |
| `app.mode` | `production` (default) hides internal error detail; `development` is more verbose. Never use `development` on a public host. |
| `logging.*` | Log directory, retention (age and total size), and optional gzip compression before deletion. |

## Using the Web UI

Open `index.php` in a browser. Drag files onto the drop zone (or click it
to pick files), and each file uploads independently with its own progress
bar and result card. A failure in one file never stops the others. Each
result card shows the Telegram file ID, unique file ID, message ID, chat
ID, upload method, and the full normalized JSON response, with one-click
copy buttons.

## Using the REST API

```
POST /api/index.php
Content-Type: multipart/form-data
Field: file
```

One file per request — if you have several files, send several requests
(this is exactly what the web UI's own JavaScript does).

```bash
curl -F "file=@photo.jpg" https://your-host.example/api/index.php
```

Success:

```json
{
    "success": true,
    "request_id": "b3f1c2b0-3b7a-4b7a-9b7a-3b7a4b7a9b7a",
    "data": {
        "file_id": "AgACAgIAAxk...",
        "file_unique_id": "AQADq...",
        "file_size": 182300,
        "message_id": 42,
        "chat_id": "123456789",
        "upload_method": "sendPhoto",
        "original_filename": "photo.jpg",
        "original_mime_type": "image/jpeg",
        "uploaded_at": "2026-07-14T12:34:56+00:00",
        "telegram_response": { "...": "the raw Telegram message object" }
    }
}
```

Failure:

```json
{
    "success": false,
    "request_id": "b3f1c2b0-3b7a-4b7a-9b7a-3b7a4b7a9b7a",
    "error": {
        "code": "file_too_large",
        "message": "The file exceeds the maximum allowed size of 50.00 MB."
    }
}
```

Every response — success or failure — carries the same `request_id`,
which also appears in the server logs for that request, making
troubleshooting straightforward.

## Architecture

The application is organized into four layers, with dependencies flowing
in one direction only:

```
Presentation → Application → Storage → Infrastructure
```

- **Presentation** (`src/Presentation/`) — HTTP request/response
  handling, the web controller, the API controller, and the HTML
  template. Contains no business logic and never calls Telegram directly.
- **Application** (`src/Application/`) — the actual upload workflow:
  validation, metadata extraction, upload-method selection, response
  normalization, and logging. This is the only place the workflow is
  implemented; the web UI and the REST API both funnel through it (in
  practice, the web UI's JavaScript simply calls the same
  `api/index.php` endpoint, so there is only one code path that ever
  touches Telegram).
- **Storage** (`src/Storage/`) — talks to the Telegram Bot API. Version 1
  ships one implementation, `TelegramStorage`, behind a `StorageInterface`
  the Application layer depends on instead of the concrete class. This is
  the one abstraction the project introduces deliberately: it lets a
  future storage provider (S3, Google Drive, ...) be added without
  touching the upload workflow.
- **Infrastructure** (`src/Infrastructure/`) — generic, reusable
  utilities with no business meaning: configuration loading, the file
  logger, the cURL-based HTTP client, JSON helpers, UUID generation, and
  MIME/extension inspection.

### Key design decisions

- **No dependency injection container.** The object graph (validator,
  storage driver, pipeline) is wired explicitly in `index.php` and
  `api/index.php`. For a project this size, an explicit constructor call
  is easier to read and debug than a container.
- **The web UI never handles uploads server-side.** `index.php` only
  renders the page; its JavaScript uploads through `api/index.php`. This
  guarantees, by construction, that there is exactly one upload code path
  — duplicating it in a second controller would risk the two drifting
  apart.
- **The REST API accepts one file per request.** Batch endpoints would
  need to invent a new response shape for partial batch failures. Instead,
  each file is its own independent request with its own request id,
  which is simpler and lets a failure in one file never affect another —
  a hard requirement in the spec.
- **Response normalization is a distinct step from Telegram communication.**
  `TelegramStorage` returns Telegram's raw `result` payload; the
  Application layer's `ResponseNormalizer` immediately converts it into
  one consistent shape. Telegram's raw, endpoint-specific payloads never
  leak past that single conversion point.
- **Log retention runs probabilistically, not via cron.** A small
  percentage of requests trigger a cleanup pass. This keeps the
  "configurable cleanup" requirement fully satisfied without requiring
  shared-hosting operators to set up a scheduled task.

## Security notes

- The bot token and chat id are server-side configuration only; clients
  can never choose an arbitrary destination chat.
- Uploaded files are never written anywhere but PHP's own temporary
  upload path, and that path is deleted immediately after the request
  finishes (success or failure).
- `config.php`, `src/`, and `logs/` are blocked from direct HTTP access.
- All uploaded filenames are stripped of directory components and control
  characters before use, in both API responses and log entries.
- In `production` mode, no stack trace, file path, or internal exception
  message is ever returned to a client — only a safe, generic message and
  a request id for cross-referencing server logs.

## Out of scope (v1)

By design, this version does not include authentication, user accounts, a
database, a download service, background workers/queues, or any storage
driver other than Telegram. See the project's design philosophy for why:
the goal of v1 is a small, dependable foundation, not a large feature set.
These may be revisited in future versions without changing v1's core
upload workflow.

## License

MIT — see [LICENSE](LICENSE).
