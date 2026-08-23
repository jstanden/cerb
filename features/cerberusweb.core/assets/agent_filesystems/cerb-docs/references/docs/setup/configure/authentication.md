---
id: "docs-setup-configure-authentication"
title: "Authentication"
url: "https://cerb.ai/docs/setup/configure/authentication/"
summary: "This page provides information on authentication methods for Cerb, focusing on Single Sign-on (SSO) and Multi-Factor Authentication (MFA). It explains how connected services that support SSO, such as OpenID Connect and SAML, can be used to allow workers to log in using their existing identities from platforms like G Suite and Salesforce. Additionally, it describes the MFA feature, which enhances security by allowing trusted devices to be remembered, thus requiring a security code only during new logins after a specified period."
tags: ["docs"]
---
 

# Authentication

## Single Sign-on (SSO)

If you have created [connected services](/docs/connected-services/) that support SSO (e.g. OpenID Connect, SAML), they will be displayed here. You can select those services to allow workers to log in to Cerb using their existing identities.

- [Authenticate worker single sign-on (SSO) from G Suite using SAML](/guides/sso/google-saml/)
- [Authenticate worker single sign-on (SSO) from Salesforce using OpenID Connect](/guides/sso/salesforce-openid/)

## Multi-Factor Authentication

When multi-factor authentication (MFA) is enabled for a worker's account, this setting allows their trusted devices to be "remembered" and a security code will only be requested during new logins once per the specified number of days.

