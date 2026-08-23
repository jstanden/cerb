---
id: "guides-localization-import-translation"
title: "Import a translation language pack"
url: "https://cerb.ai/guides/localization/import-translation/"
summary: "This page provides a comprehensive guide on importing a translation language pack into Cerb. It covers enabling the Translation Editor plugin, downloading and importing language packs, and configuring language preferences for both personal and other users' settings. The guide explains the format and naming conventions of language packs, which are shared as `.xml` files in the TMX1 format, and provides examples of language codes using ISO standards. Additionally, it offers instructions on how to enable the necessary plugin, download available language packs, and import them into the system. The page also includes references to relevant Wikipedia articles for further information on translation memory exchange and ISO language and country codes."
tags: ["guides"]
---
- [Introduction](#introduction)
- [Enable the Translation Editor plugin](#enable-the-translation-editor-plugin)
- [Download a language pack](#download-a-language-pack)
- [Import a language pack](#import-a-language-pack)
- [Configure the language](#configure-the-language)
  - [Setting your own language preference](#setting-your-own-language-preference)
  - [Modifying someone else's language preference](#modifying-someone-elses-language-preference)

- [References](#references)

# Introduction

The text within Cerb's interface [can be translated](/guides/localization/create-translation/) into any language.

**Translations** are shared as `.xml` files in the TMX[1](#fn:tmx) format.

Language packs use the following naming convention:

`cerb_lang_<language>_<country>.xml`

The `<language>` and `<country>` codes use ISO 639[2](#fn:iso-639) and ISO 3166[3](#fn:iso-3166) respectively.

For instance:

- **de\_DE**: German (Germany)
- **en\_US**: English (American)
- **en\_GB**: English (British)
- **es\_ES**: Spanish (Spain)
- **es\_MX**: Spanish (Mexico)
- **it\_IT**: Italian (Italy)

[Workers](/docs/workers/) and [contacts](/docs/contacts/) can each configure their own preferred language.

This guide walks through the process of installing a new language pack.

# Enable the Translation Editor plugin

First, make sure the Translation Editor plugin is enabled.

Navigate to: **Setup&nbsp;» Configure&nbsp;» Plugins&nbsp;» Installed Plugins**

Search for: `translation`

**_If the plugin is enabled:_**

You're all set!

**_If the plugin is disabled:_**

1. Click the **Configure** button.

2. Set the **Status** to **Enabled**.

3. Click the **Save Changes** button.

 

# Download a language pack

Cerb is distributed with the most popular language packs.

Right-click one of these translation links and select **Download**:

- German (Germany)
- English (British)
- Spanish (Spain)
- Italian (Italy)
- Dutch (Netherlands)
- Portuguese (Portugal)
- Russian (Russia)

**Can't find the language you're looking for?** We offer billing credits for [creating, maintaining, and sharing translations](/guides/localization/create-translation/).

# Import a language pack

You can now import the language pack with the translations plugin.

1. Navigate to **Setup&nbsp;» Configure&nbsp;» Translation Editor**.

2. Click the **Import** button.

3. Select the XML file you downloaded above.

4. Click the **Save Changes** button.

You should see your new language added to the translation worklist.

 

# Configure the language

### Setting your own language preference

1. Click on your name in the top right.

2. Select **Settings** from the menu.

3. On the **Settings** tab, in the **Localization** section, select a **Language**.

 

### Modifying someone else's language preference

1. Select a worker or contact record from the **Search** menu.

2. Edit their record.

3. Select a **Language**.

 

# References

1. Wikipedia: Translation Memory eXchange (TMX) - https://en.wikipedia.org/wiki/Translation\_Memory\_eXchange&nbsp;[↩](#fnref:tmx)

2. Wikipedia: ISO 639 - https://en.wikipedia.org/wiki/ISO\_639&nbsp;[↩](#fnref:iso-639)

3. Wikipedia: ISO 3166-1 alpha-2 - https://en.wikipedia.org/wiki/ISO\_3166-1\_alpha-2&nbsp;[↩](#fnref:iso-3166)

