---
id: "docs-friendly-urls"
title: "Friendly URLs"
url: "https://cerb.ai/docs/friendly-urls/"
summary: "This page provides detailed instructions on enabling friendly URLs in Cerb to improve the appearance and professionalism of URLs by removing the default `/index.php/` path. It explains the concept of virtual URLs in Cerb and offers step-by-step guidance for enabling URL rewriting on different web servers, including Nginx, Apache, and IIS. For Nginx, it involves creating a `.htaccess` file or editing the `framework.config.php` file. For Apache, it requires enabling `mod_rewrite` and copying a configuration file. For IIS, it involves using the URL Rewrite module to set up a rule for friendly URLs. The page emphasizes that these changes are not enabled by default and depend on the web server's capabilities."
tags: ["docs"]
---
# Enabling friendly URLs

This is automatically handled for you with [Docker](/docs/installation/docker/) installations. You can [skip ahead](/docs/security/).

You may notice that your URLs look a bit ugly with `/index.php/` in every path by default.

This URL doesn't look very professional:

 

This looks much nicer:

 

Ironically, the default URLs are ugly because Cerb's underlying design is secure and elegant. Every URL in Cerb is _virtual_ – there isn't a corresponding file on the webserver for each link. Every page request routes through the `index.php` script, and all asynchronous requests are routed through `ajax.php`. Those two scripts are the only entry points into all of Cerb's functionality.

Cerb supports URL rewriting to make the URLs shorter and more user-friendly. This isn't enabled by default because it requires your webserver to support it.

- [Enabling friendly URLs](#enabling-friendly-urls)
  - [Enabling friendly URLs with Nginx](#enabling-friendly-urls-with-nginx)
  - [Enabling friendly URLs with Apache](#enabling-friendly-urls-with-apache)
  - [Enabling friendly URLs with IIS](#enabling-friendly-urls-with-iis)

## Enabling friendly URLs with Nginx

This is all you should need to do to enable friendly URLs in Nginx:

```
cd /path/to/cerb
touch .htaccess
```

You can alternatively edit the `framework.config.php` file and manually set `DEVBLOCKS_REWRITE` to `true`.

## Enabling friendly URLs with Apache

If you're using the Apache web server you can enable URL rewriting with the following commands:

```
cd /path/to/cerb
cp .htaccess-dist .htaccess
```

For this to work you will need to enable `mod_rewrite` in your Apache configuration. It is usually already enabled.

If `mod_rewrite` isn't enabled, you can use the following commands on many Linux-based servers:

```
sudo a2enmod rewrite
service apache2 reload
```

If these commands don't work, you'll need to enable the module manually.

## Enabling friendly URLs with IIS

Install **URL Rewrite 2.1** from the Web Platform Installer (WPI).

Open **Internet Information Services (IIS) Manager**.

Click on the **Cerb** web site.

Double click on the **URL Rewrite** icon.

Click **Add Rule(s)…** in the right sidebar.

Double click **Blank Rule**:

- Name: Friendly URLs
- Requested URL: Matches the pattern
- Using: Regular Expressions
- Pattern: ^(.\*)
- Conditions: {REQUEST\_FILENAME} 'is not a file'
- Rewrite URL: /index.php/{R:1}

Click **Apply** in the right sidebar.

In the directory where you installed Cerb (e.g. `C:\inetpub\wwwroot\cerb\`), create an empty `.htaccess` file.

