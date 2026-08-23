---
id: "solutions-integrations-smartsheet"
title: "Smartsheet"
url: "https://cerb.ai/solutions/integrations/smartsheet/"
summary: "This page provides a detailed guide on integrating Cerb with Smartsheet, enabling the use of Smartsheet's API within Cerb's bot behaviors for automation purposes. It outlines the steps to create an OAuth access token in Smartsheet, set up the Smartsheet service in Cerb, and utilize the connected account in bot behaviors. The guide includes instructions for generating an access token in Smartsheet, configuring the Smartsheet service in Cerb (version 9.2.1 or later), and using the connected account to execute HTTP requests through Cerb's bots."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Create an OAuth access token at Smartsheet](#create-an-oauth-access-token-at-smartsheet)
- [Create the Smartsheet service in Cerb](#create-the-smartsheet-service-in-cerb)
- [Use the connected account in bot behaviors](#use-the-connected-account-in-bot-behaviors)

# Introduction

In this guide we'll walk through the process of linking Cerb to Smartsheet. You'll be able to use Smartsheet's full API from bots in Cerb to automate whatever you need.

# Create an OAuth access token at Smartsheet

1. Log in to Smartsheet.

2. Click the account silhouette icon in the top right.

3. Select **Personal Settings**.

4. Select **API Access** in the left sidebar of the popup.

5. Click the blue **Generate new access token** button.

6. Name the access token **Cerb**.

7. Copy the access token so it can be used later in this guide.

# Create the Smartsheet service in Cerb

(This requires Cerb [9.2.1](/releases/9.2.1/) or later)

1. Log in to Cerb.

2. Navigate to **Search&nbsp;» Connected Services**.

3. Click the **(+)** icon above the services worklist.

4. Select **Smartsheet**.

5. Paste your access token from above.

6. Click the **Create** button.

This will create a new connected service and account for you.

# Use the connected account in bot behaviors

You can use the connected account you just created to access Smartsheet's API from bot behaviors in Cerb. This is typically accomplished using the **Execute HTTP Request** action from a bot, and selecting the connected account in the **Authentication:** section.

