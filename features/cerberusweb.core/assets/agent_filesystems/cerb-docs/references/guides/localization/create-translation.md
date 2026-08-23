---
id: "guides-localization-create-translation"
title: "Translate Cerb to a new language"
url: "https://cerb.ai/guides/localization/create-translation/"
summary: "This page provides a comprehensive guide on translating Cerb's user interface into new languages using the Translation Editor plugin. It details the process of enabling the plugin, creating a new language, and translating text using the built-in translation editor. The guide also explains how to share translations in the TMX1 format, which can be imported by other Cerb users. Additionally, it offers a financial incentive for translation work, with a billing credit of $0.50 USD per phrase. The page includes step-by-step instructions for each part of the translation process, from enabling the plugin to exporting the completed translations. References to the TMX format are also provided for further understanding."
tags: ["guides"]
---
- [Introduction](#introduction)
- [Enable the Translation Editor plugin](#enable-the-translation-editor-plugin)
- [Create a new language](#create-a-new-language)
- [Translate text](#translate-text)
  - [Using the built-in translation editor](#using-the-built-in-translation-editor)

- [Sharing translations](#sharing-translations)
- [References](#references)

# Introduction

Cerb includes a plugin for translating the user interface into new languages. These translations are shared as `.xml` files in the TMX[1](#fn:tmx) format, which can be shared and [imported](/guides/localization/import-translation/) by other Cerb users.

The TMX format is pretty simple. Each `<tu>` tag defines a new phrase with the ID given in the `tuid` attribute. Within each phrase is any number of `<tuv>` translations that specify a language in the `xml:lang` attribute.

For example:

```
<tmx>
	<header creationtool="Cerb" creationtoolversion="8.1.4" srclang="en_US" />
	<body>
	 	<!-- ... -->
		<tu tuid="common.notspam">
			<tuv xml:lang="en_US">
				<seg>not spam</seg>
			</tuv>
			<tuv xml:lang="de_DE">
				<seg>Kein Spam</seg>
			</tuv>
		</tu>
		<!-- ... -->
	</body>
</tmx>
```

We store each translated language in its own TMX file, so there's only one `<tuv>` child within each `<tu>` phrase.

In Cerb, templates in plugins can use these phrases like:

```
{'common.notspam'|devblocks_translate|capitalize}
```

This way we display each phrase in the preferred language of each worker or contact.

In this guide, we'll cover the steps required to translate Cerb to a new language.

We offer a billing credit of **$0.50 USD per phrase** for translation work. At the time of writing, there are roughly **1,300 phrases** in Cerb. This credit is also available for maintaining existing translations as new versions of Cerb are released.

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

 

# Create a new language

1. Navigate to: **Setup&nbsp;» Configure&nbsp;» Translation Editor**

2. Click on the **Languages** button in the top left.

3. In **Add New Translation**, select the language you want to provide a new translation for.

4. If your new language is closely related to an existing language (e.g. British English&nbsp;» American English, Mexican Spanish&nbsp;» Castilian Spanish) then you can **Copy New Text From** that language. Otherwise, use the default of **leave blank**.

5. Click the **Save Changes** button.

# Translate text

## Using the built-in translation editor

1. Navigate to: **Setup&nbsp;» Configure&nbsp;» Translation Editor**

2. Use quick search to filter the worklist to phrases in your new language without a translation. For example:

```
lang:fr_FR mine:""
```

1. Cerb automatically provides the English version of each phrase. Translate that phrase into your language in the textbox below each entry.

2. After each page of translations is complete, click the **Save Changes** button. This will refresh the worklist with the next page of entries needing translation. You're done when there aren't any non-translated entries left.

# Sharing translations

To share your new translation:

1. Navigate to: **Setup&nbsp;» Configure&nbsp;» Translation Editor**

2. Use quick search to filter the worklist to phrases in your new language. For example:

```
lang:fr_FR
```

1. Click the **Export** button below the worklist.

2. Your browser will download the translation as an `.xml` file TMX format. You can share this file in email, include it in a pull request on GitHub, etc.

# References

1. Wikipedia: Translation Memory eXchange (TMX) - https://en.wikipedia.org/wiki/Translation\_Memory\_eXchange&nbsp;[↩](#fnref:tmx)

