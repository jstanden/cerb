---
id: "docs-scripting-filters--imageinfo"
title: "Scripting Filter: image_info"
url: "https://cerb.ai/docs/scripting/filters/#imageinfo"
summary: "Return information about an image from bytes or data URI"
tags: ["docs", "docs-scripting"]
---
## image\_info

Returns information about an image. The image may be provided as bytes or in data URI format.

`|image_info()`

```
{% set image_string %}
data:image/png;base64,iVBORw0KGgoAAAA....
{% endset %}
{{image_string|image_info|json_encode|json_pretty}}
```

```
{
    "width": 100,
    "height": 100,
    "channels": 3,
    "bits": 8,
    "type": "image/png"
}
```
