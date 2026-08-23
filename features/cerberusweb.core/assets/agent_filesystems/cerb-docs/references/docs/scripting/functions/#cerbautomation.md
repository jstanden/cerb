---
id: "docs-scripting-functions--cerbautomation"
title: "Scripting Function: cerb_automation"
url: "https://cerb.ai/docs/scripting/functions/#cerbautomation"
summary: "Invoke a scripting.function automation from any scripting feature"
tags: ["docs", "docs-scripting"]
---
## cerb\_automation

Invoke a [scripting.function](/docs/automations/triggers/scripting.function/) automation from any feature that supports [scripting](/docs/scripting/).

The function returns keys for `exit_state:` (`exit`, `return`, `error`) and `return:` (an arbitrary dictionary).

This brings the full functionality of automations to email signatures, snippets, legacy bot behaviors, automation event bindings, toolbars bindings, etc.

For instance, a snippet could use an automation to dynamically generate content based on the target record or current worker. This solves many feature requests.

`cerb_automation(uri, inputs)`

| **uri** | The URI of an [automation](/docs/automations/) record to invoke. It must be of type `scripting.function`. |
| **inputs** | A key/value dictionary of inputs. The possible keys depend on the function being invoked. |

```
{% set ip_data = cerb_automation('wgm.scripting.getLocationByIP', { ip:"1.2.3.4" } ) %}
{% if ip_data.return.data %}
I see you are contacting us from {{ip_data.return.data.country_name}}.
{% endif %}
```

```
I see you are contacting us from Australia.
```
