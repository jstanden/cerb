---
id: "docs-installation-self-hosted"
title: "Installation"
url: "https://cerb.ai/docs/installation/self-hosted/"
summary: "This page provides a comprehensive guide for installing Cerb, detailing the necessary browser and server requirements, including supported operating systems, webserver applications, PHP version, and database servers. It outlines the steps to log in to the server, download the source code from GitHub, set file permissions, and create a database. The guide walks through the Cerb installation process with a step-by-step guided installer, covering requirements check, license agreement, database setup, configuration file saving, database initialization, general settings, outgoing mail configuration, admin account creation, testing mode, and security recommendations. The page concludes with instructions for finalizing the installation and references for further information."
tags: ["docs"]
---
We do not recommend installing Cerb components on your server directly. Use the [Docker instructions](/docs/installation/docker/) as a reference.

- [Requirements](#requirements)
  - [Browser requirements](#browser-requirements)
  - [Server requirements](#server-requirements)

- [Log in to your server](#log-in-to-your-server)
- [Download the source code from GitHub](#download-the-source-code-from-github)
- [Set permissions](#set-permissions)
- [Create the database](#create-the-database)
- [Run the guided installer](#run-the-guided-installer)

# Requirements

## Browser requirements

- An Internet or intranet connection to the server (e.g. Ethernet, Wi-Fi, mobile data)
- The latest version of any of these modern HTML5 web browsers, with Javascript enabled: 
  - Mozilla Firefox
  - Apple Safari
  - Brave
  - Opera
  - Google Chrome
  - Microsoft Edge
  - (Internet Explorer is no longer supported)

## Server requirements

- Any of these 64-bit operating systems: 
  - Linux _(recommended, using Ubuntu 26.04 LTS)_
  - BSD
  - macOS
  - Windows

- Any of these webserver applications: 
  - Caddy _(recommended, using PHP-FPM)_
  - Nginx _(recommended, using PHP-FPM)_
  - Apache
  - Microsoft Internet Information Server (IIS)
  - Built-in PHP webserver (for development and evaluation)

- PHP 8.5 (64-bit) or later 
  - With the following extensions enabled: 
    - curl
    - dom
    - gd
    - json
    - mailparse
    - mbstring
    - mysqli
    - openssl
    - pcre
    - session
    - simplexml
    - spl
    - yaml
    - xml
    - tidy _(recommended)_
    - gmp _(recommended)_
    - memcached _(optional)_
    - redis _(optional)_

  - With these _php.ini_ settings: 
    - file\_uploads = On
    - memory\_limit = 128M _(or higher)_
    - upload\_max\_filesize = 32M _(or higher)_
    - post\_max\_size = 32M _(or higher)_

- Any of these database servers: 
  - MySQL 8.0 or later
  - MariaDB 10.5 or later
  - Amazon Aurora 3.x or later

If you are unable to meet these requirements, consider [Cerb Cloud](/pricing/).

# Log in to your server

The following general instructions assume that you have console access to a Linux-based server that meets the above requirements. You should already have a webserver, database, and PHP installed before proceeding.

You can follow one of these guides to set up a new server:

- [Installing Cerb on Ubuntu 26.04 LTS with Nginx and PHP-FPM](/guides/installation/ubuntu/)

# Download the source code from GitHub

Navigate to your website's document directory on the filesystem. The directory will usually be named something like `htdocs`, `httpdocs`, `public_html`, or `www`.

```
cd /path/to/example.com/httpdocs
```

When deploying Cerb on a production server you should use **Git** to manage the project files. This provides many useful capabilities:

- Quickly upgrade by just fetching files that have changed since your last update.
- See the local changes that you have made to any project files.
- Easily reset files back to their default condition.
- See what changes _would_ occur before performing an upgrade.
- Continuously merge your local changes with our future updates.

You won't need to download the entire project again after your initial installation. You also won't have to hassle with copying your `framework.config.php` [configuration file](/docs/config-file/) or storage directory when upgrading, or repeating any of your custom modifications to the source code.

You can download Cerb into a specific directory with a single command:

```
git clone git://github.com/cerb/cerb-release.git cerb
```

You would access Cerb at a URL with a base path like `https://example.com/cerb`. You can change the last argument above to whatever path you want: `support`, `helpdesk`, etc.

To download Cerb into the root of your domain instead, use:

```
git clone git://github.com/cerb/cerb-release.git .
```

This results in a URL without a base path, like `https://support.example.com/`

# Set permissions

Next, we need to make sure that Cerb's files are owned by the webserver's user and group. The default user and group are both `www-data` when using Apache or Nginx on Ubuntu. If you're using something different, you should consult your configuration for the proper values.

You only need to enable write access to the webserver in two locations:

- `framework.config.php` This is your [configuration file](/docs/config-file/).
- `storage/` This is where any data unique to your installation is stored: third-party plugins, attachments, temporary files, caches, etc.

Give ownership of all the files to the webserver daemon using `chown`, and make the two locations above writable using `chmod`:

```
cd cerb
chown -R www-data:www-data .
chmod -R u+w framework.config.php storage
```

You must use your own user and group for `www-data` in the example above.

There are special situations, such as PHP in FastCGI mode, custom PHP-FPM pools, or with security extensions like Suhosin, where the ownership and permissions of the files may need to be something else. Consult a system administrator if you need assistance. The Cerb installer will let you know if the permissions are incorrect.

# Create the database

Create a new MySQL database using the console or your favorite GUI tool.

From the MySQL console, you can issue the following SQL statements:

```
CREATE DATABASE cerb CHARACTER SET utf8mb4;

CREATE USER cerb@localhost IDENTIFIED BY 'secret_password';

GRANT ALL PRIVILEGES ON cerb.* TO cerb@localhost;
```

Substitute your own database name and login in place of `cerb`, and replace `secret_password` with something that's actually a secret. If you're connecting to a remote database, change `@localhost` to the network address of the webserver where you'll be connecting from.

If you're concerned about granting `ALL PRIVILEGES`, the minimum required privileges for the database user are: `SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, CREATE TEMPORARY TABLES`.

# Run the guided installer

Cerb has a [guided installer](/docs/guided-installer/) that verifies your requirements, initializes the database, and walks you through the initial configuration of the software.

[\< Installation](/docs/installation/)

[Guided Installer \>](/docs/guided-installer/)

