---
id: "guides-installation-ubuntu"
title: "Install Cerb on Ubuntu Linux 26.04"
url: "https://cerb.ai/guides/installation/ubuntu/"
summary: "This is a comprehensive guide for installing Cerb on an Ubuntu 26.04 LTS server. It covers the entire setup process, including provisioning a server using Docker or Amazon EC2, installing necessary packages like PHP 8.5, Nginx, and MySQL, and configuring the MySQL database for Cerb. The guide also details the installation of Cerb itself, setting up Nginx with SSL certificates, creating virtual hosts, and testing the Nginx configuration. Additionally, it includes instructions for enabling friendly URLs and running the Cerb installer, ensuring a complete and secure installation process."
tags: ["guides"]
---
We do not recommend installing Cerb components on your server directly. Use the [Docker instructions](/docs/installation/docker/) as a reference.

# Introduction

This is a comprehensive guide for installing Cerb on an Ubuntu 26.04 LTS server. It covers the entire setup process, including provisioning a server using Docker or Amazon EC2, installing necessary packages like PHP 8.5, Nginx, and MySQL, and configuring the MySQL database for Cerb. The guide also details the installation of Cerb itself, setting up Nginx with SSL certificates, creating virtual hosts, and testing the Nginx configuration. Additionally, it includes instructions for enabling friendly URLs and running the Cerb installer, ensuring a complete and secure installation process.

- [Provision an Ubuntu server](#provision-an-ubuntu-server)
  - [Docker](#docker)
  - [EC2](#ec2)

- [Install packages](#install-packages)
- [Install MySQL](#install-mysql)
- [Create the MySQL database](#create-the-mysql-database)
- [Install Cerb](#install-cerb)
- [Configure Nginx](#configure-nginx)
  - [SSL](#ssl)
    - [Add your SSL certificate](#add-your-ssl-certificate)
    - [Creating a self-signed SSL certificate](#creating-a-self-signed-ssl-certificate)

  - [Add a virtual host](#add-a-virtual-host)
  - [Test Nginx configuration](#test-nginx-configuration)
  - [Restart Nginx and PHP-FPM](#restart-nginx-and-php-fpm)

- [Enable friendly URLs](#enable-friendly-urls)
- [Run the Cerb installer](#run-the-cerb-installer)

# Provision an Ubuntu server

If you don't already have a server, you can use Docker or Amazon EC2.

## Docker

```
docker run -it --rm -p 80:80 -p 443:443 ubuntu:26.04 /bin/bash
```

For local evaluation, development, and testing, you can use the built-in [Docker](/docs/installation/docker/) configuration instead.

## EC2

1. Launch an [Amazon EC2](/guides/installation/ec2/) instance.

2. Connect to your server using SSH:

```
ssh ubuntu@1.2.3.4
```

1. `sudo` into the `root` user.

# Install packages

It's a good idea to update your installed packages first:

```
apt-get update && apt-get -y upgrade
```

Install PHP 8.5:

```
apt-get install -y php8.5 php8.5-cli php8.5-fpm php8.5-mysql php8.5-mbstring php8.5-gd \
   php8.5-curl php8.5-yaml php8.5-gmp php8.5-zip php8.5-mailparse php8.5-dom php8.5-xml
```

Install common tools:

```
apt-get install -y git vim
```

Install the Nginx web server:

```
apt-get install -y nginx nginx-extras
```

# Install MySQL

We recommend using a dedicated database server that replicates to a standby server. In Amazon Web Servers you should use RDS.

If you need to install MySQL on your Docker or EC2 instance instead, you can use these instructions:

```
apt-get install -y mysql-server
```

This installs MySQL 8.4 LTS on Ubuntu 26.04.

In Docker you need to start the MySQL service. The `ubuntu:26.04` image doesn't run `systemd` as PID 1, so `service mysql start` (which invokes `systemctl`) will fail. Start `mysqld` directly instead:

```
mysqld --user=mysql --daemonize
```

You really should use the `mysql:8.4` container instead.

# Create the MySQL database

Connect to MySQL:

```
mysql -h localhost -u root -p
```

The default password is empty, just press `<ENTER>`.

If you're using a remote MySQL server, use its internal IP in place of localhost above.

Set a root password.

```
ALTER USER 'root'@'localhost' IDENTIFIED WITH caching_sha2_password BY 's3cr3t';
```

MySQL 8.4 removed the legacy mysql\_native\_password plugin. Use caching\_sha2\_password (the default since MySQL 8.0).

Replace s3cr3t above with your own new password.

Create a new database and user for Cerb:

```
CREATE DATABASE cerb CHARACTER SET utf8;

CREATE USER cerb@localhost IDENTIFIED BY 's3cr3t';

GRANT ALL PRIVILEGES ON cerb.* TO cerb@localhost;

QUIT;
```

Replace s3cr3t above with your own secret password. If you're using a remote database server, replace @localhost with a subnet used by your web servers, like: @'10.0.0.%'

# Install Cerb

You should now be ready to install Cerb.

```
cd /usr/share/nginx/html/
```

```
git clone https://github.com/cerb/cerb-release.git cerb
```

```
chown -R www-data:www-data cerb
```

```
cd cerb
```

You can test Cerb using PHP's built in webserver:

```
service nginx stop
```

```
php -S 0.0.0.0:80
```

Type your server IP into a browser.

You should see the requirements checker with all tests passed:

 

If you're just testing Cerb, you can use PHP's built-in web server and skip the Nginx step below.

Type `CTRL+C` to kill the PHP web server process.

Since you just ran the web server as root, you should make sure any newly created files are owned by the `www-data` user and group:

```
chown -R www-data:www-data /usr/share/nginx/html/cerb/
```

# Configure Nginx

We're going to install Nginx as the web server. Cerb's code will run in PHP-FPM.

## SSL

### Add your SSL certificate

If you're using an Elastic Load Balancer you can configure SSL there and use internal IPs without SSL on your web servers. Amazon Certificate Manager can also generate SSL certificates for free.

Otherwise, you'll need a valid SSL certificate for your server. We recommend Let's Encrypt or a RapidSSL certificate from CheapSSLsecurity.

Enable Perfect Forward Secrecy (this may take a few minutes):

```
openssl dhparam -out /etc/ssl/certs/dhparam.pem 2048
```

### Creating a self-signed SSL certificate

For testing, you can also create a self-signed SSL certificate. You **should not** use these instructions in production:

```
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
-keyout /etc/ssl/private/nginx-selfsigned.key \
-out /etc/ssl/certs/nginx-selfsigned.pem
```

```
Country Name (2 letter code) [AU]:US
State or Province Name (full name) [Some-State]:California
Locality Name (eg, city) []:
Organization Name (eg, company) [Internet Widgits Pty Ltd]:Example, Inc.
Organizational Unit Name (eg, section) []:Internet
Common Name (e.g. server FQDN or YOUR name) []:cerb.example
Email Address []:support@cerb.example
```

## Add a virtual host

Add a new virtual host to Nginx:

```
vi /etc/nginx/sites-available/cerb
```

Type `i` to switch to insert mode and paste the following:

```
1
2
3
4
5
6
7
8
9
10
11
12
13
14
15
16
17
18
19
20
21
22
23
24
25
26
27
28
29
30
31
32
33
34
35
36
37
38
39
40
41
42
43
44
45
46
47
48
49
50
51
52
53
54
55
56
57
58
59
60
61
62
63
64
65
66
67
68
69
70
71
72
73
74
75
76
77
78
79
80
81
82
83
84
85
86
87
88
89
90
91
92
93
94
95
96
97
98
99
100
101
102
103
104
105
106
107
108
109
110
111
112
113
server {
  listen 80;
  server_name cerb.example;
  #access_log off;

  location /status/nginx {
    stub_status on;
    access_log off;
    allow 127.0.0.1;
    deny all;
  }

  location /status/fpm {
    access_log off;
    allow 127.0.0.1;
    #allow 10.0.0.0/16;
    deny all;
    include fastcgi_params;
    fastcgi_pass unix:/run/php/php8.5-fpm.sock;
  }

  location / {
    return 301 https://$host$request_uri;
  }
}

limit_req_zone $binary_remote_addr zone=cerb:10m rate=15r/s;

server {
  listen 443 ssl;
  server_name cerb.example;
  #access_log off;
  
  root /usr/share/nginx/html/cerb/;
  index index.php;

  # Increase upload max size from default of 1MB
  client_max_body_size 30m;
	  
  charset utf-8;

  # SSL
  ssl_certificate /etc/ssl/certs/nginx-selfsigned.pem;
  ssl_certificate_key /etc/ssl/private/nginx-selfsigned.key;
  ssl_protocols TLSv1.2;
  ssl_prefer_server_ciphers on;
  ssl_ciphers HIGH:!CAMELLIA:!RC4:!PSK:!aNULL:@STRENGTH;
  ssl_dhparam /etc/ssl/certs/dhparam.pem;

  # DNS
  resolver 8.8.8.8 8.8.4.4 valid=300s;
  resolver_timeout 5s;

  # Always let people see the favicon file
  location = /favicon.ico {
    allow all;
  }

  # Always let people see the robots file
  location = /robots.txt {
    allow all;
  }

  # Send PHP scripts to FPM
  location ~ ^/(index|ajax)\.php$ {
    limit_req zone=cerb burst=40 delay=15;
    
    proxy_connect_timeout 30;
    proxy_send_timeout 30;
    proxy_read_timeout 30;
    
    fastcgi_split_path_info ^(.+\.php)(/.+)$;
    fastcgi_pass unix:/run/php/php8.5-fpm.sock;
    fastcgi_index index.php;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
  }

  # ============================
  # ENABLE ONLY FOR INSTALLATION
  # ============================
  location /install/ {
    location = /install/ {
      rewrite ^(.*)$ /install/index.php?$1 last;
    }
    
    location ~ ^/install/(index|servercheck|phpinfo)\.php$ {
      fastcgi_split_path_info ^(.+\.php)(/.+)$;
      fastcgi_pass unix:/run/php/php8.5-fpm.sock;
      fastcgi_index /install/index.php;
      include fastcgi_params;
      fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
    
    location ~ ^/install/(.*)\.(css|js|svg)$ {
      allow all;
    }
    
    #location ~ ^/install/ {
    # deny all;
    #}
  }
  
  # Deny direct access to all other PHP files
  location ~ \.php$ {
    deny all;
  }
  
  # Send all other paths to the Devblocks front controller index.php
  location / {
    rewrite ^ /index.php last;
  }
}
```

On lines `3` and `29` change `cerb.example` to the domain name of your server. If for some reason you don't have one, you can temporarily use your server IP.

The first `server` block (lines `1-25`) redirects all HTTP requests to HTTPS with SSL. It also defines some `/status` pages you can use to monitor the server (lines `6` and `13`).

On lines `41-42`, you should use your own SSL key and certificate.

Save the file with `:wq`

To enable the site we need to add a symlink:

```
unlink /etc/nginx/sites-enabled/default

ln -s /etc/nginx/sites-available/cerb /etc/nginx/sites-enabled/cerb
```

## Test Nginx configuration

You can test the Nginx configuration file with:

```
nginx -t
nginx: the configuration file /etc/nginx/nginx.conf syntax is ok
nginx: configuration file /etc/nginx/nginx.conf test is successful
```

## Restart Nginx and PHP-FPM

```
service nginx restart
```

```
service php8.5-fpm restart
```

For more information about Nginx + PHP-FPM, see: https://www.nginx.com/resources/wiki/start/topics/examples/phpfcgi/

# Enable friendly URLs

```
touch .htaccess
```

# Run the Cerb installer

Type the hostname of your server into a browser and follow the [guided installer](/docs/installation/#run-the-guided-installer).

If you're installing with Docker, use `127.0.0.1` rather than `localhost` for the database server.

