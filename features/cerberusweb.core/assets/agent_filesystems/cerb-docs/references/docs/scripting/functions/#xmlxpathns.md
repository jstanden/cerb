---
id: "docs-scripting-functions--xmlxpathns"
title: "Scripting Function: xml_xpath_ns"
url: "https://cerb.ai/docs/scripting/functions/#xmlxpathns"
summary: "Define an XML namespace for XPath queries"
tags: ["docs", "docs-scripting"]
---
## xml\_xpath\_ns

You can define an XML namespace with the **xml\_xpath\_ns** function:

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
