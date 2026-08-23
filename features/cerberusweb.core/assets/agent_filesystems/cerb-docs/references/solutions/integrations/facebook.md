---
id: "solutions-integrations-facebook"
title: "Facebook"
url: "https://cerb.ai/solutions/integrations/facebook/"
summary: "This page provides a comprehensive guide on integrating Facebook with Cerb. It outlines the steps to create a Facebook app, set up a Facebook service in Cerb, and link a connected account to Facebook. The guide also details how to create connected accounts for Facebook Pages and utilize these accounts in bot behaviors within Cerb. By following these instructions, users can leverage Facebook's full API to automate tasks using Cerb's bot functionalities."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Create an app at Facebook](#create-an-app-at-facebook)
- [Create the Facebook service in Cerb](#create-the-facebook-service-in-cerb)
- [Link the connected account to Facebook in Cerb](#link-the-connected-account-to-facebook-in-cerb)
- [Create connected accounts for Facebook Pages](#create-connected-accounts-for-facebook-pages)
- [Use the connected account in bot behaviors](#use-the-connected-account-in-bot-behaviors)

# Introduction

In this guide we'll walk through the process of linking a Facebook account to Cerb. You'll be able to use Facebook's full API from bots in Cerb to automate whatever you need.

# Create an app at Facebook

Next, you need to create a new app on Facebook for Cerb to connect to.

1. Open https://developers.facebook.com and sign in.

2. In the top right, click **My Apps&nbsp;» Add a New App**. 
 
3. In the menu on the left, select **Settings**.

4. Under **App Domains**, add the domain where you host Cerb (e.g. `example.cerb.me`).

5. Click the **+ Add Platform** button at the bottom and select **Website**.

6. In **Site URL**, add the URL to your Cerb installation (e.g. `https://YOUR-CERB-HOST`).

7. Click the blue **Saves Changes** button in the bottom right.

8. Make a note of your **App ID** and **App Secret** for the next step.

# Create the Facebook service in Cerb

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon in the top right of the list.

3. Select **Facebook**.

4. Enter your App ID and App Secret.

5. Click the **Create** button.

# Link the connected account to Facebook in Cerb

1. Navigate to **Search&nbsp;» Connected Accounts**.

2. Click the **(+)** icon in the top right of the list.

3. Select **Facebook**.

4. Click the blue **Link to Facebook** button.

5. Accept consent on Facebook.

6. Click the **Save Changes** button.

# Create connected accounts for Facebook Pages

1. Navigate to **Setup&nbsp;» Connected Accounts**.

2. Click the **(+)** icon above the worklist.

3. Click **Facebook Pages**. 
  - Name: `(your page name)`
  - Owner: **Cerb**
  - Facebook Account: (select the Facebook account from above)

4. This will display a list of Facebook pages associated with your account. Select one.

5. Click the **Save Changes** button.

You can repeat the steps in this section for all of your Facebook pages.

# Use the connected account in bot behaviors

You can use the connected account you just created to access Facebook's Graph API from bot behaviors in Cerb. This is typically accomplished using the **Execute HTTP Request** action from a bot, and selecting the connected account in the **Authentication:** section.

You can import the [Facebook Page Bot](/packages/facebook-page-bot/) package for a working example.

