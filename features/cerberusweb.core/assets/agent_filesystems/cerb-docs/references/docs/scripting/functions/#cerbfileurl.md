---
id: "docs-scripting-functions--cerbfileurl"
title: "Scripting Function: cerb_file_url"
url: "https://cerb.ai/docs/scripting/functions/#cerbfileurl"
summary: "Retrieve the download link for a given attachment ID"
tags: ["docs", "docs-scripting"]
---
## cerb\_file\_url

Retrieve the download link for a given attachment ID.

This automatically adapts to use within Cerb and community portals (e.g. SSL, proxies).

`cerb_file_url(id)`

```
{{cerb_file_url('1')}}
```

```
https://cerb.example/files/1/original_message.html
```
