---
id: "guides-portals-nginx-proxy"
title: "Host community portals using Nginx"
url: "https://cerb.ai/guides/portals/nginx-proxy/"
summary: "This page provides a detailed guide on hosting community portals using Nginx, specifically for Cerb users. It introduces a PHP-based reverse proxy script for testing purposes but strongly recommends using Nginx for production environments. The guide includes step-by-step instructions for setting up a virtual host in Nginx, configuring SSL certificates, and ensuring proper URL rewriting. It also covers the necessary modifications to the Nginx configuration file, such as setting domain variables and handling SSL. Additionally, it provides instructions for reloading Nginx and testing the community portal. The page is aimed at users who want to host their community portals efficiently and securely, with references to related resources for further assistance."
tags: ["guides"]
---
# Introduction

We provide a simple, PHP-based reverse proxy script for hosting [community portals](/docs/portals/) from any web server. You'll find this `index.php` file on the **Installation** tab of your community portal configuration. This is useful for testing; allowing you to serve a community portal from a directory on an existing website, or from the built-in PHP webserver.

However, we highly recommend that you use a production-ready proxy like nginx[1](#fn:nginx) to serve community portals. With this approach you can completely ignore the `index.php` script.

If you're a [Cerb Cloud](/pricing/) subscriber, we include high-availability community portal hosting. This is already handled for you.

# Add a virtual host to Nginx

Create a new virtual host in nginx for your community portal. Use the following configuration file:

```
# HTTPS
server {
  set $cerb_host cerb.example;
  set $portal_host portal.example;
  set $portal_code abcd1234;

  listen 443 ssl;
  server_name $portal_host;
  #access_log off;

  # Increase upload max size from default of 1MB
  client_max_body_size 32m;

  charset utf-8;

  # DNS
  #resolver 8.8.8.8 8.8.4.4 valid=300s;
  #resolver_timeout 5s;

  # SSL
  # [TODO] Replace this with your own SSL certificate!
  ssl_certificate /etc/ssl/certs/nginx-selfsigned.pem;
  ssl_certificate_key /etc/ssl/private/nginx-selfsigned.key;
  ssl_protocols TLSv1.2;
  ssl_prefer_server_ciphers on;
  ssl_ciphers HIGH:!CAMELLIA:!RC4:!PSK:!aNULL:@STRENGTH;
  ssl_dhparam /etc/ssl/certs/dhparam.pem;

  # Resource proxy
  location ~* ^/resource/ {
    rewrite ^/resource/(.*) /resource/$1 break;
    proxy_pass https://$cerb_host;
  }

  # Everything else
  location / {
    rewrite ^/(.*) /portal/$portal_code/$1 break;
    proxy_pass https://$cerb_host;
    proxy_set_header DevblocksProxySSL 1;
    proxy_set_header DevblocksProxyHost $portal_host;
    proxy_set_header DevblocksProxyBase "";
  }

  # Deny any other PHP scripts
  location ~ \.php$ {
    deny all;
  }
}

# Redirect HTTP to HTTPS
server {
  set $portal_host portal.example;

  listen 80;
  server_name $portal_host;
  #access_log off;

  location / {
    return 301 https://$host$request_uri;
  }
}
```

Modify the variables at the top of the **server** block:

- `$cerb_host` is the domain where you're hosting your Cerb installation. We assume you're using `https://`.
- `$portal_host` is the domain where you're hosting the public Support Center.
- `$portal_code` is your portal's unique ID, which you can find in **Setup&nbsp;» Portals**.

You'll need to provide your own SSL certificate and private key in ssl\_certificate and ssl\_certificate\_key.

### Note:
This guide assumes you have [friendly URLs](/docs/friendly-urls/) enabled for Cerb. If not, you'll need to modify the rewrite directives, like: 
- /index.php/resource/$1
- /index.php/portal/$portal\_code/$1

# Reload nginx

Reload nginx to start serving your community portal.

On Ubuntu:

```
service nginx reload
```

# Test the community portal

Open your community portal URL a web browser.

# Related resources

- [Create a new Support Center community portal](/guides/portals/support-center/)

# References

1. Nginx - http://nginx.org&nbsp;[↩](#fnref:nginx)

