---
id: "docs-data-queries-platform-points"
title: "Data Queries: Platform Extension Points"
url: "https://cerb.ai/docs/data-queries/platform/points/"
summary: "This page provides information on how to use `platform.extensions.points` data queries to retrieve details about available platform extension points. The query returns a filterable and pageable list of platform extension points, with optional filtering, limiting, and pagination capabilities. Examples of response formats include dictionaries suitable for sheets and API results, as well as raw JSON output."
tags: ["docs"]
---
# platform.extension.points

`platform.extension.points` data queries return a filterable and pageable list of platform extension points.

### Inputs

| `filter:` | An optional keyword used to filter the results. |
| `limit:` | The desired number of results per page. |
| `page:` | The desired starting page (zero-based). |

### Response Formats

- **dictionaries** (default) returns a table-based format suitable for [sheets](/docs/sheets/) and API results.

### Examples

#### Query:

- [query](#)
- [response](#)

- 
```
type:platform.extension.points
format:dictionaries
```
- 
```
{
  "data": [
    {
      "id": "cerb.automation.trigger",
      "name": "Automation Trigger",
      "class": "Extension_AutomationTrigger"
    },
    {
      "id": "devblocks.event.action",
      "name": "Bot Action",
      "class": "Extension_DevblocksEventAction"
    },
    {
      "id": "devblocks.event",
      "name": "Bot Event",
      "class": "Extension_DevblocksEvent"
    },
    {
      "id": "devblocks.cache.engine",
      "name": "Cache Engine",
      "class": "Extension_DevblocksCacheEngine"
    },
    {
      "id": "cerberusweb.calendar.datasource",
      "name": "Calendar Datasource",
      "class": "Extension_CalendarDatasource"
    },
    {
      "id": "cerb.card.widget",
      "name": "Card Widget",
      "class": "Extension_CardWidget"
    },
    {
      "id": "cerb.connected_service.provider",
      "name": "Connected Service Provider",
      "class": "Extension_ConnectedServiceProvider"
    },
    {
      "id": "devblocks.controller",
      "name": "Controller",
      "class": "DevblocksControllerExtension"
    },
    {
      "id": "cerb.custom_field",
      "name": "Custom Field Type",
      "class": "Extension_CustomField"
    },
    {
      "id": "devblocks.listener.event",
      "name": "Event Listener",
      "class": "DevblocksEventListenerExtension"
    },
    {
      "id": "devblocks.listener.http",
      "name": "Http Request Listener",
      "class": "DevblocksHttpResponseListenerExtension"
    },
    {
      "id": "cerberusweb.mail.transport",
      "name": "Mail Transport Type",
      "class": "Extension_MailTransport"
    },
    {
      "id": "cerberusweb.ui.page.menu.item",
      "name": "Page Menu Item",
      "class": "Extension_PageMenuItem"
    },
    {
      "id": "cerberusweb.ui.page.section",
      "name": "Page Section",
      "class": "Extension_PageSection"
    },
    {
      "id": "cerberusweb.page",
      "name": "Page Type",
      "class": "CerberusPageExtension"
    },
    {
      "id": "cerb.portal",
      "name": "Portal",
      "class": "Extension_CommunityPortal"
    },
    {
      "id": "cerb.portal.layout.widget",
      "name": "Portal Layout Widget",
      "class": "Extension_PortalLayoutWidget"
    },
    {
      "id": "cerb.portal.page",
      "name": "Portal Page",
      "class": "Extension_PortalPage"
    },
    {
      "id": "cerb.portal.widget",
      "name": "Portal Widget",
      "class": "Extension_PortalWidget"
    },
    {
      "id": "cerberusweb.renderer.prebody",
      "name": "Prebody Renderer",
      "class": "Extension_AppPreBodyRenderer"
    },
    {
      "id": "cerb.profile.tab",
      "name": "Profile Tab Type",
      "class": "Extension_ProfileTab"
    },
    {
      "id": "cerb.profile.tab.widget",
      "name": "Profile Widget Type",
      "class": "Extension_ProfileWidget"
    },
    {
      "id": "devblocks.context",
      "name": "Record Type",
      "class": "Extension_DevblocksContext"
    },
    {
      "id": "cerb.resource.type",
      "name": "Resource Type",
      "class": "Extension_ResourceType"
    },
    {
      "id": "cerberusweb.rest.controller",
      "name": "Rest API Controller",
      "class": "Extension_RestController"
    },
    {
      "id": "cerberusweb.cron",
      "name": "Scheduled Job",
      "class": "CerberusCronPageExtension"
    },
    {
      "id": "cerberusweb.datacenter.sensor",
      "name": "Sensor Type",
      "class": "Extension_Sensor"
    },
    {
      "id": "devblocks.storage.engine",
      "name": "Storage Engine",
      "class": "Extension_DevblocksStorageEngine"
    },
    {
      "id": "devblocks.storage.schema",
      "name": "Storage Schema",
      "class": "Extension_DevblocksStorageSchema"
    },
    {
      "id": "usermeet.sc.controller",
      "name": "Support Center Controller",
      "class": "Extension_UmScController"
    },
    {
      "id": "usermeet.login.authenticator",
      "name": "Support Center Login Authenticator",
      "class": "Extension_ScLoginAuthenticator"
    },
    {
      "id": "usermeet.sc.rss.controller",
      "name": "Support Center RSS Feed",
      "class": "Extension_UmScRssController"
    },
    {
      "id": "cerberusweb.ui.workspace.page",
      "name": "Workspace Page Type",
      "class": "Extension_WorkspacePage"
    },
    {
      "id": "cerberusweb.ui.workspace.tab",
      "name": "Workspace Tab Type",
      "class": "Extension_WorkspaceTab"
    },
    {
      "id": "cerberusweb.ui.workspace.widget.datasource",
      "name": "Workspace Widget Datasource",
      "class": "Extension_WorkspaceWidgetDatasource"
    },
    {
      "id": "cerberusweb.ui.workspace.widget",
      "name": "Workspace Widget Type",
      "class": "Extension_WorkspaceWidget"
    }
  ],
  "_": {
    "type": "platform.extension.points",
    "format": "dictionaries"
  }
}
```

