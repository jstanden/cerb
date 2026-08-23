---
id: "solutions-automations-split-text-delimiter"
title: "Split text with delimiter"
url: "https://cerb.ai/solutions/automations/split-text-delimiter/"
summary: "This page demonstrates how to use the `|split` filter to divide text strings into arrays using various delimiters. It shows examples of splitting comma-separated lists, URIs, paths, and text into equal chunks."
tags: ["solutions", "solutions-automations"]
---
## Split text with delimiters

Here is an example of using the [|split](/docs/scripting/filters/#split) filter to divide text using different delimiters.

- [automation](#)
- [output](#)

- 
```
start:
  return:
    split_list@json: {{"1,2,3"|split(',')|json_encode}}
    split_uris@json: {{"cerb:ticket:123"|split(':')|json_encode}}
    split_limit@json: {{"/usr/share/html/cerb/storage/"|trim('/')|split('/', limit=2)|json_encode}}
    split_chunks@json: {{"abcdefgh"|split('',limit=2)|json_encode}}
```
- 
```
__return:
  split_list:
  - "1"
  - "2"
  - "3"
  split_uris:
  - cerb
  - ticket
  - "123"
  split_limit:
  - usr
  - share/html/cerb/storage
  split_chunks:
  - ab
  - cd
  - ef
  - gh
```

