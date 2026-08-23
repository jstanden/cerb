---
id: "solutions-automations-json-encode-decode"
title: "JSON encode and decode"
url: "https://cerb.ai/solutions/automations/json-encode-decode/"
summary: "This page demonstrates how to use JSON encoding and decoding to convert between JSON strings and native data types. It shows examples of encoding arrays and dictionaries to JSON using the `|json_encode` filter, as well as decoding JSON strings back into usable data structures using the `json_decode()` function."
tags: ["solutions", "solutions-automations"]
---
## Converting to JSON

Here is an example of using the [|json\_encode](/docs/scripting/json/) filter to convert native data types to JSON strings.

- [automation](#)
- [output](#)

- 
```
start:
  set:
    data:
      name: Joe Customer
      order_id@int: 54321
      status:
        text: shipped
        tracking_id: Z1F238
  return:
    encoded: {{data|json_encode}}
    pretty: {{data|json_encode|json_pretty}}
```
- 
```
__return:
  encoded: '{"name":"Joe Customer","order_id":54321,"status":{"text":"shipped","tracking_id":"Z1F238"}}'
  pretty: |-
    {
        "name": "Joe Customer",
        "order_id": 54321,
        "status": {
            "text": "shipped",
            "tracking_id": "Z1F238"
        }
    }
```

## Converting from JSON using @json

The [@json](/docs/kata/#json) annotation converts a JSON string back into native types.

- [automation](#)
- [output](#)

- 
```
start:
  set:
    json_string@text: {"name":"Joe Customer","order_id":12345}
    decoded_data@json: {{json_string}}
  
  return:
    customer: {{decoded_data.name}}
    order_num@int: {{decoded_data.order_id}}
```
- 
```
__return:
  customer: Joe Customer
  order_num: 12345
```

## Converting from JSON using json\_decode

Here is an example of using the [json\_decode()](/docs/scripting/json/) function to convert JSON strings back into native data types.

- [automation](#)
- [output](#)

- 
```
start:
  set:
    json_string@text: "{\"name\":\"Joe Customer\",\"order_id\":12345}"
    decoded_data@json: {{json_decode(json_string)}}
  
  return:
    customer: {{decoded_data.name}}
    order_num@int: {{decoded_data.order_id}}
```
- 
```
__return:
  customer: Joe Customer
  order_num: 12345
```

