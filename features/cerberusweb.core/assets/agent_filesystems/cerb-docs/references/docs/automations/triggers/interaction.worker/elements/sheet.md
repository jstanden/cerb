---
id: "docs-automations-triggers-interaction-worker-elements-sheet"
title: "Sheet - Interaction Form Element"
url: "https://cerb.ai/docs/automations/triggers/interaction.worker/elements/sheet/"
summary: "This page provides detailed information on the 'sheet' element used in interaction web forms within Cerb. It explains how a sheet element can display a schema using either static or dynamic data, with options for single or multiple selections. The page outlines the syntax for various attributes such as labels, required fields, data sources, limits, pagination, filtering, default selections, and schema definitions. It also covers the use of synthesized data, interaction toolbars, and custom validation scripts to enhance form functionality. The document includes examples of how to configure these elements, emphasizing flexibility in data presentation and user interaction."
tags: ["docs", "docs-automations"]
---
In [interaction](/docs/automations/triggers/interaction.worker/) web forms, a **sheet** element displays a [sheet](/docs/sheets/) schema using static or dynamic data.

Selection prompt can be set to 'single' or 'multiple'.

```
start:
  await:
    form:
      elements:
        sheet/prompt_choice:
          label: Make a selection:
          required@bool: yes
          data:
            0:
              key: option1
              name: Option 1
              description: This is a description of option one.
            1:
              key: option2
              name: Option 2
              description: This is a description of option two.
          limit: 5
          default: option1
          schema:
            layout:
              headings@bool: no
              paging@bool: no
              #filtering@bool: yes
              #title_column: key
            columns:
              selection/key:
                params:
                  mode: single
              text/name:
                params:
                  bold@bool: yes
              text/description:
```

 

# Syntax

### label:

The optional label to display above the form element.

### required@bool:

If user input is required on this element use a value of `yes`. Otherwise, omit.

### data:

A sheet element can display an array of dictionaries as a dataset.

Alternatively, a [ui.sheet.data](/docs/automations/triggers/ui.sheet.data/) automation can fetch a dynamic dataset.

```
start:
  await:
    form:
      elements:
        sheet/prompt_workers:
          label: Workers:
          required@bool: yes
          data:
            automation:
              uri: cerb:automation:cerb.data.records
              inputs:
                record_type: worker
                query_required@text:
                  isDisabled:n
          limit: 5
          schema:
            layout:
              headings@bool: no
              paging@bool: no
              filtering@bool: yes
            columns:
              selection/id:
                params:
                  mode: multiple
              card/_label:
                params:
                  bold@bool: yes
                  image@bool: yes
```

 

#### Synthesized data

You can also specify a list of keys in `data:` without properties. These will be available as the `key` property.

```
start:
  await:
    form:
      elements:
        sheet/prompt_rating:
          label: Rating:
          required@bool: yes
          data:
            1:
            2:
            3:
            4:
            5:
          schema:
            layout:
              headings@bool: no
              paging@bool: no
            columns:
              text/key:
```

### limit:

The number of dataset items to display per page. Default: `10`

### page:

The page of dataset items to display. Default: `0`

### filter:

The default filtering text, if `schema:layout:filtering:` is enabled.

### default:

The optional selected dataset item(s) by default.

### hidden:

This form element can be conditionally hidden.

```
hidden@bool: {{not worker_is_superuser}}
```

### schema:

A [sheet](/docs/sheets/) schema defining the column types to display for each dataset item.

Selection is managed by a `selection:` column in the sheet.

The selected values are set on the element placeholder.

The `selection:params:mode:` may be `single` or `multiple`.

### toolbar:

An optional [interaction toolbar](/docs/automations/triggers/interaction.worker/#toolbars) for this sheet.

### validation:

An optional custom validation script. Any output is considered to be an error.

You can use `if...elseif` to check multiple conditions.

```
text/prompt_name:
  label: Name:
  required@bool: yes
  type: freeform
  validation@raw:
    {% if prompt_name is empty %}
    A name is required.
    {% elseif prompt_name|length < 8 %}
    A name must be 8 or more characters. 
    {% elseif prompt_name|length > 32 %}
    A name must be less than 32 characters. 
    {% endif %}
```
