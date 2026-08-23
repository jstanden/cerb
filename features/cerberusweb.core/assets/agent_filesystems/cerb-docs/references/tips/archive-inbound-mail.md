---
id: "tips-archive-inbound-mail"
title: "Archive a copy of all inbound mail"
url: "https://cerb.ai/tips/archive-inbound-mail/"
summary: "This page provides a tip for archiving all inbound mail in Cerb before it is processed. By adding a specific line of code to the `framework.config.php` file, users can ensure that all incoming messages are archived in the filesystem under a structured directory path based on the year, month, and day. The archived files are not further processed by Cerb, allowing users the flexibility to move or delete them as necessary."
tags: ["tips"]
---
If you need to archive a copy of all inbound messages before Cerb processes them, you can add this line to the top of your `framework.config.php` file:

```
define('DEVELOPMENT_ARCHIVE_PARSER_MSGSOURCE', true);
```

Messages are archived in the filesystem under `storage/mail/archive/<year>/<month>/<day>`.

Cerb doesn't do anything with these files after they're written, so you can move or delete them as needed.

