---
id: "docs-toolbars"
title: "Toolbars"
url: "https://cerb.ai/docs/toolbars/"
summary: "This page provides a detailed overview of toolbars in Cerb, focusing on their configuration and functionality. It explains that toolbars are collections of interactions and menus, with top-level items displayed as buttons and menu items as links. The page introduces the KATA dialect used for configuring toolbars, allowing custom input through placeholders. It describes how interactions are initiated and the various attributes that can be configured, such as URI, inputs, labels, icons, tooltips, keyboard shortcuts, badges, and conditional visibility. Additionally, it covers menu configurations, which can include interactions and submenus. The page also lists various toolbar configurations available in Cerb, such as for automation editing, email composing, and record viewing."
tags: ["docs"]
---
A **toolbar** is a collection of [**interactions**](/docs/interactions/) and **menus**.

Top-level toolbar items are displayed as **buttons**. Items within a menu are displayed as **links**.

- [KATA](#kata)
  - [interaction:](#interaction)
  - [menu:](#menu)

- [Records](#records)

 

# KATA

Toolbars are configured using a [KATA](/docs/kata/) dialect, which can pass custom [input](/docs/automations/#inputs) to the interaction using placeholders (current worker, record, etc).

### interaction:

An interaction begins when it is clicked in the toolbar.

```
interaction/participants:
  label: Participants
  badge: {{record_participants|length|default(0)}}
  uri: cerb:automation:cerb.ticket.participants.manage
  inputs:
    ticket@key: record_id
  #hidden@bool: no
  after:
    refresh_widgets@csv: Actions

interaction/locationByIp:
  uri: cerb:automation:example.interaction.locationByIP
  label: Location by IP
  icon: globe
  after:
    refresh_widgets@list: Actions
```

| Req'd | Key | &nbsp; |
| --- | --- | --- |
| **x** | `uri:` | The URI of the [interaction.worker](/docs/automations/triggers/interaction.worker/) [automation](/docs/automations/) to start when clicked. |
| &nbsp; | `inputs:` | The optional [inputs](/docs/automations/#inputs) dictionary for the interaction. |
| &nbsp; | `label:` | The label to describe the interaction in buttons and menu links. This may be omitted if an icon is provided. |
| &nbsp; | `icon:` | The optional [icon](/docs/developers/icons/) to display in buttons and menu links. This can be in addition to, or instead of, the label. |
| &nbsp; | `tooltip:` | If a button only has an icon and not a label, the tooltip can show a label when hovering over it. |
| &nbsp; | `keyboard:` | An alternative keyboard shortcut to start the interaction (e.g. `Ctrl+Shift+K`) |
| &nbsp; | `badge:` | The optional counter to display on buttons. |
| &nbsp; | `hidden@bool:` | Conditionally determine whether to display this toolbar item or not. For instance, check worker permissions or record fields. |
| &nbsp; | `after:` | Actions to take when the interaction completes successfully. For instance, a completed interaction on a dashboard can refresh any number of widgets by name to show updated data. Options here depend on the toolbar. |

### menu:

Menus may contain any combination of interactions and submenus.

```
menu/moreMenu:
  icon: more
  tooltip: More
  items:
    menu/tools:
      label: Tools
      items:
        interaction/debug:
          uri: cerb:automation:example.interaction.echo
          label: Debug
          icon: bug
```

 

| Req'd | Key | &nbsp; |
| --- | --- | --- |
| **x** | `label:` | The label to describe the menu in buttons and menu links. This may be omitted if an icon is provided. |
| &nbsp; | `icon:` | The optional [icon](/docs/developers/icons/) to display in buttons and menu links. This can be in addition to, or instead of, the label. |
| **x** | `items:` | A list of [menu](#menu) and [interaction](#interaction) items. |
| &nbsp; | `default:` | Display a "split" menu button. Clicking on the left-side immediately runs this default [interaction](#interaction) by name. Clicking on the right-side opens a menu of alternative options. |

# Records

Toolbars can be configured from **Search&nbsp;» Toolbars**.

There is no longer an `agent.pane` toolbar. Which [AI agents](/docs/agents/) an editor's agent pane and the command bar offer is configured [on each agent](/docs/toolbars/interactions/agent.pane/) instead.

| Toolbar | &nbsp; |
| --- | --- |
| [automation.editor](/docs/toolbars/interactions/automation.editor/) | Editing an automation |
| [comment.editor](/docs/toolbars/interactions/comment.editor/) | Editing a comment |
| [draft.read](/docs/toolbars/interactions/draft.read/) | Reading a draft message |
| [global.menu](/docs/toolbars/interactions/global.menu/) | Global interactions from the floating icon in the lower right |
| [global.search](/docs/toolbars/interactions/global.search/) | Searching from the top right of any page |
| [mail.compose](/docs/toolbars/interactions/mail.compose/) | Composing new email messages |
| [mail.read](/docs/toolbars/interactions/mail.read/) | Reading email messages |
| [mail.reply](/docs/toolbars/interactions/mail.reply/) | Replying to email messages |
| [record.card](/docs/toolbars/interactions/record.card/) | Viewing a record card popup |
| [record.profile](/docs/toolbars/interactions/record.profile/) | Viewing a record profile page |
| [record.profile.image.editor](/docs/toolbars/interactions/record.profile.image.editor/) | Editing a record profile image |
| [records.worklist](/docs/toolbars/interactions/records.worklist/) | Viewing a worklist of records |

