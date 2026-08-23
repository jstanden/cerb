---
id: "solutions-integrations-slack"
title: "Slack"
url: "https://cerb.ai/solutions/integrations/slack/"
summary: "This page provides a comprehensive guide on integrating Cerb with Slack, detailing the steps to create a new Slack app and configure authentication using either bot tokens or OAuth2. It explains how to set up a Slack connected account in Cerb, with specific instructions for both authentication methods. The guide also includes related resources for further integration, such as setting up Slack notifications within Cerb."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Create a new app at Slack](#create-a-new-app-at-slack)
  - [Configure Slack authentication](#configure-slack-authentication)
    - [Option 1: Bot Tokens (Recommended)](#option-1-bot-tokens-recommended)
    - [Option 2: OAuth2](#option-2-oauth2)

- [Create the Slack connected account in Cerb](#create-the-slack-connected-account-in-cerb)
  - [Bot Tokens](#bot-tokens)
  - [OAuth2](#oauth2)

- [Examples:](#examples)
  - [Send a simple message to a channel](#send-a-simple-message-to-a-channel)
  - [Send a Block Kit message to a channel](#send-a-block-kit-message-to-a-channel)
  - [List channels](#list-channels)
  - [List Users](#list-users)

- [Related Resources](#related-resources)

# Introduction

In this guide we'll walk through the process of linking Cerb to Slack. You'll be able to use Slack's full API from automations in Cerb.

# Create a new app at Slack

First, you need to create a new app on Slack for Cerb to connect to.

Log in to Slack's developer portal.

Click the green **Create New App** button.

Select **From scratch**.

Enter the following details:

| **App Name:** | Cerb |
| **Development Slack Team:** | (your team name) |

 

Click the **Create App** button.

## Configure Slack authentication

You can choose to authenticate with either **bot tokens** or **OAuth2**.

### Option 1: Bot Tokens (Recommended)

This is the simplest authentication method. It uses a single OAuth2 token for the bot and doesn't link Slack user accounts.

Select **OAuth & Permissions** from the left sidebar.

Scroll down to **Scopes**.

In **Bot Token Scopes** click the **Add an OAuth Scope** button.

Add the `chat:write` scope.

Scroll up to **OAuth Tokens** and click the **Install** button.

Click **Allow** on the consent screen.

Copy the **Bot User OAuth Token**.

### Option 2: OAuth2

This authentication method links individual Slack users to Cerb connected accounts. Use this if you need to automate a user's account.

Select **Basic Information** in the left sidebar.

Scroll down to **App Credentials** and make a note of your **Client ID** and **Client Secret** for the next step.

Select **OAuth & Permissions** from the left sidebar.

Scroll down to **Redirect URLs**.

Click the **Add Redirect URL** button and enter the base URL to your Cerb install (e.g. `https://YOUR-CERB-HOST/`).

# Create the Slack connected account in Cerb

In Cerb, navigate to **Search&nbsp;» Connected Services**.

Click the **(+)** icon in the top right of the list.

Select **Slack**.

Enter either the **[Bot Tokens]** or **[OAuth2]** fields depending on your authentication option above.

Click the **Create** button at the bottom of the popup.

 

### Bot Tokens

You're done!

### OAuth2

If you're using OAuth2 authentication, you need to link Slack user accounts to Cerb.

Navigate to **Search&nbsp;» Connected Accounts**.

Click the **(+)** icon in the top right of the list.

Select **Slack**.

Click the blue **Link to Slack** button.

Accept consent on Slack.

 

Click the **Save Changes** button.

# Examples:

## Send a simple message to a channel

```
start:
  http.request:
   output: http_response
   inputs:
      url: https://slack.com/api/chat.postMessage
      method: POST
      authentication: cerb:connected_account:slack
      headers@text:
        Content-Type: application/json; charset=utf8
      body:
        channel: #testing
        text@text:
          Here is an example message.
   on_success:
     set:
        response@json: {{http_response.body}}
        http_response@json: null
```

## Send a Block Kit message to a channel

```
start:
  http.request:
   output: http_response
   inputs:
      url: https://slack.com/api/chat.postMessage
      method: POST
      authentication: cerb:connected_account:slack
      headers@text:
        Content-Type: application/json; charset=utf8
      body:
        channel: #testing
        text@text:
          Here is an example message using Block Kit:
        blocks:
          0:
            type: section
            text:
              text: A message *with some bold text* and _some italicized text_.
              type: mrkdwn
            fields:
              0:
                type: mrkdwn
                text: *Priority*
              1:
                type: mrkdwn
                text: *Type*
              2:
                type: plain_text
                text: High
              3:
                type: plain_text
                text: Silly
          1:
            type: image
            title:
              type: plain_text
              text: a cat
            image_url: https://cataas.com/cat
            alt_text: a cat
   on_success:
     set:
        response@json: {{http_response.body}}
        http_response@json: null
```

## List channels

Requires `channels:read` scope.

```
start:
  http.request:
   output: http_response
   inputs:
      url: https://slack.com/api/conversations.list
      method: GET
      authentication: cerb:connected_account:slack
      headers@text:
        Content-Type: application/json; charset=utf8
    on_success:
      set:
        response@json: {{http_response.body}}
        http_response@json: null
```

## List Users

Requires `users:read` scope.

```
start:
  http.request:
    output: http_response
    inputs:
      url: https://slack.com/api/conversations.list
      method: GET
      authentication: cerb:connected_account:slack
      headers:
        Content-Type: application/json; charset=utf8
    on_success:
      set:
        response@json: {{http_response.body}}
        http_response@json: null
```

# Related Resources

- Guide: [Send commands to a Cerb automations using Slack](guides/integrations/slack/slash-commands/)
- Workflow: [Slack Notifications](/workflows/cerb.integrations.slack.notifications/)

