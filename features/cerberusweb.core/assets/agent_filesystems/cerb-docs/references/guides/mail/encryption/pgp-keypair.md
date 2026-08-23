---
id: "guides-mail-encryption-pgp-keypair"
title: "Generate a PGP key-pair for encryption"
url: "https://cerb.ai/guides/mail/encryption/pgp-keypair/"
summary: "This page provides a comprehensive guide on generating and managing PGP key-pairs for encryption within Cerb. It explains that Cerb has eliminated the need for the GnuPG PHP extension, allowing PGP key-pairs to be generated directly in the browser, which supports various key lengths and multiple user IDs. The guide details the steps for creating a new key-pair, including selecting key length and adding user IDs, and emphasizes the importance of choosing the appropriate key strength based on the sensitivity of the information. Additionally, it covers how to share the public key by copying and distributing it to those who need to send encrypted messages, highlighting that the public key is not confidential and can be shared widely."
tags: ["guides"]
---
- [Introduction](#introduction)
- [Generating a new key-pair](#generating-a-new-key-pair)
- [Sharing your public key](#sharing-your-public-key)

# Introduction

Cerb no longer requires (or uses) the GnuPG PHP extension for PGP email encryption, decryption, signing, or verifying. This makes it easier to use Cerb + PGP in more environments, including Windows.

PGP key-pairs can now be generated entirely in the browser. These support variable key lengths and multiple user IDs (UIDs), and follow the best practice of separate signing and encryption subkeys.

This simplifies PGP setup for new users who don't have an existing private key.

# Generating a new key-pair

1. Navigate to **Search&nbsp;» show all&nbsp;» PGP Private Keys**.

2. Click the **(+)** icon in the right of the gray bar above the worklist.

3. Select the **Create** tab.

4. On the **Key Length**, the default of `2048` bits is reasonably strong, faster, and more widely compatible. A `4096` bit key is stronger, a little slower to use, and may not work on some platforms; but it will be effective for a longer duration. You can make a decision based on the sensitivity of the information you'll be encrypting.

5. On **User IDs**, enter the name and email address of each identity you want covered by this key. You can sign and encrypt messages using any key whether it matches the `From:` sender or not, but those that match are generally more trusted.

6. Once done, click the **Create** button.

You now have a private key (decrypt/sign) and a public key (encrypt/verify).

# Sharing your public key

1. Navigate to **Search&nbsp;» show all \> PGP Public Keys**.

2. Open the card for your new public key.

3. If you don't have a **Public Key** widget, you can click **Add Widget** at the bottom of the card popup and import this one:

```
{
		"widget": {
				"uid": "card_widget_pgp_ascii",
				"_context": "cerb.contexts.card.widget",
				"name": "Public Key",
				"record_type": "cerberusweb.contexts.gpg_public_key",
				"extension_id": "cerb.card.widget.sheet",
				"pos": "4",
				"width_units": "4",
				"zone": "content",
				"extension_params": {
						"data_query": "type:worklist.records\r\nof:gpg_public_key\r\nquery:(\r\n id:{{record_id}}\r\n limit:1\r\n sort:[id]\r\n)\r\nformat:dictionaries",
						"cache_secs": "",
						"placeholder_simulator_yaml": "",
						"sheet_yaml": "layout:\r\n style: fieldset\r\n headings: false\r\n paging: false\r\ncolumns:\r\n- text:\r\n key: _label\r\n label: Label\r\n params:\r\n value_template: |\r\n <pre>\r\n {{key_text}}\r\n </pre>\r\n- "
				}
		}
}
```

1. Copy the public key text and share it with anyone who needs to send you encrypted messages. The public key is not a secret. You can upload it to key exchange servers or post it on your website.

