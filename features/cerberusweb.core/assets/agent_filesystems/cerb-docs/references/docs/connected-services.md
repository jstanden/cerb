---
id: "docs-connected-services"
title: "Connected Services"
url: "https://cerb.ai/docs/connected-services/"
summary: "This page details the functionality of connected services in Cerb, which facilitate authentication, secure storage, and reuse of user credentials for various third-party services. It outlines the supported authentication methods, including Amazon Web Services, Bearer Tokens, Facebook Pages, HTTP Basic Auth, LDAP, OAuth 1.0a, OAuth 2.0, OpenID Connect, and SAML Identity Providers. Additionally, it mentions that plugins can introduce new service provider types and that these services can offer single sign-on (SSO) capabilities for worker logins from corporate directories. The page also notes that services may support creating multiple connected accounts for use in automations."
tags: ["docs"]
---
**Connected services** provide authentication, secure storage, and reuse of user credentials for arbitrary third-party services.

Various methods of authentication are supported:

- Amazon Web Services
- Bearer Tokens
- Facebook Pages
- HTTP Basic Auth
- LDAP
- OAuth 1.0a
- OAuth 2.0
- OpenID Connect Identity Provider
- SAML Identity Provider

Plugins can [implement new service provider types](/docs/plugins/extensions/points/cerb.connected_service.provider/).

Services may provide [single sign-on](/guides/) (SSO) functionality for authenticating worker logins from a corporate directory.

Services may also support the creation of multiple [connected accounts](/docs/connected-accounts/) for use in [automations](/docs/automations/).

