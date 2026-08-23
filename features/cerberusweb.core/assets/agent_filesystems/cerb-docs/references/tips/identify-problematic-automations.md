---
id: "tips-identify-problematic-automations"
title: "Identity problematic automations or behaviors"
url: "https://cerb.ai/tips/identify-problematic-automations/"
summary: "This page provides tips on identifying problematic automations or behaviors in Cerb, which can be optimized to run less frequently or more efficiently. To find automations that have run too often or take too long to execute, users can use data queries to track automation and behavior invocations over the past month, and then visualize the results using a chart or data query tester. By analyzing these metrics, users can identify slowest automations and behaviors and optimize them with better conditions in their automation events."
tags: ["tips"]
---
You can use a [data query](docs/data-queries/) to identify which automations or behaviors have run in the past month and how many times. If an automation runs too often, you can optimize it with better `disabled@bool:` conditions in the automation event.

You can add these queries to a chart or run then in the data query tester found in **Setup&nbsp;» Developers&nbsp;» Data Query Tester**:

```
type:metrics.timeseries
series.automations:(
  metric:cerb.automation.invocations
  by:[automation_id]
)
series.behaviors:(
  metric:cerb.behavior.invocations
  by:[behavior_id]
)
range:"-1 month"
period:year
format:dictionaries
```

You can find the slowest automations and behaviors (in milliseconds) with this query:

```
type:metrics.timeseries
series.automations:(
  metric:cerb.automation.invocations
  by:[automation_id]
)
series.behaviors:(
  metric:cerb.behavior.invocations
  by:[behavior_id]
)
range:"-1 month"
period:year
format:dictionaries
```
