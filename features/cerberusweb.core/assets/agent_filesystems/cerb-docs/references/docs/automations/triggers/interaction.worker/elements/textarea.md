---
id: "docs-automations-triggers-interaction-worker-elements-textarea"
title: "Textarea - Interaction Form Element"
url: "https://cerb.ai/docs/automations/triggers/interaction.worker/elements/textarea/"
summary: "This page provides detailed information on the use of the **textarea** element in interaction web forms within Cerb. It explains the syntax and various optional attributes that can be configured for a textarea, such as `label`, `required`, `placeholder`, `default`, `max_length`, `min_length`, `truncate`, and `validation`. The page includes examples of how to set these attributes, including a custom validation script to ensure user input meets specific criteria. The textarea element is designed for multi-line text input without additional editor functionalities, and the page guides users on how to implement and customize it effectively in their forms."
tags: ["docs", "docs-automations"]
---
In [interaction](/docs/automations/triggers/interaction.worker/) web forms, a **textarea** element displays a multi-line text input without the extra functionality of an [editor](/docs/automations/triggers/interaction.worker/elements/editor/).

```
start:
  await:
    form:
      elements:
        textarea/prompt_comment:
          label: Please share your experience:
```

 

# Syntax

### label:

The optional label to display above the form element.

### hidden:

This form element can be conditionally hidden.

```
hidden@bool: {{not worker_is_superuser}}
```

### required@bool:

If user input is required on this element use a value of `yes`. Otherwise, omit.

### placeholder:

An optional tooltip displayed in the text box when there is no input.

### default:

An optional default value.

### max\_length:

An optional maximum length for the text input (default `1024`). This displays a character count when set.

### min\_length:

An optional minimum length for the text input.

### truncate:

If input longer than `max_length:` is truncated (default `yes`) or unmodified.

### validation:

An optional custom validation script. Any output is considered to be an error.

You can use `if...elseif` to check multiple conditions.

```
textarea/prompt_comment:
  label: Comment:
  validation@raw:
    {% if prompt_comment is empty %}
    A comment is required.
    {% elseif prompt_comment|length < 100 %}
    A comment must be at least 100 characters. 
    {% endif %}
```
