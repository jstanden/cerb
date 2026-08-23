---
id: "docs-records-fields-types-timestamp"
title: "Timestamp Record Fields"
url: "https://cerb.ai/docs/records/fields/types/timestamp/"
summary: "This page provides information on timestamp record fields in Cerb, detailing how Unix timestamps are represented as 32-bit integers indicating seconds since January 1, 1970. It explains how timestamps can be expressed in JSON packages using relative or absolute date formats, as well as Unix timestamps in seconds. Additionally, it covers how to use these timestamp fields in PUT or POST requests through the Records API."
tags: ["docs"]
---
A **timestamp** field contains a _Unix timestamp_ as a 32-bit integer, representing the number of elapsed seconds since January 1, 1970 00:00:00 GMT.

The value is text describing an absolute or relative date.

### Packages

As JSON from [packages](/docs/packages/):

#### Relative dates

```
{
	"created": "-1 week 8am"
}
```

#### Absolute dates

```
{
	"updated": "Jan 1 2019 13:00:00 +0000"
}
```

#### Unix timestamps (as seconds)

```
{
	"created": 1550080259
}
```

### Records API

In [PUT](/docs/api/endpoints/records/#update) or [POST](/docs/api/endpoints/records/#create) requests from the [API](/docs/api/):

```
&amp;fields[timestamp]=1550080259
```

[\< Text](/docs/records/fields/types/text/)

[URL \>](/docs/records/fields/types/url/)

