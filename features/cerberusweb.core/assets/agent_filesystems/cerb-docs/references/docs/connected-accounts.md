---
id: "docs-connected-accounts"
title: "Connected Accounts"
url: "https://cerb.ai/docs/connected-accounts/"
summary: "This page discusses the use of connected accounts in Cerb to enable automations to authenticate and interact with third-party APIs through cryptographic signing of HTTP requests. It highlights the flexibility this provides by allowing bots to access a wide range of services beyond predefined actions. The sharing of connected accounts is controlled by the account owner, allowing for both team-wide and individual access. Integration examples are provided for popular services like Amazon Web Services, Dropbox, Facebook, GitHub, LinkedIn, Salesforce, Slack, Stripe, and Twilio. The page also recommends creating automation functions for each service to centralize credential management and API interactions, facilitating secure and reusable automation processes."
tags: ["docs"]
---
[Automations](/docs/automations/) can use **connected accounts** to cryptographically sign (or otherwise authenticate) arbitrary HTTP requests for a specific [service provider](/docs/connected-services/).

This opens up entire third-party APIs to bots, rather than only offering a few hand-picked actions.

The _owner_ of a connected account determines how it's shared. For instance, a corporate ActivityPub account could be shared by an entire team, while a worker's private Salesforce account could be accessed by only them and their bots.

We have [integration examples](/solutions/#integrations) for many popular services:

- [Airtable](/solutions/integrations/airtable/)
- [Amazon Web Services](/solutions/integrations/aws/)
- [Azure](/solutions/integrations/azure/)
- [Dropbox](/solutions/integrations/dropbox/)
- [ElevenLabs](/solutions/integrations/elevenlabs/)
- [Exa](/solutions/integrations/exa/)
- [Facebook](/solutions/integrations/facebook/)
- [GitHub](/solutions/integrations/github/)
- [GitLab](/solutions/integrations/gitlab/)
- [Gmail](/solutions/integrations/gmail/)
- [ipstack](/solutions/integrations/ipstack/)
- [LinkedIn](/solutions/integrations/linkedin/)
- [Linkup](/solutions/integrations/linkup/)
- [Notion](/solutions/integrations/notion/)
- [OpenAI](/solutions/integrations/openai/)
- [OpenWeather](/solutions/integrations/openweather/)
- [Pinecone](/solutions/integrations/pinecone/)
- [Postmark](/solutions/integrations/postmark/)
- [Salesforce](/solutions/integrations/salesforce/)
- [SambaNova](/solutions/integrations/sambanova/)
- [Slack](/solutions/integrations/slack/)
- [Smartsheet](/solutions/integrations/smartsheet/)
- [Stripe](/solutions/integrations/stripe/)
- [Tavily](/solutions/integrations/tavily/)
- [Together.ai](/solutions/integrations/together-ai/)
- [Twilio](/solutions/integrations/twilio/)

We recommend creating an automation function for each service (e.g. _Facebook Bot_) to act as a delegate. That way the credentials and API interaction for a particular service are handled in a single place, and any number of other automations can use [automation.function:](/docs/automations/commands/function/) to interface with those services in a secure and reusable way.

