---
id: "docs-project-boards"
title: "Project Boards"
url: "https://cerb.ai/docs/project-boards/"
summary: "This page provides an overview of Cerb's project boards, which are based on the kanban development process. It explains how project boards organize work into cards and columns, emphasizing the principle of limiting work in progress. The page highlights the simplicity of kanban and describes how Cerb's digital implementation enhances this process with features like team visibility, activity logs, and automations for card movements. These automations can update fields, add comments, send notifications, and more, ensuring that cards display the most current information."
tags: ["docs"]
---
 

**Project boards** are based on the kanban[1](#fn:kanban-dev) development process, which itself was derived from improvements to just-in-time production pioneered by Japanese manufacturer Toyota in the 1940s[2](#fn:kanban).

**Project boards vs. the Daily Task Board.** A project board is a shared, team-facing board whose columns you define. The [Daily Task Board](/docs/workspaces/#daily-task-board) is a personal, day-oriented board whose columns and placement are _derived_ from a task's own status and dates rather than stored on the board. Use a project board to model a process; use a Daily Task Board to plan your day.

A card's board placement derives from its [task project](/docs/records/types/task_project/) and active flag rather than a stored board configuration, and the board templates are restyled on [Cerb UI](/docs/developers/cerb-ui/).

With kanban, each project has a **board**, and each unit of work in that project is represented by a **card**. Cards are organized into **columns** on the board based on their stage of completion. Typically, cards move from left to right through the columns of the process.

One of the core principles of kanban is limiting the amount of work in progress. For instance, while there may be a large number of cards in a leftmost column named **"TODO"**, there is an agreed upon limit of 2-3 cards in the next **"In Progress"** column. Cerb does not enforce these limits for you, but a useful convention is adding the limit to the end of the column name – like **"In Progress (3)"**.

One of the most compelling advantages of the kanban process is its simplicity. It can be implemented with a physical white board that has been divided into columns, with a stack of index cards (or Post-it® notes) for the tasks.

However, Cerb's digital implementation of kanban provides several enhancements:

- The project boards are visible to the entire team from anywhere.

- An activity log provides a full history of changes to the project and its cards.

- [Automations](/docs/automations/) can be [triggered](/docs/automations/triggers/projectBoard.cardAction/) every time a card is moved into a new column. This can automate field changes, comments, notifications, webhooks, or anything else.

- Automations can also [customize the display of cards](/docs/automations/triggers/projectBoard.renderCard/) based on dynamic factors like record type, field values, custom fields, and the current column. Cards always show the most recent information available.

### References

1. Wikipedia: Kanban (development) - https://en.wikipedia.org/wiki/Kanban\_(development))&nbsp;[↩](#fnref:kanban-dev)

2. Wikipedia: Kanban - https://en.wikipedia.org/wiki/Kanban&nbsp;[↩](#fnref:kanban)

