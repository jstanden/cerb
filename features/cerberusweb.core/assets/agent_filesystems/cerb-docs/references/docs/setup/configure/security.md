---
id: "docs-setup-configure-security"
title: "Security"
url: "https://cerb.ai/docs/setup/configure/security/"
summary: "This page describes Cerb's security configuration -- service tokens for authenticating anonymous access to privileged endpoints like /cron, /debug, and /update; session expiration policies; and (deprecated) IP allowlists. Service tokens replace the previous IP-based allowlist."
tags: ["docs"]
---
 

### Service Tokens

[Service tokens](/docs/records/types/service_token/) authenticate anonymous, privileged access to endpoints like `/cron`, `/debug`, and `/update` without requiring a worker session. Service tokens replace the [`AUTHORIZED_IPS_DEFAULTS`](/docs/config-file/#common-settings) IP allowlist and the removed `DEVELOPMENT_MODE_ALLOW_DEBUG` flag.

Tokens are passed either in an HTTP `Authorization: Bearer <token>` header or as an `_authorization` HTTP POST parameter – for instance, from a cronjob, monitoring tool, or deploy script. Each token can be restricted to specific endpoint **scopes** (e.g. `cron:*`, `debug:status`, `update`).

When viewing a protected endpoint in a browser, you'll be prompted to enter a token to continue.

A master service token can be configured in `framework.config.php` using `APP_SERVICE_TOKEN`. The master token's scope defaults to `*` (all endpoints) and can be restricted with `APP_SERVICE_TOKEN_SCOPE`. The master token is especially useful for `/update`, since worker logins are blocked until the update has finished.

Service tokens are managed from **Setup » Configure » Security**.

### Session Expiration

This section determines the lifespan of [session](/docs/setup/configure/sessions/) cookies. When a session expires, a worker will need to log in again from that particular device.

### Remote Administration (deprecated)

This legacy section configured the [`AUTHORIZED_IPS_DEFAULTS`](/docs/config-file/#common-settings) allowlist of IPs allowed to access `/cron`, `/debug`, and `/update` without a session. It has been replaced by [service tokens](/docs/records/types/service_token/), and the `DEVELOPMENT_MODE_ALLOW_DEBUG` configuration option has been removed.

