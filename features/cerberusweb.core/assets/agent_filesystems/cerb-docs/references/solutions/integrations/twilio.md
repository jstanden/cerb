---
id: "solutions-integrations-twilio"
title: "Twilio"
url: "https://cerb.ai/solutions/integrations/twilio/"
summary: "This page provides a step-by-step guide on integrating Cerb with Twilio, enabling the use of Twilio's API within Cerb's bot behaviors. It covers obtaining API keys from the Twilio Dashboard, setting up a Twilio service in Cerb by entering the Account SID and Auth Token, and utilizing the connected account to execute HTTP requests through bot behaviors. The guide also mentions the availability of a Twilio Bot package for practical implementation examples."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Get your API keys from the Twilio Dashboard](#get-your-api-keys-from-the-twilio-dashboard)
- [Create the Twilio service in Cerb](#create-the-twilio-service-in-cerb)
- [Use the connected account in bot behaviors](#use-the-connected-account-in-bot-behaviors)

# Introduction

In this guide we'll walk through the process of linking Cerb to Twilio. You'll be able to use Twilio's full API from bots in Cerb.

# Get your API keys from the Twilio Dashboard

1. Browse to: https://www.twilio.com/login

2. In the top right, copy your **Account SID** (username) and **Auth Token** (password).

# Create the Twilio service in Cerb

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon in the top right of the list.

3. Select **Twilio**.

4. Enter your Account SID and Auth Token.

5. Click the **Create** button.

# Use the connected account in bot behaviors

You can use the connected account you just created to access Twilio's API from bot behaviors in Cerb. This is typically accomplished using the **Execute HTTP Request** action from a bot, and selecting the connected account in the **Authentication:** section.

You can import the [Twilio Bot](/packages/twilio-bot/) package for a working example.

