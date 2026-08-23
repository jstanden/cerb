---
id: "docs-scripting-functions--dnsgetrecord"
title: "Scripting Function: dns_get_record"
url: "https://cerb.ai/docs/scripting/functions/#dnsgetrecord"
summary: "Resolve DNS records by hostname and type"
tags: ["docs", "docs-scripting"]
---
## dns\_get\_record

Resolve DNS records by hostname and type. This enables workflows like verifying domain ownership via TXT records, validating SPF/DKIM, verifying MX servers, etc.

`dns_get_record(hostname,type)`

- **hostname**: The lookup hostname.
- **type**: The record type (`a`, `aaaa`, `caa`, `cname`, `mx`, `ns`, `ptr`, `soa`, `srv`, `txt`)

```
{{dns_get_record('cerb.ai','a')|json_encode|json_pretty}}
```

```
[
    {
        "host": "cerb.ai",
        "class": "IN",
        "ttl": 77,
        "type": "A",
        "ip": "54.192.81.51"
    },
    {
        "host": "cerb.ai",
        "class": "IN",
        "ttl": 77,
        "type": "A",
        "ip": "54.192.81.69"
    }
]
```
