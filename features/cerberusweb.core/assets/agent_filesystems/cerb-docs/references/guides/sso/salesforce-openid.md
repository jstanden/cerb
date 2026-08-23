---
id: "guides-sso-salesforce-openid"
title: "Authenticate worker single sign-on (SSO) from Salesforce using OpenID Connect"
url: "https://cerb.ai/guides/sso/salesforce-openid/"
summary: "This webpage provides a comprehensive guide on setting up single sign-on (SSO) for Cerb workers using Salesforce as an identity provider through the OpenID Connect (OIDC) standard. It details the steps to configure Salesforce as an OpenID Connect identity provider, including setting up a connected app for Cerb and obtaining necessary OAuth credentials. The guide also explains how to configure Cerb to authenticate with Salesforce by creating an OpenID service and enabling SSO. The process involves associating Salesforce accounts with Cerb worker records and potentially disabling password-based logins for enhanced security. The guide ensures a seamless login experience for users by allowing them to authenticate using their Salesforce credentials."
tags: ["guides"]
---
- [Introduction](#introduction)
- [Configure Salesforce as an OpenID Connect identity provider](#configure-salesforce-as-an-openid-connect-identity-provider)
  - [Configure your identity provider](#configure-your-identity-provider)
  - [Create a connected app for Cerb](#create-a-connected-app-for-cerb)
    - [Basic Information](#basic-information)
    - [API (Enable OAuth Settings)](#api-enable-oauth-settings)
    - [Copy the OAuth credentials](#copy-the-oauth-credentials)

- [Configure Cerb for authentication with Salesforce](#configure-cerb-for-authentication-with-salesforce)
  - [Create an OpenID service for Salesforce](#create-an-openid-service-for-salesforce)
    - [Configure SSO](#configure-sso)
    - [Log in](#log-in)

# Introduction

This guide demonstrates how to enable one-click single sign-on (SSO) for Cerb workers by authenticating against existing Salesforce accounts using the OpenID Connect (OIDC) standard.

The email address for each account in Salesforce will need to be associated with a worker record in Cerb. You can also disable password-based logins for those accounts.

# Configure Salesforce as an OpenID Connect identity provider

## Configure your identity provider

1. Log in to Salesforce as an administrator.

2. Click **Setup** in the top right.

3. In the left sidebar, navigate to **Administer&nbsp;» Security Controls&nbsp;» Identity Provider**.

4. Follow the prompts to configure your identity provider if you haven't already.

5. Make a note of your **Issuer** URL. You'll need it later.

## Create a connected app for Cerb

1. Click **Setup** in the top right.

2. In the left sidebar, navigate to **Build&nbsp;» Create&nbsp;» Apps**.

3. In the **Connected Apps** section, click the **New** button.

### Basic Information

- Connected App Name: `Cerb`
- API Name: `cerb`
- Contact Email: (your team's email address)

### API (Enable OAuth Settings)

1. Enter the following details: 
  - Enable OAuth Settings: [**x**]
  - Callback URL: `https://YOUR-CERB-HOST/sso/salesforce-oidc`
  - Select OAuth Scopes:

  - Access your basic information (id, profile, email, address, phone)
  - Allow access to your unique identifier (openid) \* Require Secret for Web Server Flow: [**x**]

2. Click the **Save** button at the bottom of the page.

3. Click the **Continue** button.

### Copy the OAuth credentials

Make a note of the **Consumer Key** (Client ID) and **Consumer Secret** (Consumer Secret) for your new app. You'll need them in Cerb.

At this point you'll have to wait up to 10 minutes to test the integration, so let's work on configuring Cerb.

# Configure Cerb for authentication with Salesforce

Log in to Cerb as an administrator.

## Create an OpenID service for Salesforce

Navigate to **Setup&nbsp;» Packages&nbsp;» Import**.

Paste the following package:

```
{
  "package": {
    "name": "Salesforce OpenID Connect Provider",
    "revision": 1,
    "requires": {
      "cerb_version": "9.1.0",
      "plugins": []
    },
    "configure": {
      "placeholders": [],
      "prompts": [
        {
          "type": "text",
          "label": "Client ID",
          "key": "prompt_client_id",
          "params": {
            "default": "",
            "placeholder": "(paste your Client ID)"
          }
        },
        {
          "type": "text",
          "label": "Client Secret",
          "key": "prompt_client_secret",
          "params": {
            "default": "",
            "placeholder": "(paste your Client Secret)"
          }
        },
        {
          "type": "text",
          "label": "Issuer URL",
          "key": "prompt_issuer_url",
          "params": {
            "default": "",
            "placeholder": "(paste your Issuer URL from Salesforce)"
          }
        }
      ]
    }
  },
  "records": [
    {
      "uid": "service_salesforce",
      "_context": "connected_service",
      "name": "Salesforce",
      "uri": "salesforce-oidc",
      "extension_id": "cerb.service.provider.oidc",
      "params": {
        "client_id": "{{{prompt_client_id}}}",
        "client_secret": "{{{prompt_client_secret}}}",
        "scope": "openid profile",
        "issuer": "{{{prompt_issuer_url}}}",
        "authorization_url": "{{{prompt_issuer_url}}}/services/oauth2/authorize",
        "access_token_url": "{{{prompt_issuer_url}}}/services/oauth2/token",
        "userinfo_url": "{{{prompt_issuer_url}}}/services/oauth2/userinfo",
        "jwks_url": "{{{prompt_issuer_url}}}/id/keys"
      }
    }
  ]
}
```

Click the **Import** button.

Enter your client ID, client secret, and issuer URL from Salesforce.

Click the **Import** button again.

### Configure SSO

1. Navigate to **Setup&nbsp;» Security&nbsp;» Authentication**.

2. Check **Salesforce**.

3. Click the **Save Changes** button.

### Log in

1. Visit the login form in Cerb.

2. Click the **Salesforce** button.

3. Log in using your Salesforce ID.

4. Accept consent.

5. You should be logged into Cerb as the worker associated with your Salesforce email address.

