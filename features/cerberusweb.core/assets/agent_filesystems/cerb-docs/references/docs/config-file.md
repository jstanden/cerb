---
id: "docs-config-file"
title: "Configuration File"
url: "https://cerb.ai/docs/config-file/"
summary: "This page is a guide to the Cerb config file, including settings and their default values. The config file has multiple variables that can be set to meet specific needs, with some being required for setup while others are optional. The list includes both required and optional variables, providing descriptions of what each does, such as database settings, language encoding, and development mode options."
tags: ["docs"]
---
When self-hosting, you have access to low-level settings to fine-tune the platform on your servers.

The defaults come from the `/libs/devblocks/framework.default.php` file.

To override a setting, add a line to the `framework.config.php` file like:

```
const APP_SETTING_STRING = "setting value";
const APP_SETTING_NUMBER = 1234;
const APP_SETTING_BOOLEAN = false;
```

# Required Settings

The following variables are required to set up Cerb.

| Setting | Description |
| --- | --- |
| `APP_DB_DATABASE` | MySQL database name |
| `APP_DB_HOST` | MySQL hostname (e.g. `database.example.com`) |
| `APP_DB_PASS` | MySQL password |
| `APP_DB_USER` | MySQL user name |

# Common Settings

| Setting | Default | Description |
| --- | --- | --- |
| `APP_DB_READER_HOST` | (disabled) | Database reader endpoint for read/write splitting |
| `APP_HOSTNAME` | &nbsp; | Override server hostname (e.g. `support.example.com`) |
| `APP_SECURITY_ALLOW_ADMIN_SESSION_TOKEN` | true | Allow admin sessions to authenticate `/cron` and `/update`. When `false`, those endpoints always require a [service token](/docs/records/types/service_token/). |
| `APP_SERVICE_TOKEN` | &nbsp; | Master [service token](/docs/records/types/service_token/) for `/cron`, `/debug`, `/update` |
| `APP_SERVICE_TOKEN_SCOPE` | `cron update` | Space-separated scopes restricting the master service token (e.g. `cron:* update`) |
| `APP_STORAGE_PATH` | /storage | Override storage directory (e.g. `/mnt/storage`) |
| `AUTHORIZED_IPS_DEFAULTS` | &nbsp; | Comma-separated list of IPv4 prefixes that can access `/cron` and `/update` without a session. **Deprecated in [12.0](/releases/12.0/)** – use [service tokens](/docs/records/types/service_token/) instead. |
| `DEVBLOCKS_REWRITE` | `file_exists('.htaccess')` | Rewrite `/index.php/` in URLs to `/` |
| `DEVBLOCKS_HTTP_PROXY` | (disabled) | SOCKS proxy server (e.g. `http://user:pass@localhost:8888`) |
| `DEVELOPMENT_MODE` | false | Enable developer mode and disable template caching |

# Optional Settings

| Setting | Default | Description |
| --- | --- | --- |
| `APP_DB_OPT_CONNECTION_RECONNECTS` | 5 | The number of times to retry a failed database connection |
| `APP_DB_OPT_CONNECTION_RECONNECTS_WAIT_MS` | 2000 | The number of milliseconds to wait between retry attempts |
| `APP_DB_OPT_MASTER_CONNECT_TIMEOUT_SECS` | 3 | Timeout in seconds on database writer connections |
| `APP_DB_OPT_MASTER_READ_TIMEOUT_SECS` | 30 | Timeout in seconds on database writer queries |
| `APP_DB_OPT_READER_CONNECT_TIMEOUT_SECS` | 3 | Timeout in seconds on database reader connections |
| `APP_DB_OPT_READER_READ_TIMEOUT_SECS` | 30 | Timeout in seconds on database reader queries |
| `APP_DB_OPT_READ_MASTER_AFTER_WRITE` | 0 | When using write/read splitting, read from writer after writes |
| `APP_DB_PCONNECT` | false | Enable persistent database connections |
| `APP_DB_PORT` | 3306 | MySQL database port (if not socket or TCP `:3306`) |
| `APP_DB_ENGINE` | InnoDB | MySQL database engine |
| `APP_DB_READER_PASS` | APP\_DB\_PASS | Read-only MySQL password (if different) |
| `APP_DB_READER_PORT` | APP\_DB\_PORT | Read-only MySQL port (if different) |
| `APP_DB_READER_USER` | APP\_DB\_USER | Read-only MySQL user (if different) |
| `APP_DEFAULT_CONTROLLER` | core.controller.page | Default controller for un-routed requests |
| `APP_OPT_AUTOCOMPLETE_TICKET_QUERY` | &nbsp; | Additional filters for ticket autocompletion (e.g. `sort:-created`) |
| `APP_OPT_DEPRECATED_PROFILE_QUICK_SEARCH` | false | Display a legacy search query bar on ticket profiles |
| `APP_OPT_GROUP_BEHAVIOR_TRIGGERS` | false | Automatically trigger legacy group behaviors (without automation event binds) |
| `APP_OPT_IIS_LEGACY_REWRITE` | false | Legacy IIS rewrite support |
| `APP_OPT_PARSER_ATTACHMENT_DUPE_WITH_FILENAME` | false | Include filename when detecting duplicate file attachments |
| `APP_OPT_SQL_SUBQUERY_TO_IDS` | false | Optimize WHERE subqueries as IN(ids) |
| `APP_OPT_SQL_SUBQUERY_TO_IDS_LIMIT` | 500 | Upper threshold to convert WHERE subqueries to ID |
| `APP_PATH` | `dirname(__FILE__)` | Absolute home path of Cerb |
| `APP_SECURITY_CSP_DEFAULT_SRC` | &nbsp; | Content Security Policy `default-src` (space-delimited URLs) |
| `APP_SECURITY_CSP_FRAME_SRC` | &nbsp; | Content Security Policy `frame-src` |
| `APP_SECURITY_CSP_IMG_SRC` | &nbsp; | Content Security Policy `img-src` (e.g. `https://syndication.twitter.com`) |
| `APP_SECURITY_CSP_MEDIA_SRC` | &nbsp; | Content Security Policy `media-src` |
| `APP_SECURITY_CSP_OBJECT_SRC` | &nbsp; | Content Security Policy `object-src` |
| `APP_SECURITY_CSP_SCRIPT_SRC` | &nbsp; | Content Security Policy `script-src` |
| `APP_SECURITY_CSP_STYLE_SRC` | &nbsp; | Content Security Policy `style-src` |
| `APP_SECURITY_FIREWALL_ALLOWLIST` | (disabled) | Enable firewall and only allow comma-separated IPv4 or subnets |
| `APP_SECURITY_FRAMEOPTIONS` | SAME ORIGIN | `X-Frame-Options` header: `none`, `deny` |
| `APP_SESSION_NAME` | Devblocks | Session cookie name |
| `APP_SMARTY_COMPILE_PATH` | /templates\_c | Path for template cache |
| `APP_SMARTY_COMPILE_PATH_MULTI_TENANT` | false | Avoid template cache clearing in multi-tenant environments |
| `APP_SMARTY_COMPILE_USE_SUBDIRS` | false | Cache template files in a subdirectory structure |
| `APP_SMARTY_SANDBOX_COMPILE_PATH` | /templates\_c | Path for template sandbox cache |
| `APP_SMARTY_SANDBOX_COMPILE_PATH_MULTI_TENANT` | false | Avoid template cache clearing in multi-tenant environments |
| `APP_TEMP_PATH` | /tmp | Path for temporary files |
| `DB_CHARSET_CODE` | utf-8 | Database connection default character set |
| `DEVBLOCKS_CACHE_ENGINE` | devblocks.cache.engine.disk | Override the default cache extension |
| `DEVBLOCKS_CACHE_ENGINE_OPTIONS` | &nbsp; | Options for the default cache extension |
| `DEVBLOCKS_CACHE_ENGINE_PREVENT_CHANGE` | false | Hide cache config in Setup |
| `DEVBLOCKS_LANGUAGE` | en | Default language |
| `DEVBLOCKS_PATH` | /libs/devblocks/ | The relative path to Devblocks |
| `DEVBLOCKS_STORAGE_ENGINE_PREVENT_CHANGE` | false | Hide storage config in Setup |
| `DEVELOPMENT_ARCHIVE_PARSER_MSGSOURCE` | false | Store a copy of all pre-parsed email in `./storage/archive/` |
| `DEVELOPMENT_MODE_ALLOW_CSRF` | false | Disable CSRF protections |
| `DEVELOPMENT_MODE_QUERIES` | false | Display executed database queries per request |
| `DEVELOPMENT_MODE_SECURITY_SCAN` | false | Suppress excessive warnings during a vulnerability scan |
| `LANG_CHARSET_CODE` | utf8 | Browser character set |

