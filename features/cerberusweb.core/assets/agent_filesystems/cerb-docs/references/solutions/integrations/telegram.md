---
id: "solutions-integrations-telegram"
title: "Telegram"
url: "https://cerb.ai/solutions/integrations/telegram/"
summary: "This page provides a step-by-step guide for integrating Cerb and Telegram, allowing users to utilize Telegram's full Bot API for text and voice messaging in Cerb automations. To start, users must create a new bot on the Telegram BotFather chat, obtaining an authorization token that will be used later in the integration process. Next, they need to set up a webhook by creating a new automation that sends a request to the Telegram API with their bot's token and a specified URL. This sets up the communication channel between Cerb and Telegram, enabling users to respond to messages sent via the bot. Examples of automations are provided, including tests for authorization and sending messages, which can be used as a starting point for more complex integrations."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Get a Telegram Bot Token.](#get-a-telegram-bot-token)
- [Create the Telegram service in Cerb](#create-the-telegram-service-in-cerb)
- [Set a Webhook](#set-a-webhook)
- [Examples](#examples)
  - [Test Authorization](#test-authorization)
  - [Send a Message](#send-a-message)

# Introduction

In this guide we'll walk through the process of linking Cerb to Telegram. You'll be able to use Telegram's full Bot API for text and voice messaging in Cerb automations.

# Get a Telegram Bot Token.

Open a Telegram chat with the BotFather

Type the command `/newbot` to create a new bot.

Give the bot a name and username (eg. Example, ExampleBot)

Copy the authorization token for use later.

# Create the Telegram service in Cerb

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon in the top right of the list.

3. Select **Telegram**.

4. Paste the token you copied earlier in the **Bot Token** field.

5. Click the **Create** button.

# Set a Webhook

In order to properly use your new Telegram Bot in Cerb, you need to set up a webhook.

1. Navigate to **Search&nbsp;» Webhooks**

2. Click the **(+)** icon in the top right of the list.

3. Name the webhook.

4. Copy the Webhook URL.

5. Run the following automation (for more info see https://core.telegram.org/bots/api#setwebhook):

```
1
2
3
4
5
6
7
8
9
10
11
12
13
14
15
16
start:
  http.request/setwebhook:
    output: http_response
    inputs:
      method: POST
      url: https://api.telegram.org/bot/setWebhook
      authentication: cerb:connected_account:telegram
      headers:
        Content-Type: application/json
      body:
        url: https://cerb.example/webhook/abcdef123456
        allowed_updates@csv: message
    on_success:
      set:
        response@json: {{http_response.body}}
        http_response@json: null
```

Use your webhook URL on Line 11 above.

The webhook will receive requests in the format outlined here: https://core.telegram.org/bots/api#update

From here you can set an automation to respond to the webhook however you want.

# Examples

## Test Authorization

https://core.telegram.org/bots/api#getme

```
start:
  http.request/test:
    output: http_response
    inputs:
      method: GET
      url: https://api.Telegram.io/bot/getMe
      authentication: cerb:connected_account:Telegram
      headers:
        Content-Type: application/json
    on_success:
      set:
        response@json: {{http_response.body}}
        http_response@json: null
```

## Send a Message

https://core.telegram.org/bots/api#sendmessage

```
start:
  http.request/chat:
    output: http_response
    inputs:
      method: GET
      url: https://api.Telegram.io/bot/sendMessage
      authentication: cerb:connected_account:Telegram
      headers:
        Content-Type: application/json
      body:
        chat_id: [your chat or group ID]
        text: hello testing testing 123
    on_success:
      set:
        response@json: {{http_response.body}}
        http_response@json: null
```
