---
id: "docs-scripting-filters--xmlencode"
title: "Scripting Filter: xml_encode"
url: "https://cerb.ai/docs/scripting/filters/#xmlencode"
summary: "Build XML from an array"
tags: ["docs", "docs-scripting"]
---
## xml\_encode

Build XML from an array.

`|xml_encode(format)`

**Arguments:**

| Name | Notes |
| --- | --- |
| `format` | Optional. When `true`, the output is indented. Defaults to `false`. |

**Returns:** The constructed XML as a string, or an empty string on failure.

**The array must have exactly one top-level key.** It becomes the root element, and any sibling keys beside it are **silently dropped** -- no error, no warning.

```
{% set data = {'name':'Acme','city':'Portland'} %}
{{data|xml_encode}}
```

```
<name>Acme</name>
```

`city` is gone. Wrap the whole thing in a single root key instead:

```
{% set data = {'org':{'name':'Acme','city':'Portland'}} %}
{{data|xml_encode}}
```

```
<org><name>Acme</name><city>Portland</city></org>
```

String keys become tag names. Integer keys – the elements of a list – become `<item>`:

```
{% set data = {'tickets':[{'mask':'ABC-1'},{'mask':'ABC-2'}]} %}
{{data|xml_encode}}
```

```
<tickets><item><mask>ABC-1</mask></item><item><mask>ABC-2</mask></item></tickets>
```

### Hints

Keys beginning with `@` are **hints rather than content**. They're skipped when walking children, and they're only read on integer-keyed entries – on a string-keyed entry the key itself is already the tag name, and both hints are ignored.

`@tag` renames those `<item>` elements:

```
{% set data = {'tickets':[
  {'@tag':'ticket','mask':'ABC-1'},
  {'@tag':'ticket','mask':'ABC-2'}
]} %}
{{data|xml_encode}}
```

```
<tickets><ticket><mask>ABC-1</mask></ticket><ticket><mask>ABC-2</mask></ticket></tickets>
```

`@attributes` sets attributes on that element, and a second argument of `true` indents the output:

```
{% set data = {'tickets':[
  {'@tag':'ticket','@attributes':{'id':'1','status':'open'},'mask':'ABC-1'}
]} %}
{{data|xml_encode(true)}}
```

```
<tickets>
  <ticket id="1" status="open">
    <mask>ABC-1</mask>
  </ticket>
</tickets>
```

Only `@tag` and `@attributes` are read. Other `@` keys are ignored rather than raising an error. Scalar values become text content, with carriage returns stripped.

There is also an [**xml\_encode** function](/docs/scripting/functions/#xml_encode), and it is a different function doing the opposite job: it _serializes_ an existing XML node back to a string. Passing an array to the function returns `false`, and piping a node into this filter won't serialize it.

[\< Functions](/docs/scripting/functions/)

[Tests \>](/docs/scripting/tests/)

# References

1. Wikipedia: Hash-based message authentication code (HMAC) - https://en.wikipedia.org/wiki/Hash-based\_message\_authentication\_code&nbsp;[↩](#fnref:hmac)

2. Wikipedia: Markdown - https://en.wikipedia.org/wiki/Markdown&nbsp;[↩](#fnref:markdown)

3. Wikipedia: MD5 - https://en.wikipedia.org/wiki/MD5&nbsp;[↩](#fnref:md5)

4. Wikipedia: Regular Expression - https://en.wikipedia.org/wiki/Regular\_expression&nbsp;[↩](#fnref:regexp)

5. Wikipedia: SHA-1 - https://en.wikipedia.org/wiki/SHA-1&nbsp;[↩](#fnref:sha1)