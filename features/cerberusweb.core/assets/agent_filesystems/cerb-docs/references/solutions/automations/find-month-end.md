---
id: "solutions-automations-find-month-end"
title: "Find month end dates"
url: "https://cerb.ai/solutions/automations/find-month-end/"
summary: "This page demonstrates how to use the `|date_modify` filter to find the last day of each month in a given year. It shows how to iterate through months and format the output as readable dates."
tags: ["solutions", "solutions-automations"]
---
## Finding last day of each month

Here is an example of using the [date()](/docs/scripting/functions/#date) function to create date objects and the [|date\_modify](/docs/scripting/filters/#date_modify) filter to find the last day of each month in a given year.

- [automation](#)
- [output](#)

- 
```
start:
  return:
    output@text:
      {% for mo in range(1,12) %}
      {{date('2028-' ~ mo)|date_modify('last day of this month')|date('r')}}
      {% endfor %}
```
- 
```
__return:
  output: |
    Mon, 31 Jan 2028 00:00:00 -0800
    Tue, 29 Feb 2028 00:00:00 -0800
    Fri, 31 Mar 2028 00:00:00 -0700
    Sun, 30 Apr 2028 00:00:00 -0700
    Wed, 31 May 2028 00:00:00 -0700
    Fri, 30 Jun 2028 00:00:00 -0700
    Mon, 31 Jul 2028 00:00:00 -0700
    Thu, 31 Aug 2028 00:00:00 -0700
    Sat, 30 Sep 2028 00:00:00 -0700
    Tue, 31 Oct 2028 00:00:00 -0700
    Thu, 30 Nov 2028 00:00:00 -0800
    Sun, 31 Dec 2028 00:00:00 -0800
```

