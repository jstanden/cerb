---
id: "guides-integrations-ldap"
title: "Authenticate worker logins with an LDAP corporate directory"
url: "https://cerb.ai/guides/integrations/ldap/"
summary: "This page provides a comprehensive guide on how to authenticate worker logins in Cerb using an LDAP corporate directory. It covers the introduction to LDAP as a centralized authentication mechanism, the necessary requirements for setting up LDAP with Cerb, and detailed steps to create an LDAP service within Cerb. The guide also explains how to enable single sign-on (SSO) with LDAP, allowing workers to log in using their LDAP credentials. Additionally, it outlines the process for logging in with LDAP, including handling two-factor authentication and using multiple LDAP services for different corporate directories. References are provided for further reading on LDAP."
tags: ["guides"]
---
- [Introduction](#introduction)
- [Requirements](#requirements)
- [Create an LDAP service in Cerb](#create-an-ldap-service-in-cerb)
- [Enable single sign-on with LDAP](#enable-single-sign-on-with-ldap)
- [Log in with LDAP](#log-in-with-ldap)
- [References](#references)

# Introduction

Enterprises commonly store and share contact information throughout their organization using an open industry standard called **Lightweight Directory Access Protocol (LDAP)** [1](#fn:ldap). This can serve as a corporate email and telephone directory; but more importantly, it can also provide a centralized authentication mechanism for various applications and services.

Cerb can use LDAP to authenticate worker logins. This guide will walk through the process of configuring this integration.

 

# Requirements

If you're self-hosting Cerb, make sure the `ldap` PHP extension is enabled in your environment.

If you're on Cerb Cloud, we've already done this for you.

# Create an LDAP service in Cerb

The LDAP integration only needs a [connected service](/docs/connected-services/).

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon above the worklist to add a new service.

3. Enter the following service details: 
  - Name: `LDAP`
  - URI: `ldap`
  - Type: **LDAP**

4. Enter your LDAP server details: 
  - Host: (your LDAP server host; e.g. ldap.example.com)
  - Port: (your LDAP server port; e.g. 389)
  - Bind DN: (the read-only distinguished name to authenticate as)
  - Bind Password: (the password to use for authentication)
  - Search context: (the distinguished name for searching employee accounts)
  - Email field: (the email field on employee accounts)
  - First name field: (the given name field on employee accounts)
  - Last name field: (the surname field on employee accounts)

 
5. Click the **Save Changes** button.

# Enable single sign-on with LDAP

1. Navigate to **Setup&nbsp;» Security&nbsp;» Authentication**.

2. Check the box next to your new LDAP service:

3. Click the **Save Changes** button.

# Log in with LDAP

When LDAP is enabled as a single sign-on identity provider, a button will appear at the top of the login page.

 

Clicking this button will prompt for the worker's email address and password from the LDAP directory, rather than from their Cerb account.

If you want workers to only authenticate using SSO, you can disappear their Cerb password by editing their record.

When a worker authenticates using LDAP, one of the email addresses on their Cerb account must match the email address from their LDAP record.

If a worker has [two-factor authentication](/guides/security/two-factor-auth/) enabled, they'll be prompted for their security code after authenticating with their password.

You can use multiple LDAP services to authenticate workers from different corporate directories.

# References

1. Wikipedia: Lightweight Directory Access Protocol (LDAP) - https://en.wikipedia.org/wiki/Lightweight\_Directory\_Access\_Protocol&nbsp;[↩](#fnref:ldap)

