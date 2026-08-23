---
id: "docs-automations-triggers-interaction-website-elements-textarea"
title: "Textarea - Interaction Form Element"
url: "https://cerb.ai/docs/automations/triggers/interaction.website/elements/textarea/"
summary: "This page provides detailed information on the use of the 'textarea' element in website interaction forms within Cerb. It explains the syntax and various configuration options available for the textarea, such as setting a label, making the input required, adding a placeholder, specifying default values, and defining maximum and minimum input lengths. Additionally, it covers the option to truncate input exceeding the maximum length and includes guidance on implementing custom validation scripts to ensure input meets specific criteria. The example provided demonstrates how to require a comment and enforce a minimum character length using conditional logic."
tags: ["docs", "docs-automations"]
---
In [website interactions](/docs/automations/triggers/interaction.website/) forms, a **textarea** element displays a multi-line text input.

```
start:
  await:
    form:
      title: Example
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
hidden@bool: {{expression}}
```

### required@bool:

If user input is required on this element use a value of `yes`. Otherwise, omit.

### placeholder:

An optional tooltip displayed in the text box when there is no input.

### default:

An optional default value.

### max\_length:

An optional maximum length for the text input (default `1024`).

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
