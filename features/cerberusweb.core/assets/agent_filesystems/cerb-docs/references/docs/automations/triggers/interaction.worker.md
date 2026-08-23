---
id: "docs-automations-triggers-interaction-worker"
title: "interaction.worker"
url: "https://cerb.ai/docs/automations/triggers/interaction.worker/"
summary: "This page provides a comprehensive overview of the 'interaction.worker' automations in Cerb, which are designed to facilitate conversational processes that can pause to collect additional input, such as through web-based forms or external events. It details the structure and functionality of these interactions, including inputs, outputs, and the various states like await:form, await:draft, await:duration, await:interaction, and await:record. The page also explains how interactions are initiated by callers, such as toolbars and built-in features like project boards and sheets, and how they return results to the caller. The document emphasizes the modularity and reusability of these interactions, allowing them to be easily integrated and customized within different components of Cerb."
tags: ["docs", "docs-automations"]
---
**interaction.worker** [automations](/docs/automations/) are [conversational](/docs/interactions/) processes that can [pause](/docs/automations/#continuations) between steps to collect additional input, such as web-based forms or other external events.

An interaction continues to the next step once additional input is received.

The most common source of additional input is a web-based form with multiple fields.

- [Inputs](#inputs)
- [Outputs](#outputs)
  - [await:form:](#awaitform)
  - [await:draft:](#awaitdraft)
  - [await:duration:](#awaitduration)
  - [await:interaction:](#awaitinteraction)
  - [await:record:](#awaitrecord)
  - [return:](#return)

- [Callers](#callers)
  - [Toolbars](#toolbars)
  - [Built-in](#built-in)
    - [Interactions](#interactions)
    - [Project boards](#project-boards)
    - [Sheets](#sheets)
    - [Internal](#internal)

 

These interactions are started by a [caller](#callers) in response to a worker action within Cerb. The caller is usually a customizable [toolbar](/docs/toolbars/), but it could be any interface component or feature (e.g. buttons, links, images).

At its conclusion, an interaction returns a [dictionary](/docs/automations/#dictionaries) and [exit state](/docs/automations/#exit-states) to the caller, which is then responsible for acting on the results.

Each caller provides its own inputs, as well as the possible responses it accepts.

This separation of duties makes interactions simpler to build and reuse. An interaction doesn't have to be concerned with the capabilities of a specific caller.

For instance, an interaction may be started from a toolbar in the email reply editor to fetch information based on the recipients (e.g. order history). The interaction generates and returns a text snippet, which the caller pastes into the editor at the cursor. The interaction only needs to generate some text, without any consideration for how it will be pasted into the editor.

 

# Inputs

An interaction automation [dictionary](/docs/automations/#dictionaries) starts with the following input values:

| Key | Type | Notes |
| --- | --- | --- |
| `caller_name` | string | The [caller](#callers) which started the interaction. |
| `caller_params` | dictionary | Built-in parameters based on the caller type. |
| `client_browser_name` | string | The client browser name (e.g. Safari). |
| `client_browser_platform` | string | The client browser platform (e.g. Macintosh). |
| `client_browser_version` | string | The client browser version. |
| `client_ip` | string | The client IP address. |
| `client_url` | string | The client browser URL for the current page. |
| `inputs` | dictionary | [Custom input](/docs/automations/#inputs) values from the caller. |
| `worker_*` | record | The active [worker](/docs/records/types/worker/) record. Supports key expansion. |

# Outputs

## await:form:

**You don't have to write this by hand.** The automation editor includes a visual **Form Builder**: drag components onto a canvas that renders as the finished form, and the [KATA](/docs/kata/) below is generated for you.

When suspending in the `await:form:` state, the interaction displays a web form with the desired elements. The form may prompt for user input, validate it, and set dictionary keys (placeholders) with the responses.

```
await:
  form:
    title: Your form title
    elements:
      # ...
```

### title:

The title of this form to be displayed in the interaction popup. This is usually a summary of the current step.

### resume:

An [interaction](/docs/interactions/) that parks is offered back to you later – in the command bar's Resume list, and in an [agent pane](/docs/toolbars/interactions/agent.pane/)'s History. The optional `resume:` block overrides how it reads there.

```
await:
  form:
    title: Draft a reply
    resume:
      label: Reply to {{ticket_subject}}
      preview: {{last_message_excerpt}}
      icon: mail
      color: '#3b82f6'
    elements:
      # ...
```

| Key | Notes |
| --- | --- |
| `label:` | The conversation's name in the list |
| `preview:` | A line of context below the name, to tell similar conversations apart |
| `icon:` | An [icon](/docs/developers/icons/) name |
| `color:` | The icon's color |

**None of this is required.** A resumable conversation is already named and iconed after the toolbar item that launched it, and a form containing an [`llmTranscript`](/docs/automations/triggers/interaction.worker/elements/llmTranscript/) additionally picks up the model provider's brand mark and an excerpt of the latest prompt. Set a key here only when you know a better value than those defaults.

Values are **sticky**: an await that omits a key leaves the stored one alone. That's what keeps a long LLM turn parked on `await:queue:` from blanking the name its conversation was given.

Whether a conversation can be resumed at all isn't decided here – that's the launcher's business.

### elements:

Form elements are defined with a key in the format `type/name:`.

The `name` must be unique within the form. When an element prompts for user input, a placeholder with the same name will be created with their response. For instance, `text/prompt_name:` will create a placeholder of `{{prompt_name}}` with the value of that text element.

A form can be created with any combination of the following element types:

| Element | &nbsp; |
| --- | --- |
| [**agentPrompt:**](/docs/automations/triggers/interaction.worker/elements/agentPrompt/) | Composer for an [AI agent](/docs/agents/) conversation |
| [**audio:**](/docs/automations/triggers/interaction.worker/elements/audio/) | Play an audio file |
| [**chart:**](/docs/automations/triggers/interaction.worker/elements/chart/) | Render an interactive data visualization |
| [**chooser:**](/docs/automations/triggers/interaction.worker/elements/chooser/) | A search popup for selecting records |
| [**editor:**](/docs/automations/triggers/interaction.worker/elements/editor/) | A code editor with syntax highlighting, autocompletion, and a custom toolbar |
| [**fileDownload:**](/docs/automations/triggers/interaction.worker/elements/fileDownload/) | File download prompt |
| [**fileUpload:**](/docs/automations/triggers/interaction.worker/elements/fileUpload/) | File upload prompt |
| [**llmTranscript:**](/docs/automations/triggers/interaction.worker/elements/llmTranscript/) | AI agent chat transcript |
| [**map:**](/docs/automations/triggers/interaction.worker/elements/map/) | Interactive [map](/docs/maps/) |
| [**query:**](/docs/automations/triggers/interaction.worker/elements/query/) | [Search query](/docs/search/) prompt with autocompletion |
| [**say:**](/docs/automations/triggers/interaction.worker/elements/say/) | Block of text or Markdown |
| [**sheet:**](/docs/automations/triggers/interaction.worker/elements/sheet/) | [Sheet](/docs/sheets/) with row selection |
| [**submit:**](/docs/automations/triggers/interaction.worker/elements/submit/) | Continue to next step |
| [**text:**](/docs/automations/triggers/interaction.worker/elements/text/) | Text input with data types |
| [**textarea:**](/docs/automations/triggers/interaction.worker/elements/textarea/) | Multiple lines of text |
| [**uiCommand:**](/docs/automations/triggers/interaction.worker/elements/uiCommand/) | Run a command in the host editor |

A `uiCommand:` element round-trips a command to the editor an interaction was launched beside, which requires a caller that provides a command bridge. It's advertised on the [interaction.worker.agent](/docs/automations/triggers/interaction.worker.agent/) trigger, which extends this one and offers every element listed here as well.

When the interaction suspends in the `await` state, a `submit:` element is automatically appended to the form if one doesn't already exist.

```
start:
  await/who:
    form:
      title: Introduction
      elements:
        text/prompt_name:
          label: What is your name?
          required@bool: yes
  
  await/hello:
    form:
      title: Hello!
      elements:
        say/hello:
          content: Hello, {{prompt_name}}!
  
  return:
    user:
      name@key: prompt_name
```

## await:draft:

When suspending in the `await:draft:` state, the interaction opens the email editor popup and waits for completion.

### uri:

The `uri:` parameter specifies a compose or reply [draft](/docs/records/types/draft/) to resume (by ID or token). An automation can create a draft record prior to this step.

### output:

An optional `output:` parameter has the following keys:

| `status` | One of: `compose.sent`, `compose.draft`, `compose.discard`, `reply.sent`, `reply.draft`, `reply.discard` |
| `record` | The dictionary of the record. For `.sent` this will be a [message](/docs/records/types/message/). For `.draft` it will be a [draft](/docs/records/types/draft/). |

This allows the interaction to make decisions based on those outcomes.

```
start:
  record.create:
    output: draft_record
    inputs:
      record_type: draft
      # See: https://cerb.ai/docs/records/types/draft/
      fields:
        name: Draft for customer@cerb.example
        type: mail.compose
        worker_id@key,int: worker_id
        params:
          to: customer@cerb.example
          content@text:
            Hi,
            
            #signature

  await/resume:
    draft:
      uri: cerb:draft:{{draft_record.id}}
      output: results_draft

  outcome/sent:
    if@bool: {{results_draft.status ends with '.sent'}}
    then:
      await:
        form:
          say: Message sent!
```

## await:duration:

 

When suspending in the `await:duration:` state, the interaction displays a `message:` and waits `until:` a given date/time or interval (e.g. `5 seconds`).

This can be used to display a status update while waiting for a long-running asynchronous task to finish. For instance, an AWS Step Function for new account provisioning.

### message:

The `message:` to be displayed in the interaction while waiting.

### until:

The `until:` parameter is an absolute (`2021-12-31 08:00 America/New_York`) or relative (`10 seconds`) duration to wait for before resuming.

### output:

(none)

```
start:
  set:
    isPlaying@bool: yes
  
  while:
    if@bool: {{isPlaying}}
    do:
      # A suspenseful wait
      await/rolling:
        duration:
          message: Rolling...
          until: 3 seconds
      
      await/rolled:
        form:
          title: Results
          elements:
            say:
              content@text:
                You rolled a {{random(1,6)}}
                ====
            submit/prompt_continue:
              buttons:
                continue/yes:
                  label: Roll again
                  icon: playing-dices
                  icon_at: start
                  value: roll
                continue/no:
                  label: Quit
                  style: secondary
                  value: quit
      
      # If quitting, break the loop
      outcome/quit:
        if@bool: {{prompt_continue == 'quit'}}
        then:
          set:
            isPlaying@bool: no
```

## await:interaction:

When suspending in the `await:interaction:` state, the interaction temporarily hands control to another delegate interaction. The interaction resumes at the current point when the delegate exits.

Delegates can be nested to any depth. For instance, a reusable delegate could handle email or SMS validation, and be shared by many other interactions.

This makes interactions much more modular and reusable.

### uri:

The `uri:` parameter specifies the delegate [automation](/docs/records/types/automation/). This must use the [interaction.worker](/docs/automations/triggers/interaction.worker/) trigger.

### output:

An `output:` key specifies the placeholder that should receive the results from the delegate.

```
start:
  while:
    if@bool: yes
    do:
      await/menu:
        form:
          title: Menu
          elements:
            say:
              message: How can we help?
            sheet/prompt_menu:
              required@bool: yes
              data:
                0:
                  key: map
                  label: Map
                1:
                  key: echo
                  label: Echo
              schema:
                layout:
                  style: buttons
                  headings@bool: no
                  paging@bool: no
                  title_column: label
                columns:
                  selection/key:
                    params:
                      mode: single
                  text/label:
            submit:
              continue@bool: no
              reset@bool: no
      await/do:
        interaction:
          output: results
          uri@text:
            cerb:automation:{{{
                'map': 'wgm.interaction.locationByIP',
                'echo': 'wgm.interaction.echo',
              }[prompt_menu]}}
```

## await:record:

When suspending in the `await:record:` state, the interaction opens a record editor popup and waits for completion.

### uri:

This takes a `uri:` parameter which specifies a record type (and optionally record ID) to open in an editor popup.

If the ID is omitted then the editor is opened in 'create' mode.

### output:

An optional `output:` parameter specifies a placeholder to store the status of the record after creation/modification (e.g. saved, deleted, aborted).

| `event` | `record.created`, `record.updated`, `record.deleted`, `record.aborted` |
| `record` | The dictionary of the created or modified record. |

This allows the interaction to make decisions based on those outcomes.

```
inputs:
  text/record_type:
    type: record_type
    required@bool: yes
  record/column:
    record_type: project_board_column
    required@bool: yes

start:
  await:
    record:
      uri: cerb:{{inputs.record_type}}
      output: result
  outcome:
    if@bool: {{result.record._context and result.record.id}}
    then:
      record.update:
        output: new_record
        inputs:
          record_type: {{result.record._context}}
          record_id: {{result.record.id}}
          fields:
            links@list:
              project_board_column:{{inputs.column.id}}
```

## return:

When the interaction concludes in the `return` state, it returns any number of key/value pairs to the caller. Keys may be nested to return dictionaries.

Each [caller](#callers) has a set of expected return keys to control its behavior.

```
return:
  key1: value1
  key2: value2
  ...
```

The following keys are available on all worker interactions:

| Key | Description |
| --- | --- |
| `alert:` | Display a time-limited message at the top of a worker's browser. This is particularly useful to confirm non-interactive actions (e.g. "Copied!"). |
| `callout:` | Trigger a callout in the UI. This is particularly useful for onboarding, tutorials, and tours. A callout requires a DOM `selector` and `message` to display. Optional `my` and `at` position elements align the callout to the target element (e.g. `right top`, `left+20 bottom-5`. |
| `clipboard:` | Copy the given text to the worker's keyboard. This is only available in response to a worker gesture (e.g. clicking an interaction toolbar). |
| `open_link:` | Open a new browser tab with the given URL. |
| `open_url:` | Open the given URL in the current browser tab. |
| `search:` | Open a search popup with the given `record_type:` and `query:`. |
| `snippet:` | If the interaction was started from an editor, paste the given text at the cursor. |
| `timer:` | Start a time tracking timer with the given time entry record ID. |

# Callers

An interaction receives different inputs and expects different outputs depending on its caller.

## Toolbars

| Toolbar | &nbsp; |
| --- | --- |
| [**global.menu**](/docs/toolbars/interactions/global.menu/) | Global interactions from the floating icon in the lower right |
| [**mail.compose**](/docs/toolbars/interactions/mail.compose/) | Composing new email messages |
| [**mail.read**](/docs/toolbars/interactions/mail.read/) | Reading email messages |
| [**mail.reply**](/docs/toolbars/interactions/mail.reply/) | Replying to email messages |
| [**record.card**](/docs/toolbars/interactions/record.card/) | Viewing a record card popup |
| [**record.profile**](/docs/toolbars/interactions/record.profile/) | Viewing a record profile page |

## Built-in

### Interactions

| Caller | &nbsp; |
| --- | --- |
| [**cerb.toolbar.cardWidget.interactions**](/docs/automations/triggers/interaction.worker/callers/cerb.toolbar.cardWidget.interactions/) | Interactions [toolbar](/docs/toolbars/) in [card widgets](/docs/records/types/card_widget/) |
| [**cerb.toolbar.profileWidget.interactions**](/docs/automations/triggers/interaction.worker/callers/cerb.toolbar.profileWidget.interactions/) | Interactions [toolbar](/docs/toolbars/) in [profile widgets](/docs/records/types/profile_widget/) |
| [**cerb.toolbar.workspaceWidget.interactions**](/docs/automations/triggers/interaction.worker/callers/cerb.toolbar.workspaceWidget.interactions/) | Interactions [toolbar](/docs/toolbars/) in [workspace widgets](/docs/records/types/workspace_widget/) |

### Project boards

| Caller | &nbsp; |
| --- | --- |
| [**cerb.toolbar.projectBoardColumn**](/docs/automations/triggers/interaction.worker/callers/cerb.toolbar.projectBoardColumn/) | [Project board](/docs/project-boards/) column [toolbar](/docs/toolbars/) |

### Sheets

| Caller | &nbsp; |
| --- | --- |
| [**cerb.toolbar.automation.interaction.worker.await.sheet**](/docs/automations/triggers/interaction.worker/callers/cerb.toolbar.automation.interaction.worker.await.sheet/) | [Sheet prompt](/docs/automations/triggers/interaction.worker/elements/sheet/) toolbar in an [interaction](/docs/interactions/) |
| [**cerb.toolbar.cardWidget.sheet**](/docs/automations/triggers/interaction.worker/callers/cerb.toolbar.cardWidget.sheet/) | [Sheet](/docs/sheets/) toolbar in [card widgets](/docs/records/types/card_widget/) |
| [**cerb.toolbar.profileWidget.sheet**](/docs/automations/triggers/interaction.worker/callers/cerb.toolbar.profileWidget.sheet/) | [Sheet](/docs/sheets/) toolbar in [profile widgets](/docs/records/types/profile_widget/) |

### Internal

| Caller | &nbsp; |
| --- | --- |
| [**cerb.toolbar.editor.automation.script**](/docs/automations/triggers/interaction.worker/callers/cerb.toolbar.editor.automation.script/) | [Automation](/docs/automations/) script editor toolbar |
| [**cerb.toolbar.editor.automation.trigger**](/docs/automations/triggers/interaction.worker/callers/cerb.toolbar.editor.automation.trigger/) | [Automation](/docs/automations/) editor trigger chooser |
| [**cerb.toolbar.eventHandlers.editor**](/docs/automations/triggers/interaction.worker/callers/cerb.toolbar.eventHandlers.editor/) | [Automation](/docs/automations/) event handlers editor toolbar |
| [**cerb.toolbar.editor**](/docs/automations/triggers/interaction.worker/callers/cerb.toolbar.editor/) | [Toolbar](/docs/toolbars/) editor |
| [**cerb.toolbar.editor.map**](/docs/automations/triggers/interaction.worker/callers/cerb.toolbar.editor.map/) | Map editor toolbar |

