---
id: "docs-records-fields-types-text"
title: "Text Record Fields"
url: "https://cerb.ai/docs/records/fields/types/text/"
summary: "This page provides guidance on handling text fields in Cerb, specifically focusing on how to format and encode text for use in JSON packages and API requests. It explains the use of control characters for multi-line text in JSON and the necessity of URL encoding text in API requests, including encoding new line characters."
tags: ["docs"]
---
A **text** field contains free-form text.

### Packages

As JSON from [packages](/docs/packages/):

```
{
	"subject": "I need some help with this software"
}
```

To enter multiple lines of text, use `\n` control characters.

```
{
	"subject": "Line 1\nLine 2\n"
}
```

### Records API

In [PUT](/docs/api/endpoints/records/#update) or [POST](/docs/api/endpoints/records/#create) requests from the [API](/docs/api/) the text should be URL encoded.

```
&amp;fields[subject]=I+need+help+with+this+software
```

To enter multiple lines of text, encode `\n` as `%0A`:

```
&amp;fields[subject]=Line+1%0ALine+2
```

[\< Object](/docs/records/fields/types/object/)

[Timestamp \>](/docs/records/fields/types/timestamp/)

