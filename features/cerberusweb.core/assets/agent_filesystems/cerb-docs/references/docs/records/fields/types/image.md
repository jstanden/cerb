---
id: "docs-records-fields-types-image"
title: "Image Record Fields"
url: "https://cerb.ai/docs/records/fields/types/image/"
summary: "This page provides information on handling image fields in Cerb, specifically focusing on Base64-encoded images. It details how images are represented in JSON packages and how they can be managed through the Records API using PUT or POST requests. Additionally, it explains the process for removing an image by setting its data to null."
tags: ["docs"]
---
An **image** field contains a Base64-encoded image.

### Packages

As JSON from [packages](/docs/packages/):

```
{
	"image": "data:image/png;base64,iVBORw0KGgo..."
}
```

### Records API

In [PUT](/docs/api/endpoints/records/#update) or [POST](/docs/api/endpoints/records/#create) requests from the [API](/docs/api/):

```
&amp;fields[image]=data:image/png;base64,iVBORw0KGgo...
```

### Remove

You can remove an image by setting it to `data:null`

[\< Float](/docs/records/fields/types/float/)

[Links \>](/docs/records/fields/types/links/)

