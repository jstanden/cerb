---
id: "docs-scripting-functions--xmldecode"
title: "Scripting Function: xml_decode"
url: "https://cerb.ai/docs/scripting/functions/#xmldecode"
summary: "Decode an XML string into an XML object"
tags: ["docs", "docs-scripting"]
---
## xml\_decode

You can decode an XML[1](#fn:xml) string into an XML object with the **xml\_decode** function.

Use the [xml\_xpath](#xml_xpath) function to extract values with XPath[2](#fn:xpath) queries.

`xml_decode(xml_string,namespaces,mode)`

- **xml\_string**: The string of XML to convert into an object.
- **namespaces**: An optional array of namespaces.
- **mode**: Use `html` to convert an HTML DOM into an XML document.

```
{% set string_of_xml = 
"<response>
  <client_id>1</client_id>
  <invoice_id>123</invoice_id>
</response>"
-%}
{% set xml = xml_decode(string_of_xml) %}
{{xml_encode(xml)}}
```

```
<?xml version="1.0"?>
<response>
  <client_id>1</client_id>
  <invoice_id>123</invoice_id>
</response>
```
