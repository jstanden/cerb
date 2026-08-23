---
id: "solutions-automations-set-dictionary-values"
title: "Set dictionary values"
url: "https://cerb.ai/solutions/automations/set-dictionary-values/"
summary: "This page demonstrates how to use dictionary structures to set and expand dynamic values. It shows how to define nested attributes and conditionally display values based on dictionary properties."
tags: ["solutions", "solutions-automations"]
---
## Setting dictionary values

When a value (like a model ID selected from a sheet) needs to be expanded, a dictionary structure can associate multiple attributes with that key. This enables dynamic lookups and conditional formatting based on those attributes in subsequent logic.

- [automation](#)
- [output](#)

- 
```
start:
  set:
    models:
      llama3.3:
        tools@bool: yes
        params: 70b
      phi4:
        tools@bool: no
        params: 14b
      mistral:
        tools@bool: yes
        params: 7b
  
  return:
    output@text:
      {% for model in models|keys %}
      {{model}} has {{models[model].params}} parameters{{models[model].tools ? ' and supports tools'}}.
      {% endfor %}
```
- 
```
output@text:
  llama3.3 has 70b parameters and supports tools.
  phi4 has 14b parameters.
  mistral has 7b parameters and supports tools.
```

