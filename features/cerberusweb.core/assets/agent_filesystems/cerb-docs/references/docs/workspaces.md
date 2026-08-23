---
id: "docs-workspaces"
title: "Workspaces"
url: "https://cerb.ai/docs/workspaces/"
summary: "This page explains the concept of workspaces in Cerb, which are customizable pages designed to enhance specific workflows. Workspaces can be shared with everyone, restricted to a group or role, or kept private for individual use. Users can add multiple workspace pages to their navigation bar, effectively turning Cerb into a personalized mission control. These workspaces utilize tabs to organize content into sections, similar to dividers in a binder. The default type of workspace is a dashboard, which is a flexible and responsive collection of visualization widgets."
tags: ["docs"]
---
A **workspace** is a page designed to optimize a particular workflow. Workspaces can be shared by everyone, shared within a [group](/docs/groups/) or [role](/docs/roles/), or private to a specific [worker](/docs/workers/).

You can add any number of workspace pages to your navigation bar and personalize Cerb into your own mission control.

Workspaces use **tabs** to organize their content into sections; much like dividers are used to partition pages in a large binder.

The default workspace type is a [dashboard](/docs/dashboards/) – a customizable and responsive collection of visualization widgets.

# Tab types

| Type | Description |
| --- | --- |
| [Dashboard](/docs/dashboards/) | A responsive collection of visualization widgets. The default. |
| Worklists | One or more record [worklists](/docs/worklists/). |
| [Daily Task Board](#daily-task-board) | A personal, day-oriented planning board. |
| [Project Board](/docs/project-boards/) | A board that organizes multi-step processes. |
| Calendar | A [calendar](/docs/calendars/) of dates and events. |

Worklist and dashboard tabs can [refresh themselves](/docs/dashboards/#auto-refresh) on an interval.

A tab's worklists can be configured directly from the tab rather than through the tab's edit popup, and tab configuration uses the [Cerb UI](/docs/developers/cerb-ui/) design system.

# Daily Task Board

A **Daily Task Board** is a personal, day-oriented planning board built on real [task](/docs/records/types/task/) records and [task projects](/docs/records/types/task_project/).

Unlike a [project board](/docs/project-boards/), **nothing board-specific is stored**. Every column and day placement is derived from a task's status, active flag, importance, and completion or wake dates – so the board and the underlying tasks can never disagree.

### Layout

The board is a horizontal timeline of days, newest first.

- **Today** is fully interactive, with TODO, In Progress, and Done columns
- **The previous six days** are a read-only completion log
- **The Stash** spans the board and holds waiting tasks, grouped by wake date

The scheduler revives a stashed task into today's TODO once its "stashed until" date passes.

**Jump to Date** renders any off-window day on demand – a past completion log or a future stash column – with month pips marking which days carry completed or waiting tasks.

### Working with cards

Cards support inline title editing, drag-and-drop between columns and days, manual ranking in the TODO lane by importance, quick-add, delete, and moving a task to another project.

**Quick Triage** nudges a card's importance up or down. A multi-select column action bulk-moves tasks between columns or projects. Each card's meta strip shows the assignee's avatar with an "assign to me" claim.

### Configuration

Each board has a **shared** configuration stored on the tab – which [task projects](/docs/records/types/task_project/) appear, their accent colors, and their order.

Every worker then overlays a **personal** selection, order, and "focus my tasks" preference on top, so one shared board serves a team without everyone seeing the same thing.

Project accent colors seed automatically from a standard palette.

### Permissions

A task's permissions derive from its [project](/docs/records/types/task_project/). Archived or unreadable projects are dropped from the board gracefully.

# Dynamic tabs

Workspace tabs support the `invoke` pattern, matching workspace pages and widgets. A tab can implement dynamic behavior without adding a custom page section controller.

 
