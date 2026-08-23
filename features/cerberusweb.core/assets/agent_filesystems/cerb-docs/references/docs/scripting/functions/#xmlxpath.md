---
id: "docs-scripting-functions--xmlxpath"
title: "Scripting Function: xml_xpath"
url: "https://cerb.ai/docs/scripting/functions/#xmlxpath"
summary: "Extract values from XML using XPath queries"
tags: ["docs", "docs-scripting"]
---
## xml\_xpath

Use the **xml\_xpath** function to extract values with XPath[2](#fn:xpath) queries:

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
