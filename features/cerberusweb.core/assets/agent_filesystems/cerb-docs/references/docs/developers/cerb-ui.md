---
id: "docs-developers-cerb-ui"
title: "Cerb UI"
url: "https://cerb.ai/docs/developers/cerb-ui/"
summary: "This page is a developer reference for Cerb UI, Cerb's own component library. Cerb UI is plain JavaScript with no bundler and no node_modules, built by concatenating and minifying its source files with terser. It replaced jQuery UI, Ace.js, ace-diff, D3, c3, dagre-d3, TopoJSON, miniColors, and async.js. This page covers where the components live, how they are built and loaded, the interactive UI Reference gallery in Setup, the full component catalog by category, the shared architecture behind the editor, chart, and chooser families, the CSS utilities and style helpers, and which legacy jQuery plugins and shims were retired."
tags: ["docs"]
---
**Cerb UI** is the component library Cerb's interface is built from. It gives Cerb full control of its own interface, and makes it straightforward for widgets and plugins to add visual elements with consistent styling, light and dark mode support, and interactivity.

It replaced jQuery UI, Ace.js, ace-diff, D3, c3, dagre-d3, TopoJSON, miniColors, and `async.js`.

https://www.youtube.com/embed/ArVRK6cwsBM

- [How it's built](#how-its-built)
- [Component catalog](#component-catalog)
  - [Overlays and layout](#overlays-and-layout)
  - [Drag and drop](#drag-and-drop)
  - [Inputs and choosers](#inputs-and-choosers)
  - [Editors](#editors)
  - [Charts and visualization](#charts-and-visualization)
  - [Node graphs](#node-graphs)
  - [AI agents](#ai-agents)
  - [Builders](#builders)
  - [Utility modules](#utility-modules)

- [Shared architecture](#shared-architecture)
  - [Editors – `CerbUI.editorCore`](#editors--cerbuieditorcore)
  - [Charts – `CerbUI.Chart`](#charts--cerbuichart)
  - [Gantt – `CerbUI.Gantt`](#gantt--cerbuigantt)
  - [Choosers – `CerbUI.chooserCore`](#choosers--cerbuichoosercore)

- [CSS utilities](#css-utilities)
- [Retired dependencies](#retired-dependencies)

**The authoritative reference is in the product.** **Setup » Developers » UI Reference** is an interactive gallery of every component with live examples, variants, code snippets, and per-component documentation. This page is the orientation; the gallery is the detail.

# How it's built

Cerb UI is **plain JavaScript** – no bundler, no `package.json`, no `node_modules`. Components attach to a shared `CerbUI` global.

| Command | Result |
| --- | --- |
| `composer build-js` | Concatenates and minifies the sources with `terser` into `cerb-ui.js` |
| `composer build-css` | Compiles the SCSS into `cerb.css` |
| `composer dist` | Runs both |

The sources live in `cerberusweb.core/resources/js/cerb-ui/`.

When `DEVELOPMENT_MODE` is enabled, the individual source files load separately so you can edit and reload without a build step. In production, the single minified `cerb-ui.js` is served.

Load order matters: `_core.js` is first, and a component that depends on another (an editor on `editor-core`, a chart on `chart.js`) loads after it.

# Component catalog

## Overlays and layout

| Component | Notes |
| --- | --- |
| `CerbUI.Dialog` | Draggable, resizable, minimizable dialogs. Minimized dialogs dock to a shared tray |
| `CerbUI.Confirm` | Modal confirmation, replacing `confirmPopup()` |
| `CerbUI.Menu` | Cascading menus. Renders only the open path and virtualizes long panels |
| `CerbUI.Tooltip` | Floating tooltips with `point` (charts) and `anchored` (callouts) modes |
| `CerbUI.Tabs` | Tab strips with static or AJAX-loaded panels, caching, and keyboard navigation |
| `CerbUI.Accordion` | Expanding and collapsing sections |
| `CerbUI.Sidebar` | Collapsible vertical navigation rails; a `palette` mode supplies drag sources |
| `CerbUI.Toolbar` | Toolbar strips with buttons, menus, and badges |
| `CerbUI.SplitPane` | Two panes with a draggable divider |
| `CerbUI.Spinner` | Pure-CSS SVG spinners (`arc`, `dots`, `spark`) |

## Drag and drop

| Component | Notes |
| --- | --- |
| `CerbUI.Sortable` | Pointer-drag reordering, with a grid mode |
| `CerbUI.Draggable` | Lift and drag elements, with edge auto-scrolling |
| `CerbUI.Droppable` | Drop zones, with an `onMove` callback for live drop previews |

## Inputs and choosers

Server-backed **choosers** search large sets; local **pickers** work from a fixed option set or freeform text.

| Component | Notes |
| --- | --- |
| `CerbUI.RecordChooser` | Single or multiple record selection with ACL-filtered autocomplete |
| `CerbUI.ContextChooser` | Record selection that can switch record type on the fly |
| `CerbUI.TextChooser` | Freeform text with optional endpoint-backed suggestions |
| `CerbUI.ValuePicker` | A small local option set, replacing tall checkbox lists |
| `CerbUI.TagInput` | Freeform tags, replacing repeating text inputs |
| `CerbUI.SelectMenu` | Searchable, styled `<select>` elements |
| `CerbUI.DatePicker` | Calendar date input, plus a natural-language text field |
| `CerbUI.Slider` | A `0..N` value slider |
| `CerbUI.PriorityPicker` | Multi-select with drag-to-prioritize |
| `CerbUI.ColorPicker` | Photoshop-style color selection |
| `CerbUI.IconPicker` | A filterable grid of every Cerb icon |
| `CerbUI.ImageEditor` | An image well with a pan/zoom/crop canvas editor |
| `CerbUI.FileUpload` | Inline drag-and-drop file uploads |
| `CerbUI.Avatar` | Monogram avatars and avatar stacks with hash-locked colors |

## Editors

| Component | Notes |
| --- | --- |
| `CerbUI.KataEditor` | [KATA](/docs/kata/) code editor with folding, find/replace, and gutter markers |
| `CerbUI.JsonEditor` | JSON editor with bracket folding and validation |
| `CerbUI.ScriptingEditor` | Twig and KataScript template documents |
| `CerbUI.MarkdownEditor` | Markdown prose with a formatting toolbar and `@mention` autocomplete |
| `CerbUI.SearchQuery` | Cerb search-query syntax with filter-path autocompletion |
| `CerbUI.DataQuery` | [Data query](/docs/data-queries/) editor, subclassing `ScriptingEditor` |
| `CerbUI.DiffViewer` | Side-by-side KATA diff with client-side line diffing |
| `CerbUI.SyntaxHighlight` | Read-only highlighting for KATA and JSON |

## Charts and visualization

| Component | Notes |
| --- | --- |
| `CerbUI.Chart` | The base class for the chart family |
| `CerbUI.CartesianChart` | Bars, lines, splines, and areas on a shared axis |
| `CerbUI.PieChart` | Pie and donut charts |
| `CerbUI.ScatterChart` | XY scatterplots on two continuous axes |
| `CerbUI.Gauge` | Single-value radial gauges with thresholds |
| `CerbUI.Timeblocks` | Activity heatmaps |
| `CerbUI.Gantt` | Named rows of spans across a shared numeric or time axis |
| `CerbUI.Map` | SVG region and point [maps](/docs/maps/) with its own projections |
| `CerbUI.Sparkchart` | Compact multi-series categorical charts |
| `CerbUI.Distbar` | Horizontal stacked bars |
| `CerbUI.Legend` | Color-matched keys, shared with `Distbar` |
| `CerbUI.QrCode` | Self-contained SVG QR codes |
| `CerbUI.Calendar` | Day, week, month, and year calendar views |
| `CerbUI.TimeRing` | Countdown and progress rings |

## Node graphs

`CerbUI.NodeEditor` is a visual node canvas – used for the [automation](/docs/automations/) control-flow graph – built from `NodeCanvas`, `Node`, `NodeEdge`, `NodeGraph`, and `nodeTypes`.

## AI agents

| Component | Notes |
| --- | --- |
| `CerbUI.AgentPrompt` | Composes an agent turn, with `@mention` completion and a model picker |
| `CerbUI.AgentTranscript` | Renders an [agent](/docs/agents/) session |
| `CerbUI.AgentPane` | A collapsible agent chat beside an editor |

## Builders

`CerbUI.FormBuilder`, `CerbUI.SheetBuilder`, and `CerbUI.IconBuilder` back the visual builders in **Setup » Developers**.

These three and `CerbUI.NodeEditor` are **not in the `cerb-ui.js` bundle**. They're compositions used on a handful of screens, so a page that needs one loads it itself rather than every page carrying it:

```
Devblocks.loadResources({
  js: ['/resource/cerberusweb.core/js/cerb-ui/sheet-builder.js?v=' + APP_BUILD]
}, function() {
  if(!window.CerbUI || !CerbUI.SheetBuilder)
    return console.error('CerbUI.SheetBuilder failed to load.');

  // construct here
});
```

| Component | Source |
| --- | --- |
| `CerbUI.NodeEditor` | `js/cerb-ui/node-editor.js` |
| `CerbUI.FormBuilder` | `js/cerb-ui/form-builder.js` |
| `CerbUI.SheetBuilder` | `js/cerb-ui/sheet-builder.js` |
| `CerbUI.IconBuilder` | `js/cerb-ui/icon-builder.js` |

The primitives they're built from – `Node`, `NodeCanvas`, `NodeEdge`, `NodeGraph`, `nodeTypes`, and `AgentPane` – **stay in the bundle**, so only the composition is deferred.

## Utility modules

| Module | Notes |
| --- | --- |
| `CerbUI.date` | `remain()` countdowns, `ago()` elapsed time, `strftime()` formatting |
| `CerbUI.num` | Compact counts, digit grouping, percentages, humanized durations |
| `CerbUI.color` | Hex parsing, WCAG luminance, contrast ratios, automatic text contrast |
| `CerbUI.palettes` / `CerbUI.ColorScale` | Shared palettes and hash-assigned ordinal color |
| `CerbUI.effects` | `flash`, `transfer`, `pulse` – all `prefers-reduced-motion` aware |
| `CerbUI.utils` | `apply`, `series`, `parallelLimit` async flow control |
| `CerbUI.Switcher` / `CerbUI.Toggle` | Segmented toggles and on/off switches |

# Shared architecture

Three families share a core rather than reimplementing the hard parts. This is why the library stays small.

## Editors – `CerbUI.editorCore`

Every editor is a transparent `<textarea>` over a colored mirror `<div>`. `editorCore` owns everything language-independent: overlay rendering, caret pixel measurement, scroll synchronization, fuzzy match and filter, and the suggestion menu lifecycle.

Each editor owns only what's genuinely its own – its tokenizer, its scope or key-path computation, its keyboard model (does Enter submit or insert a newline?), and its field source.

Because highlighting is a mirror, **glyph advances must match the textarea exactly** or the caret drifts. That's why Markdown highlighting is color-only: `**bold**` is colored, not rendered bold.

## Charts – `CerbUI.Chart`

The base class owns lifecycle (one instance per element, a `ResizeObserver` re-render, `destroy()`), color resolution, a single shared point-mode tooltip across all charts, bubbling `cerb-ui-chart:*` events, and the drill-through to Cerb search.

Concrete charts implement `render()` and nothing else. Drill-through being shared means clicking a mark in **any** chart runs a search, instead of each widget template re-implementing it.

## Gantt – `CerbUI.Gantt`

A row is a label plus any number of spans, and rows covering the same range **overlap on screen**. That overlap is the point: a region two rows share is drawn as the place they meet, rather than invented as a third series that would imply a third kind of thing.

Span ends work two ways, and getting this wrong is the easy mistake:

| Axis | Span end | Example |
| --- | --- | --- |
| Continuous (no `step`) | Half-open `[start, end)` | A job running 09:00 to 10:00 doesn't overlap one starting at 10:00 |
| Discrete (`step` set) | **Inclusive**, drawn to `end + step` | Slots `[1,6]` cover six cells, not five |

`step` also governs tick generation. Without it the generic tick generator labels positions that don't exist – on a three-slot pool it offers a slot 1.5.

| Option | Default | Notes |
| --- | --- | --- |
| `step` | – | Makes the axis discrete units of this size, and the span end inclusive |
| `segment` | `false` | Draws a span as one block per unit instead of one continuous bar. **Requires `step`** – without it there are no units to cut on, and it silently degrades to a normal bar |
| `segmentGap` | `3` | Pixels between blocks, clamped to 35% of a cell so a dense axis degrades into thin blocks rather than slivers |

Use `segment` when a row's axis counts **units of capacity** – slots in a pool – rather than measuring a duration. Spans stay whole in the data either way: a hover reads back the span the caller supplied, not a unit number, so a caller can keep emitting compressed runs.

A time axis chooses its own tick step, from one second to one week, and aligns from **local midnight**. Aligning from the epoch lands on the hour only in whole-hour zones, and would label every tick at `:30` for anyone on a half-hour offset.

## Choosers – `CerbUI.chooserCore`

Shared behavior for the chooser and picker family: suggestion menus, keyboard handling, and selection rendering.

# CSS utilities

| Class | Notes |
| --- | --- |
| `cerb-ui-form` | Consistent form layouts with minimal markup |
| `cerb-ui-kbd` | Keyboard key caps |
| `cerb-u-anim-*` | Icon animations; `cerb-u-anim-*-hover` runs only on hover |
| Magic gradient | Animated icon styles – `sweep`, `aurora`, `cycle` |
| Opacity utilities | Element opacity at 0%, 25%, 50%, 75%, 100% |
| Separators | Optional start and end arrows, plus a `--thick` variant |

Colors are theme tokens, so dark mode is automatic for anything built from them.

# Retired dependencies

If a third-party plugin calls any of the following, it needs updating to the Cerb UI equivalent:

| Retired | Replacement |
| --- | --- |
| jQuery UI | The components above |
| Ace.js, ace-diff | `KataEditor`, `JsonEditor`, `ScriptingEditor`, `DiffViewer` |
| D3, c3, `jquery.devblocksCharts` | `Chart` and its subclasses |
| dagre-d3 | `NodeEditor` / `NodeGraph` |
| TopoJSON | `Map` |
| miniColors | `ColorPicker` |
| `jquery.qrcode` | `QrCode` |
| `async.js` | `CerbUI.utils` |
| `.cerbCodeEditor`, `.cerbPropertyGrid` | The editor family |
| `.cerbQueryTrigger` | `SearchQuery` |
| `.cerbChooserTrigger` | `RecordChooser.pickerLink()` |
| `cerbTextEditor` family | `MarkdownEditor` |
| `Devblocks.tooltip` | `Tooltip` |
| `Devblocks.selectValue` | Native JavaScript |

**jQuery itself is still present** and still loaded. Only jQuery _UI_ was retired. A plugin that bundles its own copy of a removed library continues to work; one that relied on Cerb loading it does not.

