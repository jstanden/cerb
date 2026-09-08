---
id: "docs-scripting-functions--xmltag"
title: "Scripting Function: xml_tag"
url: "https://cerb.ai/docs/scripting/functions/#xmltag"
summary: "Return an XML node's element name"
tags: ["docs", "docs-scripting"]
---
## xml\_tag

Return an XML node's element name.

`xml_tag(xml_node)`

**Arguments:**

| Name | Notes |
| --- | --- |
| `xml_node` | A single XML node, usually from [xml\_xpath](#xml_xpath) |

**Returns:** The element's tag name. Returns `false` when `xml_node` isn't an XML node.

Despite the name, this inspects a node rather than building one. It belongs with [xml\_attr](#xml_attr) and [xml\_attrs](#xml_attrs) as the node-inspection family used after [xml\_decode](#xml_decode).
