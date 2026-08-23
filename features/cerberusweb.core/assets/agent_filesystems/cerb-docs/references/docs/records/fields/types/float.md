---
id: "docs-records-fields-types-float"
title: "Float Record Fields"
url: "https://cerb.ai/docs/records/fields/types/float/"
summary: "This page provides information on float record fields in Cerb, which are used to store numbers with decimal precision. It explains how these floating point numbers are represented in JSON format within packages and how they can be utilized in PUT or POST requests through the Records API."
tags: ["docs"]
---
A **float** field contains a number with decimal precision.

The value is a floating point number.

### Packages

As JSON from [packages](/docs/packages/):

```
{
	"spam_score": 0.9999
}
```

### Records API

In [PUT](/docs/api/endpoints/records/#update) or [POST](/docs/api/endpoints/records/#create) requests from the [API](/docs/api/):

```
&amp;fields[spam_score]=0.9999
```

[\< Extension](/docs/records/fields/types/extension/)

[Image \>](/docs/records/fields/types/image/)

