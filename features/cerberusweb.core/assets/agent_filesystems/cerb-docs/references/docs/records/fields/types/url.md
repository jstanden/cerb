---
id: "docs-records-fields-types-url"
title: "URL Record Fields"
url: "https://cerb.ai/docs/records/fields/types/url/"
summary: "This page provides information on how to format and use URL fields within Cerb, detailing the structure of a URL and how it should be represented in JSON packages and API requests. It includes examples of encoding URLs for PUT or POST requests in the Records API, ensuring proper handling of hyperlinks in the system."
tags: ["docs"]
---
A **URL** field contains a hyperlink to a web page in the format:

```
<protocol>://<host:port>/<path-to-resource>
```

### Packages

As JSON from [packages](/docs/packages/):

```
{
	"website": "https://cerb.ai/docs/"
}
```

### Records API

In [PUT](/docs/api/endpoints/records/#update) or [POST](/docs/api/endpoints/records/#create) requests from the [API](/docs/api/), the URL should be encoded:

```
&amp;fields[website]=https%3A%2F%2Fcerb.ai%2Fdocs%2F
```

[\< Timestamp](/docs/records/fields/types/timestamp/)

