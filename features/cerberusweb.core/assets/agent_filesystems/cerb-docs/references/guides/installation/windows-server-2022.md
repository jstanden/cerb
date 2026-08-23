---
id: "guides-installation-windows-server-2022"
title: "Install Cerb on Microsoft Windows Server 2022"
url: "https://cerb.ai/guides/installation/windows-server-2022/"
summary: "This webpage provides a comprehensive guide for installing Cerb on a Microsoft Windows Server 2022. It covers the entire setup process, including provisioning a server, connecting via Remote Desktop Protocol, and installing necessary components such as IIS, PHP, and MySQL. The guide details the installation of PHP extensions like mailparse and YAML, configuring PHP settings, and setting up a MySQL database and user. It also includes instructions for downloading Cerb from Git, setting file permissions, and running the Cerb installer. The guide concludes with recommendations for setting up friendly URLs and reviewing security best practices."
tags: ["guides"]
---
# Introduction

This guide will walk you through preparing a Microsoft Windows Server 2022 server for installing Cerb, including IIS, PHP, and MySQL.

- [Provision a Microsoft Windows Server 2022 server](#provision-a-microsoft-windows-server-2022-server)
- [Connect to your server](#connect-to-your-server)
- [Install IIS](#install-iis)
- [Install PHP with Web Platform Installer](#install-php-with-web-platform-installer)
  - [Install PHP](#install-php)
  - [Install Git](#install-git)

- [Install the PHP mailparse extension](#install-the-php-mailparse-extension)
- [Install the YAML extension](#install-the-yaml-extension)
- [Install MySQL](#install-mysql)
  - [Create a database and user](#create-a-database-and-user)

- [Configure PHP](#configure-php)
- [Download Cerb from Git Shell](#download-cerb-from-git-shell)
- [Permissions](#permissions)
- [Run the Cerb installer](#run-the-cerb-installer)
- [Next Steps](#next-steps)

# Provision a Microsoft Windows Server 2022 server

If you don't already have a server, you can [create an EC2 instance in Amazon Web Services](/guides/installation/ec2/).

This guide uses the following Amazon Machine Image (AMI):

**Windows\_Server-2022-English-Full-Base-2022.07.13** (`ami-0648d932874575779`)

# Connect to your server

Connect to your server using Remote Desktop Protocol (RDP).

If you're using AWS, go to the EC2 console, Instances, select your instance, and choose **Security&nbsp;» Get Windows Password** from the instances menu.

# Install IIS

1. **Start&nbsp;» Server Manager**

2. Add roles and features

3. Role-based or feature-based installation

4. Web Server (IIS)

5. **Include Application Development&nbsp;» CGI**

# Install PHP with Web Platform Installer

1. Navigate to: https://docs.microsoft.com/en-us/iis/install/web-platform-installer/web-platform-installer-direct-downloads

2. Download and install **WebPI 5.1 x64**

## Install PHP

1. **Start&nbsp;» Microsoft Web Platform Installer**

2. **Search&nbsp;» PHP 8.0.0 (x64)&nbsp;» Add**

3. Click the **Install** button.

## Install Git

1. **Start&nbsp;» Microsoft Web Platform Installer**

2. **Search&nbsp;» Git for Windows**

3. Click the **Install** button.

# Install the PHP mailparse extension

https://windows.php.net/downloads/pecl/releases/mailparse/3.1.3/php\_mailparse-3.1.3-8.0-nts-vs16-x64.zip

Copy `php_mailparse.dll` to `C:\Program Files\PHP\v8.0\ext\`

# Install the YAML extension

https://windows.php.net/downloads/pecl/releases/yaml/2.2.2/php\_yaml-2.2.2-8.0-nts-vs16-x64.zip

Copy `php_yaml.dll` to `C:\Program Files\PHP\v8.0\ext\`

# Install MySQL

(Windows Platform Installer / Server Manager)

https://dev.mysql.com/downloads/installer/

Download MySQL 8.0.29+.

Install MySQL Server.

Server Configuration: Server Computer

Use Legacy Authentication Method (MySQL 5.x compatible)

Set a MySQL root password.

## Create a database and user

**Start&nbsp;» MySQL 8.0 Command Line Client**

Enter your root password.

```
CREATE DATABASE cerb CHARACTER SET utf8;

CREATE USER cerb@localhost IDENTIFIED BY 's3cr3t';

GRANT ALL PRIVILEGES ON cerb.* TO cerb@localhost;

QUIT;
```

Replace s3cr3t above with your own secret password. If you're using a remote database server, replace @localhost with a subnet used by your web servers, like: @'10.0.0.%'

# Configure PHP

Edit `C:\Program Files\PHP\v8.0\php.ini`

```
extension=php_curl.dll
extension=php_gd.dll
extension=php_mbstring.dll
extension=php_mysqli.dll
extension=php_openssl.dll
extension=php_mailparse.dll
extension=php_tidy.dll
extension=php_yaml.dll
```

**Start&nbsp;» Command Prompt**

```
iisreset /restart

exit
```

# Download Cerb from Git Shell

**Start&nbsp;» Git Bash**

```
cd /c/inetpub/wwwroot/

git clone https://github.com/cerb/cerb-release.git cerb

cd cerb
```

# Permissions

**Start&nbsp;» File Explorer**

Navigate to C:\inetpub\www\root\cerb\storage\

**Properties&nbsp;» Security**

**IUSR&nbsp;» Full Control&nbsp;» Recursively**

# Run the Cerb installer

Type the hostname of your server (e.g. `http://localhost/cerb/`) into a browser and follow the [guided installer](/docs/installation/#run-the-guided-installer).

# Next Steps

Set up [Friendly URLs](/docs/friendly-urls/).

Read the [Security Best Practices](/docs/security/).

