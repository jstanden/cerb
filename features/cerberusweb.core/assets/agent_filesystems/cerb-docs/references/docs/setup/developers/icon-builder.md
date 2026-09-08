---
id: "docs-setup-developers-icon-builder"
title: "Icon Builder"
url: "https://cerb.ai/docs/setup/developers/icon-builder/"
summary: "This page documents the Icon Builder in Cerb's developer menu. It's a tool for drawing icons in Cerb's own icon set, and the document it edits is only the inner SVG geometry -- the surrounding svg tag is fixed by the icon pipeline, which is what keeps every Cerb icon to one visual convention. The page covers the live preview across the contexts an icon actually appears in, seeding the editor from any existing icon, the two source lines it produces and the two files they belong in, the agent pane that can read and rewrite the geometry, and the fact that copying those lines out is the only way the work survives."
tags: ["docs"]
---
The Icon Builder is for drawing icons in Cerb's own [icon set](/docs/developers/icons/). You edit the shape, watch it render everywhere it would appear in the interface, and copy out the two source lines that add it to the set.

 

- [Access](#access)
- [What you're editing](#what-youre-editing)
- [Working on an icon](#working-on-an-icon)
- [What it produces](#what-it-produces)
- [Agent pane](#agent-pane)

# Access

Click **Setup » Developers » Icon Builder**. This tool is limited to administrators.

# What you're editing

The editor holds **only the inner geometry** – the paths, circles, and lines that make up the shape. It isn't a file and it isn't a complete SVG.

The surrounding tag is fixed, supplied by the icon pipeline:

```
<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black'
     stroke-width='2' stroke-linecap='round' stroke-linejoin='round'>
```

That wrapper is why every Cerb icon looks like it belongs to one set: a 24×24 canvas, no fill, and a rounded 2-unit stroke. The canvas is fixed – everything you draw is placed on those 24 units.

The stroke and fill are **defaults rather than limits**. They're presentation attributes on the wrapper, so geometry that sets its own `fill` or `stroke` overrides them, which is how the handful of solid glyphs in the set are drawn. Staying with the defaults is what keeps a new icon consistent with the rest.

Because the geometry is the unit of work, an icon's color comes from the surrounding text rather than from the shape. Icons inherit `currentColor` wherever they're placed.

# Working on an icon

Pick any existing icon from the picker and its real geometry loads into the editor, read back from the compiled stylesheet rather than from a source file – so what you start editing is exactly what's shipping.

The preview above the editor isn't an approximation either. It renders the icon through the same path the interface uses, shown at a range of sizes, on buttons, inside a panel, and with the [animation utilities](/docs/developers/icons/) applied.

The name field drives the output lines as you type. Edits are kept in an undo history as you work.

# What it produces

Two source lines, each with its own copy button:

| Line | Belongs in |
| --- | --- |
| The SCSS map entry | The `$icons` map in `cerb-icons.scss` |
| The name entry | The icon name list in the platform's UI service |

Adding an icon to the set is a source change followed by a CSS rebuild. Nothing is saved to your Cerb installation, and no record is created – which is why this lives under **Developers** rather than alongside the record editors.

**Copying out is the only way the work survives.** The current geometry is kept for the browser session, so a reload or a click away won't lose it, but nothing is stored on the server. Close the tab without copying both lines into the two source files and the icon is gone.

# Agent pane

The Icon Builder is one of the editors with an [agent pane](/docs/toolbars/interactions/agent.pane/). An agent chatting beside it can read the geometry you're editing, look up any icon in the shipped set to work from, and write geometry back.

That set-wide lookup is what lets an agent match the house style rather than guess at it – asked for a new glyph, it can list every icon name in the set to check whether one already exists, then read a comparable icon's geometry before drawing. The listing is answered by Cerb rather than by the editor, since the browser has no way to say what the whole set contains.

Agent edits land in the undo history like your own, so a change you didn't want can be stepped back rather than having overwritten your work.

