---
id: "docs-scripting-functions--xmlencode"
title: "Scripting Function: xml_encode"
url: "https://cerb.ai/docs/scripting/functions/#xmlencode"
summary: "Encode an object as XML"
tags: ["docs", "docs-scripting"]
---
## xml\_encode

You can encode an object as XML with the **xml\_encode** function:

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
