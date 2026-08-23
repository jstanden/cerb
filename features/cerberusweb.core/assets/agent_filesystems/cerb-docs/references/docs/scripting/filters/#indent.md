---
id: "docs-scripting-filters--indent"
title: "Scripting Filter: indent"
url: "https://cerb.ai/docs/scripting/filters/#indent"
summary: "Prefix the start of each line with a given marker"
tags: ["docs", "docs-scripting"]
---
## indent

Prefix the start of each line with a given marker in a block of text.

`|indent(marker, start_line)`

| **marker** | The prefix to add to the beginning of each line. |
| **start\_line** | The line number to start prefixing from (0-based). |

```
{% set text = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris eget diam 
eu orci hendrerit elementum. Suspendisse egestas, dolor at efficitur sollicitudin, magna eros 
scelerisque risus, at tincidunt massa augue a eros. Nullam scelerisque luctus suscipit. Sed 
dui metus, rhoncus sed diam non, pretium maximus augue. Phasellus feugiat justo mi, in 
tristique quam euismod pellentesque. Curabitur ut libero sagittis sem semper ultrices. Nullam 
et mi id arcu vulputate fringilla ut quis nibh. Fusce lobortis magna eu quam porta scelerisque.
Suspendisse maximus fringilla tellus, a pellentesque sem tincidunt sit amet." -%}

{{text|indent('> ')}}
```

```
> Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris eget diam 
> eu orci hendrerit elementum. Suspendisse egestas, dolor at efficitur sollicitudin, magna eros 
> scelerisque risus, at tincidunt massa augue a eros. Nullam scelerisque luctus suscipit. Sed 
> dui metus, rhoncus sed diam non, pretium maximus augue. Phasellus feugiat justo mi, in 
> tristique quam euismod pellentesque. Curabitur ut libero sagittis sem semper ultrices. Nullam 
> et mi id arcu vulputate fringilla ut quis nibh. Fusce lobortis magna eu quam porta scelerisque.
> Suspendisse maximus fringilla tellus, a pellentesque sem tincidunt sit amet.
```
