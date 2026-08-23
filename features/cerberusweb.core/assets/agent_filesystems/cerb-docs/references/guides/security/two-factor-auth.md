---
id: "guides-security-two-factor-auth"
title: "Configure two-factor authentication"
url: "https://cerb.ai/guides/security/two-factor-auth/"
summary: "This page provides a comprehensive guide on configuring two-factor authentication (2FA) for Cerb to enhance account security. It emphasizes the importance of multi-factor authentication in protecting against cybercrime and outlines best practices for password management. The guide details the process of setting up 2FA, including configuring policies, using popular TOTP apps like 1Password, Google Authenticator, and Authy, and enabling the 'remember trusted devices' feature. It also covers the steps for logging in with 2FA and the benefits of using 1Password for generating one-time passwords. The page underscores the critical role of 2FA in safeguarding online accounts and provides references for further reading on password strength and authentication methods."
tags: ["guides"]
---
- [Introduction](#introduction)
- [Configuring the two-factor authentication policy](#configuring-the-two-factor-authentication-policy)
- [Setting up two-factor authentication](#setting-up-two-factor-authentication)
- [Configuring 1Password to generate one-time passwords](#configuring-1password-to-generate-one-time-passwords)
- [Logging in with two-factor authentication](#logging-in-with-two-factor-authentication)
  - [Remembering trusted devices](#remembering-trusted-devices)

- [References](#references)

# Introduction

A common target of cybercrime is authentication details for online accounts – usernames, email addresses, passwords, answers to account recovery questions, etc. With this information, a criminal can assume your identity and cause you a lot of lasting damage.

You should already be following the best practices for passwords, including:

- Use high entropy passwords[1](#fn:xkcd-passwords). That's a fancy way of saying your password should be something difficult for computers to guess – even when there are many machines working together for a significant amount of time. Don't use dictionary words, family member names, important dates, etc.
- Don't reuse passwords between accounts. Otherwise, if one account is compromised then they all are vulnerable.
- Use an encrypted password manager[2](#fn:password-manager) that is routinely security audited. This helps you generate strong passwords without having to remember them. You'll have access to your passwords on multiple devices, and through browser plugins.

Even when you follow these guidelines, it is possible for your password to be disclosed if an app or service itself is compromised, or if you're tricked into entering your password into a fake form (i.e. "phishing").

That's where an additional best practice becomes critically important:

- Use **multi-factor authentication (MFA)** [3](#fn:2fa).

Multi-factor authentication means proving your identity with multiple methods ("factors"), which significantly increases the difficulty for someone trying to break into your accounts.

A common variant of multi-factor authentication is referred to as **two-factor authentication (2FA)**, which is generally comprised of _"something you know"_ (an intangible secret like your password) and _"something you have"_ (a physical object like your mobile phone, a small hardware device on your keychain, biometrics, etc).

Unlike your password, the second factor is something that should constantly change without your intervention. In the case of an app on your mobile phone, or a hardware device on your keychain, a new random number of a certain length (e.g. 4-8 digits) is usually generated every 30 seconds. Your device and the server are synchronized once in secret with regards to how this **time-based one-time password (TOTP)** is created.

If someone discovers your password, they most likely won't also have access to your mobile phone. In fact, there's a good chance that the attacker will be on the other side of the world.

Likewise, if someone steals your mobile phone, and somehow manages to unlock or decrypt it, they most likely will not have access to your passwords.

Without both factors of authentication, your accounts remain protected.

While some services deliver TOTP codes to your mobile device through SMS rather than an app, this is strongly discouraged. A thief or other nearby attacker can often view text messages on your phone's lock screen without the password. There are also reported cases of attackers rerouting a target's mobile phone service to steal SMS messages, or intercepting SMS messages in transit through other means.

We highly recommend securing your online accounts with two-factor authentication wherever possible. This certainly includes Cerb, where we provide built-in support for two-factor authentication.

One popular mobile app for generating TOTP codes is Google Authenticator[4](#fn:google-auth). It's available for both iOS and Android devices.

Another is Authy[5](#fn:authy) from Twilio. It's available for iOS and Android mobile devices, Windows/Mac computers, and the Chrome browser.

Our favorite is 1Password[6](#fn:1password), which we use as a personal password manager, team-based password manager, and TOTP generator.

In this guide, we'll walk through the process of linking Cerb to several different popular TOTP apps to improve the security of worker accounts.

 

# Configuring the two-factor authentication policy

By default, two-factor authentication is optional and workers can enable it in from their account settings.

Alternatively, administrators may require that all workers (or any subset of them) use two-factor authentication. We highly recommend this, and it can be quickly enabled with bulk update on a [worklist](/docs/worklists/) of worker records.

Administrators may also determine if workers are able use the "remember this trusted device" feature, and for how long. This is configured in **Setup&nbsp;» Security&nbsp;» Authentication**.

# Setting up two-factor authentication

If two-factor authentication is required but not configured for a particular worker, they will be required to set it up after entering their password. When 2FA is optional, they will go through the same process if they enable it from their worker settings.

First, a secret code (i.e. "seed") is displayed as both text and a QR code image (which simplifies setup in many apps).

 

The worker now has a choice of which app they want to use for creating one-time passwords:

- 1Password
- Authy
- Google Authenticator

If using **1Password** (our recommendation), jump ahead to the [Setting up 1Password to generate one-time passwords](#setting-up-1password-to-generate-one-time-passwords) section below.

For **Google Authenticator** and **Authy**, after installing the respective app on your smartphone, open it and follow the app's instructions to setup a new account.

Once the worker enters the correct six-digit number from their app, two-factor authentication is enabled and will be required for future logins.

# Configuring 1Password to generate one-time passwords

If you are already a user of 1Password, then you can use it for generating one-time passwords without the need for a standalone TOTP app (like Google Authenticator or Authy).

1. Create or edit an entry for Cerb in 1Password, and then click on the **(…)** icon.

2. Select the option for **One-Time Password** in the menu:

3. Click the **QR code** icon to bring up the scanner:

4. Move the scanner window over top of the QR code in your browser. When it turns green, the scan is successful and one-time passwords will be properly configured.

5. Enter the six digit number from 1Password into Cerb.

6. Click **Continue** to finish the setup.

# Logging in with two-factor authentication

When a worker logs in with a password, and two-factor authentication is enabled, they are asked to enter their security code from their app.

An application like 1Password will automatically copy the security code to your clipboard.

When the security code is entered properly, the worker is logged in. After multiple failures, the login process is aborted and the worker's account may be temporarily locked out to counteract "brute force" attacks.

 

### Remembering trusted devices

If enabled by an administrator, a worker can optionally choose to remember trusted devices for a set number of days (1-60). This will only ask for a security code once within the given time period for devices that have successfully authenticated. It will still prompt for the security code from unrecognized devices.

The "remember trusted devices" feature is implemented with an encrypted browser cookie. You may still be prompted to enter a security code if you use a different browser on a trusted device, if you delete your cookies, or if you use private browsing.

# References

1. XKCD: Password strength - https://xkcd.com/936/&nbsp;[↩](#fnref:xkcd-passwords)

2. Wikipedia: Password manager - https://en.wikipedia.org/wiki/Password\_manager&nbsp;[↩](#fnref:password-manager)

3. Wikipedia: Multi-factor authentication - https://en.wikipedia.org/wiki/Multi-factor\_authentication&nbsp;[↩](#fnref:2fa)

4. Google Authenticator - http://m.google.com/authenticator&nbsp;[↩](#fnref:google-auth)

5. Authy - https://authy.com&nbsp;[↩](#fnref:authy)

6. 1Password - https://1password.com&nbsp;[↩](#fnref:1password)

