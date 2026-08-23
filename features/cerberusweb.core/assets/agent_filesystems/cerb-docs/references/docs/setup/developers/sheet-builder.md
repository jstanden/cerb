---
id: "docs-setup-developers-sheet-builder"
title: "Sheet Builder"
url: "https://cerb.ai/docs/setup/developers/sheet-builder/"
summary: "This page documents the Sheet Builder in Cerb's developer menu. It's a visual builder for sheet KATA that works against a live sample dataset, so every column's value source is picked from real keys rather than typed from memory. The page covers the three-column layout and why the live preview is not the canvas, the four dataset modes, which of the fourteen column types get a guided inspector and which fall back to raw KATA, the fact that the standalone tool copies to the clipboard rather than saving a record, how the Form Builder entry point differs by importing and writing back, and the two things that surprise people most: the emitted data block is a three-row synthesized sample rather than your query, and colors are palette references rather than hex values."
tags: ["docs"]
---
The Sheet Builder is a visual builder for [sheet](/docs/sheets/) [KATA](/docs/kata/). You assemble columns against a **live sample dataset**, so each column's value source is picked from the keys your data actually has rather than typed from memory, and the sheet renders as you go.

 

- [Access](#access)
- [What it produces](#what-it-produces)
- [Layout](#layout)
- [Dataset](#dataset)
- [Columns](#columns)
- [Editing an existing sheet](#editing-an-existing-sheet)
- [Where you can use it](#where-you-can-use-it)
- [Things worth knowing](#things-worth-knowing)

# Access

Click **Setup » Configure » Developers » Sheet Builder**. This tool is limited to administrators.

# What it produces

The standalone builder doesn't save anything. There's no record and no export file – the **Copy** button puts the finished KATA on your clipboard, and you paste it wherever sheet KATA is accepted.

What it emits is a `sheet:` node, and **what's inside depends on where the rows come from**.

Build against a records query or a data query and the rows live outside the sheet – they're the adopting widget's job – so you get the schema alone, with a comment saying so:

```
# Data source: a data query (set the data_query on the adopting widget)
sheet:
  schema:
    layout:
      # ...
    columns:
      # ...
```

Paste that into a [sheet widget](/docs/sheets/) and point the widget at your real query.

The other two modes do carry their rows with them, which is what a [sheet form element](/docs/automations/triggers/interaction.worker/elements/sheet/) in an interaction needs, since nothing else supplies them:

| Dataset | What lands in the KATA |
| --- | --- |
| Records, data query | No `data:` – a comment telling you to set the query on the widget |
| Manual | A `data:` block holding exactly the rows you typed |
| Automation | A `data:` block pointing at the automation, with its inputs |

# Layout

Three columns: a palette on the left, the document in the middle, and an inspector on the right.

The middle column holds a **column strip**, the live **preview**, and a read-only KATA pane you can expand to see the current output.

**The preview isn't the canvas.** Unlike the [Form Builder](/docs/automations/triggers/interaction.worker/#awaitform), where you drag components onto the thing you're looking at, a sheet renders as a table -- so there's nothing meaningful to select or drop into. The canvas is the **column strip**: one chip per column, showing its identifier and type, which is what you select, reorder, and drag. The preview updates live but is read-only.

# Dataset

The sample dataset drives the preview _and_ supplies the key names every column picker offers. It opens seeded to a records query so the pickers are populated before you start.

There are four modes – records, data query, automation, and manual. Each keeps its own configuration, so switching between them to compare doesn't discard what you set up in the others.

# Columns

Every [column type](/docs/sheets/) the runtime supports can be built here. Ten of them have a guided inspector with real fields:

`card`, `text`, `date`, `link`, `icon`, `markdown`, `selection`, `slider`, `time_elapsed`, `code`

The remaining four – `interaction`, `search`, `search_button`, and `toolbar` – are offered, but their inspector is a raw KATA box rather than a form.

Those four sit in the palette's **Actions** group, which also holds `selection` and `slider`. Those two are guided like the rest, so the group isn't a clean split between the two kinds.

The types on offer also depend on where the builder was opened. The standalone tool shows all fourteen; opened from a portal-facing form, it's narrowed to the types that are safe to expose publicly.

# Editing an existing sheet

The standalone builder always starts fresh. There's no way to load an existing sheet into it.

Opened from the Form Builder's sheet element, it does import: it's seeded from that element's current `data:` and `schema:`, and writes the result back when you apply it.

**That import is a round-trip through the builder's model, not a text edit.** The document is parsed, rebuilt, and re-emitted, so comments and formatting are not preserved. If you've hand-tuned KATA you care about, use the Form Builder's raw **Data** and **Schema** fields, which remain available alongside the design button.

# Where you can use it

There are two entry points: this Setup tool, and the **Design sheet** button in the Form Builder's sheet element inspector.

The sheet widgets – profile, card, and workspace – don't offer it yet.

# Things worth knowing

### Applying to a form element writes sample rows

This one applies to the Form Builder route rather than the Copy button. When you apply a sheet built against a records or data query dataset back to a form element, the `data:` written there is **synthesized** – capped at three rows and trimmed to the keys your columns reference.

That's deliberate: a form element has to carry its own rows, so it's given placeholder scaffolding to be replaced with a real `data@key:` or automation. The `schema:` half is exactly what you designed; the `data:` half is a stand-in, and nothing says so at the time.

### Colors are palette references

Color fields don't take hex values. You define palettes first, under **Sheet settings » Color palettes**, and columns then reference them by name and index. A palette can carry a parallel dark set, which the runtime swaps in automatically for viewers in dark mode.

### Check the KATA pane, not just the preview

The preview renders from the dataset configuration rather than from the emitted KATA, so it's possible for the preview to look correct while the generated KATA isn't what you expect. If something behaves unexpectedly after pasting, read the KATA pane.

### Columns are added by dragging

Drag a type from the palette onto the column strip. Clicking a palette tile does nothing at all – no column, no feedback.

Naming is also manual: pointing a column at a value key doesn't name the column, so a strip left alone reads `text/…`, `date/…`, `icon/…`. Fill in each column key to get a strip you can actually read.

### A card column brings friends

A `card` column needs a record's context, ID, and label to render at all, so adding one pulls that trio of keys into the sample data together. This is why a single card column appears to drag three keys along with it.

### Code columns are highlighted in the browser

Syntax highlighting for `code` columns is applied client-side by [Cerb UI](/docs/developers/cerb-ui/). Rendered through the API, or in a portal that isn't running Cerb UI, the same column comes back as plain unhighlighted text.

