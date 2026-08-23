---
id: "guides-sso-okta-openid"
title: "Authenticate worker single sign-on (SSO) from Okta using OpenID Connect"
url: "https://cerb.ai/guides/sso/okta-openid/"
summary: "This page provides a comprehensive guide on setting up single sign-on (SSO) for Cerb workers using Okta as an identity provider through the OpenID Connect (OIDC) standard. It details the steps to configure Okta as an OpenID Connect identity provider, including creating an app integration and obtaining necessary credentials like the Client ID, Client Secret, and Issuer URL. The guide also explains how to configure Cerb to authenticate with Okta by creating an OpenID service, setting up SSO, and logging in. It emphasizes the need to associate Okta email addresses with Cerb worker records and offers the option to disable password-based logins for enhanced security."
tags: ["guides"]
---
 

- [Introduction](#introduction)
- [Configure Okta as an OpenID Connect identity provider](#configure-okta-as-an-openid-connect-identity-provider)
  - [Configure your identity provider](#configure-your-identity-provider)

- [Configure Cerb for authentication with Okta](#configure-cerb-for-authentication-with-okta)
  - [Create an OpenID service for Okta](#create-an-openid-service-for-okta)
    - [Configure SSO](#configure-sso)
    - [Log in](#log-in)

# Introduction

This guide demonstrates how to enable one-click single sign-on (SSO) for Cerb workers by authenticating against existing Okta accounts using the OpenID Connect (OIDC) standard.

The email address for each account in Okta will need to be associated with a worker record in Cerb. You can also disable password-based logins for those accounts.

# Configure Okta as an OpenID Connect identity provider

## Configure your identity provider

1. Log in to Okta as an administrator.

2. Click the **Admin** button in the top menu.

3. Expand the **Applications** menu in the left sidebar.

4. Click **Applications**.

5. Click the blue **Create App Integration** button at the top.

6. Select **OIDC - OpenID Connect**.

7. Select **Web Application**.

8. Click the blue **Next** button in the bottom right.

9. Enter the following details:

10. Click the blue **Save** button.

11. In the application settings screen, scroll down and copy the **Client ID** and **Client Secret**.

12. Make a note of your **Issuer** URL. You'll need it below. This is typically your Okta subdomain (e.g. `https://example.okta.com`).

13. Assign the application to groups or users.

# Configure Cerb for authentication with Okta

Log in to Cerb as an administrator.

## Create an OpenID service for Okta

Navigate to **Setup&nbsp;» Packages&nbsp;» Import**.

Paste the following package:

```
{
  "package": {
    "name": "Okta OpenID Connect Provider",
    "revision": 1,
    "requires": {
      "cerb_version": "9.5.0",
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
            "placeholder": "(paste your Issuer URL from Okta)"
          }
        }
      ]
    }
  },
  "records": [
    {
      "uid": "service_okta",
      "_context": "connected_service",
      "name": "Okta",
      "uri": "okta-oidc",
      "extension_id": "cerb.service.provider.oidc",
      "params": {
        "client_id": "{{{prompt_client_id}}}",
        "client_secret": "{{{prompt_client_secret}}}",
        "scope": "openid email",
        "issuer": "{{{prompt_issuer_url}}}",
        "authorization_url": "{{{prompt_issuer_url}}}/oauth2/v1/authorize",
        "access_token_url": "{{{prompt_issuer_url}}}/oauth2/v1/token",
        "userinfo_url": "{{{prompt_issuer_url}}}/oauth2/v1/userinfo",
        "jwks_url": "{{{prompt_issuer_url}}}/oauth2/v1/keys"
      }
    }
  ]
}
```

Click the **Import** button.

Enter your client ID, client secret, and issuer URL from Okta.

Click the **Import** button again.

### Configure SSO

1. Navigate to **Setup&nbsp;» Security&nbsp;» Authentication**.

2. Check **Okta**.

3. Click the **Save Changes** button.

### Log in

1. Visit the login form in Cerb.

2. Click the **Okta** button.

3. Log in using your Okta ID.

4. Accept consent.

5. You should be logged into Cerb as the worker associated with your Okta email address.

