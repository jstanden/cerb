---
id: "resources-workflows"
title: "Workflows"
url: "https://cerb.ai/resources/workflows/"
summary: "This page outlines various workflow templates in Cerb designed to synchronize records across different environments such as development, staging, and production. Key features include automated email notifications for @mentions, ticket assignment based on priority, and automatic responses for new tickets. It also covers tools for capturing user feedback, managing custom records for academic institutions, and conducting customer satisfaction surveys. Additional functionalities include parsing DMARC reports, generating profile images using Amazon Bedrock, geolocating IPs with IPstack, and integrating with GitLab for issue tracking. The page also details encryption options with PGP, organization assignment by email hostname, enforcement of Service Level Agreements, simplified ticket search, Slack notifications, and translation services via DeepL. Furthermore, it includes a test custom fieldset for development purposes and a requirement for workers to accept Terms of Use before logging in."
tags: []
---
[Workflows](/docs/workflows/) are templates with versioned updates that keep a related set of records in sync between multiple environments (e.g. dev, staging, production).

[Built-in](#built-in-workflows) [Community](#community-workflows)

## Built-in workflows

[ 
Auto Dispatcher
 
Automatically assign tickets to workers based on priority.
 ](/workflows/cerb.auto_dispatcher/) [ 
Auto Responder
 
Send an automatic response when new tickets are opened.
 ](/workflows/cerb.auto_responder/) [ 
Auto Watcher
 
Automatically add workers as a watcher when they reply to a ticket...
 ](/workflows/cerb.email.auto_watcher/) [ 
Capture Feedback
 
Capture user feedback while reading email messages.
 ](/workflows/cerb.capture_feedback/) [ 
Customer Satisfaction Surveys
 
Gather and monitor customer satisfaction metrics like NPS, CSAT, and CES.
 ](/workflows/cerb.satisfaction.surveys/) [ 
DMARC Reports
 
Automatically parse DMARC report attachments in email.
 ](/workflows/cerb.email.dmarc_reports/) [ 
Email Notification Mentions
 
Email workers when they are @mentioned in a comment.
 ](/workflows/cerb.notifications.mention_emailer/) [ 
Generate Profile Images (Amazon Bedrock)
 
Generate profile images from a text prompt using Amazon Bedrock foundational models....
 ](/workflows/cerb.integrations.aws_bedrock.profile_images/) [ 
Geolocate IPs (IPstack)
 
Geolocate IPs and render locations on maps with IPstack.
 ](/workflows/cerb.integrations.ipstack/) [ 
Issue Tracking (GitLab Issues)
 
Search and link GitLab issues to tickets.
 ](/workflows/cerb.integrations.gitlab.issues/) [ 
PGP Inline Encryption
 
Encrypt messages with PGP and paste them inline in outgoing email.
 ](/workflows/cerb.email.pgp_inline/) [ 
Record Reminders
 
Create reminders from record profiles and cards.
 ](/workflows/cerb.records.reminders/) [ 
Sender Org By Hostname
 
Assign organizations to new senders based on their email @hostname.
 ](/workflows/cerb.email.org_by_hostname/) [ 
Service Level Agreements
 
Enforce Service Level Agreements (SLA) for tickets from organizations.
 ](/workflows/cerb.sla/) [ 
Simple Ticket Search
 
Simplified point-and-click ticket search popup without using search queries.
 ](/workflows/cerb.search.simple/) [ 
Slack Notifications
 
Notify a Slack channel about new ticket messages.
 ](/workflows/cerb.integrations.slack.notifications/) [ 
Translate (DeepL)
 
Translate inbound and outbound email messages using the DeepL API.
 ](/workflows/cerb.integrations.deepl.translate/) [ 
Worker Login Terms of Use
 
Require acceptance of Terms of Use before a worker can log in....
 ](/workflows/cerb.login.terms_of_use/)

## Community workflows

[ 
Close Idle Tickets
 
Automatically close tickets after no activity during a given duration.
 ](/workflows/cerb.ticket.auto_close/) [ 
Custom Records (Academia)
 
A set of custom records for academic institutions: instructors, courses, rooms, and...
 ](/workflows/wgm.example.custom_records.academia/) [ 
Dropbox Integration
 
A workflow demonstrating integration between Cerb and Dropbox.
 ](/workflows/wgm.integrations.dropbox/) [ 
Export Ticket to JSON
 
Export tickets to a downloadable JSON file from their profile page.
 ](/workflows/wgm.ticket.export_json/) [ 
GitHub Issues
 
Integrate Cerb with GitHub's API
 ](/workflows/wgm.integrations.github/) [ 
Group (Opt-In) Watchers
 
Automatically add workers who are watching a group as watchers for tickets...
 ](/workflows/wgm.email.group_optin_watchers/) [ 
Group Watchers
 
Automatically add all group members as watchers for incoming tickets.
 ](/workflows/wgm.email.group_watchers/) [ 
New Ticket Templates
 
A workflow demonstrating how to create tickets from interactive snippet templates with...
 ](/workflows/wgm.example.new_ticket_interaction_with_snippets/) [ 
Similar Senders
 
Find a list of contacts with emails from the same hostname from...
 ](/workflows/cerb.email.samehost/) [ 
Smart Multi-Record Search
 
A smart search that can search multiple record types in a single...
 ](/workflows/wgm.search.multi_record/) [ 
Test Custom Fieldset
 
A custom fieldset for that includes an example of every custom field...
 ](/workflows/wgm.example.custom_fieldsets/)
