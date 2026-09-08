---
id: "docs-kata"
title: "KATA"
url: "https://cerb.ai/docs/kata/"
summary: "This page provides a comprehensive overview of KATA, a human-friendly format used in Cerb for modeling structured data. It explains how KATA is designed to avoid common pitfalls associated with YAML, such as automatic data type detection and security issues with local objects. The page details KATA's syntax, including indentation, key names, values, and annotations, and how these elements are used to build a tree of key/value relationships. It also covers the use of references to manage complex data structures and provides an annotation reference for various data types and operations, such as base64, bool, csv, date, int, json, and more. The page emphasizes KATA's flexibility and security, highlighting its ability to handle text blocks, comments, and placeholders without requiring escaping or executing external code."
tags: ["docs"]
---
**KATA** (_"Key Annotated Tree of Attributes"_) is a human-friendly format for modeling structured data that is used throughout Cerb to describe configurations, customizations, sheets, and automations.

https://www.youtube.com/embed/3dNqWrR-zek

KATA was inspired by YAML[1](#fn:yaml) but avoids many of its pitfalls[2](#fn:no-yaml).

| Pitfall | YAML | KATA |
| --- | --- | --- |
| Data types | YAML attempts to automatically detect data types, which requires the use of double quotes on keys and values to ensure they remain text. For example, `no` becomes `FALSE` and currency/versions (`1.50`) become decimal (`1.5`). | KATA treats all keys and values as text without any type coercion. You never need to use quotes. You can optionally annotate a key to convert its value to a specific type. |
| Local objects | YAML allows language-specific tags. In some implementations (PHP, Perl, Python, Ruby) these may be automatically enabled for classes and present a security issue. | KATA never executes or refers to outside code. |
| Curly braces | YAML allows objects to be defined on a single line using `{key:value}` syntax. This conflicts with our `{{placeholder}}` syntax, and requires double quoting, explicit types, or text blocks. | KATA doesn't interpret `{}`, so those characters can be used in values without quoting. |
| Text blocks | YAML has a variety of symbols for blocks of text (`|`, `|-`, `>`). The default always preserves the first trailing linefeed and trims the rest. | KATA implies blocks of text by key annotation (e.g. `@text`). The first trailing linefeed is always ignored and the rest are preserved. |
| Lists | YAML requires `-` prefixes on list items and is sensitive to indentation. Lists are required for duplicate key names amongst siblings. Mistakes with lists of objects can lead to children being added to the wrong parent. | KATA has `@csv` and `@list` key annotations for text-based lists. Sibling keys can be repeated with unique identifiers and never require list syntax. |
| Comments | YAML ignores any text following an unquoted `#` character. | KATA only treats indented lines that begin with `#` as a comment. You do not need to escape the character in values. |

- [Syntax](#syntax)
  - [Indentation](#indentation)
  - [Root](#root)
  - [Key names](#key-names)
    - [Names used as placeholders](#names-used-as-placeholders)

  - [Values](#values)
  - [Whitespace](#whitespace)
  - [Key annotations](#key-annotations)
  - [Text blocks](#text-blocks)
  - [Comments](#comments)
  - [References](#references)

- [Dictionaries](#dictionaries)
- [Annotation Reference](#annotation-reference)
  - [base64](#base64)
  - [bit](#bit)
  - [bool](#bool)
  - [csv](#csv)
  - [date](#date)
  - [float](#float)
  - [int](#int)
  - [json](#json)
  - [kata](#kata)
  - [key](#key)
  - [list](#list)
  - [nowrap](#nowrap)
  - [optional](#optional)
  - [raw](#raw)
  - [ref](#ref)
  - [text](#text)
  - [trim](#trim)

- [Footnotes](#footnotes)

# Syntax

### Indentation

KATA uses indentation (spaces) to build a tree of key/value relationships.

Key names end with a colon (`:`), which may be followed by either a value, or a set of child keys at a deeper level of indentation.

By convention, the indentation for each level should be two spaces.

```
parent:
  child:
    name:
```

### Root

The root of the tree is implicit, so there may be multiple keys at the top-level:

```
picklist:
date_range:
text:
```

### Key names

Key names must be unique amongst their siblings, but can be repeated elsewhere.

Two siblings with the same name don't parse. The error names the key and the line: ``set:` has a sibling with the same name (line 4)`

Key names serve as _declarative_ instructions to a feature using KATA for customization (e.g. dashboard filters, snippet prompts, form interactions, automations). In such cases, the possible keys are predefined by that feature.

A slash (`/`) may be appended to a key name to provide a unique identifier. This format should be read as `type/name:`.

```
options:
  picklist/status:
  picklist/color:
  picklist/group:
```

#### Names used as placeholders

Where a `type/name:` identifier becomes a **dictionary key** – something you read back later as a placeholder – the name must be a valid variable name: letters, numbers, and underscores only.

```
inputs:
  text/order_number: # valid
  text/order-number: # rejected
```

The reason is that a dash isn't part of a variable reference. `{{a-b}}` is the subtraction `a - b`, which renders as `0` with no error. Rather than let a dashed name silently produce zero everywhere it's read – a worklist export column full of zeros, or a snippet placeholder that evaluates to nothing – Cerb rejects it.

This is enforced where a name actually lands in a dictionary:

- `inputs:`
- `await:form:elements:`
- [Snippet](/docs/snippets/) and dashboard prompts
- [Worklist](/docs/worklists/) export columns
- LLM tool `parameters:`

It's also available declaratively as `nameFormat: variable`.

The parser still accepts the wider character set elsewhere, deliberately. Most `type/name:` names are labels, IDs, or handles that are never read back as a placeholder, so enforcing the narrow rule everywhere would reject working content for no benefit.

### Values

A key may be followed by a text value rather than children. KATA will not automatically detect its type, which often removes the need for escaping.

```
object:
  color: red
```

Values may also contain subsequent colons (`:`), which do not require escaping:

```
widget:
  label: Status:
```

### Whitespace

Sibling keys may be separated with blank lines for readability. The blank lines must contain the same indentation as the keys.

```
widget/chart:
  type: chart
  label: Chart
  data: ...

widget/gauge:
  type: gauge
  label: Gauge
  data: ...
```

### Key annotations

KATA does not perform type coercion on key values. It will not unpredictably convert digits to numbers, nor words like `true` or `no` to booleans. All values are treated as text by default.

To manipulate values, a comma-separated list of [annotations](#annotation-reference) may be appended to a key name starting with a `@` character. The annotations are processed in order.

```
picklist:
  options:
    color@csv: red, green, blue
    multiple@bool: no
    hidden@bool:
      {% if has_access %}
      no
      {% else %}
      yes
      {% endif %}
```

### Text blocks

When using key annotations, a value may contain multiple lines of text. Most annotations imply a text block (e.g. `@bool`, `@csv`, `@json`, `@list`). The `@text` annotation may be used for arbitrary text without any special handling.

The `@optional` and `@ref` annotations are the exception. They only accept an inline value on the same line as the key. Following one with an indented block is a syntax error, and leaving its value blank produces an empty object rather than a blank value.

```
comment:
  content@text:
    This is a comment with
    multiple lines of content.
  author:
    name: Cerb
```

### Comments

Lines that begin with `#` are treated as comments and are ignored.

```
picklist:
  # The options from a placeholder
  options:
    color@csv: {{colors}}
```

You do not need to escape the `#` character in values or text blocks.

```
# This is a comment
article:
  title: Using #commands <-- not a comment
  format: markdown
  content@text:
    # Heading <-- not a comment
    
    Some **bold** text.
```

### References

You can break up a complex tree into manageable, reusable sections with _references_.

You define a reference by prefixing an ampersand (`&`) to a top-level key.

Any key can then use an `@ref` annotation to copy the contents and annotations of the reference.

```
picklist:
  options@ref: colors

&colors@list:
  red
  green
  blue
```

An `@ref` may target a child of a reference using dot-notation:

```
picklist:
  options@ref: options.colors

&options:
  colors@list:
    red
    green
    blue
```

And references themselves may contain `@ref` annotations:

```
picklist:
  options@ref: options.colors

&options:
  colors@ref: colors
  
&colors@list:
  red
  green
  blue
```

A reference's annotations take the place of `@ref`, and any remaining annotations will affect the copied content.

For instance, this copies a reference as a `@text` block and _then_ converts it to a `@list`:

```
picklist:
  options@ref,list: colors

&colors@text:
  red
  green
  blue
```

The result of all four examples above is:

```
picklist:
  options@list:
    red
    green
    blue
```

# Dictionaries

Features that use KATA may enable [scripting](/docs/scripting/) and provide a [dictionary](/docs/guide/developers/dictionaries/) of placeholders for dynamic content.

Placeholders and scripting may be used in any value and do not require escaping.

Use the [@raw](#raw) annotation to prevent tags from being parsed.

```
chooser:
  label: {{label}}
  params:
    record_type: {{record_type}}
```

# Annotation Reference

### base64

`@base64` converts a key's value from base64-encoded[3](#fn:base64) text into binary. This is particularly useful for HMAC keys and images.

```
image@base64: QnVzdGVkISBUaGlzIGlzIG5vdCByZWFsbHkgYW4gaW1hZ2Uu
```

### bit

`@bit` convert's a key's value into a `0` or `1`.

The following values result in `0`:

- null/blank
- `0`
- `false`
- `off`
- `no`
- `n`

```
result@bit: off
```

Any other value returns a `1`.

Matching ignores surrounding whitespace and letter case, so `No`, `FALSE`, and ` off ` are all recognized.

### bool

`@bool` convert's a key's value into a boolean `true` or `false`.

The following values result in `false`:

- null/blank
- `0`
- `false`
- `off`
- `no`
- `n`

Any other value returns `true`.

Matching ignores surrounding whitespace and letter case, so `No`, `FALSE`, and ` off ` are all recognized.

```
enabled@bool: yes
```

### csv

`@csv` converts a comma-separated list into an array.

```
colors@csv: red,green,blue
```

### date

`@date` converts a human-readable absolute (`Jan 1 2025 08:00`) or relative (`+2 hours`) date text value into a Unix timestamp.

```
when@date: +2 hours
```

### float

`@float` converts the key's value into a decimal number.

```
price@float: 1.50
```

Conversion is _prefix-based_. Leading whitespace is ignored, then as many characters as form a valid number are used, and the rest is discarded. Nothing is rejected, so a value that isn't a number converts to `0` rather than raising an error.

| Value | Result |
| --- | --- |
| `3.14` | `3.14` |
| `.5` | `0.5` |
| `-2.7` | `-2.7` |
| `1.5e3` | `1500` |
| `12.5%` | `12.5` |
| `12abc` | `12` |
| `1,500` | `1` |
| `$5` | `0` |
| `banana` | `0` |

Two of these are worth watching for. A thousands separator ends the number, so `1,500` becomes `1`. A leading currency symbol prevents any number from being read at all, so `$5` becomes `0`. Neither reports a problem.

### int

`@int` parses the key's value as a whole number.

```
number@int: 123
```

### json

`@json` parses text as a JSON-encoded value.

This is particularly useful when combined with a placeholder based on an API response.

```
person@json:
  {
    "name": "Kina Halpue",
    "title": "Customer Support Manager"
  }

numbers@json: [1,2,3]
```

In an [automation](/docs/automations/), a decoded object that carries both an `id` and a `_context` key additionally becomes a record [dictionary](/docs/guide/developers/dictionaries/), so its placeholders resolve like any other record. Nested objects are converted the same way.

### kata

`@kata` parses text as a KATA-encoded value.

This is particularly useful for dynamically generating KATA using scripting.

```
records@kata:
  {% for doc in docs %}
    record/{{doc.id}}:
      name: {{doc.name}}
  {% endfor %}
```

### key

`@key` sets the value from a [dictionary](#dictionaries) path.

```
http_status@key: response.http.status.code
```

The path separator depends on where the document is parsed. KATA itself uses dots, as above. In an [automation](/docs/automations/) the dictionary convention is colons instead:

```
http_status@key: response:http:status:code
```

A path written with the wrong separator returns nothing rather than reporting an error.

### list

`@list` converts text into an array with one line per item.

```
colors@list:
  red
  green
  blue
```

### nowrap

`@nowrap` reads the key's indented value as a text block and removes newlines and line feeds. This continues until the indent returns to the same level as the key.

```
content@nowrap:
  This is a _bunch_ of content 
  on several lines 
  that will be treated as a single line
```

`@nowrap` is applied by KATA itself. It has no effect in an [automation](/docs/automations/), where the value passes through with its newlines intact.

### optional

`@optional` removes the key entirely when its value is empty.

This is applied by [automations](/docs/automations/) rather than by KATA itself, so a KATA document parsed on its own keeps the key.

```
headers:
  In-Reply-To@optional: {{message_headers['in-reply-to']}}
```

This builds a set of keys from placeholders that may not exist, without emitting blank ones. It's most useful in a `record.update:` where `fields:` should only carry the values that actually changed.

A value is empty when it's blank, null, or an empty list. Falsy values are **not** empty, so `0`, `false`, and a whitespace-only value all keep the key.

Annotations are applied from left to right, and `@optional` tests the value as it stands at that point in the chain. This makes the order significant:

| Annotations | Value | Result |
| --- | --- | --- |
| `@trim,optional` | `' '` | The key is removed. Trim empties the value first. |
| `@optional,trim` | `' '` | The key is kept with a blank value. |
| `@optional,int` | blank | The key is removed. |
| `@int,optional` | blank | The key is kept as `0`, which isn't empty. |

Write `@trim,optional` when the value may be whitespace. Writing `@optional` first has no effect against it.

### raw

`@raw` returns a key's text without substituting `{{placeholders}}` or executing [automation scripting](/docs/scripting/) using the [dictionary](#dictionaries).

This is useful for returning templates to other functionality (e.g. [sheets](/docs/sheets/)).

```
template@raw:
  {{person}} is {{title}} at {{organization}}
```

### ref

`@ref` copies the contents of a top-level `&key:` into the current key.

This can target a reference by name (`key@ref: target`) or by path (`key@ref: target.nested.child.value`).

The annotations of the target key replace the `@ref`, and any remaining annotations apply to the copied content.

See: [References](#references)

```
event/start@ref: menu

&menu:
  options@list:
    Option 1
    Option 2
    Option 3
```

### text

`@text` reads the key's indented value as a text block. This continues until the indent returns to the same level as the key.

This is useful when a text block can't be implied from any other attributes.

```
content@text:
  This is a _bunch_ of content
  on several lines
  that stops **here**
format: markdown
```

The first trailing linefeed is always removed so that multiple line scripts can return a single value.

End the text block with one or more indented blank lines to keep linefeeds:

```
content@text:
  This content ends
  with a blank line
  
format: markdown
```

### trim

`@trim` removes leading and trailing whitespace from a key's value.

```
content@trim: this has no whitespace
```

# Footnotes

1. YAML: https://yaml.org&nbsp;[↩](#fnref:yaml)

2. NoYAML: https://noyaml.com&nbsp;[↩](#fnref:no-yaml)

3. Base64: https://en.wikipedia.org/wiki/Base64&nbsp;[↩](#fnref:base64)

