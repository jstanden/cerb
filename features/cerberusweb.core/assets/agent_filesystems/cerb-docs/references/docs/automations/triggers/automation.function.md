---
id: "docs-automations-triggers-automation-function"
title: "automation.function"
url: "https://cerb.ai/docs/automations/triggers/automation.function/"
summary: "This page provides an overview of the **automation.function** feature in Cerb, which allows for the creation of reusable shared functions that can be triggered by other automations. It details the structure of inputs and outputs for these functions, explaining that the automation dictionary begins with custom input values from the caller. The page also describes how the function returns key/value pairs to the caller, with the possibility of nesting keys to return dictionaries."
tags: ["docs", "docs-automations"]
---
**automation.function** [automations](/docs/automations/) are reusable shared functions triggered by other automations.

- [Inputs](#inputs)
- [Outputs](#outputs)
  - [return:](#return)

- [Example](#example)

# Inputs

The automation [dictionary](/docs/automations/#dictionaries) starts with the following values:

| Key | Type | Notes |
| --- | --- | --- |
| `inputs` | dictionary | [Custom input](/docs/automations/#inputs) values from the caller |

# Outputs

## return:

When the function concludes in the `return` state, it returns any number of key/value pairs to the caller. Keys may be nested to return dictionaries.

```
return:
  key1: value1
  key2: value2
  ...
```

# Example

Calling a function to translate text:

- [interaction](#)
- [interaction policy](#)
- [function](#)
- [function policy](#)

- 
```
start:
  await/input:
    form:
      title: Translate Text
      elements:
        textarea/prompt_text:
          label: Text to translate:
          required@bool: yes
          default: {{inputs.text}}
        text/prompt_target_lang:
          label: Target language:
          data@key: languages
          required@bool: yes
  
  function/translate:
    uri: cerb:automation:example.translate.function
    output: results
    inputs:
      text: {{prompt_text}}
      target_lang: {{prompt_target_lang}}
  
  return:
    snippet: {{results.text}}
```
- 
```
commands:
  function:
    deny/uri@bool: {{uri != 'cerb:automation:example.translate.function'}}
    allow@bool: yes
```
- 
```
inputs:
  text/text:
    type: freeform
    required@bool: yes
  text/target_lang:
    type: freeform
    required@bool: no

start:
  http.request/translate:
    output: http_response
    inputs:
      method: POST
      url: https://translate.example/v2/translate
      authentication: cerb:connected_account:translate
      headers:
        Content-Type: application/json
      body:
        target_lang: {{inputs.target_lang|default('EN')}}
        text:
          0: {{inputs.text}}
    on_success:
      outcome/ok:
        if@bool: {{200 == http_response.status_code}}
        then:
          set:
            response_body@json: {{http_response.body}}
          return:
            source_lang: {{response_body.translations|first.detected_source_language}}
            target_lang: {{inputs.target_lang|default('EN')}}
            text: {{response_body.translations|first.text}}
```
- 
```
commands:
  http.request:
    deny/url@bool: {{inputs.url is not prefixed ('https://translate.example/v2/translate')}}
    allow@bool: yes
```

