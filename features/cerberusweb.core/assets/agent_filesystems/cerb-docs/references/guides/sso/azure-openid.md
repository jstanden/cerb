---
id: "guides-sso-azure-openid"
title: "Authenticate worker single sign-on (SSO) from Microsoft Azure AD using OpenID Connect"
url: "https://cerb.ai/guides/sso/azure-openid/"
summary: "This page provides a comprehensive guide on setting up single sign-on (SSO) for Cerb using Microsoft Entra Active Directory (AD) through the OpenID Connect (OIDC) standard. It details the steps to configure Azure AD, including creating an OAuth app for Cerb, generating a client secret, and setting up optional claims. The guide also covers configuring Cerb to connect with Azure, enabling SSO, and the process for logging in using Entra AD credentials. This setup allows Cerb workers to authenticate seamlessly with their existing Microsoft accounts, enhancing security and user convenience by potentially disabling password-based logins."
tags: ["guides"]
---
 

- [Introduction](#introduction)
- [Configure Entra AD](#configure-entra-ad)
  - [Create OAuth app for Cerb](#create-oauth-app-for-cerb)
  - [Create client secret](#create-client-secret)
  - [Configure optional claims](#configure-optional-claims)

- [Configure Cerb](#configure-cerb)
  - [Create a connected service for Azure](#create-a-connected-service-for-azure)
  - [Configure SSO](#configure-sso)
  - [Log in](#log-in)

# Introduction

This guide demonstrates how to enable one-click single sign-on (SSO) for Cerb workers by authenticating against existing Microsoft Entra AD (Active Directory) accounts using the OpenID Connect (OIDC) standard.

The email address for each account in Entra AD will need to be associated with a worker record in Cerb. You can also disable password-based logins for those accounts.

# Configure Entra AD

Log in to the Entra Portal.

### Create OAuth app for Cerb

1. Select **Applications&nbsp;» App registrations** from the left menu.

2. Click the **New registration** button at the top.

3. Click the blue **Register** button at the bottom.

### Create client secret

1. In the new app registration, navigate to **Certificates & secrets**.

2. Click the **New client secret** button in the **Client secrets** section near the middle of the page.

3. Click the blue **Add** button.

4. Copy the **Value** (not the **Secret ID**).

### Configure optional claims

1. In the new app registration, navigate to **Token configuration**.

2. Click the **Add optional claim** button.

3. Select **ID** for **Token type**.

4. Check the box to the left of the `email` claim.

5. Click the blue **Add** button at the bottom of the claim list.

# Configure Cerb

Log in to Cerb as an administrator.

### Create a connected service for Azure

1. Navigate to **Search&nbsp;» Connected Services** and click the **(+)** icon above the worklist.

2. Click the **Run Discovery** button.

3. Click the **Save Changes** button.

### Configure SSO

1. Navigate to **Setup&nbsp;» Security&nbsp;» Authentication**.

2. Check **Azure AD**.

3. Click the **Save Changes** button.

### Log in

1. Visit the login form in Cerb.

2. Click the **Azure AD** button.

3. Log in using your Microsoft ID.

4. Accept consent.

5. You should be logged into Cerb as the worker associated with your Microsoft email address.

