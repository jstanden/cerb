---
id: "docs-records-fields-types-context"
title: "Context Record Fields"
url: "https://cerb.ai/docs/records/fields/types/context/"
summary: "This page provides information on context record fields in Cerb, explaining that a context field contains a record type identified by an ID or URI. It includes examples of how context fields are used in JSON packages and in PUT or POST requests through the Records API, specifically showing how to set a context field to a record type like 'ticket.'"
tags: ["docs"]
---
A **context** field contains a [record type](/docs/records/types/).

The value is the `identifier` (ID) or `uri` (alias) of a record type.

### Packages

As JSON from [packages](/docs/packages/):

```
{
	"context": "ticket"
}
```

### Records API

In [PUT](/docs/api/endpoints/records/#update) or [POST](/docs/api/endpoints/records/#create) requests from the [API](/docs/api/):

```
&amp;fields[context]=ticket
```

[\< Boolean](/docs/records/fields/types/boolean/)

[Extension \>](/docs/records/fields/types/extension/)

