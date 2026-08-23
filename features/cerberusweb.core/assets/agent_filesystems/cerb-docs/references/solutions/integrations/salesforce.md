---
id: "solutions-integrations-salesforce"
title: "Salesforce"
url: "https://cerb.ai/solutions/integrations/salesforce/"
summary: "This page provides a comprehensive guide on integrating Cerb with Salesforce. It details the steps to create a new app in Salesforce, including configuring basic information and OAuth settings, and obtaining OAuth credentials. The guide then explains how to create a Salesforce service in Cerb, link a connected account to Salesforce, and utilize this connection in bot behaviors within Cerb. The integration allows users to automate tasks using the Salesforce API through Cerb's bot functionalities."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Create an app at Salesforce](#create-an-app-at-salesforce)
  - [In Salesforce Classic](#in-salesforce-classic)
  - [In the Lightning Experience](#in-the-lightning-experience)
  - [Configure Basic information](#configure-basic-information)
  - [Configure OAuth](#configure-oauth)
  - [Copy your OAuth credentials](#copy-your-oauth-credentials)

- [Create the Salesforce service in Cerb](#create-the-salesforce-service-in-cerb)
- [Link the connected account to Salesforce in Cerb](#link-the-connected-account-to-salesforce-in-cerb)
- [Examples](#examples)
  - [Create an Account record](#create-an-account-record)
  - [Get Account info](#get-account-info)
  - [Update an Account record](#update-an-account-record)

- [Use the connected account in bot behaviors](#use-the-connected-account-in-bot-behaviors)

# Introduction

In this guide we'll walk through the process of linking Cerb to Salesforce. You'll be able to use the full Salesforce API from automations in Cerb to automate whatever you need.

# Create an app at Salesforce

Next, you need to create a new app on Salesforce for Cerb to connect to.

Log in to Salesforce.

### In Salesforce Classic

1. Click **Setup** in the top right.

2. In the **Build** menu on the left, expand **Create** and select **Apps**.

3. In the **Connected Apps** section at the bottom, click the **New** button.

### In the Lightning Experience

1. Click the gear icon in the top right and choose **Setup**

2. Under **Platform Tools** in the menu on the left, click **Apps&nbsp;» App Manager**

3. Click the **Create Connected App** button in the top right.

4. Choose **Create a Connected App** and click **Continue**

## Configure Basic information

Enter the following details:

- **Connected App Name:** `Salesforce for Cerb`
- **API Name:** `Salesforce_for_Cerb`
- **Contact Email:** (your email address)
- **Logo URL:** [image](/assets/cerb_mascot.png)

## Configure OAuth

In the **API (Enable OAuth Settings)** section:

- **Enable OAuth Settings:** yes
- **Callback URL:** `https://YOUR-CERB-HOST/oauth/callback`
- **Selected OAuth Scopes:**
  - Perform requests on your behalf at any time (refresh\_token, offline\_access)
  - Access and manage your data (api)
  - Provide access to your data via the Web (web)

 

## Copy your OAuth credentials

1. Scroll to the bottom and click the **Save** button.

2. Click the **Continue** button.

3. Click **Manage Consumer Details**

4. Make a note of your **Consumer Key** and **Consumer secret**. You'll need them in the next step.

# Create the Salesforce service in Cerb

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon in the top right of the list.

3. Select **Salesforce**.

4. Enter your Client ID and Client Secret.

5. Click the **Create** button.

# Link the connected account to Salesforce in Cerb

1. Navigate to **Search&nbsp;» Connected Accounts**.

2. Click the **(+)** icon in the top right of the list.

3. Select **Salesforce**.

4. Click the blue **Link to Salesforce** button.

5. Accept consent on Salesforce.

6. Click the **Save Changes** button.

It may take up to 10 minutes for your new app to be available in Salesforce. If you have trouble with the OAuth authentication step, wait a few minutes and try again.

# Examples

## Create an Account record

https://developer.salesforce.com/docs/atlas.en-us.api\_rest.meta/api\_rest/dome\_sobject\_create.htm

```
start:
  http.request/createAccount:
    output: http_response
    inputs:
      method: POST
      url: https://YOUR.SALESFORCE.DOMAIN.my.salesforce.com/services/data/v63.0/sobjects/Account/
      headers:
        Content-Type: application/json
      authentication: cerb:connected_account:salesforce
      body:
        Name: Testerson LLC
        NumberOfEmployees: 5
        Industry: Testing
    on_success:
      set:
        response@json: {{http_response.body}}
        http_response@json: null
```

## Get Account info

https://developer.salesforce.com/docs/atlas.en-us.api\_rest.meta/api\_rest/dome\_get\_field\_values.htm

```
start:
  http.request/createAccount:
    output: http_response
    inputs:
      method: GET
      url: https://YOUR.SALESFORCE.DOMAIN.my.salesforce.com/services/data/v63.0/sobjects/Account/ABCDEF12345?fields=Name,NumberOfEmployees,Industry
      headers:
        Content-Type: application/json
      authentication: cerb:connected_account:salesforce
    on_success:
      set:
        response@json: {{http_response.body}}
        http_response@json: null
```

## Update an Account record

https://developer.salesforce.com/docs/atlas.en-us.api\_rest.meta/api\_rest/dome\_update\_fields.htm

```
start:
  http.request/createAccount:
    output: http_response
    inputs:
      method: PATCH
      url: https://YOUR.SALESFORCE.DOMAIN.my.salesforce.com/services/data/v63.0/sobjects/Account/ABCDEF12345
      headers:
        Content-Type: application/json
      authentication: cerb:connected_account:salesforce
      body:
        BillingCity: Testerville
    on_success:
      set:
        response@json: {{http_response.body}}
        http_response@json: null
```

# Use the connected account in bot behaviors

You can use the connected account you just created to access the Saleforce REST API from bot behaviors in Cerb. This is typically accomplished using the **Execute HTTP Request** action from a bot, and selecting the connected account in the **Authentication:** section.

You can import the [Salesforce Bot](/packages/salesforce-bot/) package for a working example.

