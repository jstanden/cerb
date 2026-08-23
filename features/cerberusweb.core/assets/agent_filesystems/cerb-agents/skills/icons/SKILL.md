---
name: icons
description: Designing SVG geometry for Cerb's icon set -- the four pattern families, the fixed outer template, and the mask-image constraints that make ordinary SVG instincts wrong. Load it before drawing or editing any icon.
---

# Cerb SVG icons

Cerb icons live in an SCSS Sass map and render via CSS `mask-image`. **The icon's geometry determines opacity; the button's CSS text color provides the actual visible color.** Fill and stroke are both just "opaque" — there is no color contrast available. Every visual distinction must come from geometry: opaque shape vs transparent gap.

## Output format

Each icon is a single line in a Sass map:

```scss
  name:           "<inner-svg-geometry>",
```

The geometry is **strictly inner geometry**. **Never** include the `<svg>` tag (start or end), `</svg>` closing tags, `<g>` groups, or `xmlns` attributes. Just the shapes and paths. Use **single quotes** on SVG attributes so the whole thing fits in the double-quoted Sass string.

## Outer template (applied automatically at render time)

```html
  <svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'
     fill='none' stroke='black' stroke-width='2'
     stroke-linecap='round' stroke-linejoin='round'>
  ...your inner geometry here...
  <!-- The closing </svg> is added automatically -->
</svg>
```

Your geometry inherits:
- 24×24 viewBox (everything must fit; stroke extends 1 unit past the path)
- `fill='none'` (override per-element with `fill='black'` to fill)
- `stroke='black' stroke-width='2'` (override per-element with `stroke='none'` to remove)
- `stroke-linecap='round'`, `stroke-linejoin='round'` — **sharp acute corners get rounded by ~1 unit**

## The four pattern families

Pick a family up front and design within its constraints. Combining families in one icon usually fails because of the mask-image color limitation.

**1. Line icon (Lucide style, default).** `<path>`, `<rect>`, `<circle>`, `<line>`, `<polyline>`, `<polygon>` with no extra attributes — they inherit `fill='none'`, `stroke='black' stroke-width='2'`. Covers the vast majority of icons.
```scss
  mail:           "<rect x='2' y='4' width='20' height='16' rx='2'/><path d='m22 7-10 6L2 7'/>",
```

**2. Filled solid icon.** Add `fill='black'`. Add `stroke='none'` for crisp edges / sharp corners (small triangles, chevrons, arrow tips, pixel-precise cutouts); keep the template stroke when you want visual weight matching line icons.
```scss
  chevron-down:   "<path fill='black' stroke='none' d='M12 15.5l-7-7 2-2 5 5 5-5 2 2z'/>",
```

**3. Compass / ring-with-inner-element.** Outlined ring + filled inner element(s).
```scss
  play-button:    "<circle cx='12' cy='12' r='10'/><polygon fill='black' points='7.5 6 17.5 12 7.5 18'/>",
```

**4. Filled disc with a punched symbol (the `circle-*` family).** Filled r=10 disc centered at (12,12), symbol cut out via `fill-rule='evenodd'` + `stroke='none'`. The symbol MUST be a single continuous closed polygon (or non-overlapping subpaths) — overlapping subpaths push the winding count odd and re-fill the cutout. When in doubt, look up a shipped `circle-*` icon and trace from its geometry.
```scss
  circle-plus:    "<path stroke='none' fill='black' fill-rule='evenodd' d='M2 12A10 10 0 1 0 22 12A10 10 0 1 0 2 12ZM10.5 6 13.5 6 13.5 10.5 18 10.5 18 13.5 13.5 13.5 13.5 18 10.5 18 10.5 13.5 6 13.5 6 10.5 10.5 10.5Z'/>",
```

## Critical mask-image constraints

Non-negotiable consequences of `mask-image` rendering:

1. **No fill/stroke color contrast.** Both are opaque. Traditional outlining isn't possible — only geometric outline tricks (negative-space halo via evenodd, or a hollow stroked shape with no fill).
2. **Filled shape + stroke = bigger filled shape.** The 2px stroke adds ~1 unit of opacity in every direction. Inset a polygon by 1 unit to keep its visible size when it carries the template stroke.
3. **A transparent gap is required to "outline" anything.** Shape B is only visible against filled shape A if B is outside A, a cutout in A (evenodd), or both.
4. **`stroke-linejoin='round'` rounds every sharp corner by ~1 unit.** Use `stroke='none'` for true sharp corners.
5. **ViewBox edges clip.** A path at x=24 with a 2px stroke renders to x=25 and gets clipped. Pull paths in by 1 unit to preserve perimeter stroke. (The `circle-*` family can use a full r=10 disc only because it uses `stroke='none'`.)
6. **Masks can't occlude — no "in front / behind".** Overlapping shapes merge into one blob. Fanned decks, stacked cards, "one shape behind another" do NOT work — use distinct, spatially-separated elements.
7. **Stroke eats the gap between elements.** Each stroked shape extends half its `stroke-width` toward its neighbor. Budget ~4 geometric units of gap for ~2.5 visual units when spacing composites.
8. **SVG `transform` renders fine in the mask** (e.g. `transform='rotate(angle cx cy)'`) — but re-read #6 before overlapping.

## Reference docs (mounted at `@cerb-docs`, read-only)

You have the full Cerb documentation mounted. Read these instead of guessing — especially before claiming an icon does or doesn't exist.

| Path | What it gives you |
| --- | --- |
| `@cerb-docs/references/docs/developers/icons.md` | **The canonical icon list.** Every registered icon name in the shipped set, plus why an icon is a single-color mask that inherits `currentColor`. Check here first for naming conventions and prior art (`circle-*`, `logo-*`, `chart-*`, `window-*`, `move-*` families). |
| `@cerb-docs/references/docs/setup/developers/icon-builder.md` | The Icon Builder: it holds **inner geometry only**, the wrapper `<svg>` is fixed by the icon pipeline, and output is two source lines (the `$icons` entry in `cerb-icons.scss` + the name entry in the UI service). Nothing is saved server-side. |
| `@cerb-docs/references/docs/data-queries/ui/icons.md` | `type:ui.icons` data query — filterable/pageable list of icon names, the same source behind the icon-listing tool and the in-app pickers. |
| `@cerb-docs/references/docs/toolbars.md` | How `icon:` is consumed on toolbar buttons and menu links (icon in addition to, or instead of, a label). |
| `@cerb-docs/references/docs/sheets.md` | The `icon` column type and the `icon:`/`icon_at:` parameters on card, link, interaction, and button columns — the other main place icons render. |

Practical notes that follow from those pages:

- **An icon name must be registered to be accepted.** Icon pickers, toolbars, and sheets reject unknown names (they silently render nothing), so a new glyph isn't usable until both source lines are copied out and the CSS is rebuilt.
- **Names are the API.** Match the existing naming conventions in the icon reference — kebab-case, family prefix first (`circle-plus`, not `plus-circle`; `chart-bar-stacked`, `window-left`).
- **Check for an existing icon before drawing one.** Look it up in the shipped set or the icon reference; adapting a shipped glyph's geometry keeps the house style better than inventing.
- Icons are sized and colored by their context (buttons, panels, sheet cells, at a range of sizes), so geometry must read at small sizes with no color contrast available.

## Draft, look, refine -- do not try to one-shot it

`set_geometry` pushes a revision and the preview updates immediately, so you can SEE the icon. That makes a rough first pass cheap and a long analytical build expensive. Get something on screen in your first turn or two, then adjust.

- **Ship the draft early.** A plain first version -- right family, right proportions, nothing refined -- teaches you more in one turn than another round of reasoning does. The user is watching the preview and will often correct you before you would have caught it yourself.
- **Refine in small passes.** One change per revision, or a couple of related ones. Every `set_geometry` is undoable, so a wrong step costs a revision rather than the work.
- **Reason about geometry when the preview disagrees with you.** Winding at the center of an evenodd cutout, stroke growth against the viewBox edge, the gap eaten between two stroked shapes -- these are diagnoses for a symptom you can see, not a checklist to run before drawing.
- **Don't precompute what you can just look at.** Curve math, traced coordinates, a scratch file of alternatives: reach for those when a shape is genuinely fiddly, not by default.
- **Say where you are.** "Here is a first pass -- I will tighten the stem next" sets the expectation that this is a draft. Several silent minutes reads as being stuck, and a perfect icon delivered late is worth less than a good one delivered in three quick rounds.

## Working approach

1. **Start from Lucide.** Most icons map to an existing Lucide design — adapt rather than invent. Lucide path data drops in cleanly after converting double quotes to single quotes.
2. **Pick a pattern family up front** and design within its constraints.
3. **If an evenodd cutout fills in solid, check the winding at its center** — for a disc + symbol, the symbol's center must be winding 2 (transparent). That is the diagnosis for that symptom, not a step to run before every draft.
4. **Take multi-icon requests one at a time.** An icon editor holds a single icon's geometry, so work through a set in sequence — settle one glyph, note its shared parameters (stroke weight, corner radius, optical size), and carry them into the next so the family stays consistent.
5. **Iterate on dimensions, not approach.** "Fatter / thinner / shorter / deeper" → adjust existing parameters. "Different style entirely" → switch pattern family.
6. **Round to 2 decimals max** unless precision matters (e.g. cutout sagitta math).
7. **Use your scratch area when a shape genuinely needs it** — traced path data, winding and sagitta math, intermediate geometry held across steps. Prefer looking at the preview over computing what it would have looked like.
