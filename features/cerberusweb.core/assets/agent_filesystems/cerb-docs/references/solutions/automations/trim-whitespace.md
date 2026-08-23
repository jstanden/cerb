---
id: "solutions-automations-trim-whitespace"
title: "Trim whitespace in scripting tags"
url: "https://cerb.ai/solutions/automations/trim-whitespace/"
summary: "This page demonstrates techniques for controlling whitespace. Learn how to trim leading and trailing whitespace using dash modifiers in template tags, and remove whitespace between HTML tags using the `|spaceless` filter."
tags: ["solutions", "solutions-automations"]
---
## Using tag modifiers

Adding a dash `-` to opening or closing scripting tags will trim leading or trailing whitespace.

- [automation](#)
- [output](#)

- 
```
start:
  return:
    output@text:
      This text
      
      {{-" has no leading or trailing whitespace "-}}
      
      in it.
```
- 
```
__return:
  output: This text has no leading or trailing whitespace in it.
```

## Using |spaceless filter

The `|spaceless` filter removes whitespace between HTML tags.

- [automation](#)
- [output](#)

- 
```
start:
  return:
    output@text: 
      {{
        "<div>
          <p>This has extra space</p>
          <p>between tags</p>
        </div>"|spaceless
      }}
```
- 
```
__return:
  output: <div><p>This has extra space</p><p>between tags</p></div>
```

## Using apply spaceless

For larger blocks of HTML, you can use the apply spaceless approach.

- [automation](#)
- [output](#)

- 
```
start:
  return:
    output@text:
      {% apply spaceless %}
      <div>
        <span>This will all be on a single line.</span>
      </div>
      {% endapply %}
```
- 
```
__return:
  output: <div><span>This will all be on a single line.</span></div>
```

