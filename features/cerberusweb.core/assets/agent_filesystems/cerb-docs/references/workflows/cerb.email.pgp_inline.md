---
id: "workflows-cerb-email-pgpinline"
title: "PGP Inline Encryption"
url: "https://cerb.ai/workflows/cerb.email.pgp_inline/"
summary: "This page provides a comprehensive guide on implementing PGP Inline Encryption within Cerb, focusing on its introduction, installation, and usage. It explains how this workflow allows users to encrypt entire emails or specific parts, such as sensitive information, ensuring that recipients can decrypt the content using their preferred tools. The installation section details that this feature is integrated into Cerb 11.0+ and can be enabled through the workflow settings. The usage section covers adding public keys and sending encrypted messages, including step-by-step instructions for encrypting messages and selecting recipient keys. Additionally, the page offers a reference template for building a custom PGP Inline Encryption workflow, complete with detailed configuration and policy settings."
tags: ["workflows"]
---
- [Introduction](#introduction)
- [Installation](#installation)
- [Usage](#usage)
  - [Add public keys](#add-public-keys)
  - [Send encrypted messages](#send-encrypted-messages)

- [Reference](#reference)

# Introduction

This workflow adds inline PGP encryption when composing and replying to email.

When you encrypt an entire email message, your recipients must be using an email reader that supports encryption along with access to one of their PGP private keys.

With inline encryption, you can encrypt only part of a message and your recipients can decrypt it in the tool of their choice.

For instance, you may encrypt login credentials or payment information.

# Installation

This workflow is built into Cerb [11.0+](/releases/11.0/). It will automatically update.

You can enable it from **Search&nbsp;» Workflows&nbsp;» (+)&nbsp;» PGP Inline Encryption**.

# Usage

### Add public keys

You need a PGP public key for a recipient to send an encrypted message to them.

You can import these from **Search&nbsp;» PGP Public Keys&nbsp;» (+)**.

Here's ours:

```
-----BEGIN PGP PUBLIC KEY BLOCK-----
Comment: https://keybase.io/wgm
Version: Keybase Go 5.9.2 (darwin)

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
fofJE6UzEDu4i71xK84y9oQQRSqQPw==
=1rCz
-----END PGP PUBLIC KEY BLOCK-----
```

### Send encrypted messages

Open any ticket profile page from **Search&nbsp;» Tickets**.

Click the **Reply** button below an email message.

Click the new inline encryption icon in the reply toolbar:

 

Enter the message to encrypt and select one or more recipient public keys.

 

Click the blue **Continue** button at the bottom to paste the encrypted message into your reply.

 

# Reference

You can build your own PGP Inline Encryption workflow using this template as a reference.

Change occurrences of **cerb.email.pgp\_inline** to your own workflow identifier. Use a prefix based on a domain you own (e.g. `com.example.workflow`).

```
workflow:
  name: cerb.email.pgp_inline
  version: 2026-08-13T00:00:00Z
  description: Encrypt messages with PGP and paste them inline in outgoing email
  website: https://cerb.ai/workflows/cerb.email.pgp_inline/
  requirements:
    cerb_version: >=12.0 <12.1
    cerb_plugins: cerberusweb.core

records:
  automation/pgp_encrypt_interaction:
    fields:
      name: cerb.email.pgpInline.interaction
      extension_id: cerb.trigger.interaction.worker
      description: Encrypt messages with PGP and paste them inline in outgoing email
      script@raw:
        start:
          await:
            form:
              title: Encrypt message
              elements:
                textarea/prompt_message:
                  label: Message:
                  required@bool: yes

                sheet/prompt_recipient_keys:
                  label: Recipient public keys:
                  required@bool: yes
                  data:
                    automation:
                      uri: cerb:automation:cerb.data.records
                      inputs:
                        record_type: gpg_public_key
                  limit: 10
                  schema:
                    layout:
                      headings@bool: no
                      paging@bool: yes
                      filtering@bool: yes
                    columns:
                      selection/id:
                        params:
                          mode: multiple
                      card/_label:
                        params:
                          bold@bool: yes

          encrypt.pgp:
            output: encrypted_message
            inputs:
              message@key: prompt_message
              public_keys:
                ids@key: prompt_recipient_keys

          return:
            snippet@key: encrypted_message
      policy_kata@raw:
        commands:
          encrypt.pgp:
            allow: yes

  toolbar_section/reply_pgp:
    fields:
      name: Inline PGP Encryption
      priority: 10
      toolbar_name: mail.reply
      toolbar_kata@raw:
        interaction/encryptWithPGP:
          uri: cerb:automation:cerb.email.pgpInline.interaction
          tooltip: Encrypt with PGP
          icon: mail-lock
```
