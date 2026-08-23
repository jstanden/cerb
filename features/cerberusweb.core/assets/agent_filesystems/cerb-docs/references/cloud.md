---
id: "cloud"
title: "Cerb Cloud"
url: "https://cerb.ai/cloud/"
summary: "This page provides an overview of Cerb Cloud, a subscription-based service that offers a fully managed, highly available, and scalable instance of Cerb in the cloud. The service includes features such as automated failover, performance optimization, and secure encryption. Cerb Cloud supports a wide range of configurations, including custom domains and email deliverability, and provides a free trial with optional paid upgrades. The service also offers a flexible pricing model, with unlimited workers allowed per seat, and features such as automatic backup storage and disaster recovery."
tags: []
---
**Cerb Cloud** is a subscription-based service that provides a finely tuned, ready-to-use instance of Cerb in an ideal environment. All you need is a web browser and your team can start putting Cerb's tools to work. We'll handle everything else.

- **Fully managed**: We install Cerb and its dependencies in an ideal environment, apply updates and security patches, monitor and scale the infrastructure, optimize performance, maintain backups, interface with email service providers for deliverability, provide application support and other technical services, and everything else. You can focus on what you do best.

- **Highly available**: Failed components are automatically replaced and redundant capacity allows your service to continue uninterrupted. The Enterprise tier provides a database cluster with near-instant automated failover, and the other tiers recover from database failures automatically within minutes.

- **Scalable**: Your Cerb environment can scale seamlessly from a single worker who sends a couple of messages per day, to hundreds of concurrent workers with a history spanning millions of conversations. Resources can seamlessly "scale up" and "scale out". New resources are automatically provisioned and added to load balancers in response to traffic needs (web servers, cache servers, incoming and outgoing mail servers, etc).

- **High performing**: Cerb is already designed to be fast and efficient. Cerb Cloud further accelerates performance by optimizing the underlying infrastructure and taking advantage of distributed services in the cloud. The database is continuously tuned for your workload. Resource requests (images, scripts, stylesheets, and fonts) are served instantly from a memory cache. Frequently accessed application data is retrieved from a memory-based cache cluster to reduce database latency. Background jobs are managed by an automated scheduler.

- **Secure**: All traffic between you and your Cerb instance is encrypted with SSL. We support "Perfect Forward Secrecy", which is a strategy that protects your past encrypted transmissions even if they are intercepted and recorded (even we can't decrypt them once your session ends). Our resources operate in a "private cloud" with private networks for traffic between components, and firewall rules in front of public components that expose a minimally necessary attack surface. Our own access to those resources requires RSA keys and two-factor authentication.

- **Durable**: We archive a sequence of full daily database backups, as well as the incremental point-in-time changes in between. Long term object storage (like attachments) are redundantly stored in several geographically separate locations. We can also arrange for backups to be routinely transfered to you.

Start free trial of Cerb Cloud

## How much does Cerb Cloud cost?

Cerb Cloud is $40/month (USD) per seat. Unlimited workers can share seats. Receive two months free with annual billing.

## Do you require a credit card to start a free trial of Cerb Cloud?

No! All we require is a valid email address to contact you. You will **never** receive an invoice until you request one.

## How many workers can I invite to a Cerb Cloud trial?

By default, your trial allows unlimited worker accounts with **3** seats. [Contact us](/help/) if you need to test with more seats, and we can raise the limit.

## Do I need my own email server to use Cerb Cloud?

No, we provide a high-volume SMTP service for outgoing mail with SPF and DKIM support. We also provide a redirect mailbox for instantly delivering incoming mail.

You can easily configure Cerb to use remote mail services if desired.

With Cerb Cloud, we provide you with a subdomain worth of temporary email addresses, like **\*@example.cerb.email**. You can send and receive email from any of these addresses (e.g. billing@, support@, sales@), which makes it much easier to practice routing work to the appropriate groups/buckets in Cerb.

We also configure SPF, DKIM, and DMARC records for these temporary email addresses so you can [test our mail deliverability](/docs/guide/admins/quick-start/#send-a-message-to-test-mail-deliverability).

If you switch to a Cerb Cloud subscription, you can even use these email addresses in production, but you'll probably want to use your own domains. We'll help you configure the SPF, DKIM, and DMARC records on your domains to optimize your mail deliverability from Cerb as well.

## How fast is incoming mail delivered into Cerb Cloud?

When mail is received by your redirect mailbox it will be delivered into Cerb instantly (within seconds). This enables you to respond more quickly to your customers.

If you choose to use remote mailboxes instead, new mail will generally be downloaded every few minutes.

## Can I use my own URL in Cerb Cloud, rather than \*.cerb.me?

Yes! We'll always use an `*.cerb.me` domain to identify your Cerb Cloud instance internally, but you can use a DNS _CNAME_ record to access your site with a custom domain like `support.example.com`. You can also use a custom domain for each community portal you deploy.

Keep in mind that you'll need to provide an SSL certificate for each custom domain you use. We provide wildcard SSL certificates for `*.cerb.me` and the generic portal domains like `*.official.support` and `*.user.community`.

## What if I plan to eventually switch from Cerb Cloud to self-hosted?

We still recommend that you start with a Cerb Cloud trial, so your team can immediately get to work learning about Cerb rather than getting bogged down with installation requirements and troubleshooting.

This also allows us to provide the highest level of assistance during your evaluation. We provide temporary email accounts so you can easily test incoming and outgoing messages, we've already installed the prerequisites for every plugin, etc.

All of your Cerb Cloud configuration and data can be exported to a self-hosted instance of Cerb at any time. Once you're confident that Cerb is a good fit for your team, we're happy to help you with that migration.

If you still need to evaluate Cerb in your own environment, you can simply grab a copy of the project from GitHub and follow the [installation instructions](/docs/installation/).

