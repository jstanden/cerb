---
id: "solutions-automations-shuffle-list-string"
title: "Shuffle lists and strings"
url: "https://cerb.ai/solutions/automations/shuffle-list-string/"
summary: "This page demonstrates how to use the `shuffle` function to randomly reorder elements in lists and strings. It shows examples of shuffling both numerical arrays and text strings, illustrating how to create randomized sequences."
tags: ["solutions", "solutions-automations"]
---
## Shuffling lists and strings

Here is an example of using the [shuffle](/docs/scripting/functions/#shuffle) function to randomly reorder elements in both lists and strings.

- [automation](#)
- [output](#)

- 
```
start:
  return:
    shuffled_list: {{shuffle([1,2,3,4,5,6,7,8,9,10])|json_encode}}
    shuffled_text: {{shuffle("abcdefghijklmnopqrstuvwxyz"|split(''))|join('')}}
```
- 
```
__return:
  shuffled_list: '[2,4,5,1,7,10,8,9,6,3]'
  shuffled_text: wrgpudtahnqjemklsfizoybvcx
```

