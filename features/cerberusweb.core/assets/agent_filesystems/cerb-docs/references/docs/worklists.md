---
id: "docs-worklists"
title: "Worklists"
url: "https://cerb.ai/docs/worklists/"
summary: "This page provides a comprehensive overview of worklists in Cerb, highlighting their functionality as customizable, searchable, pageable, and sortable sets of records. Key features include the ability to search and filter records, sort them by specific fields, and view detailed information through the peek function. Worklists support pagination for managing large sets of data and allow for subtotals to group records by similar values. Users can perform bulk updates on selected records, import and export data in various formats, and utilize explore mode for sequentially viewing record profiles. These features enable efficient data management and streamlined workflows within Cerb."
tags: ["docs"]
---
A **worklist** is a [searchable](#search), [pageable](#pagination), [sortable](#sorting), and customizable set of [records](/docs/records/) displayed using rows and columns.

Each row of a worklist is a matching record, and each column is a particular [field](/docs/records/#fields) from that record. The displayed columns can be customized for each worklist based on your needs.

 

By saving a worklist, you'll always have an up-to-date view of matching records without wasting any time searching.

For instance, a worklist can display new customer messages that need a response, client organizations in a specific industry, tasks that are overdue, etc.

- [Search](#search)
- [Columns](#columns)
  - [Sparkline columns](#sparkline-columns)
  - [Distribution bar columns](#distribution-bar-columns)

- [Sorting](#sorting)
- [Peek](#peek)
- [Pagination](#pagination)
- [Subtotals](#subtotals)
- [Bulk Update](#bulk-update)
- [Import/Export](#importexport)
- [Explore Mode](#explore-mode)

https://www.youtube.com/embed/cWF2lkmnAYU

# Search

The most useful feature of worklists is their ability to pull out interesting sets of records from your data using [search queries](/docs/search/).

For instance, you can build a worklist of email messages sent by organizations in the health care industry who have an enterprise SLA and also have at least one female contact whose name starts with the letter 'M'.

A worklist remembers its query per worker, so the filter you leave on it is the filter you come back to – across sessions, and including on a page you reached from a link that carried a query. If a worklist shows fewer records than you expect, check for a filter chip above it, and check that the quick search box is empty: a query typed but not applied still sits there waiting.

 

# Columns

Click the gear icon on a worklist to choose which columns are visible.

This is a searchable menu of available fields, segmented by [custom fieldset](/docs/records/types/custom_fieldset/), with icons hinting at each field's data type. Selecting a field dims it in the menu but leaves it in place, and the selected fields reorder in a single sortable column.

Start typing to filter. Filtering flattens the fieldset groups into a single list of matching fields, so the groups reappear when the filter is cleared.

### Sparkline columns

Several record types offer a **sparklines** column – a mini-chart of recent activity per row, with a `2h`/`1d`/`30d` range toggle in the column header.

These load asynchronously, per page, and only when the column is actually visible, so they cost nothing on worklists where you haven't enabled them.

| Record type | Column | Shows |
| --- | --- | --- |
| [Automation](/docs/records/types/automation/) | Usage | Runs, errors, duration |
| [Automation event](/docs/records/types/automation_event/) | Usage | Runs, errors, duration |
| Bot behavior | Usage | Runs, duration |
| [Mailbox](/docs/records/types/mailbox/) | Usage | Messages received, errors |
| [Mail routing rule](/docs/records/types/mail_routing_rule/) | Usage | Rule matches |
| [Mail transport](/docs/records/types/mail_transport/) | Usage | Deliveries, failures |
| [Queue](/docs/records/types/queue/) | Activity | Done, failed, open |
| [Search index](/docs/records/types/search_index/) | Records | Indexed record count |
| [Service token](/docs/records/types/service_token/) | Usage | Authentications |
| [Snippet](/docs/records/types/snippet/) | Usage | Uses |
| [Webhook listener](/docs/records/types/webhook_listener/) | Usage | Invocations |
| [Metric](/docs/records/types/metric/) | Dataset | Min, max, average, sum, count |

The Metric 'Dataset' column adapts to counters versus gauges, so each metric is charted the way it's meant to be read.

Because these are backed by [metrics](/docs/metrics/), each one also brings a matching quick search filter. See [parameterized metrics filters](/docs/search/#parameterized-metrics-filters).

### Distribution bar columns

A **distribution bar** column shows how a row's related records are split between states as a single horizontal stacked bar – for instance, a [task project](/docs/records/types/task_project/) worklist can show its tasks divided between done, stashed, todo, and in-progress.

# Sorting

In conjunction with filtering, **sorting** highlights the records of most interest by ordering a worklist by a particular field.

For example, you may be interested in the oldest messages in a list, or the opportunities with the highest potential value.

You can click on the column label to toggle sorting between ascending and descending order.

 

# Peek

When records are displayed in a worklist, you can hover over any row and click the **peek** icon to open its [card](/docs/cards/) without leaving the list.

 

# Pagination

When a worklist has many results, it's automatically divided into smaller chunks called **pages**.

You can navigate through the pages using **Next** and **Prev** links, or quickly jump to the first or last page.

 

# Subtotals

Another useful feature in Cerb is the ability to **subtotal** worklists by grouping records with similar values for a given field.

Perhaps you want to subtotal organizations by country, email conversations by group, tasks by owner, etc.

 

Once you've subtotaled a list, you can click on a particular category to automatically add a new filter to the worklist.

# Bulk Update

You can perform actions on an entire worklist, on selected records, or on a random sample of matching records of any size.

For instance, when looking at a long list of indistinguishable leads that need a followup, you can bulk assign a random set of 25 records to yourself.

You can also use random samples to run A/B tests.

 

Bulk updates run as parallel background [queue jobs](/docs/records/types/queue_job/) rather than blocking the browser. When an update starts, the queue job progress monitor popup opens. If you close your browser or navigate away, the job continues in the background and you'll receive a notification when it completes.

Bulk commenting is also available from the **Bulk Update** popup on every record type that supports [comments](/docs/records/types/comment/) (tickets, tasks, organizations, opportunities, time tracking, domains, servers, calls).

The popup can also be extended with actions of your own. An [automation](/docs/automations/) on the [`record.bulkUpdate`](/docs/automations/triggers/record.bulkUpdate/) trigger runs for each batch of selected records, and can prompt for whatever it needs before the job starts.

# Import/Export

Once you have a worklist filtered the way you want it, you can **export** data in CSV (comma-separated), JSON, JSONL, or XML formats. You also aren't limited to the fields displayed by the worklist; you can choose any fields, including those from related records.

Similarly, you can also **import** records on most worklists in CSV or JSONL format. You'll be given the opportunity to map columns in your import file to record fields.

 

Worklist imports and exports run as parallel background [queue jobs](/docs/records/types/queue_job/) rather than blocking the request. On completion, exported chunks are sorted and saved as a single file attachment linked to the job – workers can close their browser and the export will continue in the background. They will receive a notification when the file is ready.

# Explore Mode

When you need to view the [profile](/docs/profiles/) page of each matching [record](/docs/records/) in sequence, click the **explore** button below a worklist. This will create a consistent snapshot of the worklist at the current point-in-time and allow you to navigate through it. You can even send an explore set to another worker using its permalink.

You can use the `[` and `]` keyboard shortcuts to navigate backward and forward through the list, respectively.

 
