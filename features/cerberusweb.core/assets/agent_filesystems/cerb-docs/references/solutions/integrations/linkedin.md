---
id: "solutions-integrations-linkedin"
title: "LinkedIn"
url: "https://cerb.ai/solutions/integrations/linkedin/"
summary: "This page provides a comprehensive guide on integrating Cerb with LinkedIn, enabling users to utilize LinkedIn's API for automation through Cerb bots. It details the steps to create a LinkedIn app, configure authentication, and set up a LinkedIn service within Cerb. The guide also explains how to link a connected account to LinkedIn in Cerb and use this connection in bot behaviors, allowing for automated interactions with LinkedIn's REST API. The process includes creating an app on LinkedIn, configuring OAuth settings, and using the connected account for executing HTTP requests in bot actions."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Create an app at LinkedIn](#create-an-app-at-linkedin)
  - [Configure authentication for your LinkedIn App](#configure-authentication-for-your-linkedin-app)

- [Create the LinkedIn service in Cerb](#create-the-linkedin-service-in-cerb)
- [Link the connected account to LinkedIn in Cerb](#link-the-connected-account-to-linkedin-in-cerb)
- [Use the connected account in bot behaviors](#use-the-connected-account-in-bot-behaviors)

# Introduction

In this guide we'll walk through the process of linking Cerb to LinkedIn. You'll be able to use LinkedIn's full API from bots in Cerb to automate whatever you need.

# Create an app at LinkedIn

Next, you need to create a new app on LinkedIn for Cerb to connect to.

1. Log in to LinkedIn's developer portal.

2. Click the green **Create New App** button.

3. Click **My Apps** in the top menu.

4. Click the yellow **Create Application** button.

5. Enter the following details: 
  - **Company Name:** (your company)
  - **Name:** Cerb
  - **Description:** Integration with Cerb
  - **Application Logo:** [image](/assets/cerb_mascot.png)
  - **Application Use:** Sales (CRM), Marketing
  - **Website URL:** https://cerb.ai/
  - **Business Email:** (your email address)
  - **Business Phone:** (your phone number)

 
6. Review the API Terms of Use and check **agree**.

7. Click the **Submit** button.

## Configure authentication for your LinkedIn App

1. In the **Authentication** panel, under **Authentication Keys**, copy your **Client ID** and **Client Secret**. You'll need to input these into Cerb.

2. For **Default Application Permissions**, select everything.

3. Under **OAuth 2.0**, add an **Authorized Redirect URL** with the following format and click the **Add** button: `https://YOUR-CERB-HOST/oauth/callback`

4. Scroll down to **App Credentials** and make a note of your **Client ID** and **Client Secret** for the next step.

5. Select **OAuth & Permissions** from the left sidebar.

6. Click the **Add Redirect URL** button and enter the base URL to your Cerb install (e.g. `https://YOUR-CERB-HOST/`).

7. Click the blue **Update** button.

# Create the LinkedIn service in Cerb

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon in the top right of the list.

3. Select **LinkedIn**.

4. Enter your Client ID and Client Secret.

5. Click the **Create** button.

# Link the connected account to LinkedIn in Cerb

1. Navigate to **Search&nbsp;» Connected Accounts**.

2. Click the **(+)** icon in the top right of the list.

3. Select **LinkedIn**.

4. Click the blue **Link to LinkedIn** button.

5. Accept consent on LinkedIn.

6. Click the **Save Changes** button.

# Use the connected account in bot behaviors

You can use the connected account you just created to access LinkedIn's REST API from bot behaviors in Cerb. This is typically accomplished using the **Execute HTTP Request** action from a bot, and selecting the connected account in the **Authentication:** section.

You can import the [LinkedIn Bot](/packages/linkedin-bot/) package for a working example.

