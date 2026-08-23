---
id: "docs-setup-configure-branding"
title: "Branding"
url: "https://cerb.ai/docs/setup/configure/branding/"
summary: "This page provides guidance on customizing the branding of a Cerb instance. It covers how to upload and manage an organization's logo, including resetting to the default logo if needed. The page also explains how to configure the browser title and favicon URL to enhance the user experience. Additionally, it offers instructions on using a custom stylesheet to modify the interface's appearance, with specific tips on targeting the logo for responsiveness and interactivity. The document includes examples of referencing uploaded logos and using external URLs for logo images."
tags: ["docs"]
---
 

# Logo

You can personalize your Cerb instance by uploading your organization's logo.

In the **Logo** section, click the edit button below the current logo and select an image file from your machine.

To reset back to the default logo, click the remove button below the current logo image.

# Settings

The **Browser Title** is the label you see on browser tabs and saved bookmarks by default.

You can configure your **Favicon URL** from your organization's website. This is the icon that people see when they add links to their bookmarks.

For example: `http://example.com/favicon.ico`

# Custom Stylesheet

You can provide your own stylesheet to modify the overall _"look and feel"_ of Cerb's interface.

Using the `#cerb-logo` selector you can target the logo. This is useful if you want to add responsiveness or interactivity – like using a smaller logo image on narrow screens and a higher resolution image on retina displays.

To reference a logo you previously uploaded, use:

```
#cerb-logo {
  background-image:url(logo?v=1);
}
```

If you refer to url(logo) manually then you should increment a version counter to prevent browsers from serving a stale cached image. If you let Cerb manage the logo it will do this for you automatically.

You can also use an external logo URL like:

```
#cerb-logo {
  background-image:url(https://example.com/logo.png);
}
```
