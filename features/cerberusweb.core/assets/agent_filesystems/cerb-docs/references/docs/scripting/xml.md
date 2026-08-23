---
id: "docs-scripting-xml"
title: "Scripting Reference: XML"
url: "https://cerb.ai/docs/scripting/xml/"
summary: "This page provides a scripting reference for handling XML in Cerb, detailing functions for XML decoding, XPath querying, namespace handling, and XML encoding. It includes examples of using the `xml_decode()` function to convert XML strings into objects, extracting values with `xml_xpath()` and `xml_xpath_ns()` for namespace-specific queries, and encoding objects back into XML with `xml_encode()`. The page also references external resources for further reading on XML and XPath."
tags: ["docs", "docs-scripting"]
---
XML[1](#fn:xml) is another popular format for serializing or exchanging structured data.

# XML Decoding

You can decode an XML string into an XML object with the [xml\_decode()](/docs/scripting/functions/#xml_decode) function.

Use the [xml\_xpath()](/docs/scripting/functions/#xml_xpath) function to extract values with XPath[2](#fn:xpath) queries.

```
{% set string_of_xml = 
"<response>
  <client_id>1</client_id>
  <invoice_id>123</invoice_id>
</response>"
-%}
{% set xml = xml_decode(string_of_xml) %}
{% set client_id = xml_xpath(xml, '//client_id')|first %}
{% set invoice_id = xml_xpath(xml, '//invoice_id')|first %}
Client ID: {{client_id}}
Invoice ID: {{invoice_id}}
```

```
Client ID: 1
Invoice ID: 123
```

# XML Namespaces

You can define an XML namespace with the [xml\_xpath\_ns()](/docs/scripting/functions/#xml_xpath_ns) function:

```
{% set string_of_xml = 
"<response xmlns=\"http://www.example.com/api/\">
  <client_id>1</client_id>
  <invoice_id>123</invoice_id>
</response>"
-%}
{% set xml = xml_decode(string_of_xml) %}
{% set xml = xml_xpath_ns(xml, 'ns', 'http://www.example.com/api/') %}
{% set client_id = xml_xpath(xml, '//ns:client_id')|first %}
{% set invoice_id = xml_xpath(xml, '//ns:invoice_id')|first %}
Client ID: {{client_id}}
Invoice ID: {{invoice_id}}
```

```
Client ID: 1
Invoice ID: 123
```

# XML Encoding

You can encode an object as XML with the [xml\_encode()](/docs/scripting/functions/#xml_encode) function:

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

[\< JSON](/docs/scripting/json/)

[Commands \>](/docs/scripting/commands/)

# References

1. Wikipedia: XML - https://en.wikipedia.org/wiki/XML&nbsp;[↩](#fnref:xml)

2. Wikipedia: XPath - https://en.wikipedia.org/wiki/XPath&nbsp;[↩](#fnref:xpath)

