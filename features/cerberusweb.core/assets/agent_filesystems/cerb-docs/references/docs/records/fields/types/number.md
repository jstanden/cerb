---
id: "docs-records-fields-types-number"
title: "Number Record Fields"
url: "https://cerb.ai/docs/records/fields/types/number/"
summary: "This page provides information on number record fields in Cerb, which are used to store integer (whole number) values. It includes examples of how these fields are represented in JSON format for packages and how they can be utilized in PUT or POST requests through the Records API. The page serves as a guide for implementing and managing number fields within Cerb's system."
tags: ["docs"]
---
A **number** field contains an _integer_ (whole number) value.

The value is an integer.

### Packages

As JSON from [packages](/docs/packages/):

```
{
	"importance": 50
}
```

### Records API

In [PUT](/docs/api/endpoints/records/#update) or [POST](/docs/api/endpoints/records/#create) requests from the [API](/docs/api/):

```
&amp;fields[importance]=50
```

[\< Links](/docs/records/fields/types/links/)

[Object \>](/docs/records/fields/types/object/)

