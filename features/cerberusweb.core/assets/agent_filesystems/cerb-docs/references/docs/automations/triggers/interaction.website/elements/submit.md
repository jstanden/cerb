---
id: "docs-automations-triggers-interaction-website-elements-submit"
title: "Submit - Interaction Form Element"
url: "https://cerb.ai/docs/automations/triggers/interaction.website/elements/submit/"
summary: "This page provides information on the 'submit' element used in website interaction forms within Cerb. It explains that the submit element is responsible for displaying 'Continue' and 'Reset' buttons on forms. The submit element is automatically added when an interaction exits in the await state, meaning users do not need to manually add it. An example code snippet is provided to illustrate how the submit element is configured within a form."
tags: ["docs", "docs-automations"]
---
In [website interactions](/docs/automations/triggers/interaction.website/) forms, a **submit** element displays the 'Continue' and 'Reset' buttons.

This is automatically added when an interaction exits in the [await state](/docs/automations/#exit-states). You do not need to do it yourself.

```
start:
  await:
    form:
      title: Menu
      elements:
        submit:
          continue@bool: yes
          reset@bool: yes
```

 

# Syntax

### buttons:

Alternatively, you can define custom buttons of type `continue` or `reset`. The clicked button will set the `submit/` placeholder to its `value:`.

```
start:
  await:
    form:
      title: Menu
      elements:
        submit/prompt_menu:
          buttons:
            continue/save:
              label: Save
              size: whole
            continue/discard:
              label: Discard
              style: secondary
              size: half
            reset/back:
              label: Back
              value: back
              size: half
```

 

### hidden:

This form element can be conditionally hidden.

```
hidden@bool: {{expression}}
```

### is\_automatic:

If true, the form is automatically submitted. This is primarily useful in place of `await:duration:` before a long-running action.

```
is_automatic@bool: yes
```
