---
id: "docs-records-fields-types-links"
title: "Links Record Fields"
url: "https://cerb.ai/docs/records/fields/types/links/"
summary: "This page provides information on the 'links' field in Cerb, which is used to store a list of `context:id` pairs that reference other records. It explains how these links are represented as an array of `type:id` pairs and provides examples of how they are formatted in JSON for packages and in PUT or POST requests through the Records API."
tags: ["docs"]
---
A **links** field contains a list of `context:id` pairs pointing to other records.

The value is an array of `type:id` pairs.

### Packages

As JSON from [packages](/docs/packages/):

```
{
	"links": [
		"ticket:123",
		"org:456"
	]
}
```

### Records API

In [PUT](/docs/api/endpoints/records/#update) or [POST](/docs/api/endpoints/records/#create) requests from the [API](/docs/api/):

```
&amp;fields[links][]=ticket:123
&amp;fields[links][]=org:456
```

[\< Image](/docs/records/fields/types/image/)

[Number \>](/docs/records/fields/types/number/)

