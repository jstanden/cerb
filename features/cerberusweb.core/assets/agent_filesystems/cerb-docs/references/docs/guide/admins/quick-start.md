---
id: "docs-guide-admins-quick-start"
title: "Admin Quick Start"
url: "https://cerb.ai/docs/guide/admins/quick-start/"
summary: "This webpage serves as an administrative quick start guide for setting up a new instance of Cerb. It provides detailed instructions on personalizing the platform with a team logo, configuring both outbound and inbound email settings, and ensuring mail deliverability. The guide covers setting up mail transports, sender addresses, email signatures, and mail routing. It also includes steps for reviewing and setting up groups, testing mail deliverability, and managing worker permissions through roles. Additionally, it guides administrators on inviting workers, configuring mailboxes, and reviewing mail routing rules. The guide touches on configuring the scheduler, reviewing security considerations, and exploring available plugins to enhance Cerb's functionality. This comprehensive setup guide is intended for administrators performing the initial configuration of a fresh Cerb instance."
tags: ["docs"]
---
- [Add your logo](#add-your-logo)
- [Configure outbound email](#configure-outbound-email)
  - [Configure mail transports](#configure-mail-transports)
  - [Configure sender addresses](#configure-sender-addresses)
  - [Configure email signatures](#configure-email-signatures)
  - [Configure your default sender address](#configure-your-default-sender-address)

- [Configure inbound email](#configure-inbound-email)
  - [Configure instant delivery in Cerb Cloud](#configure-instant-delivery-in-cerb-cloud)
  - [Configure mailboxes](#configure-mailboxes)
  - [Configure mail routing](#configure-mail-routing)

- [Review groups](#review-groups)
- [Grant worker permissions with roles](#grant-worker-permissions-with-roles)
- [Invite workers](#invite-workers)
- [Configure incoming email](#configure-incoming-email)
- [Routing](#routing)
- [Scheduler](#scheduler)
- [Security](#security)
- [Plugins](#plugins)

This guide assumes that you have a fresh instance of Cerb and you're an administrator who is performing the initial configuration. If Cerb is already configured and you want to learn how to use it, you can [jump to the worker guide](/docs/home/).

# Add your logo

Let's personalize your copy of Cerb with your team's logo.

Navigate to **Setup&nbsp;» Configure&nbsp;» Branding**.

You can upload a light and dark version of your logo. If you only upload the light version, we'll use that image for both themes.

We recommend using the SVG format, but you can alternatively use a PNG, JPEG, or GIF.

You can change the **Browser Title** to set the label shown in browser tabs.

# Configure outbound email

Let's configure outbound email so your team can reply to your audience.

## Configure mail transports

**Mail transports** are used to deliver outgoing email to its destination.

There are many reasons you might want to use multiple transports:

- In development or test environments, you can use a **null** mail transport to prevent sending live email.

- If you manage multiple brands, each can use their own transport and sender reputation.

- You can use an official corporate mail server if required by policy.

- By configuring a backup mail transport, you can continue to serve customers during problems with your primary transport.

If you're using **Cerb Cloud**, we've already configured a mail transport for you named **Cerb Cloud SMTP**.

To configure mail transports, navigate to **Setup&nbsp;» Mail&nbsp;» Outgoing&nbsp;» Email Transports**.

## Configure sender addresses

You should configure each email address that you intend to send mail from. Each sender address can be linked to a specific mail transport.

To configure sender addresses, navigate to **Setup&nbsp;» Mail&nbsp;» Outgoing&nbsp;» Sender Addresses**.

You can also use **Search&nbsp;» Email Addresses** to edit an existing email address. Select the **We send email from this address** option at the bottom of the editor.

To make sure your email isn't marked as spam, configure SPF, DKIM, and DMARC records in DNS for all sender domains. We'll test this a little later.

If you're using **Cerb Cloud**, we set up the SPF, DKIM, and DMARC records for you on the default `@<instance>.cerb.email` sender domain. You can add `include:cerb.email` to the SPF records on your own domains. We can permit these domains and generate DKIM keys for you by request.

## Configure email signatures

You can configure multiple email signatures for outbound mail.

Navigate to **Search&nbsp;» Email Signatures**.

The owner of an email signature determines who can edit it. Signatures may be owned by Cerb (where only admins can edit it), or a specific group (where its managers can edit it).

Each email signature has a plaintext and HTML version. If you only create a plaintext version, we'll generate the HTML one for you.

Email signatures may contain **placeholders** that are automatically substituted for each outgoing message. For instance, you can use `{{first_name}}` to display the name of the worker who sent the message, and `{{title}}` to refer to their job title.

## Configure your default sender address

In situations where a specific sender address isn't configured, the default sender address is used. You can configure this from **Setup&nbsp;» Mail&nbsp;» Outgoing&nbsp;» Settings**.

# Configure inbound email

Now that you can send email, let's configure inbound email so that you can receive it.

## Configure instant delivery in Cerb Cloud

If you're using Cerb Cloud, you can configure instant email delivery by redirecting a copy of your mail to `support@INSTANCE.cerb.email`, where `INSTANCE` is your Cerb Cloud instance name.

You can also change the `support@` mailbox to anything you want.

If you do this, your new mail will show up instantly, and you won't need to configure any mailboxes in the next step.

You can also send email to these addresses directly to test inbound mail. For instance, if your Cerb Cloud instance is named `example`, then send a test message to `support@example.cerb.email`. A new message will show up in Cerb without any configuration required.

## Configure mailboxes

By default, Cerb uses **mailboxes** to receive new email messages. Each account is checked every few minutes.

To configure mailboxes, navigate to **Setup&nbsp;» Mail&nbsp;» Incoming&nbsp;» Mailboxes**.

Unless you're using a service like Gmail that archives messages rather than deleting them, you should always create a separate mailbox for Cerb to download a copy of your mail from. **Messages will be deleted once they are downloaded.**

Cerb supports the standard POP3 and IMAP protocols, with multiple forms of encryption.

We also support the emerging **XOAuth2** standard that major email providers like Gmail and Office365 are migrating to. This replaces vulnerable passwords with rotating, time-limited access tokens. You can refer to the [Authenticate a Gmail mailbox using IMAP and XOAUTH2](/guides/integrations/google/gmail-xoauth/) guide for an example implementation.

## Configure mail routing

New email messages can be delivered to groups of workers with flexible routing rules.

Navigate to **Setup&nbsp;» Mail&nbsp;» Incoming&nbsp;» Mail Routing**.

Mail routing rules are [automations](/docs/automations/) on the [mail.route](/docs/automations/events/mail.route/) event.

If a new message doesn't match any routing rules, it will be delivered to the default group specified at the top of **Setup&nbsp;» Mail&nbsp;» Incoming&nbsp;» Settings**.

# Review groups

You should set up a few groups to distribute work.

Navigate to **Search** » **Groups**

Review your [groups](/docs/groups/).

To add a new group, click the **(+)** icon in the blue bar of the worklist.

# Grant worker permissions with roles

Before we invite the rest of your team, you should establish the permissions that grant or restrict functionality for each worker.

Navigate to **Search**&nbsp;» **Roles**

In Cerb, worker permissions are managed using **roles**. Multiple roles can be assigned to a single worker, and a worker is granted a particular privilege if any of their roles permit it.

The _Default_ role was automatically created for you during installation. It simply grants all permissions to all workers. You'll probably want to adjust this depending on your needs. Even if workers are granted all permissions, they still must also be an administrator to enter **Setup** and perform global configuration.

# Invite workers

Now that you've set up your groups and roles, and verified that outgoing mail works, let's invite the rest of your team to use Cerb.

Navigate to **Search**&nbsp;» **Workers**

Click the **(+)** icon in the blue bar of the worklist to add new workers. At minimum, provide a first name, personal email address, and group memberships. If you leave the password field blank, then setup instructions will be sent to the worker's email address.

# Configure incoming email

Let's give these new workers something to do.

Navigate to **Setup&nbsp;» Mail&nbsp;» Incoming Mail&nbsp;» Mailboxes**

This page lists the mailboxes that Cerb checks for new messages.

Review your [mailboxes](/docs/setup/mail/mailboxes/).

If you're using **Cerb Cloud**, you can alternatively redirect your incoming mail to `support@<you>.cerb.email` for instant delivery. Replace `<you>` with the name of your instance. With this delivery method you won't need to set up a mailbox here.

Cerb deletes messages from your mailbox after it downloads them (unless the mail server prevents this behavior, like Google Apps). If this is not desirable, you should send a copy of all incoming email to a separate mailbox and add that to Cerb.

# Routing

Navigate to **Setup&nbsp;» Mail&nbsp;» Incoming Mail&nbsp;» Mail Routing**

Review your [mail routing rules](/docs/setup/mail/routing/).

# Scheduler

Navigate to **Setup**&nbsp;» **Configure**&nbsp;» **Scheduler**

Review the instructions for [configuring the scheduler](/docs/setup/configure/scheduler/).

If you're using **Cerb Cloud**, we handle this for you.

# Security

Review the [security considerations](/docs/security/).

# Plugins

Navigate to **Setup**&nbsp;» **Plugins**&nbsp;» **Installed Plugins**

This is where you'll find the available [plugins](/docs/plugins/) that expand Cerb's functionality.

