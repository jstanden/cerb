---
id: "docs-scripting-json"
title: "Scripting Reference: JSON"
url: "https://cerb.ai/docs/scripting/json/"
summary: "This page provides a scripting reference for handling JSON in Cerb, covering key operations such as decoding, modifying, encoding, and prettifying JSON data. It includes examples of using functions like `json_decode` to convert JSON strings into objects, `dict_set` to modify JSON objects, and `json_encode` to serialize data back into JSON format. Additionally, it demonstrates how to format JSON data for readability using the `json_pretty` filter. The page serves as a practical guide for working with JSON in Cerb's scripting environment."
tags: ["docs", "docs-scripting"]
---
JSON[1](#fn:json) is a popular format for serializing or exchanging human-readable data using key/value pairs.

# JSON Decoding

You can decode a JSON-encoded string with the [json\_decode()](/docs/scripting/functions/#json_decode) function:

```
{% set json_string = "{\"name\":\"Joe Customer\",\"order_id\":12345}" %}
{% set json = json_decode(json_string) %}
Customer: {{json.name}}
Order #: {{json.order_id}}
```

```
Customer: Joe Customer
Order #: 12345
```

This returns an [object](/docs/scripting/arrays-objects/#objects).

# JSON Modification

You can construct or modify a JSON object using the [dict\_set()](/docs/scripting/functions/#dict_set) function:

```
{% set json = {'name': 'Joe Customer', 'order_id': 12345} %}
{% set json = dict_set(json, 'order_id', 54321) %}
{% set json = dict_set(json, 'status.text', 'shipped') %}
{% set json = dict_set(json, 'status.tracking_id', 'Z1F238') %}
Customer: {{json.name}}
Order #: {{json.order_id}}
Status: {{json.status.text}}
Tracking #: {{json.status.tracking_id}}
```

```
Customer: Joe Customer
Order #: 54321
Status: shipped
Tracking #: Z1F238
```

# JSON Encoding

You can encode any variable as a JSON string with the [json\_encode](/docs/scripting/filters/#json_encode) filter:

```
{% set json = {'name': 'Joe Customer'} %}
{% set json = dict_set(json, 'order_id', 54321) %}
{% set json = dict_set(json, 'status.text', 'shipped') %}
{% set json = dict_set(json, 'status.tracking_id', 'Z1F238') %}
{{json|json_encode}}
```

```
{"name":"Joe Customer","order_id":54321,"status":{"text":"shipped","tracking_id":"Z1F238"}}
```

# JSON Prettification

You can _"prettify"_ a JSON string with the [json\_pretty](/docs/scripting/filters/#json_pretty) filter:

```
{% set json = {'name': 'Joe Customer'} %}
{% set json = dict_set(json, 'order_id', 54321) %}
{% set json = dict_set(json, 'status.text', 'shipped') %}
{% set json = dict_set(json, 'status.tracking_id', 'Z1F238') %}
{{json|json_encode|json_pretty}}
```

```
{
  "name": "Joe Customer",
  "order_id": 54321,
  "status": {
    "text": "shipped",
    "tracking_id": "Z1F238"
  }
}
```

[\< Regular Expressions](/docs/scripting/regex/)

[XML \>](/docs/scripting/xml/)

# References

1. Wikipedia: JSON - https://en.wikipedia.org/wiki/JSON&nbsp;[↩](#fnref:json)

