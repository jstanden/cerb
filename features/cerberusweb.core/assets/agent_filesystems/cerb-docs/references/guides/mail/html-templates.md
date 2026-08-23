---
id: "guides-mail-html-templates"
title: "Send email on company letterhead"
url: "https://cerb.ai/guides/mail/html-templates/"
summary: "This page provides a comprehensive guide on how to create and use email templates with company letterhead in Cerb. It covers the process of setting up a new HTML template for emails, including adding a company logo, customizing the layout with CSS, and incorporating HTML signatures. The guide also explains how to apply these templates when sending emails from the browser or through bots, ensuring consistent corporate branding. Additionally, it highlights the ability to toggle between plaintext and HTML modes and offers tips for previewing and modifying templates for compatibility with various email readers. References to external resources, such as Wikipedia on Cascading Style Sheets, are also included for further reading."
tags: ["guides"]
---
- [Introduction](#introduction)
- [Creating the layout](#creating-the-layout)
- [Using the mail template](#using-the-mail-template)
- [Using the mail template from bots](#using-the-mail-template-from-bots)
- [References](#references)

# Introduction

You can use mail templates in Cerb to send messages with images and fancy layouts. This is typically used for corporate branding.

In this example we'll create a letterhead mail template and demonstrate how to use it when sending mail from the browser or using bots.

# Creating the layout

You'll need to be an admin to create a new email template.

Click **Setup**&nbsp;» **Mail**&nbsp;» **HTML Templates**.

Click the **(+)** icon above the worklist to add a new HTML template.

 

In the **Name:** field, add some descriptive text like _"Company Letterhead"_.

In the next large text box, paste the following template:

```
<div id="body">
  <img id="logo" src="https://cerb.ai/assets/cerb_logo.png">
  <div id="content">
    {{message_body}}
  </div>
  <div id="footer">
    Sent from <a href="https://cerb.ai/">Cerb</a>
  </div>
</div>

<style type="text/css">
body {
  margin: 0;
  padding: 0;
}

#body {
  margin: 0px;
  font-family: 'Helvetica Neue', Helvetica, 'Segoe UI', sans-serif;
  font-size: 10pt;
  background-color: #dddddd;
}

#content {
  margin: 0px 20px 20px 20px;
  padding: 20px;
  background-color: white;
  border-radius: 10px;
}

#logo {
  margin: 10px 0px 10px 20px;
}

#footer {
  color: rgb(150,150,150);
  font-weight: normal;
  text-align: center;
  padding-bottom: 20px;
}

#footer a {
  font-weight: bold;
  color: rgb(150,150,150);
}

a { 
  color: black;
}

blockquote {
  color: rgb(0, 128, 255);
  font-style: italic;
  margin-left: 0px;
  border-left: 1px solid rgb(0, 128, 255);
  padding-left: 5px;
}

blockquote a {
  color: rgb(0, 128, 255);
}
</style>
```

In the **Signature** section, you can add HTML formatting to signatures. For example:

```
-- 
{% if first_name %}{{first_name}}{% else %}the **support** team{% endif %} @ cerb.ai
```

You can preview the HTML template by clicking on the last icon in the editor toolbar.

 

This example uses Cerb's branding, but you can easily modify the template to use your own logo, footer, and style preferences.

You'll notice that styles are defined in this template using a CSS[1](#fn:css) stylesheet in the `<style>` element. This is a convenience for you when editing the template. When mail is sent using the template, Cerb converts the stylesheet into inline `style="..."` attributes for the widest compatibility with email readers.

Click the **Save Changes** button.

# Using the mail template

When composing or replying to a message, you can toggle between plaintext and HTML with the first icon in the editor toolbar.

 

In HTML mode, you'll see some new formatting options. On the far right of the toolbar is a list of your mail templates.

 

Your client will see:

 

# Using the mail template from bots

When using the [Send email action on a bot behavior](/guides/bots/send-automatic-replies/), you can enable HTML mode from the first icon on the editor toolbar. In this mode, you'll see a new **HTML Template:** option for selecting the template. You can then preview the message with the last icon.

 

# References

1. Wikipedia: Cascading Style Sheets - https://en.wikipedia.org/wiki/Cascading\_Style\_Sheets&nbsp;[↩](#fnref:css)

