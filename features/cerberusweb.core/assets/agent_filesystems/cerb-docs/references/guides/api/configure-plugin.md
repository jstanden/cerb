---
id: "guides-api-configure-plugin"
title: "Configure the REST API plugin"
url: "https://cerb.ai/guides/api/configure-plugin/"
summary: "This page provides a detailed guide on configuring the REST API plugin for Cerb. It covers the steps to enable the API plugin, generate an API key-pair, and view the key-pair. The guide explains how to navigate the Cerb interface to enable the Web Services API plugin, create new API credentials, and manage permissions for API requests. It also includes instructions on how to restrict API access to specific endpoints. Additionally, the page offers a resource link to a Wikipedia article on Representational State Transfer (REST) for further reading."
tags: ["guides"]
---
- [Introduction](#introduction)
- [Enable the API plugin](#enable-the-api-plugin)
- [Generate an API key-pair](#generate-an-api-key-pair)
- [View the key-pair](#view-the-key-pair)
- [Resources](#resources)

# Introduction

The REST-based[1](#fn:rest) [Web API](/docs/api/) provides the ability to remote control Cerb for automation, synchronization, and integration. For example, you can use the API from other applications and services to create tickets, search records, monitor notifications, manage tasks, and run [automations](/docs/automations/).

# Enable the API plugin

First, we need to enable the API plugin:

1. Navigate to **Setup&nbsp;» Configure&nbsp;» Plugins&nbsp;» Installed Plugins**.

2. Search the plugins list for `API`.

3. In the entry for the **Web Services API** plugin, click the **Configure** button.

4. Select **Enabled** and click the **Save Changes** button.

# Generate an API key-pair

The API uses per-application credentials and per-worker permissions to authorize API requests.

1. Click on the **setup** page in top right to reload the search menu.

2. Navigate to **Search&nbsp;» Api Keys**.

3. Click the **(+)** icon above the worklist to create a new key-pair.

4. Enter the following details: 
  - **Name:** API Example
  - **Worker:** (click the **me** button)
  - **Allowed Endpoints:** (keep the `*` default for now)

 
5. Click the **Save Changes** button.

# View the key-pair

1. Click on **API Example** in the yellow notification above the worklist to open its card:

2. Click on **(reveal)** to view the secret key.

As an administrator, you can repeat this process to create a key-pair for other workers.

By configure the **Allowed Endpoints** option you can restrict a key-pair to only certain endpoints [in the API](/docs/api/).

# Resources

1. Wikipedia: Representational State Transfer (REST) - https://en.wikipedia.org/wiki/Representational\_state\_transfer&nbsp;[↩](#fnref:rest)

