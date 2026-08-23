---
id: "guides-portals-cloudflare-proxy"
title: "Host community portals using Cloudflare"
url: "https://cerb.ai/guides/portals/cloudflare-proxy/"
summary: "This page provides a detailed guide on hosting Cerb community portals using Cloudflare Workers. It introduces Cloudflare's edge computing platform as an alternative to traditional reverse proxy solutions for hosting community portals. The guide includes step-by-step instructions for setting up DNS, configuring Cloudflare Workers, and implementing a custom JavaScript proxy script. It covers domain configuration, Worker deployment, custom domain routing, and portal path mapping. Additionally, it provides the complete Worker script code and instructions for testing the community portal. The page is aimed at users who want to leverage Cloudflare's global CDN and edge computing capabilities to host their community portals efficiently and securely."
tags: ["guides"]
---
- [Introduction](#introduction)
- [Add domain to Cloudflare](#add-domain-to-cloudflare)
- [Configure DNS records](#configure-dns-records)
- [Create a Cloudflare Worker](#create-a-cloudflare-worker)
- [Configure the Worker script](#configure-the-worker-script)
- [Add custom domain to Worker](#add-custom-domain-to-worker)
- [Test the community portal](#test-the-community-portal)
- [Related resources](#related-resources)
- [References](#references)

# Introduction

We provide a simple, PHP-based reverse proxy script for hosting [community portals](/docs/portals/) from any web server. You'll find this `index.php` file on the **Installation** tab of your community portal configuration. This is useful for testing; allowing you to serve a community portal from a directory on an existing website, or from the built-in PHP webserver.

However, you can also use Cloudflare Workers[1](#fn:cloudflare-workers) to serve community portals. This approach leverages Cloudflare's global edge network and provides excellent performance and reliability. With this approach you can completely ignore the `index.php` script.

If you're a [Cerb Cloud](/pricing/) subscriber, we include high-availability community portal hosting. However, using Cloudflare provides their full suite of tools like DDoS protection.

# Add domain to Cloudflare

First, you'll need to add your domain to Cloudflare:

1. Log into your Cloudflare dashboard
2. From the **Add** menu in the top right, click **Connect a domain**
3. Enter your root domain
4. Select the desired **DNS** option
5. Select the desired **AI Crawlers** option
6. Choose your plan (Free plan works fine for most use cases)

# Configure DNS records

Complete the domain activation by changing your domain's nameservers to the ones provided by Cloudflare.

# Create a Cloudflare Worker

Navigate to the Workers section in your Cloudflare dashboard:

1. Click on your account name in the top left.
2. In the left sidebar, click **Compute** → **Workers & Pages**
3. Click **Create application**
4. Select **Create Worker**
5. Choose **"Start with Hello World!"**
6. Enter a worker name (e.g., `cerb-cloud-proxy`)
7. Click **Deploy**

# Configure the Worker script

After deployment, click **Edit code** and replace the default script with the following:

```
export default {
  async fetch(request, env) {
    const backendHost = 'example.cerb.me';
    
    const portalRouting = {
      '/support': 'a1b2c3d4'
    };

    let backendUrl;
    const url = new URL(request.url);
    const newHeaders = new Headers(request.headers);
    newHeaders.set('DevblocksProxyHost', request.headers.get('Host'));

    for(let portalPath in portalRouting) {
      if(!backendUrl && url.pathname.startsWith(portalPath)) {
        const pathName = url.pathname.substring(portalPath.length);
        backendUrl = `https://${backendHost}/portal/${portalRouting[portalPath]}${pathName}${url.search}`;
        newHeaders.set('DevblocksProxyBase', portalPath);
        break;
      }
    }

    if(!backendUrl)
      backendUrl = `https://${backendHost}${url.pathname}${url.search}`;
    
    const modifiedRequest = new Request(backendUrl, {
      method: request.method,
      headers: newHeaders,
      body: request.body,
      redirect: 'manual'
    });
    
    const response = await fetch(modifiedRequest);
    return response;
  }
};
```

Modify the variables in the script:

- `backendHost` is the domain where you're hosting your Cerb installation (e.g., `example.cerb.me` for Cerb Cloud).
- `portalRouting` maps URL paths to portal codes. The keys are the URL paths (e.g., `/support`) and the values are your portal's unique ID, which you can find in **Search&nbsp;» Portals**.

Click **Deploy** to save your changes.

# Add custom domain to Worker

Now you need to connect your domain to the Worker:

1. In the left sidebar, click **Compute** → **Workers & Pages**
2. Click on your worker name
3. Go to **Settings** → **Domains & Routes**
4. Click **Add** → **Custom domain**
5. Enter your full domain (e.g., `support.example.com`)
6. Click **Add domain**

Cloudflare will automatically create the necessary DNS records and SSL certificates.

# Test the community portal

Open your community portal URL in a web browser to verify it's working correctly.

The Worker will:

- Proxy requests to your Cerb backend
- Handle SSL termination automatically
- Set the appropriate headers for Cerb to recognize the portal
- Route different paths to different portals if configured

# Related resources

- [Create a new Support Center community portal](/guides/portals/support-center/)
- [Host community portals using Nginx](/guides/portals/nginx-proxy/)

# References

1. Cloudflare Workers - https://workers.cloudflare.com/&nbsp;[↩](#fnref:cloudflare-workers)

