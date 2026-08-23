---
id: "docs-scripting-functions--cerbplaceholderslist"
title: "Scripting Function: cerb_placeholders_list"
url: "https://cerb.ai/docs/scripting/functions/#cerbplaceholderslist"
summary: "Return an object with every placeholder in the current behavior"
tags: ["docs", "docs-scripting"]
---
## cerb\_placeholders\_list

Return an [object](/docs/scripting/arrays-objects/) with every placeholder in the current behavior.

`cerb_placeholders_list(extract, prefix)`

| **extract** | The key prefix to extract (e.g. `ticket_group_`) |
| **prefix** | The optional new prefix to add (e.g. `group_`) |

```
{{cerb_placeholders_list()|json_encode|json_pretty}}
```

```
{
  "worker__context": "cerberusweb.contexts.worker",
  "worker__loaded": true,
  "worker__label": "Kina Halpue",
  "worker__image_url": "https://cerb.example/avatars/worker/1?v=1512582324",
  "worker_at_mention_name": "Kina",
  "worker_calendar_id": 7,
  "worker_dob": null,
  "worker_id": 1,
  "worker_first_name": "Kina",
  "worker_full_name": "Kina Halpue",
  "worker_gender": "F",
  "worker_is_disabled": 0,
  "worker_is_superuser": 1,
  "worker_language": "en_US",
  "worker_last_name": "Halpue",
  "worker_location": "",
  "worker_mobile": "15555555555",
  "worker_phone": "",
  "worker_time_format": "D, d M Y h:i a",
  "worker_timezone": "America/Los_Angeles",
  "worker_title": "Customer Support",
  "worker_updated": 1512582324,
  "worker_record_url": "https://cerb.example/profiles/worker/1-Kina-Halpue",
  ...
}
```
