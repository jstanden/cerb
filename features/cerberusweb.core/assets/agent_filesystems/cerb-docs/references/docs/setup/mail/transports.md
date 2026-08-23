---
id: "docs-setup-mail-transports"
title: "Setup: Mail Transports"
url: "https://cerb.ai/docs/setup/mail/transports/"
summary: "This page provides an overview of mail transports in Cerb, which are used for sending outgoing email messages. It explains the default types of mail transports available, including SMTP for delivering emails via a mail server and Null for discarding emails in development environments. The page also discusses the possibility of using plugins to implement new mail transport types and the flexibility of using different transports for various email addresses. It emphasizes the importance of email deliverability and outlines several validation systems like SPF, DKIM, DMARC, and RBL that help ensure legitimate emails reach recipients while blocking spam. The page advises on configuring these systems correctly when setting up a new SMTP mail transport."
tags: ["docs"]
---
 

In Cerb, outgoing email messages are sent using **mail transports**. This page displays your available mail transports. Your first transport was created during the [installation](/docs/installation/) process.

By default there are two types of mail transports available:

- **SMTP**: This transport delivers outgoing email messages to a mail server using the Simple Mail Transfer Protocol[1](#fn:smtp). That server is then responsible for routing messages to the appropriate recipients.

- **Null**: This transport pretends to deliver outgoing email messages and discards them. It's useful for development, testing, and evaluation environments where you want to be absolutely sure that no live email is being sent.

[Plugins](/docs/plugins/) can also implement new types of mail transports. For example, you could deliver outgoing messages using a web-based API.

You're not limited to a single mail transport, either. You can choose a different transport for each of the email addresses you send mail from. If you send messages on behalf of several different organizations or brands, you may actually be required to use multiple SMTP servers for authentication or compliance.

When you're sending email, _deliverability_ should be your primary goal. You want your legitimate messages to land at the top of your recipient's inbox, and not be mistakingly discarded as spam[2](#fn:spam) (unwanted advertising, viruses, malware, or phishing attempts).

Several validation systems are used by mail servers to help approve legitimate email while blocking most spam:

- **SPF:** Sender Policy Framework[3](#fn:spf) defines the format of a simple text record that you add to the DNS[4](#fn:dns) for each domain you send email from. This record lists a series of network addresses that are permitted to send email on your behalf. For instance, if you specify that only your organization's own SMTP server is permitted to send email for your domain, then other mail servers will be far more suspicious of messages that purport to be from you but originate elsewhere. This helps combat _spoofing_ [5](#fn:spoofing), where malicious senders attempt to trick recipients into believing a message was sent by you.

- **DKIM:** DomainKeys Identified Mail[6](#fn:dkim) also attempts to detect spoofing and tampering by cryptographically signing an email message using a secret key (which should only be known by authorized senders). Any mail server can verify this digital signature by retrieving the corresponding public key from the DNS for a given sender domain. When a DKIM signature is successfully validated, a mail server can be confident that a message originated from a source that was authorized by the owner of the sender's domain name. This confidence score is generally highest when the domain of the DKIM signature matches that of the envelope sender and the `From:` header.

- **DMARC:** Domain-based Message Authentication, Reporting, and Conformance[7](#fn:dmarc) is built on top of SPF and DKIM. It defines policies with instructions on verifying the legitimacy of messages originating from a given domain, as well as policies for handling failed validation (including reporting back to the owner of a domain).

- **RBL:** Real-time Blackhole Lists[8](#fn:rbl) are shared registries of network addresses that have been previously reported for sending spam. A mail server may use one or more RBLs to reject mail from these senders. RBLs can be effective in reducing spam, but they are also responsible for blocking a lot of legitimate email in the process. A shared network address may be used to send email on behalf of multiple organizations, and all of them would be blocked if any one of them is reported for sending spam. A particular network address (especially in cloud computing) may become tainted by briefly being used to send spam, after which it is _released_ back into the shared pool for subsequent use by someone else.

When you create a new SMTP mail transport, you should verify that you have properly configured your SPF, DKIM, and DMARC records, and you should check the most popular RBL lists[9](#fn:rbl-check) to make sure your SMTP IP isn't listed.

# References

1. https://en.wikipedia.org/wiki/Simple\_Mail\_Transfer\_Protocol&nbsp;[↩](#fnref:smtp)

2. https://en.wikipedia.org/wiki/Spamming&nbsp;[↩](#fnref:spam)

3. https://en.wikipedia.org/wiki/Sender\_Policy\_Framework&nbsp;[↩](#fnref:spf)

4. https://en.wikipedia.org/wiki/Domain\_Name\_System&nbsp;[↩](#fnref:dns)

5. https://en.wikipedia.org/wiki/Email\_spoofing&nbsp;[↩](#fnref:spoofing)

6. https://en.wikipedia.org/wiki/DomainKeys\_Identified\_Mail&nbsp;[↩](#fnref:dkim)

7. https://en.wikipedia.org/wiki/DMARC&nbsp;[↩](#fnref:dmarc)

8. https://en.wikipedia.org/wiki/DNSBL&nbsp;[↩](#fnref:rbl)

9. http://www.anti-abuse.org/multi-rbl-check/&nbsp;[↩](#fnref:rbl-check)

