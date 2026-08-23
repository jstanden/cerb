---
id: "solutions-integrations-azure"
title: "Azure"
url: "https://cerb.ai/solutions/integrations/azure/"
summary: "This page provides a detailed guide on integrating Cerb with Office365 by configuring a Microsoft Entra app and setting up Cerb. It includes step-by-step instructions for creating a new app and client secret in Azure, as well as creating a connected service and account in Cerb. The guide is designed to help users automate tasks using Azure APIs with Cerb bots. It concludes with a reference to further steps for authenticating an Office365 mailbox using XOAUTH2."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Configure an Entra app](#configure-an-entra-app)
  - [Create a new app](#create-a-new-app)
  - [Create client secret](#create-client-secret)

- [Configure Cerb](#configure-cerb)
  - [Create the connected service](#create-the-connected-service)
  - [Create the connected account](#create-the-connected-account)

- [Troubleshooting](#troubleshooting)
  - [When linking the connected account I get an "invalid\_client" error](#when-linking-the-connected-account-i-get-an-invalid_client-error)
  - [The access token stops working after an hour and isn't refreshed](#the-access-token-stops-working-after-an-hour-and-isnt-refreshed)
  - [The connected account stops working after several months](#the-connected-account-stops-working-after-several-months)

- [Next steps](#next-steps)

# Introduction

In this guide we'll walk through the process of linking Cerb to Office365. You can use the same process with any Azure API from Cerb bots to automate whatever you need.

# Configure an Entra app

### Create a new app

1. Log in to: https://entra.microsoft.com/

2. Select **Applications&nbsp;» App registrations** in the left sidebar.

3. Click the **+ New registration** button at the top.

4. Enter:

5. Click the blue **Register** button at the bottom.

### Create client secret

1. In the new app registration, navigate to **Certificates & secrets**.

2. Click the **New client secret** button in the **Client secrets** section near the middle of the page.

3. Click the blue **Add** button.

4. Copy the **Value** (not the **Secret ID**).

# Configure Cerb

### Create the connected service

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click **(+)** button in the right of the gray bar above the worklist.

3. In the **Build** tab, enter:

4. Paste your **Client ID** and **Client Secret** from the credentials you copied earlier.

5. Click the **Save Changes** button.

The `offline_access` scope is required for Cerb to automatically renew the access token every hour.

### Create the connected account

1. Navigate to **Search&nbsp;» Connected Accounts**.

2. Click **(+)** button in the right of the gray bar above the worklist.

3. Select **Office365**.

4. Enter:

5. Click the blue **Link to Office365** button.

6. Log in with your Office365 account.

7. Click **Accept**.

8. Click the **Save Changes** button.

# Troubleshooting

## When linking the connected account I get an "invalid\_client" error

- Verify that the "Callback URL" in Entra matches exactly. If you have `/index.php` in your Cerb URLs, then the endpoint should be: `https://{HOST}/index.php/oauth/callback`

- Verify there is no trailing slash (`/`) in the Entra callback URL (e.g. `/oauth/callback`).

## The access token stops working after an hour and isn't refreshed

- Verify `offline_access` is included in the connected service's "Scope" field. This allows Cerb to refresh the access token every hour.

- Verify that the scheduled task that pings the `/cron` endpoint is using the same hostname you configured in the callback URL. It should always use `https://`.

## The connected account stops working after several months

- Entra app secrets expire in 6 months by default. Establish a regular process for rotating secrets before then. Update the connected service in Cerb with the new secret. Edit and save your mailboxes to clear the failure count.

# Next steps

See: [Authenticate an Office365 mailbox using XOAUTH2](/guides/integrations/azure/o365-xoauth/)

