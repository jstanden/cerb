---
id: "docs-records-fields-types-object"
title: "Object Record Fields"
url: "https://cerb.ai/docs/records/fields/types/object/"
summary: "This page provides information on object record fields in Cerb, which consist of collections of keys and their associated values. It explains how these fields can be represented in JSON format within packages and how they can be utilized in PUT or POST requests through the Records API. The examples illustrate how to structure data for fields such as 'color' and 'quantity' using both JSON and API request formats."
tags: ["docs"]
---
An **object** field contains a _collection_ of **keys** and their associated **values**.

### Packages

As JSON from [packages](/docs/packages/):

```
{
	"params": {
		"color": "red",
		"quantity": 6
	}
}
```

### Records API

In [PUT](/docs/api/endpoints/records/#update) or [POST](/docs/api/endpoints/records/#create) requests from the [API](/docs/api/):

```
&amp;fields[params][color]=red
&amp;fields[params][quantity]=6
```

[\< Number](/docs/records/fields/types/number/)

[Text \>](/docs/records/fields/types/text/)

