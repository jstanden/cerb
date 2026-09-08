---
id: "docs-scripting-functions--xmlencode"
title: "Scripting Function: xml_encode"
url: "https://cerb.ai/docs/scripting/functions/#xmlencode"
summary: "Encode an object as XML"
tags: ["docs", "docs-scripting"]
---
## xml\_encode

Serialize an existing XML node back to a string.

The argument must be a `SimpleXMLElement`, usually from [xml\_decode](#xml_decode) or [xml\_xpath](#xml_xpath); anything else returns `false`.

There is also an [**xml\_encode** filter](/docs/scripting/filters/#xml_encode), and it is a different function that does the opposite job: it _builds_ XML from an array. Piping a node into the filter won't serialize it, and passing an array to this function returns `false`.

```
{% set string_of_xml = 
"<response xmlns=\"http://www.example.com/api/\">
  <client_id>1</client_id>
  <invoice_id>123</invoice_id>
</response>"
-%}
{% set xml = xml_decode(string_of_xml) %}
{{xml_encode(xml.client_id)}}
```

```
<client_id>1</client_id>
```
