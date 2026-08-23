---
id: "solutions-integrations-airtable"
title: "Airtable"
url: "https://cerb.ai/solutions/integrations/airtable/"
summary: "This page provides a step-by-step guide for integrating Cerb with Airtable, allowing users to access Airtable's full API for automations. To begin, log in to the Airtable account and create a Personal Access Token, which is required for authentication. The token can be used to set up the Airtable service in Cerb by navigating to Connected Services, clicking 'Create' and pasting the API key. Examples of actions that can be automated using this integration include listing bases, getting base schema, and listing records from specific tables or projects within an Airtable base."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Get a Airtable API key](#get-a-airtable-api-key)
- [Create the Airtable service in Cerb](#create-the-airtable-service-in-cerb)
- [Examples](#examples)
  - [List bases](#list-bases)
  - [Get base schema](#get-base-schema)
  - [List records](#list-records)

# Introduction

In this guide we'll walk through the process of linking Cerb to Airtable. You'll be able to use Airtable's full API for any automations you wish to make.

# Get a Airtable API key

Log in to your Airtable Account or sign up if you don't already have one.

Click your user icon in the top right and click **Builder Hub**.

Choose **Personal Access Tokens** from the left menu and click **Create New Token**.

Name the token and select any scopes and access bases that are needed. For instance:

- `data.records:read`
- `schema.bases:read`

Click **Create token** and copy the API key.

# Create the Airtable service in Cerb

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon in the top right of the list.

3. Select **Airtable**.

4. Paste the key you copied earlier in the **API Key** field.

5. Click the **Create** button.

# Examples

## List bases

https://airtable.com/developers/web/api/list-bases

```
start:
  http.request/listBases:
    output: http_response
    inputs:
      method: GET
      url: https://api.airtable.com/v0/meta/bases
      authentication: cerb:connected_account:airtable
    on_success:
      set:
        response@json: {{http_response.body}}
        http_response@json: null
```

## Get base schema

https://airtable.com/developers/web/api/get-base-schema

```
start:
  set:
    baseId: appa1b2c3d4e5f6
  http.request/getBaseSchema:
    output: http_response
    inputs:
      method: GET
      url: https://api.airtable.com/v0/meta/bases/{{baseId}}/tables
      authentication: cerb:connected_account:airtable
    on_success:
      set:
        response@json: {{http_response.body}}
        http_response@json: null
```

## List records

https://airtable.com/developers/web/api/list-records

```
start:
  set:
    baseId: appa1b2c3d4e5f6
    tableIdOrName: Projects
  http.request/getRecords:
    output: http_response
    inputs:
      method: GET
      url: https://api.airtable.com/v0/{{baseId}}/{{tableIdOrName}}
      authentication: cerb:connected_account:airtable
    on_success:
      set:
        response@json: {{http_response.body}}
        http_response@json: null
```
