---
id: "guides-sso-google-saml"
title: "Authenticate worker single sign-on (SSO) from G Suite using SAML"
url: "https://cerb.ai/guides/sso/google-saml/"
summary: "This webpage provides a comprehensive guide on setting up single sign-on (SSO) for Cerb using G Suite accounts through the SAML (Security Assertion Markup Language) protocol. It details the step-by-step process of configuring G Suite to create a SAML app, including obtaining Google IdP information, setting up service provider details, and enabling the service for all users. The guide also covers configuring Cerb to authenticate with G Suite SAML by creating a SAML service and setting up SSO for worker logins. Finally, it explains how users can log in to Cerb using their G Suite credentials, enabling seamless access with a single click as long as they remain logged into G Suite."
tags: ["guides"]
---
- [Introduction](#introduction)
- [Configure G Suite for SAML](#configure-g-suite-for-saml)
  - [Create a SAML app](#create-a-saml-app)
    - [Google IdP Information](#google-idp-information)
    - [Basic information for your Custom App](#basic-information-for-your-custom-app)
    - [Service Provider Details](#service-provider-details)
    - [Attribute Mapping](#attribute-mapping)
    - [Setting up SSO for Cerb](#setting-up-sso-for-cerb)

  - [Enabling the service in G Suite](#enabling-the-service-in-g-suite)

- [Configure Cerb for authentication with G Suite SAML](#configure-cerb-for-authentication-with-g-suite-saml)
  - [Create a SAML service in Cerb](#create-a-saml-service-in-cerb)
  - [Configure SSO for worker logins](#configure-sso-for-worker-logins)

- [Log in to Cerb using G Suite](#log-in-to-cerb-using-g-suite)

# Introduction

This guide demonstrates how to enable one-click single sign-on (SSO) for Cerb workers by authenticating against existing G Suite accounts using the SAML (Security Assertion Markup Language) standard.

The email address for each account in G Suite will need to be associated with a worker record in Cerb. You can also disable password-based logins for those accounts.

# Configure G Suite for SAML

## Create a SAML app

1. Log in to Google Admin as an administrator: https://admin.google.com/

2. Click **Apps**.

3. Click **Web and mobile Apps**.

4. Click **Add app**, then **Add custom SAML app**.

### Google IdP Information

1. Copy the **SSO URL**, **Entity ID**, and **Certificate** for use in Cerb. You'll need to download the certificate and open it in a text editor to copy/paste it. Optionally, you can download the IdP metadata file.

2. Click the blue **Next** link in the bottom right of the popup.

### Basic information for your Custom App

1. Enter the following details: 
  - Application Name: `Cerb SSO`
  - Upload logo: https://cerb.ai/assets/cerb\_mascot.png

2. Click the blue **Next** link in the bottom right of the popup.

### Service Provider Details

1. Enter the following details: 
  - ACS URL: `https://YOUR-CERB-HOST/sso/gsuite`
  - Entity ID: `https://YOUR-CERB-HOST/sso/gsuite/metadata`
  - Signed Response: [**x**]
  - Name ID: **Basic Information**&nbsp;» **Primary Email**
  - Name ID Format: **EMAIL**

Replace YOUR-CERB-HOST above with your own hostname (e.g. cerb.example).

2. Click the blue **Next** link in the bottom right of the popup.

### Attribute Mapping

Click the blue **Finish** link in the bottom right of the popup.

### Setting up SSO for Cerb

Click the blue **OK** link in the bottom right of the popup.

## Enabling the service in G Suite

1. Click the **Edit Service** link in the top right of the gray header.

2. Select **ON for everyone**.

3. Click the **Save** button in the bottom right.

# Configure Cerb for authentication with G Suite SAML

## Create a SAML service in Cerb

1. In Cerb, navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon above the worklist.

3. Enter the following details: 
  - Name: `G Suite`
  - URI: `gsuite`
  - Type: **SAML Identity Provider**

4. Enter the SAML details from G Suite above: **SSO URL**, **Entity ID**, and **X.509 Certificate**.

5. Click the **Save Changes** button.

## Configure SSO for worker logins

1. Navigate to **Setup&nbsp;» Security&nbsp;» Authentication**.

2. In the **Single Sign-on (SSO)** section, check the box for **G Suite**.

3. Click the **Save Changes** button.

# Log in to Cerb using G Suite

1. Log out of Cerb.

2. At the top of the login form, in the **Log in with your identity** section, click the **G Suite** button.

3. If you aren't signed in to your G Suite account you'll need to authenticate. Otherwise you shouldn't need to do anything.

4. If everything is configured properly, you'll be signed in to Cerb. As long as you remain logged in to G Suite you can sign in to Cerb in a single click from the login form.

