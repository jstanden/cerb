---
id: "docs-scripting-functions--dnshostbyip"
title: "Scripting Function: dns_host_by_ip"
url: "https://cerb.ai/docs/scripting/functions/#dnshostbyip"
summary: "Resolve a hostname from an IP address"
tags: ["docs", "docs-scripting"]
---
## dns\_host\_by\_ip

Resolve a hostname from an IP. If a name can't be resolved for a valid IP, the IP is returned. If an invalid IP is provided, the result is an empty string.

`dns_host_by_ip(ip)`

- **ip**: The IP address to reverse lookup a hostname.

```
{{dns_host_by_ip('54.148.127.4')}}
```

```
cerb.email
```
