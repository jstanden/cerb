---
id: "guides-scaling-database-read-write-splitting"
title: "Database read/write splitting"
url: "https://cerb.ai/guides/scaling/database-read-write-splitting/"
summary: "This guide explains how to configure Cerb to split database traffic between a primary (writer) and one or more read replicas. It covers the required constants in framework.config.php, connection tuning options, post-write read behavior for avoiding replication lag issues, legacy alias support, and troubleshooting tips."
tags: ["guides"]
---
- [Overview](#overview)
- [Prerequisites](#prerequisites)
- [Configuration](#configuration)
- [Tuning connection behavior](#tuning-connection-behavior)
- [Post-write read behavior](#post-write-read-behavior)
- [Troubleshooting](#troubleshooting)

# Overview

By default, Cerb directs all database queries – reads and writes – to a single primary (writer) server. As traffic grows, you can offload read-heavy queries to one or more MySQL/MariaDB replicas by enabling read/write splitting in Cerb's configuration.

When a reader connection is configured:

- **Write** queries (`INSERT`, `UPDATE`, `DELETE`, DDL) always go to the primary.
- **Read** queries (`SELECT`) are routed to the replica, reducing load on the primary.

This is especially useful for deployments where reporting, search, and list views generate significant query volume.

# Prerequisites

- A working Cerb installation with a MySQL or MariaDB primary database.
- At least one replica already configured and replicating from the primary. Setting up MySQL/MariaDB replication is outside the scope of this guide; refer to your database vendor's documentation.
- Shell access to your Cerb server to edit `framework.config.php`.

# Configuration

Reader connection settings are defined as PHP constants in `framework.config.php`, located in the root of your Cerb installation (the same file where `APP_DB_HOST`, `APP_DB_USER`, etc. are set).

In most setups, only the host needs to be set – the replica uses the same credentials as the primary:

```
// Reader (replica) connection
define('APP_DB_READER_HOST', 'replica.db.example.com');
```

If your replica uses a different port, username, or password, those can be overridden as well:

```
define('APP_DB_READER_PORT', '3306');
define('APP_DB_READER_USER', 'cerb_reader');
define('APP_DB_READER_PASS', 'secret');
```

| Constant | Default | Description |
| --- | --- | --- |
| `APP_DB_READER_HOST` | &nbsp; | Hostname or IP of the replica. |
| `APP_DB_READER_PORT` | `(primary)` | Port for the replica connection. |
| `APP_DB_READER_USER` | `(primary)` | MySQL username for the replica. |
| `APP_DB_READER_PASS` | `(primary)` | MySQL password for the replica. |

When `APP_DB_READER_HOST` is empty, all queries continue to use the primary connection.

# Tuning connection behavior

Several optional constants control timeouts and reconnect behavior for both connections:

```
// Connection tuning
define('APP_DB_OPT_MASTER_CONNECT_TIMEOUT_SECS', 3);
define('APP_DB_OPT_MASTER_READ_TIMEOUT_SECS', 30);
define('APP_DB_OPT_READER_CONNECT_TIMEOUT_SECS', 3);
define('APP_DB_OPT_READER_READ_TIMEOUT_SECS', 30);
define('APP_DB_OPT_CONNECTION_RECONNECTS', 5);
define('APP_DB_OPT_CONNECTION_RECONNECTS_WAIT_MS', 2000);
```

| Constant | Default | Description |
| --- | --- | --- |
| `APP_DB_OPT_MASTER_CONNECT_TIMEOUT_SECS` | `3` | Seconds to wait when opening the primary connection. |
| `APP_DB_OPT_MASTER_READ_TIMEOUT_SECS` | `30` | Seconds to wait for a query response from the primary. |
| `APP_DB_OPT_READER_CONNECT_TIMEOUT_SECS` | `3` | Seconds to wait when opening the replica connection. |
| `APP_DB_OPT_READER_READ_TIMEOUT_SECS` | `30` | Seconds to wait for a query response from the replica. |
| `APP_DB_OPT_CONNECTION_RECONNECTS` | `5` | Number of reconnect attempts before giving up on a failed connection. |
| `APP_DB_OPT_CONNECTION_RECONNECTS_WAIT_MS` | `2000` | Milliseconds to wait between reconnect attempts. |

# Post-write read behavior

MySQL/MariaDB replication is asynchronous. After a write to the primary, the replica may lag slightly before reflecting the change. This can cause reads that immediately follow a write to return stale data.

To avoid this, enable the `APP_DB_OPT_READ_MASTER_AFTER_WRITE` option:

```
define('APP_DB_OPT_READ_MASTER_AFTER_WRITE', 1);
```

When enabled, Cerb automatically routes reads to the primary for the remainder of the request after any write occurs. This ensures that a worker saving a record and then viewing it sees consistent data, at the cost of slightly higher primary read load.

Disable this option only if your replica lag is consistently near zero and you have validated that stale reads are not a concern for your workflows.

# Troubleshooting

**Reads are not going to the replica**

Verify that `APP_DB_READER_HOST` is defined and non-empty. Check the PHP error log for connection failures – if Cerb cannot reach the replica, it may silently fall back to the primary.

**Connection timeout errors**

Increase `APP_DB_OPT_READER_CONNECT_TIMEOUT_SECS` if the replica is on a high-latency network link. Check that your firewall allows connections from the Cerb server to the replica on the configured port.

**Stale data after writes**

Enable `APP_DB_OPT_READ_MASTER_AFTER_WRITE` (see above). Also check your replica's replication lag with:

```
mysql -e "SHOW REPLICA STATUS\G" | grep Seconds_Behind_Source
```

**Frequent reconnect errors**

Tune `APP_DB_OPT_CONNECTION_RECONNECTS` and `APP_DB_OPT_CONNECTION_RECONNECTS_WAIT_MS` to give transient network issues more time to recover. Investigate network stability between Cerb and the replica if reconnects are common.

