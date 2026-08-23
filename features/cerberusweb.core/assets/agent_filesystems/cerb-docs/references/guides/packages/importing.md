---
id: "guides-packages-importing"
title: "Import a package"
url: "https://cerb.ai/guides/packages/importing/"
summary: "This page provides a comprehensive guide on importing packages into Cerb, detailing what packages are and how they function as blueprints for importing pre-configured records like workspaces, dashboards, and bots. It explains the process of importing packages through different methods: directly from the setup interface, using bots, and via the API. The guide includes step-by-step instructions for each method, such as copying a package, pasting it into the appropriate field, and configuring any necessary options. It also describes how bots can automate package imports and how the API can be used for programmatic imports, providing examples and JSON structures to facilitate understanding."
tags: ["guides"]
---
 

- [What are packages?](#what-are-packages)
- [Importing packages](#importing-packages)
  - [Setup](#setup)
  - [Bots](#bots)
  - [API](#api)

# What are packages?

**Packages** are a blueprint for importing a related set of pre-configured records into Cerb. A single package can contain workspaces, dashboards, bots, project boards, custom records, custom fieldsets, tasks, tickets, contacts, etc.

You can [build your own packages](/guides/packages/building/) or import them from our [package library](/resources/packages/).

# Importing packages

Packages can be imported from [setup](/docs/setup/), bots, and the [API](/docs/api/).

Here's an example package for testing imports:

```
{
  "package": {
    "name": "Example Package"
  },
  "records": [
    {
      "uid": "task_001",
      "_context": "task",
      "title": "Import your first package",
      "status": "closed"
    }
  ]
}
```

## Setup

Administrators can quickly import packages from their web browser:

1. Copy a package to your clipboard.

2. Navigate to **Setup&nbsp;» Packages&nbsp;» Import**.

3. Paste the package into the text box.

4. Click the **Import** button.

Depending on the package, you may be prompted for some configuration options. If so, enter your preferences and click the **Import** button again.

Once a package has been imported, you'll see a summary of all the records that have been created.

 

You can click on the records to open their card popups.

## Bots

Bots can import packages using the **Package Import** action from any behavior. This is currently only available to app-owned bots.

 

Paste the package into the **Package:** field.

In **Params:** you can provide the preferred values for any prompted placeholders required by the package. This is a simple JSON object with keys matching the `configure > prompts` section of the package.

In the example above, we're building the JSON object with the [dict\_set()](/docs/scripting/functions/#dict_set) function, which automatically handles escaping for us.

The action returns a placeholder named `_results` with details about the created records in the format:

```
{
  "cerberusweb.contexts.task": {
    "task_001": {
      "id": "258",
      "label": "Import a package from bots"
    }
  }
}
```

The results object is keyed by record type. Each record type contains an object keyed by `uid`, with an `id` and `label` for each record created.

## API

You can use [/packages/import.json](/docs/api/endpoints/packages/) to import packages using the API.

