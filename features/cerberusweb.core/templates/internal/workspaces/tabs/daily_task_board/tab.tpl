{$dtb_id = "dtb-`$workspace_tab->id`"}

<style nonce="{DevblocksPlatform::getRequestNonce()}">
#{$dtb_id} { padding: 0.5em 0.25em 2em; }

#{$dtb_id} .dtb-day--pip { width: 9px; height: 9px; border-radius: 50%; background: var(--cerb-color-tag-green); display: inline-block; }
#{$dtb_id} .dtb-day--chevron { font-size: 0.7em; opacity: 0.7; transition: transform .12s ease; }
#{$dtb_id} .dtb-day--summary { display: none; } /* replaced by the mini legend when collapsed */

/* Collapsed-day mini legend (TODO / In Progress / Done counts) in the title --right; empty series hidden */
#{$dtb_id} .dtb-day:not(.dtb-day--collapsed) .dtb-day--dist { display: none; }

/* Collapsed: only complete (or no) work — keep the day visible but fold the columns away. */
#{$dtb_id} .dtb-day--collapsed .dtb-day--title { margin-bottom: 0; }
#{$dtb_id} .dtb-day--collapsed .dtb-day--chevron { transform: rotate(-90deg); }
#{$dtb_id} .dtb-day--collapsed .dtb-columns { display: none; }

#{$dtb_id} .dtb-column { min-height: 25em; }
/* Prior-day Done log: a single read-only column — flex up to half the row (floor at the original third),
   so it reads as a column without spanning the full width. */
#{$dtb_id} .dtb-column--log { flex: 1 1 calc((100% - 2em) / 3); max-width: 50%; min-height: 0; }
#{$dtb_id} .dtb-column--log .dtb-column-cards { cursor: default; } /* read-only — no click-to-add */
/* Future-day stash column: a single interactive column (drop target + quick-add), same width as the log. */
#{$dtb_id} .dtb-column--future { flex: 1 1 calc((100% - 2em) / 3); max-width: 50%; min-height: 0; }
#{$dtb_id} .dtb-column--future .cerb-icon-archive { color: var(--cerb-color-tag-orange); }
#{$dtb_id} .dtb-column-cards { flex: 1 1 auto; min-height: 90px; gap: 0.8em; padding-top: 0.6em; }

/* "click anywhere to add" hint, centered in an empty lane; pointer-events:none so the click reaches the lane */
#{$dtb_id} .dtb-add-prompt { color: var(--cerb-color-text); pointer-events: none; margin: auto; }

/* Cards: larger text, no box border — only the per-project left accent stripe. Long unbroken
   text wraps/breaks rather than overflowing the card. */
#{$dtb_id} .dtb-card { position: relative; cursor: grab; margin: 0; font-size: 1.2em; line-height: 1.5em; border-width: 0; border-left: 4px solid var(--cerb-ui-accent); padding-right: 1.8em; overflow-wrap: anywhere; word-break: break-word; }
#{$dtb_id} .dtb-card:active { cursor: grabbing; }
#{$dtb_id} .dtb-card[hidden] { display: none; }
/* Card body = a flex row of left columns then the title: [done check?] [assignee avatar] [content].
   The left items don't wrap; the title fills the remaining column. Consistent across all cards. */
#{$dtb_id} .dtb-card--body { display: flex; align-items: flex-start; gap: 0.5em; }
#{$dtb_id} .dtb-card--content { flex: 1 1 auto; min-width: 0; }
/* Done check: its own left column (before the avatar), so all cards share the same left layout. */
#{$dtb_id} .dtb-card--check { color: var(--cerb-ui-accent); flex: 0 0 auto; margin-top: 0.2em; }

/* Assignee chip in the left column (avatar, or a faint "assign to me" claim when unassigned + writeable). */
#{$dtb_id} .dtb-card--owner { width: 18px; height: 18px; border-radius: 50%; object-fit: cover; display: block; flex: 0 0 auto; margin-top: 0.15em; }
#{$dtb_id} .dtb-card--claim { width: 18px; height: 18px; padding: 0; margin: 0.15em 0 0; border: 1px dashed currentColor; border-radius: 50%; background: none; color: var(--cerb-color-text); opacity: 0.35; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; line-height: 1; flex: 0 0 auto; }
#{$dtb_id} .dtb-card--claim .cerb-icons { font-size: 11px; }
#{$dtb_id} .dtb-card--claim:hover { opacity: 1; color: var(--cerb-color-action-primary); }

/* Bottom meta-strip: a small muted row BELOW the title, shown only once it holds something — for now
   the stash schedule pill; later comment/link counters + an unread-notification dot. Hidden when empty. */
#{$dtb_id} .dtb-card--meta:empty { display: none; }

/* Project picker: the board's primary control, so it's deliberately a step larger than the rest of the
   toolbar. Scoped to the trigger host -- the popover mounts on <body>, so it keeps the inherited size. */
#{$dtb_id}-projects { font-size: 1.2em; }

/* Focus scope switcher: the "My tasks" segment wears the worker's own avatar -- the same identity chip
   the cards use -- rather than a generic user icon. */
#{$dtb_id} .dtb-focus-avatar { width: 18px; height: 18px; border-radius: 50%; object-fit: cover; display: block; }
/* In focus mode every visible card is mine, so the per-card avatar is redundant — hide it (cards only,
   not the add-editor's "Me" avatar). */
#{$dtb_id}.dtb-focus-mine .dtb-card .dtb-card--owner { display: none; }

/* ── Column multi-select mode ── */
/* The normal header is hidden; the injected multi-select header takes its place. */
#{$dtb_id} .dtb-column--multi .dtb-column--head { display: none; }
#{$dtb_id} .dtb-multihead { display: flex; flex-direction: column; gap: 0.4em; margin-bottom: 0.5em; }
#{$dtb_id} .dtb-multihead--top { display: flex; align-items: center; gap: 0.5em; }
#{$dtb_id} .dtb-multihead--count { font-weight: 600; }
#{$dtb_id} .dtb-multihead--all { margin-left: auto; background: none; border: 0; padding: 0; font: inherit; cursor: pointer; color: var(--cerb-color-link); }
#{$dtb_id} .dtb-multihead--actions { align-self: flex-start; }
/* Cards while selecting: checkbox in the left slot, no other icons; whole card is the click target. */
#{$dtb_id} .dtb-column--multi .dtb-card { cursor: pointer; }
/* Keep the owner avatar (identity, useful when triaging others' work) — it only hides in focus mode.
   The checkbox sits to its left, same as it does for the unassigned placeholder. */
#{$dtb_id} .dtb-column--multi .dtb-card--menu,
#{$dtb_id} .dtb-column--multi .dtb-card--when,
#{$dtb_id} .dtb-column--multi .dtb-card--meta { display: none; }
#{$dtb_id} .dtb-card--checkbox { flex: 0 0 auto; width: 18px; height: 18px; margin-top: 0.15em; box-sizing: border-box; border: 2px solid var(--cerb-color-background-contrast-180); border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; }
#{$dtb_id} .dtb-card--checkbox .cerb-icons { font-size: 12px; color: var(--cerb-color-action-primary-text); visibility: hidden; }
#{$dtb_id} .dtb-card--selected .dtb-card--checkbox { background: var(--cerb-color-action-primary); border-color: var(--cerb-color-action-primary); }
#{$dtb_id} .dtb-card--selected .dtb-card--checkbox .cerb-icons { visibility: visible; }
#{$dtb_id} .dtb-card--selected { outline: 2px solid var(--cerb-color-action-primary); outline-offset: 1px; }
/* During a drag the card becomes the floating helper (position:fixed). Our id-scoped
   position:relative (for the ⋮ menu) would otherwise win on specificity and break the drag. */
#{$dtb_id} .dtb-card.cerb-ui-sortable--helper { position: fixed; }
/* Origin slot = a faded clone of the card (ghostOrigin) — a clean 50% ghost, no grayscale. */
#{$dtb_id} .cerb-ui-sortable--ghost { opacity: 0.5; filter: none; }

/* Day columns sort with the normal insertion placeholder (precision). The STASH isn't a sort target:
   hide its placeholders and instead overlay the WHOLE stash column (header + cards) with a droppable
   hint while a card hovers it. */
#{$dtb_id} .dtb-stash .cerb-ui-sortable--dest, #{$dtb_id} .dtb-stash .cerb-ui-sortable--origin { display: none; }
#{$dtb_id} .dtb-stash { position: relative; }
#{$dtb_id} .dtb-stash:has(.cerb-ui-sortable--active)::after { content: ''; position: absolute; inset: 0; z-index: 5; pointer-events: none; border-radius: 12px; border: 2px dashed var(--cerb-color-action-primary); background: color-mix(in srgb, var(--cerb-color-action-primary) 9%, transparent); }

/* DONE is the same kind of drop-zone (drops always append to the bottom): hide its insertion placeholders
   and overlay the WHOLE Done column with the droppable hint while a card hovers. */
#{$dtb_id} .dtb-column-cards[data-column="done"] .cerb-ui-sortable--dest,
#{$dtb_id} .dtb-column-cards[data-column="done"] .cerb-ui-sortable--origin { display: none; }
#{$dtb_id} .dtb-column:has(.dtb-column-cards[data-column="done"]) { position: relative; }
#{$dtb_id} .dtb-column:has(.dtb-column-cards[data-column="done"].cerb-ui-sortable--active)::after { content: ''; position: absolute; inset: 0; z-index: 5; pointer-events: none; border-radius: 12px; border: 2px dashed var(--cerb-color-action-primary); background: color-mix(in srgb, var(--cerb-color-action-primary) 9%, transparent); }

/* A future-day stash column is a drop-zone too (append-only): same whole-column droppable hint as DONE. */
#{$dtb_id} .dtb-column-cards[data-column="stash_day"] .cerb-ui-sortable--dest,
#{$dtb_id} .dtb-column-cards[data-column="stash_day"] .cerb-ui-sortable--origin { display: none; }
#{$dtb_id} .dtb-column:has(.dtb-column-cards[data-column="stash_day"]) { position: relative; }
#{$dtb_id} .dtb-column:has(.dtb-column-cards[data-column="stash_day"].cerb-ui-sortable--active)::after { content: ''; position: absolute; inset: 0; z-index: 5; pointer-events: none; border-radius: 12px; border: 2px dashed var(--cerb-color-tag-orange); background: color-mix(in srgb, var(--cerb-color-tag-orange) 9%, transparent); }

/* Per-card ⋮ menu — hidden until the card is hovered */
#{$dtb_id} .dtb-card--menu { position: absolute; top: 4px; right: 3px; background: none; border: 0; padding: 2px 3px; margin: 0; cursor: pointer; color: var(--cerb-color-text); opacity: 0; line-height: 1; }
#{$dtb_id} .dtb-card:hover .dtb-card--menu, #{$dtb_id} .dtb-card--menu:focus { opacity: 0.55; }
#{$dtb_id} .dtb-card--menu:hover { opacity: 1; }

/* Card ⋮ menu: the Delete item (and its armed "Confirm delete") is red at rest; hovering/selecting it
   reverts to the normal white-on-blue selection. The menu mounts on <body> and only mirrors data-* (not
   class), so this is keyed off data-dtb-danger and is intentionally unscoped. */
.cerb-ui-menu--item[data-dtb-danger]:not(.cerb-ui-menu--item-active) { color: #ef4444; }

/* ── Quick Triage menu item + modal (both live on <body>, so these are intentionally UNSCOPED). ── */
/* Tint only at rest (like data-dtb-danger) — on select the menu's own active styling takes over. */
.cerb-ui-menu--item[data-dtb-triage] { height: 35px; }
.cerb-ui-menu--item[data-dtb-triage] .cerb-icons { zoom: 1.4; } /* amber zap */
/*.cerb-ui-menu--item[data-dtb-triage].cerb-ui-menu--item-active { background: #563d6c; }*/
/*.cerb-ui-menu--item[data-dtb-triage]:not(.cerb-ui-menu--item-active) { color: #af74e6; }*/
.cerb-ui-menu--item[data-dtb-triage]:not(.cerb-ui-menu--item-active) .cerb-icons { color: #f59e0b; } /* amber zap */
.cerb-ui-menu--item[data-dtb-triage] .dtb-triage-sub { display: block; font-size: 0.82em; opacity: 0.7; font-weight: 400; }

.cerb-ui-dialog--content:has(.dtb-triage) { padding: 0; overflow: hidden; } /* let the gradient header bleed edge-to-edge */

.dtb-triage--head { padding: 0.75em 1em; border-bottom: 1px solid #e5e7eb; background: linear-gradient(to right, #3b82f6, #9333ea); color: #fff; }
html.dark .dtb-triage--head { border-bottom-color: #374151; }
@media (min-width: 768px) { .dtb-triage--head { padding: 1em 1.5em; } }
.dtb-triage--head-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 1em; }
.dtb-triage--head-left { display: flex; align-items: center; gap: 0.6em; }
.dtb-triage--head-left > .cerb-icons { font-size: 1.7em; color: #fbbf24; flex: 0 0 auto; }
.dtb-triage--title { font-size: 1.5em; font-weight: 700; line-height: 1.1; }
.dtb-triage--subtitle { opacity: 0.85; }
.dtb-triage--head-right { display: flex; align-items: center; gap: 0.5em; }
.dtb-triage--progress { font-size: 0.8em; opacity: 0.9; text-align: right; margin-right: 0.4em; }
.dtb-triage--progress b { display: block; font-size: 1.5em; }
/* Custom titlebar-button hover: a dark rounded square behind the icon; the icon stays white (the :has
   opt-out beats the global icon-hover blue) and lets the background do the accent. Formalize later. */
.dtb-triage--icon { background: none; border: 0; color: #fff; opacity: 0.85; cursor: pointer; padding: 0.35em; line-height: 1; height: auto; border-radius: 8px; transition: background 0.12s ease, opacity 0.12s ease; }
.dtb-triage--icon:hover, .dtb-triage--icon:hover:has(> span.cerb-icons) { opacity: 1; background: rgba(0,0,0,0.28); color: #fff; }
.dtb-triage--bar { height: 5px; background: rgba(255,255,255,0.3); border-radius: 999px; margin-top: 0.75em; overflow: hidden; }
.dtb-triage--bar-fill { height: 100%; width: 0; background: #fff; transition: width 0.2s ease; }

.dtb-triage--body { padding: 1.5em; text-align: center; }
.dtb-triage--remaining { font-size: 1.6em; font-weight: 700; }

/* A "deck" pile: the active card tilts left on top; faint depth cards fan to the right + down behind it. */
.dtb-triage--stack { position: relative; height: 300px; max-width: 540px; margin: 1.5em auto; }
.dtb-triage--depth, .dtb-triage--card { position: absolute; inset: 0; border-radius: 16px; background: var(--cerb-color-background-contrast-245); }
.dtb-triage--depth { border: 1px solid var(--cerb-color-background-contrast-220); transform: translate(calc(var(--d) * 9px), calc(var(--d) * 4px)) rotate(calc(var(--d) * 1.2deg)); }
.dtb-triage--card { z-index: 1; border: 2px solid var(--cerb-color-action-primary); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1em; padding: 2em; transform: rotate(-3deg); transition: transform 0.18s ease, opacity 0.18s ease; }
.dtb-triage--ghost { z-index: 2; pointer-events: none; }
.dtb-triage--card-meta { display: flex; align-items: center; gap: 0.4em; }
.dtb-triage--proj { color: var(--cerb-color-link); }
.dtb-triage--idx { opacity: 0.55; }
.dtb-triage--card-title { font-size: 1.5em; line-height: 1.4; overflow-wrap: anywhere; }
/* Current-importance bar (0–100), flanked by cartoony white-on-blue thumbs (down = low, up = high). */
.dtb-triage--imp-row { display: flex; align-items: center; gap: 0.7em; width: 75%; }
.dtb-triage--imp { flex: 1 1 auto; height: 12px; border-radius: 999px; background: var(--cerb-color-background-contrast-225); overflow: hidden; }
.dtb-triage--imp-fill { height: 100%; width: 0; background: var(--cerb-color-action-primary); transition: width 0.18s ease; }
.dtb-triage--thumb { flex: 0 0 auto; width: 30px; height: 30px; border-radius: 50%; background: var(--cerb-color-action-primary); color: #fff; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.25); }
.dtb-triage--thumb .cerb-icons { font-size: 16px; }
.dtb-triage--thumb-down .cerb-icons { transform: translateY(1px); }
.dtb-triage--thumb-up .cerb-icons { transform: translateY(-1px); }
.dtb-triage--sched { width: 90%; box-sizing: border-box; font: inherit; padding: 0.5em 0.7em; border-radius: 8px; border: 1px solid var(--cerb-color-border); }
/* Inline delete confirm on the card. Use :not([hidden]) so the hidden attribute still hides it — a
   bare display:flex would out-rank the UA hidden rule. */
.dtb-triage--confirm:not([hidden]) { display: flex; flex-direction: column; align-items: center; gap: 0.6em; }
.dtb-triage--confirm-msg { font-weight: 600; }
.dtb-triage--confirm-btns { display: flex; gap: 0.6em; }
.dtb-triage--confirm-yes { height: auto; padding: 0.6em 1.4em; border: 0; border-radius: 10px; background: #ef4444; color: #fff; font-weight: 700; cursor: pointer; }
.dtb-triage--confirm-yes:hover, .dtb-triage--confirm-yes:hover:has(> span.cerb-icons) { background: #dc2626; color: #fff; }
.dtb-triage--confirm-no { height: auto; padding: 0.6em 1.2em; border: 1px solid var(--cerb-color-border); border-radius: 10px; background: none; color: var(--cerb-color-text); cursor: pointer; transition: background 0.12s ease; }
.dtb-triage--confirm-no:hover { background: rgba(0,0,0,0.28); }
.dtb-triage--card-title.dtb-triage--editing { outline: 1px dashed var(--cerb-color-action-primary); outline-offset: 4px; border-radius: 4px; }
.dtb-triage--empty { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; font-size: 1.8em; opacity: 0.7; }
/* Deal-out animations (one per arrow direction); cleared when the next card renders. Skip uses a shake. */
.dtb-triage--dealing-up    { transform: translateY(-120%) rotate(-8deg);  opacity: 0; }
.dtb-triage--dealing-down  { transform: translateY(120%)  rotate(8deg);   opacity: 0; }
.dtb-triage--dealing-left  { transform: translateX(-120%) rotate(-12deg); opacity: 0; }
.dtb-triage--dealing-right { transform: translateX(120%)  rotate(12deg);  opacity: 0; }
.dtb-triage--dealing-skip   { transform: translate(-130%, -45%) rotate(-22deg); opacity: 0; } /* dismissive over-the-shoulder toss */
.dtb-triage--dealing-delete { transform: scale(0.15) rotate(12deg); opacity: 0; } /* shrink/crumple away */
.dtb-triage--shake { animation: cerb-u-anim-shake 0.32s ease-in-out; } /* one-shot fanfare (shuffle) */

.dtb-triage--grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75em; max-width: 640px; margin: 4em auto 0; }
/* height:auto beats the global fixed button height so the icon/label/hint stack sizes naturally. */
.dtb-triage--act { display: flex; flex-direction: column; align-items: center; gap: 0.3em; height: auto; padding: 1em 0.5em; border: 1px solid var(--cerb-color-border); border-radius: 12px; background: none; color: var(--cerb-color-text); cursor: pointer; }
/* The :has() opt-out keeps the label readable on hover (global icon-button rule recolors text otherwise). */
.dtb-triage--act:hover, .dtb-triage--act:hover:has(> span.cerb-icons) { background: var(--cerb-color-background-contrast-245); color: var(--cerb-color-text); }
.dtb-triage--act > .cerb-icons { font-size: 1.4em; }
.dtb-triage--act-label { font-weight: 600; }
.dtb-triage--act-hint { font-size: 0.78em; opacity: 0.5; }
.dtb-triage--delta { opacity: 0.6; font-weight: 400; }
/* Delete isn't red at rest — it turns red on hover (the :has variant out-specifies the opt-out above)
   and its icon shakes via cerb-u-anim-shake-hover. */
.dtb-triage--act-danger:hover, .dtb-triage--act-danger:hover:has(> span.cerb-icons) { color: #ef4444; }

/* Inline title edit — same textarea as the add-task editor (boxed, padded, grows from 5em, wraps;
   newlines stripped so it stays a single logical line) */
#{$dtb_id} .dtb-card--edit { width: 100%; box-sizing: border-box; font: inherit; padding: 0.5em; min-height: 5em; resize: none; overflow: hidden; }

/* Add-task editor (one shared, moved into the target lane) */
#{$dtb_id} .dtb-card-editor { display: flex; flex-direction: column; gap: 0.5em; padding: 0.9em; border-radius: 10px; border: 1px solid #e5e7eb; background: #f3f4f6; }
#{$dtb_id} .dtb-card-editor .dtb-editor-title { width: 100%; box-sizing: border-box; font: inherit; font-size: 1.2em; padding: 0.5em; min-height: 5em; resize: none; overflow: hidden; }
/* Me / Unassigned assignee switcher — right-aligned past the Add/Cancel buttons; icon-sized segments.
   Outline-only (no box background, no filled active segment): selection is shown with an outline ring. */
#{$dtb_id} .dtb-editor-assign { margin-left: auto; border: 0; overflow: visible; gap: 0.2em; background: none; }
/* height:auto + min-height:0 + line-height:1 override the global BUTTON { height: 2.4em } and the
   .cerb-ui-switcher button { min-height: 2.4em } so the segment sizes square to its 18px icon. Miss
   either one and the box is taller than wide, so the 50% radius / outline draws a tall oval. */
#{$dtb_id} .dtb-editor-assign button { padding: 0.25em; height: auto; min-height: 0; line-height: 1; display: inline-flex; align-items: center; justify-content: center; background: none; border: 0; border-radius: 50%; opacity: 0.5; }
#{$dtb_id} .dtb-editor-assign button + button { border-left: 0; }
#{$dtb_id} .dtb-editor-assign button.cerb-ui-switcher--active { background: none; color: inherit; opacity: 1; outline: 2px solid var(--cerb-color-action-primary); outline-offset: 1px; }
#{$dtb_id} .dtb-editor-assign .dtb-card--owner { width: 18px; height: 18px; margin: 0; }
#{$dtb_id} .dtb-editor-assign .dtb-assign-unassigned { width: 18px; height: 18px; border: 1px dashed currentColor; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; opacity: 0.6; }
#{$dtb_id} .dtb-editor-assign button.cerb-ui-switcher--active .dtb-assign-unassigned { opacity: 1; }
#{$dtb_id} .dtb-editor-assign .dtb-assign-unassigned .cerb-icons { font-size: 11px; }
html.dark #{$dtb_id} .dtb-card-editor { background: #1f2937; border-color: #374151; }

/* Both editors share the column's background, rounded — so they read as part of the column. */
#{$dtb_id} .dtb-card--edit, #{$dtb_id} .dtb-card-editor .dtb-editor-title { background: #f3f4f6; border: 1px solid rgba(0,0,0,0.10); border-radius: 6px; }
html.dark #{$dtb_id} .dtb-card--edit, html.dark #{$dtb_id} .dtb-card-editor .dtb-editor-title { background: #1f2937; border-color: rgba(255,255,255,0.12); color: #f9fafb; }

/* Project select: lift it off the page-dark default to the count-pill gray (SortBrain row color) */
#{$dtb_id} .dtb-card-editor .cerb-ui-selectmenu { background: #e5e7eb; border-color: #d1d5db; }
html.dark #{$dtb_id} .dtb-card-editor .cerb-ui-selectmenu { background: #374151; border-color: #4b5563; color: #f9fafb; }
/* Project switcher (≤5 projects): wraps within the editor's column width; outline-only like the
   assignee switcher (no fill/idle backgrounds — far less distracting), active = an outline ring.
   The font-size bump is deliberate: these pills pick the task's project, so they need to read as
   comfortably as the Add Task / Cancel buttons they sit above. */
#{$dtb_id} .dtb-editor-project-switcher { display: flex; flex-wrap: wrap; gap: 0.3em; overflow: visible; border: 0; background: none; }
#{$dtb_id} .dtb-editor-project-switcher button { height: auto; min-height: 0; line-height: 1; padding: 0.55em 0.9em; font-size: 1.05em; background: none; border: 0; border-radius: 8px; opacity: 0.5; }
#{$dtb_id} .dtb-editor-project-switcher button + button { border-left: 0; }
#{$dtb_id} .dtb-editor-project-switcher button.cerb-ui-switcher--active { background: none; color: inherit; opacity: 1; outline: 2px solid var(--cerb-color-action-primary); outline-offset: 1px; }

/* Flat SortBrain-style action buttons (no gradient/CTA chrome). height:auto drops the fixed
   .cerb-ui-button height so the roomier padding isn't clipped; the font-size bump is deliberate --
   these are the editor's primary actions and should outweigh the card text beside them. */
#{$dtb_id} .dtb-editor-btn { border-style: solid; border-color: #d1d5db; height: auto; padding: 0.6em 1.2em; font-size: 1.1em; }
#{$dtb_id} .dtb-editor-add { background: #e5e7eb; color: var(--cerb-color-text); }
#{$dtb_id} .dtb-editor-add:hover { background: #dadde1; color: var(--cerb-color-text); }
#{$dtb_id} .dtb-editor-cancel { background: transparent; color: var(--cerb-color-text); }
#{$dtb_id} .dtb-editor-cancel:hover { background: rgba(0,0,0,0.04); color: var(--cerb-color-text); }
html.dark #{$dtb_id} .dtb-editor-btn { border-color: #4b5563; }
html.dark #{$dtb_id} .dtb-editor-add { background: #374151; color: #f9fafb; }
html.dark #{$dtb_id} .dtb-editor-add:hover { background: #3f4b5b; color: #f9fafb; }
html.dark #{$dtb_id} .dtb-editor-cancel { color: #f9fafb; }
html.dark #{$dtb_id} .dtb-editor-cancel:hover { background: rgba(255,255,255,0.06); color: #f9fafb; }

/* SortBrain palette (scoped to the board); page background stays Cerb's.
   Light: gray columns + white raised cards (soft shadow separates them, since card borders are off).
   Dark:  SortBrain grays (column = gray-800, card = gray-700). */
#{$dtb_id} .dtb-column { background-color: #f3f4f6; border: 0; }
#{$dtb_id} .dtb-card   { background-color: #ffffff; box-shadow: 0 1px 2px rgba(0,0,0,0.07); }
html.dark #{$dtb_id} .dtb-column { background-color: #1f2937; }
html.dark #{$dtb_id} .dtb-card   { background-color: #374151; color: #d4e1ed; box-shadow: none; } /* #f9fafb */

/* Column heading: larger, medium-weight text (white in dark mode) */
#{$dtb_id} .dtb-column--head .cerb-ui-header--label { color: var(--cerb-color-text); font-size: 1.1em; font-weight: 500; }

/* Count pill: borderless, soft fill (lighter than the column) */
#{$dtb_id} .dtb-count { background: #e5e7eb; color: #6b7280; }
html.dark #{$dtb_id} .dtb-count { background: #374151; color: #9ca3af; }

/* Flat icon buttons (no button chrome) for the column +/actions */
#{$dtb_id} .dtb-icon-btn { background: none; border: 0; padding: 2px 3px; margin: 0; cursor: pointer; color: var(--cerb-color-text); opacity: 0.55; font-size: 1.15em; line-height: 1; }
#{$dtb_id} .dtb-icon-btn:hover { opacity: 1; }

/* Stash column: a single element JS relocates into a day's columns (left of TODO); hidden until opened.
   Full height (no internal scroll) — the page scrolls if it's tall. */
#{$dtb_id} .dtb-stash[hidden] { display: none; }
#{$dtb_id} .dtb-stash .cerb-ui-header--label { font-size: 1.3em; text-transform: none; }
#{$dtb_id} .dtb-stash .cerb-icon-archive { color: var(--cerb-color-tag-orange); }

/* The day row hosting the stash: hide DONE while the stash is open so the remaining three columns
   (Stash / TODO / In Progress) stay equal thirds — no fourth column squeezing or horizontal scroll. */
#{$dtb_id} .dtb-columns--stash { overflow-x: visible; }
#{$dtb_id} .dtb-columns--stash > .dtb-column { flex: 1 1 0; }
#{$dtb_id} .dtb-columns--stash > .dtb-column:has(.dtb-column-cards[data-column="done"]) { display: none; }

/* Responsive: below a narrow (mobile-portrait) width, columns stack — Stash → TODO → Progress → Done. */
@media (max-width: 700px) {
	#{$dtb_id} .dtb-columns, #{$dtb_id} .dtb-columns--stash { flex-direction: column; overflow-x: visible; }
	#{$dtb_id} .dtb-columns > .dtb-column, #{$dtb_id} .dtb-columns--stash > .dtb-column { flex: 1 1 auto; width: 100%; }
}

/* Stash search box (matches the editor field fills) */
#{$dtb_id} .dtb-stash-search { width: 100%; box-sizing: border-box; margin: 0.4em 0 0.2em; padding: 0.5em 0.7em; font: inherit; border-radius: 8px; border: 1px solid #d1d5db; background: transparent; }
html.dark #{$dtb_id} .dtb-stash-search { background: transparent; border-color: #4b5563; color: #f9fafb; }

/* Stash section labels (Ready / Stashed Until / Stashed Indefinitely) */
#{$dtb_id} .dtb-stash-section { margin: 0.7em 0 0.1em; font-size: 0.8em; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em; opacity: 0.55; display: flex; align-items: center; gap: 0.35em; }
#{$dtb_id} .dtb-stash-section:first-child { margin-top: 0; }
#{$dtb_id} .dtb-stash-section[hidden] { display: none; }

/* Per-card schedule pill (click to set/change a reopen date): clock+date for "until", a pause glyph when
   none. Sits inline after the card text — separated by a left MARGIN and centered to the text line. */
#{$dtb_id} .dtb-card--when { display: inline-flex; align-items: center; gap: 0.3em; margin-left: 0.6em; vertical-align: middle; padding: 0.2em 0.5em; font: inherit; line-height: 1; border: 0; border-radius: 999px; cursor: pointer; background: rgba(0,0,0,0.06); }
#{$dtb_id} .dtb-card--when .cerb-icons { display: inline-block; line-height: 1; flex: 0 0 auto; }
html.dark #{$dtb_id} .dtb-card--when { background: rgba(255,255,255,0.10); }
#{$dtb_id} .dtb-card--when:hover { background: rgba(0,0,0,0.12); }
html.dark #{$dtb_id} .dtb-card--when:hover { background: rgba(255,255,255,0.18); }

/* Reopen-date field — sits below the title in the double-click editor (stash cards); freeform string. */
#{$dtb_id} .dtb-card--date-edit { margin-top: 0.35em; width: 100%; box-sizing: border-box; font: inherit; font-size: 0.85em; padding: 0.3em 0.5em; border: 1px solid rgba(0,0,0,0.12); border-radius: 6px; background: #f3f4f6; }
html.dark #{$dtb_id} .dtb-card--date-edit { background: #1f2937; border-color: rgba(255,255,255,0.12); color: #f9fafb; }

</style>

<div id="{$dtb_id}">

	{* Top toolbar: PriorityPicker (project selection + config gear) + actions *}
	<div class="cerb-ui-header dtb-toolbar cerb-u-mb-4">
		<div class="cerb-ui-header--right cerb-u-mr-auto">
			<div id="{$dtb_id}-projects"></div>
		</div>
		{if $has_projects}
		<div class="cerb-ui-header--right">
			<div class="cerb-ui-switcher dtb-focus-switcher" data-cerb-dtb="focus-switcher">
				<button type="button" data-value="all" title="Show everyone's tasks"><span class="cerb-icons cerb-icon-users"></span> All tasks</button>
				<button type="button" data-value="mine" title="Show only my tasks (hide everyone else's across all columns + stash)"><img class="dtb-focus-avatar" src="{$worker_meta[$active_worker_id].avatar|default:''}" alt=""> My tasks</button>
			</div>
			<div class="cerb-ui-toolbar-strip">
				<button type="button" class="cerb-ui-toolbar-button" data-cerb-dtb="jump-date">
					<span class="cerb-icons cerb-icon-calendar"></span> Jump to Date
				</button>
			</div>
		</div>
		{/if}
	</div>

	{* Server-built data blobs (HEX-escaped in PHP so names can't break out of the script) *}
	<script type="application/json" id="{$dtb_id}-pp-data">{$pp_items_json nofilter}</script>
	<script type="application/json" id="{$dtb_id}-projects-data">{$projects_json nofilter}</script>
	<script type="application/json" id="{$dtb_id}-workers-data">{$worker_meta_json nofilter}</script>
	<script type="application/json" id="{$dtb_id}-writeable-data">{$writeable_project_ids_json nofilter}</script>

	{* A single board-spanning stash column on the left, then the stacked day-rows on the right.
	   Each stash card carries a clickable schedule pill: until = clock + date; indefinite = a muted clock. *}
	{function name=dtb_stash_card card=null when='' mode='indefinite'}
		{$accent = $project_colors[$card.project_id]|default:'var(--cerb-color-tag-gray)'}
		<div class="cerb-ui-panel cerb-ui-panel--accent dtb-card" style="--cerb-ui-accent:{$accent};" data-task-id="{$card.id}" data-project-id="{$card.project_id}" data-owner-id="{$card.owner_id|default:0}" data-importance="{$card.importance|default:0}"{if $mode == 'until'} data-reopen="{$card.reopen_str}"{/if}><div class="dtb-card--body">{include file="devblocks:cerberusweb.core::internal/workspaces/tabs/daily_task_board/_card_meta.tpl" owner_id=$card.owner_id|default:0 project_id=$card.project_id}<div class="dtb-card--content"><span class="dtb-card--text">{$card.text}</span></div></div><div class="dtb-card--meta cerb-u-flex cerb-u-items-center cerb-u-justify-end cerb-u-gap-2 cerb-u-mt-2 cerb-u-fs-n1">{if $mode == 'until'}<button type="button" class="dtb-card--when" data-cerb-dtb="stash-schedule" title="Change reopen date"><span class="cerb-icons cerb-icon-clock"></span> {$when}</button>{/if}{if $card.comment_count > 0}<span class="cerb-ui-pill dtb-count cerb-u-fw-500 cerb-u-border-0" title="Comments"><span class="cerb-icons cerb-icon-conversation"></span> {$card.comment_count}</span>{/if}</div><button type="button" class="dtb-card--menu" title="More" data-cerb-dtb="card-menu"><span class="cerb-icons cerb-icon-more-vertical"></span></button></div>
	{/function}

	{if !$has_projects}
		{* No live projects: the columns would all be empty and every action a no-op, so point at the
		   Projects picker (its gear opens the same config dialog as the button below) instead. *}
		<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--note">
			<div class="cerb-ui-header cerb-ui-header--center">
				<div class="cerb-ui-callout">
					<span class="cerb-icons cerb-icon-circle-info cerb-ui-callout--icon"></span>
					<div>
						<div class="cerb-ui-header--title-sm">{if $has_config}No task projects available{else}No task projects on this board{/if}</div>
						<div class="cerb-ui-header--subtitle">
							{if $has_config}
								Every project on this board is archived, or you don't have access to it.
							{else}
								This board shows tasks from the task projects you add to it.
							{/if}
							{if $is_writeable}
								Use <b>Projects</b> at the top of this tab to choose which projects appear here.
							{else}
								Ask someone who can edit this page to add task projects.
							{/if}
						</div>
					</div>
				</div>
				{if $is_writeable}
					<div class="cerb-ui-header--right">
						<button type="button" class="cerb-ui-button" data-cerb-dtb="configure"><span class="cerb-icons cerb-icon-gear"></span> Configure projects</button>
					</div>
				{/if}
			</div>
		</div>
	{else}

	{* The single stash column — hidden; JS relocates it into a day's board (left of TODO) on toggle *}
	<div class="dtb-stash dtb-column cerb-ui-panel cerb-u-flex cerb-u-flex-1 cerb-u-flex-column" data-cerb-dtb-stash hidden>
		<div class="cerb-ui-header cerb-ui-header--tight dtb-column--head cerb-u-mb-1">
			<div class="cerb-ui-header--label"><span class="cerb-icons cerb-icon-archive"></span> Stashed</div>
			<div class="cerb-ui-header--right">
				<span class="cerb-ui-pill dtb-count cerb-u-fw-500 cerb-u-border-0" data-cerb-dtb-stash-count>{$stash_count}</span>
				<button type="button" class="dtb-icon-btn" title="Add stashed task" data-cerb-dtb="add">
					<span class="cerb-icons cerb-icon-plus"></span>
				</button>
				<button type="button" class="dtb-icon-btn" title="More" data-cerb-dtb="col-menu">
					<span class="cerb-icons cerb-icon-more-vertical"></span>
				</button>
				<button type="button" class="dtb-icon-btn" title="Close stash" data-cerb-dtb="stash-close">
					<span class="cerb-icons cerb-icon-circle-remove"></span>
				</button>
			</div>
		</div>

		<input type="search" class="dtb-stash-search" placeholder="Search stashed tasks…" data-cerb-dtb-stash-search>

		<div class="dtb-column-cards cerb-u-flex cerb-u-flex-column cerb-u-cursor-pointer" data-cerb-dtb-column data-board-date="0" data-column="stash">
			<div class="dtb-stash-section" data-cerb-dtb-stash-section="until"><span class="cerb-icons cerb-icon-clock"></span> Stashed Until</div>
			{foreach from=$stash_until item=card}{call name=dtb_stash_card card=$card when=$card.when mode='until'}{/foreach}

			<div class="dtb-stash-section" data-cerb-dtb-stash-section="indefinite"><span class="cerb-icons cerb-icon-infinity"></span> Stashed Indefinitely</div>
			{foreach from=$stash_indefinite item=card}{call name=dtb_stash_card card=$card mode='indefinite'}{/foreach}
		</div>
	</div>

	{* Daily boards, newest first (each row is the shared _day.tpl partial, reused by invoke('renderDay')) *}
	{foreach from=$boards item=board}
		{include file="devblocks:cerberusweb.core::internal/workspaces/tabs/daily_task_board/_day.tpl" board=$board}
	{/foreach}

	{/if}

</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	const root = document.getElementById('{$dtb_id}');
	if(!root || !window.CerbUI)
		return;

	const tabId = '{$workspace_tab->id}';
	// Mutable sort-group registry — grows as "Jump to Date" inserts day-rows (see addLaneToSortGroup).
	let lanes = Array.prototype.slice.call(root.querySelectorAll('[data-cerb-dtb-column]'));

	// The "Jump to Date" picker (set later); refreshing drops its per-month pip cache so a just-added
	// completion/stash shows up the next time its month is viewed.
	let dtbPicker = null;
	function refreshJumpPips() { if(dtbPicker && dtbPicker.refreshIndicators) dtbPicker.refreshIndicators(); }

	// Configured projects (id/label/color) + an id→color map for card accents.
	let projects = [];
	try { projects = JSON.parse(document.getElementById('{$dtb_id}-projects-data').textContent || '[]'); } catch(e) {}
	const projectColors = {};
	projects.forEach(function(p) { projectColors[p.id] = p.color; });

	// ── Ownership / focus (shared multi-worker boards) ──
	const dtbWorkerId = {$active_worker_id|default:0};
	let dtbWorkerMeta = {}; // id → name/avatar
	try { dtbWorkerMeta = JSON.parse(document.getElementById('{$dtb_id}-workers-data').textContent || '{}'); } catch(e) {}
	let focusMine = {if $focus_mine}true{else}false{/if};

	// Projects this worker can write — gates the "assign to me" claim chip on unassigned cards.
	let writeableProjects = new Set();
	try { writeableProjects = new Set(Object.keys(JSON.parse(document.getElementById('{$dtb_id}-writeable-data').textContent || '{}'))); } catch(e) {}

	// Build the assignee avatar <img> for an owner (uses the server-provided name/avatar map). Sits in the
	// card body's left column.
	function dtbOwnerImg(ownerId) {
		const meta = dtbWorkerMeta[ownerId] || {};
		const img = document.createElement('img');
		img.className = 'dtb-card--owner';
		img.src = meta.avatar || '';
		img.title = meta.name || '';
		img.alt = '';
		return img;
	}
	// Build the faint dashed "assign to me" claim button (mirrors _card_meta.tpl). The delegated
	// [data-cerb-dtb="claim"] click handler wires it up.
	function dtbClaimBtn() {
		const btn = document.createElement('button');
		btn.type = 'button';
		btn.className = 'dtb-card--claim';
		btn.title = 'Assign to me';
		btn.setAttribute('data-cerb-dtb', 'claim');
		btn.innerHTML = '<span class="cerb-icons cerb-icon-user"></span>';
		return btn;
	}

	// Task titles are single logical lines that wrap visually — strip any newlines (typed or pasted).
	function stripNewlines(v) { return (v || '').replace(/[\r\n]+/g, ''); }
	// Grow a textarea to fit its content (so it wraps naturally without a scrollbar).
	function autoGrow(el) { el.style.height = 'auto'; el.style.height = el.scrollHeight + 'px'; }

	// ── AJAX helper for this tab's invoke() actions ──
	function dtbInvoke(action, fields, cb) {
		const fd = new FormData();
		fd.append('c', 'pages');
		fd.append('a', 'invokeTab');
		fd.append('tab_id', tabId);
		fd.append('action', action);
		Object.keys(fields || {}).forEach(function(k) {
			const v = fields[k];
			if(Array.isArray(v)) v.forEach(function(x) { fd.append(k + '[]', x); });
			else fd.append(k, v);
		});
		genericAjaxPost(fd, '', '', cb || function() {}, { dataType: 'json' });
	}

	// The one board-spanning stash lane (its order is server-defined per section — never re-sorted here).
	const stashLane = root.querySelector('[data-cerb-dtb-column][data-column="stash"]');
	let currentSelected = [];
	let stashQuery = '';

	// ── Project selection drives card visibility, sort order, and column counts ──
	function applyProjectView(selectedIds) {
		currentSelected = selectedIds || [];
		const rank = new Map(currentSelected.map((id, i) => [String(id), i]));
		// In focus mode every visible card is mine, so the per-card avatar is redundant — a root class
		// hides it via CSS.
		root.classList.toggle('dtb-focus-mine', focusMine);

		lanes.forEach(function(lane) {
			if(lane === stashLane) return; // stash handled separately (no priority re-sort)
			// Active lanes re-sort by project priority. DONE (today + prior-day logs) and the future
			// stash_day column KEEP their server order (completed_date / reopen_at) — we only filter
			// visibility for those.
			const col = lane.getAttribute('data-column');
			const sortable = (col === 'todo' || col === 'in_progress');
			const cards = Array.prototype.slice.call(lane.querySelectorAll('.dtb-card'));
			let visible = 0;

			if(sortable) cards.sort(function(a, b) {
				const pa = rank.has(a.dataset.projectId) ? rank.get(a.dataset.projectId) : Infinity;
				const pb = rank.has(b.dataset.projectId) ? rank.get(b.dataset.projectId) : Infinity;
				return pa - pb;
			});

			cards.forEach(function(card) {
				// Visible = its project is selected AND (focus off, or it's mine). Focus "my tasks" hides
				// everyone else's cards entirely across every lane — as if they don't exist.
				const selected = rank.has(card.dataset.projectId);
				const okFocus = !focusMine || String(card.dataset.ownerId) === String(dtbWorkerId);
				const show = selected && okFocus;
				card.hidden = !show;
				if(show) visible++;
				lane.appendChild(card); // commit the new order
			});

			const countEl = lane.closest('.dtb-column').querySelector('.dtb-count');
			if(countEl) countEl.textContent = visible;
		});

		applyStashFilters();
		updateDayStates();
	}

	// Stash visibility = selected project AND (matches the search box). Counts track the selection only
	// (so typing in the search doesn't make the header pills flicker); section labels follow what's shown.
	function applyStashFilters() {
		if(!stashLane) return;
		const sel = new Set(currentSelected.map(String));
		const q = stashQuery.trim().toLowerCase();
		let bySelection = 0;

		stashLane.querySelectorAll('.dtb-card').forEach(function(card) {
			const okSel = sel.has(String(card.dataset.projectId));
			// Focus "my tasks" filters the stash too (only my stashed cards).
			const okFocus = !focusMine || String(card.dataset.ownerId) === String(dtbWorkerId);
			if(okSel && okFocus) bySelection++;
			const txt = (card.querySelector('.dtb-card--text') || {}).textContent || '';
			card.hidden = !(okSel && okFocus && (!q || txt.toLowerCase().indexOf(q) !== -1));
		});

		// "Stashed Until" / "Stashed Indefinitely" labels always show; only the optional "Ready" label
		// auto-hides when no visible card follows it (until the next label).
		const kids = Array.prototype.slice.call(stashLane.children);
		kids.forEach(function(el, i) {
			if(!el.hasAttribute('data-cerb-dtb-stash-section')) return;
			if(!el.hasAttribute('data-cerb-dtb-stash-section-optional')) { el.hidden = false; return; }
			let any = false;
			for(let j = i + 1; j < kids.length; j++) {
				if(kids[j].hasAttribute('data-cerb-dtb-stash-section')) break;
				if(kids[j].classList.contains('dtb-card') && !kids[j].hidden) { any = true; break; }
			}
			el.hidden = !any;
		});

		// Sync every stash count pill (the TODO-header toggles + the stash column header).
		root.querySelectorAll('[data-cerb-dtb-stash-count]').forEach(function(el) { el.textContent = bySelection; });
	}

	// ── Per-day summary text + (load-time only) auto-collapse ──
	function updateDayStates(collapse) {
		root.querySelectorAll('[data-cerb-dtb-day]').forEach(function(day) {
			let active = 0, done = 0;
			day.querySelectorAll('[data-cerb-dtb-column]').forEach(function(lane) {
				const col = lane.getAttribute('data-column');
				const v = lane.querySelectorAll('.dtb-card:not([hidden])').length;
				if(col === 'done') done += v; else active += v;
				// Collapsed-today summary = three TODO / In Progress / Done count pills, kept in sync (zeros included).
				const distPill = day.querySelector('[data-cerb-dtb-dist-col="' + col + '"]');
				if(distPill) distPill.textContent = v;
			});

			const summary = day.querySelector('[data-cerb-dtb-summary]');
			if(summary) summary.textContent = done > 0 ? (done + ' done') : 'No tasks';

			// Off-window day count pill (in the title) tracks its single lane as cards move in/out —
			// Done for a prior day, stash for a future day (one of done/active is always 0). Target the
			// inner number span so a future pill's archive icon isn't clobbered.
			const dayCountN = day.querySelector('[data-cerb-dtb-day-count-n]');
			if(dayCountN) dayCountN.textContent = done + active;

			if(!collapse || day.dataset.manual === '1')
				return;

			const isToday = day.getAttribute('data-is-today') === '1';
			day.classList.toggle('dtb-day--collapsed', !isToday && active === 0);
		});
	}

	// Recount visible cards per lane (no re-sort/re-filter).
	function refreshCounts() {
		lanes.forEach(function(lane) {
			if(lane === stashLane) return; // stash counts handled by applyStashFilters
			const visible = lane.querySelectorAll('.dtb-card:not([hidden])').length;
			const countEl = lane.closest('.dtb-column').querySelector('.dtb-count');
			if(countEl) countEl.textContent = visible;
		});
		applyStashFilters();
		updateDayStates();
		updateAddPrompts();
	}

	// Show the "click anywhere to add" hint only in a truly empty TODO lane.
	function updateAddPrompts() {
		root.querySelectorAll('[data-cerb-dtb-prompt]').forEach(function(prompt) {
			const lane = prompt.closest('[data-cerb-dtb-column]');
			prompt.hidden = !lane || lane.querySelectorAll('.dtb-card').length > 0;
		});
	}

	// Click a day title to toggle (and pin) its collapsed state. Delegated so days inserted later by
	// "Jump to Date" are covered too.
	root.addEventListener('click', function(e) {
		const toggle = e.target.closest('[data-cerb-dtb-day-toggle]');
		if(!toggle) return;
		const day = toggle.closest('[data-cerb-dtb-day]');
		if(!day) return;
		day.classList.toggle('dtb-day--collapsed');
		day.dataset.manual = '1';
	});

	// Add/remove the check in the body's far-left column (before the avatar) as a card enters/leaves Done.
	function setCardDoneState(card, isDone) {
		const body = card.querySelector('.dtb-card--body') || card;
		const existing = card.querySelector('.dtb-card--check');
		if(isDone && !existing) {
			const check = document.createElement('span');
			check.className = 'cerb-icons cerb-icon-check dtb-card--check';
			body.insertBefore(check, body.firstChild);
		} else if(!isDone && existing) {
			existing.remove();
		}
	}

	// Build a card element matching the server markup (for newly-added tasks). New tasks are owned by
	// the active worker, so the meta-strip shows my avatar.
	function buildCard(taskId, projectId, text, isDone, ownerId) {
		if(ownerId === undefined) ownerId = dtbWorkerId;
		const card = document.createElement('div');
		card.className = 'cerb-ui-panel cerb-ui-panel--accent dtb-card';
		card.style.setProperty('--cerb-ui-accent', projectColors[projectId] || 'var(--cerb-color-tag-gray)');
		card.setAttribute('data-task-id', taskId);
		card.setAttribute('data-project-id', projectId);
		card.setAttribute('data-owner-id', ownerId || 0);
		card.setAttribute('data-importance', 50); // neutral default; the server's real value lands on reload

		// Body = [done check?] [assignee avatar] [content: title] — consistent left layout on all cards.
		const body = document.createElement('div');
		body.className = 'dtb-card--body';
		if(isDone) {
			const check = document.createElement('span');
			check.className = 'cerb-icons cerb-icon-check dtb-card--check';
			body.appendChild(check);
		}
		if(ownerId > 0) body.appendChild(dtbOwnerImg(ownerId));
		else if(writeableProjects.has(String(projectId))) body.appendChild(dtbClaimBtn());
		const content = document.createElement('div');
		content.className = 'dtb-card--content';
		const span = document.createElement('span');
		span.className = 'dtb-card--text';
		span.textContent = text;
		content.appendChild(span);
		body.appendChild(content);
		card.appendChild(body);

		// Empty meta-strip footer (hidden until it holds the stash pill / future counters).
		const meta = document.createElement('div');
		meta.className = 'dtb-card--meta cerb-u-flex cerb-u-items-center cerb-u-justify-end cerb-u-gap-2 cerb-u-mt-2 cerb-u-fs-n1';
		card.appendChild(meta);

		const menu = document.createElement('button');
		menu.type = 'button';
		menu.className = 'dtb-card--menu';
		menu.title = 'More';
		menu.setAttribute('data-cerb-dtb', 'card-menu');
		menu.innerHTML = '<span class="cerb-icons cerb-icon-more-vertical"></span>';
		card.appendChild(menu);
		return card;
	}

	// Open a task's peek editor (edit mode directly — skips the view→edit step so the save/delete fire on
	// THIS popup). A save re-derives the board (status/is_active/reopen can move the card's column); a
	// delete just drops the card inline.
	function openPeek(taskId, edit) {
		if(typeof genericAjaxPopup !== 'function') return;
		const $popup = genericAjaxPopup('peek', 'c=internal&a=invoke&module=records&action=showPeekPopup'
			+ '&context=cerberusweb.contexts.task&context_id=' + encodeURIComponent(taskId) + (edit ? '&edit=true' : ''), 'reuse', false, '50%');
		if(!$popup || !$popup.on) return;

		$popup.on('peek_saved', function(e) {
			// A peek can change status / is_active / reopen_at — all of which DERIVE the card's column —
			// so re-derive the whole board instead of just patching the card text in place.
			dtbReloadTab();
		});
		$popup.on('peek_deleted', function(e) {
			const card = root.querySelector('.dtb-card[data-task-id="' + (e.id || taskId) + '"]');
			if(card) { card.remove(); refreshCounts(); }
		});
	}

	// ── Per-card ⋮ menu (CerbUI.Menu): Edit / Stash / Delete. Delete swaps the menu for a confirm menu
	//    (Confirm delete / Cancel); Cancel restores the original. Two instances at the same anchor. ──
	let menuCard = null, menuAnchor = null, cardMenu = null, confirmMenu = null;
	if(CerbUI.Menu) {
		// data-dtb-danger (a data-* attr) is mirrored onto the rendered <li> — the menu doesn't copy class.
		// Project rows ("Move" submenu) get a color swatch instead of an icon; the card's CURRENT project
		// is hidden so Move only lists OTHER projects.
		const renderIcon = function(li) {
			if(li.dataset.action === 'move') {
				// Hide "Move" when the only board project is the card's own — its submenu would be empty.
				const cur = menuCard && menuCard.getAttribute('data-project-id');
				const others = projects.filter(function(p) { return String(p.id) !== String(cur); });
				li.style.display = others.length ? '' : 'none';
			}
			if(li.dataset.action === 'move-project') {
				if(menuCard && String(li.dataset.projectId) === String(menuCard.getAttribute('data-project-id')))
					li.style.display = 'none';
				const pip = document.createElement('span');
				pip.className = 'cerb-ui-pip';
				pip.style.color = li.dataset.color || 'var(--cerb-color-tag-gray)';
				pip.style.marginRight = '0.5em';
				li.insertBefore(pip, li.firstChild);
				return;
			}
			const ico = document.createElement('span');
			ico.className = 'cerb-icons cerb-icon-' + (li.dataset.icon || 'file');
			ico.style.marginRight = '0.5em';
			li.insertBefore(ico, li.firstChild);
		};

		const mainUl = document.createElement('ul');
		mainUl.hidden = true;
		mainUl.innerHTML =
			'<li data-action="view" data-icon="eye-open">View</li>' +
			'<li data-action="edit" data-icon="edit">Edit</li>' +
			'<li data-action="move" data-icon="transfer">Move<ul class="dtb-move-sub"></ul></li>' +
			'<li data-action="stash" data-icon="archive">Stash</li>' +
			'<li></li>' + // separator
			'<li data-action="delete" data-icon="trash">Delete</li>';
		// Populate the Move submenu with the board's projects (built once — open() caches the parsed tree).
		const moveSub = mainUl.querySelector('.dtb-move-sub');
		projects.forEach(function(p) {
			const li = document.createElement('li');
			li.dataset.action = 'move-project';
			li.dataset.projectId = p.id;
			li.dataset.color = p.color;
			li.textContent = p.label; // textContent — never innerHTML for project names
			moveSub.appendChild(li);
		});
		document.body.appendChild(mainUl);

		cardMenu = new CerbUI.Menu(mainUl, {
			onRenderItem: renderIcon,
			onSelect: function(li) {
				const card = menuCard;
				const id = card && card.getAttribute('data-task-id');
				if(!id) return;
				const action = li.dataset.action;

				if(action === 'view') {
					openPeek(id, false);
				} else if(action === 'edit') {
					openPeek(id, true);
				} else if(action === 'move-project') {
					const pid = li.dataset.projectId;
					dtbInvoke('setProject', { task_id: id, project_id: pid }, function(json) {
						if(!json || json.status !== 'ok') return;
						card.style.setProperty('--cerb-ui-accent', projectColors[pid] || 'var(--cerb-color-tag-gray)');
						card.setAttribute('data-project-id', pid);
						// Hide if the new project isn't currently selected in the picker.
						const sel = pp ? pp.getSelected().map(String) : [];
						card.hidden = sel.length > 0 && sel.indexOf(String(pid)) === -1;
						refreshCounts();
					});
				} else if(action === 'stash') {
					// Stash without a board reload: AJAX the status change, then move the card into the stash's
					// Indefinitely section. Open stash → at the top; closed → the card just leaves its lane.
					dtbInvoke('moveTask', { task_id: id, to_column: 'stash', to_date: 0, order: [] }, function(json) {
						if(!json || json.status !== 'ok') return;
						setCardDoneState(card, false);          // drop the done check if it was a Done card
						setStashAffordance(card);                // strip any schedule pill (indefinite shows none)
						tuckIntoStash(card);                     // top of "Stashed Indefinitely"
						refreshCounts();
					});
				} else if(action === 'delete') {
					// Replace this menu with the confirm menu at the same anchor (deferred past the auto-close).
					setTimeout(function() { if(menuAnchor) confirmMenu.open(menuAnchor); }, 0);
				}
			},
		});

		const confirmUl = document.createElement('ul');
		confirmUl.hidden = true;
		confirmUl.innerHTML =
			'<li data-action="confirm-delete" data-icon="trash" data-dtb-danger>Confirm delete</li>' +
			'<li data-action="cancel" data-icon="ban">Cancel</li>';
		document.body.appendChild(confirmUl);

		confirmMenu = new CerbUI.Menu(confirmUl, {
			onRenderItem: renderIcon,
			onSelect: function(li) {
				const card = menuCard;
				if(li.dataset.action === 'confirm-delete') {
					const id = card && card.getAttribute('data-task-id');
					if(!id) return;
					dtbInvoke('deleteTask', { task_id: id }, function(json) {
						if(json && json.status === 'ok') { card.remove(); refreshCounts(); }
					});
				}
				// else: cancel → just let the menu close (closeOnSelect); no need to restore the main menu.
			},
		});
	}

	// ── Column "⋯" menu + multi-select mode + bulk Actions ──
	let menuColumnEl = null; // the column whose ⋯ / Actions menu is open
	let columnMenu = null, actionsMenu = null;

	function multiLane(colEl) { return colEl.querySelector('[data-cerb-dtb-column]'); }
	function selectedCards(colEl) { return Array.prototype.slice.call(colEl.querySelectorAll('.dtb-card--selected')); }

	function updateMultiState(colEl) {
		const visible = colEl.querySelectorAll('.dtb-card:not([hidden])').length;
		const n = colEl.querySelectorAll('.dtb-card--selected').length;
		const countEl = colEl.querySelector('[data-dtb-multi-count]');
		if(countEl) countEl.textContent = n + ' selected';
		const actBtn = colEl.querySelector('[data-cerb-dtb="multi-actions"]');
		if(actBtn) actBtn.hidden = (n === 0);
		// The shortcut flips to "Deselect All" once every (visible) card is selected.
		const allBtn = colEl.querySelector('[data-cerb-dtb="multi-all"]');
		if(allBtn) allBtn.textContent = (visible > 0 && n >= visible) ? 'Deselect All' : 'Select All';
	}

	function enterMulti(colEl) {
		if(!colEl || colEl.classList.contains('dtb-column--multi')) return;
		colEl.classList.add('dtb-column--multi');

		// Multi-select header (replaces the hidden normal head): exit · count · Select All, + Actions.
		const head = document.createElement('div');
		head.className = 'dtb-multihead';
		head.innerHTML =
			'<div class="dtb-multihead--top">' +
				'<button type="button" class="dtb-icon-btn" data-cerb-dtb="multi-exit" title="Exit multi-select"><span class="cerb-icons cerb-icon-circle-remove"></span></button>' +
				'<span class="dtb-multihead--count" data-dtb-multi-count>0 selected</span>' +
				'<button type="button" class="dtb-multihead--all" data-cerb-dtb="multi-all">Select All</button>' +
			'</div>' +
			'<button type="button" class="cerb-ui-button cerb-ui-button--subtle dtb-multihead--actions" data-cerb-dtb="multi-actions" hidden><span class="cerb-icons cerb-icon-more-vertical"></span> Actions <span class="cerb-icons cerb-icon-chevron-down"></span></button>';
		const normalHead = colEl.querySelector('.dtb-column--head');
		colEl.insertBefore(head, normalHead ? normalHead.nextSibling : colEl.firstChild);

		// Checkbox in each card's left slot (other affordances are hidden via CSS).
		colEl.querySelectorAll('.dtb-card').forEach(function(card) {
			const body = card.querySelector('.dtb-card--body');
			if(!body || body.querySelector('.dtb-card--checkbox')) return;
			const cb = document.createElement('span');
			cb.className = 'dtb-card--checkbox';
			cb.innerHTML = '<span class="cerb-icons cerb-icon-check"></span>';
			body.insertBefore(cb, body.firstChild);
		});

		// No dragging while selecting.
		const lane = multiLane(colEl);
		const s = lane && CerbUI.Sortable && CerbUI.Sortable.from(lane);
		if(s) s.opts.disabled = true;

		updateMultiState(colEl);
	}

	function exitMulti(colEl) {
		if(!colEl || !colEl.classList.contains('dtb-column--multi')) return;
		colEl.classList.remove('dtb-column--multi');
		const head = colEl.querySelector('.dtb-multihead');
		if(head) head.remove();
		colEl.querySelectorAll('.dtb-card--checkbox').forEach(function(cb) { cb.remove(); });
		colEl.querySelectorAll('.dtb-card--selected').forEach(function(c) { c.classList.remove('dtb-card--selected'); });
		const lane = multiLane(colEl);
		const s = lane && CerbUI.Sortable && CerbUI.Sortable.from(lane);
		if(s) s.opts.disabled = false;
	}

	if(CerbUI.Menu) {
		// Column ⋯ menu — one shared instance: "Select Multiple Tasks" + (TODO/Stash only) "Quick Triage".
		const colUl = document.createElement('ul');
		colUl.hidden = true;
		colUl.innerHTML =
			'<li data-action="multi-select" data-icon="checked">Select Multiple Tasks</li>' +
			'<li data-action="triage" data-icon="zap" data-dtb-triage>Quick Triage</li>';
		document.body.appendChild(colUl);
		columnMenu = new CerbUI.Menu(colUl, {
			onRenderItem: function(li) {
				if(li.dataset.action === 'triage') {
					// Triage is only for TODO + the board Stash; hide it elsewhere, and give it a subtitle.
					const lane = menuColumnEl && multiLane(menuColumnEl);
					const col = lane && lane.getAttribute('data-column');
					if(col !== 'todo' && col !== 'stash') { li.style.display = 'none'; return; }
					li.style.display = '';
					// Set the label INSIDE the menu's label span (don't wipe it), then a second line under it.
					const lbl = li.querySelector('.cerb-ui-menu--label');
					if(lbl) {
						lbl.textContent = 'Quick Triage';
						const sub = document.createElement('small');
						sub.className = 'dtb-triage-sub';
						sub.textContent = 'Gamified task sorting';
						lbl.appendChild(sub);
					}
				}
				const ico = document.createElement('span');
				ico.className = 'cerb-icons cerb-icon-' + (li.dataset.icon || 'file');
				ico.style.marginRight = '0.5em';
				li.insertBefore(ico, li.firstChild);
			},
			onSelect: function(li) {
				if(li.dataset.action === 'multi-select' && menuColumnEl) enterMulti(menuColumnEl);
				else if(li.dataset.action === 'triage' && menuColumnEl) openTriage(menuColumnEl);
			},
		});

		// Bulk Actions menu — move to a column (current hidden) or a project (divider + swatches).
		const COL_TARGETS = [
			{ column: 'todo', label: 'TODO' },
			{ column: 'in_progress', label: 'In Progress' },
			{ column: 'done', label: 'Done' },
			{ column: 'stash', label: 'Stash' },
		];
		const actUl = document.createElement('ul');
		actUl.hidden = true;
		COL_TARGETS.forEach(function(c) {
			const li = document.createElement('li');
			li.dataset.action = 'move-column';
			li.dataset.column = c.column;
			li.dataset.icon = 'transfer';
			li.textContent = c.label;
			actUl.appendChild(li);
		});
		actUl.appendChild(document.createElement('li')); // empty <li> = separator
		projects.forEach(function(p) {
			const li = document.createElement('li');
			li.dataset.action = 'move-project';
			li.dataset.projectId = p.id;
			li.dataset.color = p.color;
			li.textContent = p.label; // textContent — never innerHTML for project names
			actUl.appendChild(li);
		});
		document.body.appendChild(actUl);

		actionsMenu = new CerbUI.Menu(actUl, {
			onRenderItem: function(li) {
				if(li.dataset.action === 'move-project') {
					const pip = document.createElement('span');
					pip.className = 'cerb-ui-pip';
					pip.style.color = li.dataset.color || 'var(--cerb-color-tag-gray)';
					pip.style.marginRight = '0.5em';
					li.insertBefore(pip, li.firstChild);
					return;
				}
				// Hide the active column's own target ("move to" excludes self).
				const lane = menuColumnEl && multiLane(menuColumnEl);
				if(lane && li.dataset.column === lane.getAttribute('data-column'))
					li.style.display = 'none';
				const ico = document.createElement('span');
				ico.className = 'cerb-icons cerb-icon-' + (li.dataset.icon || 'transfer');
				ico.style.marginRight = '0.5em';
				li.insertBefore(ico, li.firstChild);
			},
			onSelect: function(li) {
				const colEl = menuColumnEl;
				if(!colEl) return;
				const ids = selectedCards(colEl).map(function(c) { return c.getAttribute('data-task-id'); });
				if(!ids.length) return;
				const fields = (li.dataset.action === 'move-column')
					? { task_ids: ids, to_column: li.dataset.column }
					: { task_ids: ids, project_id: li.dataset.projectId };
				dtbInvoke('bulkUpdate', fields, function(json) {
					if(json && json.status === 'ok') dtbReloadTab(); // re-derive board + drop multi-select
				});
			},
		});
	}

	// Capturing click handler — intercepts the ⋯ menu, the multi-select header buttons, and card
	// selection (so a plain card click in multi mode selects rather than reaching the add/menu handlers).
	root.addEventListener('click', function(e) {
		const colMenuBtn = e.target.closest('[data-cerb-dtb="col-menu"]');
		if(colMenuBtn) {
			e.stopPropagation();
			menuColumnEl = colMenuBtn.closest('.dtb-column');
			if(columnMenu) columnMenu.open(colMenuBtn);
			return;
		}
		const exitBtn = e.target.closest('[data-cerb-dtb="multi-exit"]');
		if(exitBtn) { e.stopPropagation(); exitMulti(exitBtn.closest('.dtb-column')); return; }
		const allBtn = e.target.closest('[data-cerb-dtb="multi-all"]');
		if(allBtn) {
			e.stopPropagation();
			const colEl = allBtn.closest('.dtb-column');
			const cards = colEl.querySelectorAll('.dtb-card:not([hidden])');
			// Toggle: if everything's already selected, clear it; otherwise select all visible.
			const allSel = cards.length > 0 && Array.prototype.every.call(cards, function(c) { return c.classList.contains('dtb-card--selected'); });
			cards.forEach(function(c) { c.classList.toggle('dtb-card--selected', !allSel); });
			updateMultiState(colEl);
			return;
		}
		const actBtn = e.target.closest('[data-cerb-dtb="multi-actions"]');
		if(actBtn) { e.stopPropagation(); menuColumnEl = actBtn.closest('.dtb-column'); if(actionsMenu) actionsMenu.open(actBtn); return; }

		// Card selection inside a multi-select column.
		const multiCol = e.target.closest('.dtb-column--multi');
		if(multiCol) {
			const card = e.target.closest('.dtb-card');
			if(card) {
				e.stopPropagation();
				card.classList.toggle('dtb-card--selected');
				updateMultiState(multiCol);
			} else {
				e.stopPropagation(); // swallow other clicks in a multi column (e.g. empty-lane add)
			}
		}
	}, true);

	// ── Quick Triage — a gamified modal card-stack for burning down TODO / the Stash. ──
	function openTriage(colEl) {
		if(!CerbUI.Dialog) return;
		const lane = multiLane(colEl);
		if(!lane) return;
		const mode = lane.getAttribute('data-column'); // 'todo' | 'stash'

		// Snapshot the visible cards once (stash = indefinite only). No list fetch — it's all in the DOM.
		const sel = (mode === 'stash') ? '.dtb-card:not([hidden]):not([data-reopen])' : '.dtb-card:not([hidden])';
		const cards = Array.prototype.map.call(lane.querySelectorAll(sel), function(c) {
			return {
				id: c.getAttribute('data-task-id'),
				projectId: c.getAttribute('data-project-id'),
				title: ((c.querySelector('.dtb-card--text') || {}).textContent || ''),
				importance: parseInt(c.getAttribute('data-importance'), 10) || 0,
			};
		});
		const total = cards.length;
		if(!total) return;

		const projName = {};
		projects.forEach(function(p) { projName[p.id] = p.label; });

		// Column-specific action set (each advances to the next card). dir drives the deal animation.
		const SKIP     = { action: 'skip',     label: 'Skip',     hint: 'Space',     icon: 'circle-arrow-right', dir: 'up' };
		const PRIORITY = { action: 'priority', label: 'Priority', hint: 'ArrowUp',   icon: 'thumbs-up',         dir: 'up' };
		const DELETE   = { action: 'delete',   label: 'Delete',   hint: 'x',         icon: 'trash',              dir: 'up', danger: true };
		const LATER    = { action: 'later',    label: 'Later',    hint: 'ArrowDown', icon: 'thumbs-down',       dir: 'down' };
		const actions = (mode === 'stash')
			? [ SKIP, PRIORITY, DELETE,
				{ action: 'schedule', label: 'Schedule', hint: 'ArrowLeft',  icon: 'calendar',    dir: 'left',  shift: { action: 'done',       label: 'Done',        icon: 'check' } }, LATER,
				{ action: 'totodo',   label: 'TODO',     hint: 'ArrowRight', icon: 'right-arrow', dir: 'right', shift: { action: 'toprogress', label: 'In Progress', icon: 'right-arrow' } } ]
			: [ SKIP, PRIORITY, DELETE,
				{ action: 'tostash',    label: 'Stash',       hint: 'ArrowLeft',  icon: 'archive',     dir: 'left' }, LATER,
				{ action: 'toprogress', label: 'In Progress', hint: 'ArrowRight', icon: 'right-arrow', dir: 'right', shift: { action: 'done', label: 'Done', icon: 'check' } } ];
		const byAction = {};
		actions.forEach(function(a) { byAction[a.action] = a; });

		// Build the modal content (the dialog wraps this element; throwaway on close).
		const root = document.createElement('div');
		root.className = 'dtb-triage';
		root.innerHTML =
			'<div class="dtb-triage--head">' +
				'<div class="dtb-triage--head-row">' +
					'<div class="dtb-triage--head-left"><span class="cerb-icons cerb-icon-zap cerb-u-anim-pulse"></span><div><div class="dtb-triage--title"></div><div class="dtb-triage--subtitle"></div></div></div>' +
					'<div class="dtb-triage--head-right">' +
						'<div class="dtb-triage--progress">Progress <b data-dtb-triage-pct>0%</b></div>' +
						'<button type="button" class="dtb-triage--icon" data-dtb-triage="shuffle" title="Shuffle"><span class="cerb-icons cerb-icon-dice"></span></button>' +
						'<button type="button" class="dtb-triage--icon" data-dtb-triage="close" title="Close"><span class="cerb-icons cerb-icon-circle-remove"></span></button>' +
					'</div>' +
				'</div>' +
				'<div class="dtb-triage--bar"><div class="dtb-triage--bar-fill" data-dtb-triage-bar></div></div>' +
			'</div>' +
			'<div class="dtb-triage--body">' +
				'<div class="dtb-triage--remaining"><b data-dtb-triage-remaining>0</b> tasks remaining</div>' +
				'<div class="dtb-triage--stack" data-dtb-triage-stack></div>' +
				'<div class="dtb-triage--grid" data-dtb-triage-grid></div>' +
			'</div>';

		root.querySelector('.dtb-triage--title').textContent = (mode === 'stash') ? 'Stash Triage' : 'TODO Triage';
		root.querySelector('.dtb-triage--subtitle').textContent = 'Organize your ' + total + (mode === 'stash' ? ' stashed' : '') + ' tasks';

		// Decorative depth cards behind the active one.
		const stackEl = root.querySelector('[data-dtb-triage-stack]');
		let depth = '';
		for(let i = 6; i >= 1; i--) depth += '<div class="dtb-triage--depth" style="--d:' + i + ';"></div>'; // back-to-front: nearest paints last (on top)
		stackEl.innerHTML = depth +
			'<div class="dtb-triage--card" data-dtb-triage-active>' +
				'<div class="dtb-triage--card-meta"><span class="cerb-ui-pip" data-dtb-triage-pip></span> <span class="dtb-triage--proj" data-dtb-triage-proj></span> <span class="dtb-triage--idx" data-dtb-triage-idx></span></div>' +
				'<div class="dtb-triage--card-title" data-dtb-triage-title></div>' +
				'<div class="dtb-triage--imp-row" title="Importance">' +
					'<span class="dtb-triage--thumb dtb-triage--thumb-down"><span class="cerb-icons cerb-icon-thumbs-down"></span></span>' +
					'<div class="dtb-triage--imp"><div class="dtb-triage--imp-fill" data-dtb-triage-imp></div></div>' +
					'<span class="dtb-triage--thumb dtb-triage--thumb-up"><span class="cerb-icons cerb-icon-thumbs-up"></span></span>' +
				'</div>' +
				'<input type="text" class="dtb-triage--sched" data-dtb-triage-sched placeholder="e.g. next friday 1pm — Enter to schedule, Esc to cancel" hidden>' +
				'<div class="dtb-triage--confirm" data-dtb-triage-confirm hidden>' +
					'<div class="dtb-triage--confirm-msg">Delete this task?</div>' +
					'<div class="dtb-triage--confirm-btns">' +
						'<button type="button" class="dtb-triage--confirm-yes" data-dtb-triage="confirm-delete"><span class="cerb-icons cerb-icon-trash"></span> Delete</button>' +
						'<button type="button" class="dtb-triage--confirm-no" data-dtb-triage="confirm-cancel">Cancel</button>' +
					'</div>' +
				'</div>' +
			'</div>';

		const gridEl = root.querySelector('[data-dtb-triage-grid]');
		actions.forEach(function(a) {
			const b = document.createElement('button');
			b.type = 'button';
			b.className = 'dtb-triage--act' + (a.danger ? ' dtb-triage--act-danger' : '');
			b.setAttribute('data-dtb-triage-act', a.action);
			const iconCls = 'cerb-icons cerb-icon-' + a.icon + (a.danger ? ' cerb-u-anim-shake-hover' : '');
			b.innerHTML = '<span class="' + iconCls + '"></span><span class="dtb-triage--act-label"></span><span class="dtb-triage--act-hint"></span>';
			b.querySelector('.dtb-triage--act-label').textContent = a.label;
			b.querySelector('.dtb-triage--act-hint').textContent = a.hint;
			// Priority/Later carry a ±delta badge that flips to ±5 while Shift is held.
			if(a.action === 'priority' || a.action === 'later') {
				const d = document.createElement('span');
				d.className = 'dtb-triage--delta';
				d.setAttribute('data-dtb-triage-delta', a.action);
				b.querySelector('.dtb-triage--act-label').appendChild(d);
			}
			gridEl.appendChild(b);
		});

		// Reflect Shift state: ±1/±5 priority badges, and escalated actions (stash: Schedule→Done, TODO→In Progress).
		function setDeltas(shift) {
			root.querySelectorAll('[data-dtb-triage-delta]').forEach(function(d) {
				const up = d.getAttribute('data-dtb-triage-delta') === 'priority';
				d.textContent = (up ? ' +' : ' −') + (shift ? '5' : '1');
			});
			actions.forEach(function(a) {
				if(!a.shift) return;
				const b = gridEl.querySelector('[data-dtb-triage-act="' + a.action + '"]');
				if(!b) return;
				const lbl = b.querySelector('.dtb-triage--act-label');
				const ico = b.querySelector('.cerb-icons');
				if(lbl) lbl.textContent = shift ? a.shift.label : a.label;
				if(ico) ico.className = 'cerb-icons cerb-icon-' + (shift ? (a.shift.icon || a.icon) : a.icon);
			});
		}
		function onShift(e) { if(e.key === 'Shift') setDeltas(e.type === 'keydown'); }

		let idx = 0;
		const activeEl = root.querySelector('[data-dtb-triage-active]');
		const schedInput = root.querySelector('[data-dtb-triage-sched]');
		const confirmEl = root.querySelector('[data-dtb-triage-confirm]');
		function setText(s, v) { const el = root.querySelector(s); if(el) el.textContent = v; }
		function hideSched() { if(schedInput) { schedInput.hidden = true; schedInput.value = ''; } }
		function hideConfirm() { if(confirmEl) confirmEl.hidden = true; }
		function showDeleteConfirm() { if(!confirmEl) return; confirmEl.hidden = false; const yes = confirmEl.querySelector('[data-dtb-triage="confirm-delete"]'); if(yes) yes.focus(); } // focus Delete — they meant to; Enter confirms
		const titleEl = root.querySelector('[data-dtb-triage-title]');
		let editing = false;
		function isBusy() { return editing || (schedInput && !schedInput.hidden) || (confirmEl && !confirmEl.hidden); }

		// Double-click the title to fix a typo in place (contentEditable) — never advances the card.
		function startTitleEdit() {
			if(!titleEl || idx >= total || isBusy()) return;
			editing = true;
			titleEl.contentEditable = 'true';
			titleEl.classList.add('dtb-triage--editing');
			titleEl.focus();
			const r = document.createRange(); r.selectNodeContents(titleEl);
			const s = window.getSelection(); s.removeAllRanges(); s.addRange(r);
		}
		function commitTitle(save) {
			if(!titleEl) return;
			editing = false; // set first so the blur handler doesn't re-enter
			titleEl.contentEditable = 'false';
			titleEl.classList.remove('dtb-triage--editing');
			const card = cards[idx];
			if(save && card) {
				const val = (titleEl.textContent || '').replace(/[\r\n]+/g, ' ').trim();
				if(val && val !== card.title) { card.title = val; dtbInvoke('updateTaskTitle', { task_id: card.id, title: val }); }
			}
			if(card) titleEl.textContent = card.title; // normalize / revert
		}
		if(titleEl) {
			titleEl.addEventListener('dblclick', startTitleEdit);
			titleEl.addEventListener('keydown', function(e) {
				if(!editing) return;
				e.stopPropagation();
				if(e.key === 'Enter') { e.preventDefault(); commitTitle(true); titleEl.blur(); }
				else if(e.key === 'Escape') { e.preventDefault(); commitTitle(false); titleEl.blur(); }
			});
			titleEl.addEventListener('blur', function() { if(editing) commitTitle(true); });
		}

		function render() {
			const pct = total ? Math.round(idx / total * 100) : 100;
			setText('[data-dtb-triage-pct]', pct + '%');
			const bar = root.querySelector('[data-dtb-triage-bar]'); if(bar) bar.style.width = pct + '%';
			setText('[data-dtb-triage-remaining]', Math.max(0, total - idx));
			let empty = stackEl.querySelector('.dtb-triage--empty');
			if(idx >= total) {
				// Keep the card elements in the DOM (Restart reuses them) — just overlay a done state.
				activeEl.style.display = 'none';
				if(!empty) { empty = document.createElement('div'); empty.className = 'dtb-triage--empty'; empty.textContent = '🎉 All caught up'; stackEl.appendChild(empty); }
				gridEl.style.visibility = 'hidden';
				return;
			}
			if(empty) empty.remove();
			activeEl.style.display = '';
			gridEl.style.visibility = '';
			const card = cards[idx];
			const pip = root.querySelector('[data-dtb-triage-pip]');
			if(pip) pip.style.color = projectColors[card.projectId] || 'var(--cerb-color-tag-gray)';
			setText('[data-dtb-triage-proj]', projName[card.projectId] || '');
			setText('[data-dtb-triage-idx]', 'Task ' + (idx + 1) + ' of ' + total);
			setText('[data-dtb-triage-title]', card.title);
			const imp = root.querySelector('[data-dtb-triage-imp]');
			if(imp) imp.style.width = (card.importance || 0) + '%';
			hideSched();
			hideConfirm();
		}

		function advance(dir) {
			// Fling a ghost clone of the current card in its direction; reveal the next card underneath now.
			const ghost = activeEl.cloneNode(true);
			ghost.removeAttribute('data-dtb-triage-active');
			ghost.setAttribute('aria-hidden', 'true');
			ghost.classList.add('dtb-triage--ghost');
			stackEl.appendChild(ghost);
			void ghost.offsetWidth;
			ghost.classList.add('dtb-triage--dealing-' + (dir || 'up'));
			const kill = function() { if(ghost.parentNode) ghost.remove(); };
			ghost.addEventListener('transitionend', kill, { once: true });
			setTimeout(kill, 400); // fallback if transitionend doesn't fire
			idx++;
			render();
		}

		function act(a, shift) {
			if(!a || idx >= total || isBusy()) return; // ignore while a schedule/confirm prompt is open
			const card = cards[idx];
			const eff = (shift && a.shift) ? a.shift.action : a.action; // Shift can escalate an action
			if(eff === 'skip') { advance('skip'); return; }
			if(eff === 'schedule') { schedInput.hidden = false; schedInput.value = ''; schedInput.focus(); return; }
			if(eff === 'delete') { showDeleteConfirm(); return; } // inline confirm — never delete on the first press
			if(eff === 'priority') dtbInvoke('adjustImportance', { task_id: card.id, delta: shift ? 5 : 1 });
			else if(eff === 'later') dtbInvoke('adjustImportance', { task_id: card.id, delta: shift ? -5 : -1 });
			else if(eff === 'totodo') dtbInvoke('moveTask', { task_id: card.id, to_column: 'todo', to_date: 0, order: [] });
			else if(eff === 'tostash') dtbInvoke('moveTask', { task_id: card.id, to_column: 'stash', to_date: 0, order: [] });
			else if(eff === 'toprogress') dtbInvoke('moveTask', { task_id: card.id, to_column: 'in_progress', to_date: 0, order: [] });
			else if(eff === 'done') dtbInvoke('moveTask', { task_id: card.id, to_column: 'done', to_date: 0, order: [] });
			advance(a.dir);
		}

		if(schedInput) schedInput.addEventListener('keydown', function(e) {
			e.stopPropagation();
			if(e.key === 'Enter') {
				const card = cards[idx];
				if(card) dtbInvoke('stashSetReopen', { task_id: card.id, reopen_at: (schedInput.value || '').trim() });
				hideSched();
				advance('left');
			} else if(e.key === 'Escape') {
				hideSched();
			}
		});

		// Inline delete confirm: a big red Delete + a safe Cancel. Esc cancels (kept off the dialog's own
		// Esc-close via stopPropagation); Cancel is focused by default so Enter is safe.
		if(confirmEl) {
			confirmEl.addEventListener('keydown', function(e) {
				e.stopPropagation();
				if(e.key === 'Escape') hideConfirm();
			});
			confirmEl.addEventListener('click', function(e) {
				if(e.target.closest('[data-dtb-triage="confirm-delete"]')) {
					const card = cards[idx];
					if(card) dtbInvoke('deleteTask', { task_id: card.id });
					hideConfirm();
					advance('delete');
				} else if(e.target.closest('[data-dtb-triage="confirm-cancel"]')) {
					hideConfirm();
				}
			});
		}

		const keyMap = {
			' ': 'skip', 'Spacebar': 'skip',
			'ArrowUp': 'priority', 'ArrowDown': 'later', 'x': 'delete', 'X': 'delete',
			'ArrowLeft':  (mode === 'stash') ? 'schedule' : 'tostash',
			'ArrowRight': (mode === 'stash') ? 'totodo'   : 'toprogress',
		};
		function onKey(e) {
			if(isBusy()) return; // a schedule/confirm prompt owns the keyboard
			if(e.repeat) return;
			const id = keyMap[e.key];
			if(!id) return;
			e.preventDefault();
			act(byAction[id], e.shiftKey);
		}

		gridEl.addEventListener('click', function(e) {
			const b = e.target.closest('[data-dtb-triage-act]');
			if(b) act(byAction[b.getAttribute('data-dtb-triage-act')], e.shiftKey);
		});
		root.querySelector('[data-dtb-triage="close"]').addEventListener('click', function() { const d = CerbUI.Dialog.from(root); if(d) d.close(); });
		root.querySelector('[data-dtb-triage="shuffle"]').addEventListener('click', function() {
			for(let i = cards.length - 1; i > idx; i--) { const j = idx + Math.floor(Math.random() * (i - idx + 1)); const t = cards[i]; cards[i] = cards[j]; cards[j] = t; } // Fisher-Yates on the remaining cards
			render();
			stackEl.classList.remove('dtb-triage--shake');
			void stackEl.offsetWidth;
			stackEl.classList.add('dtb-triage--shake');
			setTimeout(function() { stackEl.classList.remove('dtb-triage--shake'); }, 320);
		});

		setDeltas(false);
		document.addEventListener('keydown', onKey);
		document.addEventListener('keydown', onShift);
		document.addEventListener('keyup', onShift);

		const dlg = new CerbUI.Dialog(root, {
			header: 'none', modal: true, closable: true, closeOnEscape: true, width: 900,
			namespace: 'dtb-triage-' + tabId,
			onClose: function() {
				document.removeEventListener('keydown', onKey);
				document.removeEventListener('keydown', onShift);
				document.removeEventListener('keyup', onShift);
				dtbReloadTab(); // apply every queued change at once
			},
		});
		dlg.open();
		render();
	}

	// ── Inline title edit (double-click) ──
	function startInlineEdit(card, focusField) {
		const textEl = card.querySelector('.dtb-card--text');
		if(!textEl || card.querySelector('.dtb-card--edit')) return; // already editing
		const original = textEl.textContent;

		// Title textarea (swaps in for the text span, inside .dtb-card--content).
		const input = document.createElement('textarea');
		input.className = 'dtb-card--edit';
		input.rows = 2;
		input.value = original;
		textEl.replaceWith(input);
		autoGrow(input);

		// Stash-column cards also edit their reopen-date in the same editor. The pill (until cards only)
		// is hidden while editing; an empty field = stash indefinitely.
		const lane = card.closest('[data-cerb-dtb-column]');
		const isStash = !!lane && lane.getAttribute('data-column') === 'stash';
		const origReopen = card.getAttribute('data-reopen') || '';
		const pill = card.querySelector('.dtb-card--when');
		let dateInput = null;
		if(isStash) {
			dateInput = document.createElement('input');
			dateInput.type = 'text';
			dateInput.className = 'dtb-card--date-edit';
			dateInput.placeholder = 'e.g. Next Friday 1pm — empty = indefinite';
			dateInput.value = origReopen;
			input.after(dateInput); // sits below the title, in .dtb-card--content
			if(pill) pill.style.display = 'none';
		}

		const targetDate = (focusField === 'reopen' && dateInput);
		(targetDate ? dateInput : input).focus();
		if(targetDate) { if(dateInput.value) dateInput.select(); }
		else input.setSelectionRange(input.value.length, input.value.length); // cursor at the end

		let done = false;
		const finish = function(save) {
			if(done) return; done = true;
			const val = stripNewlines(input.value).trim();
			const titleChanged = save && val && val !== original;
			const newReopen = dateInput ? dateInput.value.trim() : origReopen;
			const reopenChanged = save && dateInput && newReopen !== origReopen;

			// Restore the title text span inline either way (no full editor teardown needed).
			const span = document.createElement('span');
			span.className = 'dtb-card--text';
			span.textContent = titleChanged ? val : original;
			input.replaceWith(span);
			if(dateInput) dateInput.remove();

			if(reopenChanged) {
				// Don't reload the board — refresh just this card in place (like a stash drop). Chain the
				// title write first so it's committed before the reopen response patches the card.
				const taskId = card.getAttribute('data-task-id');
				const applyReopen = function(json) {
					const wasUntil = !!origReopen.trim();
					const nowUntil = !!(json && json.reopen_at > 0);
					if(nowUntil) { card.setAttribute('data-reopen', json.reopen_str || ''); setUntilPill(card, json.when); }
					else { card.removeAttribute('data-reopen'); setStashAffordance(card); } // no date → no pill
					if(wasUntil !== nowUntil) tuckIntoStash(card); // crossed Until↔Indefinitely → move to its section top
					refreshCounts();
				};
				const setReopen = function() { dtbInvoke('stashSetReopen', { task_id: taskId, reopen_at: newReopen }, applyReopen); };
				if(titleChanged) dtbInvoke('updateTaskTitle', { task_id: taskId, title: val }, setReopen);
				else setReopen();
				return;
			}

			// No reopen change (title-only or cancel): restore the pill and save the title if it changed.
			if(pill) pill.style.display = '';
			if(titleChanged) dtbInvoke('updateTaskTitle', { task_id: card.getAttribute('data-task-id'), title: val });
		};

		// Blur commits only when focus has left BOTH inputs (so tabbing title↔date doesn't commit).
		const onBlur = function() {
			setTimeout(function() {
				if(done) return;
				const a = document.activeElement;
				if(a === input || a === dateInput) return;
				finish(true);
			}, 0);
		};
		input.addEventListener('input', function() { input.value = stripNewlines(input.value); autoGrow(input); });
		input.addEventListener('blur', onBlur);
		input.addEventListener('keydown', function(ev) {
			if(ev.key === 'Enter') { ev.preventDefault(); finish(true); }
			else if(ev.key === 'Escape') { ev.preventDefault(); finish(false); }
		});
		if(dateInput) {
			dateInput.addEventListener('blur', onBlur);
			dateInput.addEventListener('keydown', function(ev) {
				if(ev.key === 'Enter') { ev.preventDefault(); finish(true); }
				else if(ev.key === 'Escape') { ev.preventDefault(); finish(false); }
			});
		}
	}

	root.addEventListener('dblclick', function(e) {
		const card = e.target.closest('.dtb-card');
		if(!card || card.closest('.dtb-column--multi') || e.target.closest('.dtb-card--menu') || e.target.closest('.dtb-card--when') || e.target.closest('.dtb-card--date-edit')) return;
		startInlineEdit(card, 'title');
	});

	// ── Add-task editor (one shared element, moved into the target lane) ──
	let editor = null, editorSelect = null, editorSM = null, editorLane = null, editorAssign = null;
	// Project control is (re)built per-open: a Switcher of pips when ≤5 projects are in play, else a
	// SelectMenu. editorProjectMode tracks which is live; editorProjectSwitcher holds the Switcher.
	let editorProjectWrap = null, editorProjectMode = 'select', editorProjectSwitcher = null;
	const DTB_PROJECT_SWITCHER_MAX = 5;

	function ensureEditor() {
		if(editor) return;
		editor = document.createElement('div');
		editor.className = 'dtb-card-editor';

		// Project control region — filled per-open by buildProjectControl (Switcher or SelectMenu).
		editorProjectWrap = document.createElement('div');
		editorProjectWrap.className = 'dtb-editor-project-wrap';
		editor.appendChild(editorProjectWrap);

		const input = document.createElement('textarea');
		input.className = 'dtb-editor-title';
		input.rows = 2;
		input.setAttribute('placeholder', 'Enter task text…');
		editor.appendChild(input);

		const actions = document.createElement('div');
		actions.className = 'dtb-editor-actions cerb-u-flex cerb-u-gap-2 cerb-u-items-center';
		const addBtn = document.createElement('button');
		addBtn.type = 'button';
		addBtn.className = 'cerb-ui-button dtb-editor-btn dtb-editor-add cerb-u-fw-500 cerb-u-rounded-3 cerb-u-border-1 cerb-u-shadow-none';
		addBtn.textContent = 'Add Task';
		const cancelBtn = document.createElement('button');
		cancelBtn.type = 'button';
		cancelBtn.className = 'cerb-ui-button dtb-editor-btn dtb-editor-cancel cerb-u-fw-500 cerb-u-rounded-3 cerb-u-border-1 cerb-u-shadow-none';
		cancelBtn.textContent = 'Cancel';
		actions.appendChild(addBtn);
		actions.appendChild(cancelBtn);

		// Assignee switcher (right-aligned via margin-left:auto): Me (my avatar) / Unassigned (dashed user).
		const assignEl = document.createElement('div');
		assignEl.className = 'cerb-ui-switcher dtb-editor-assign';
		const meBtn = document.createElement('button');
		meBtn.type = 'button';
		meBtn.setAttribute('data-value', 'me');
		meBtn.title = 'Assign to me';
		meBtn.appendChild(dtbOwnerImg(dtbWorkerId));
		const unBtn = document.createElement('button');
		unBtn.type = 'button';
		unBtn.setAttribute('data-value', 'unassigned');
		unBtn.title = 'Unassigned';
		unBtn.innerHTML = '<span class="dtb-assign-unassigned"><span class="cerb-icons cerb-icon-user"></span></span>';
		assignEl.appendChild(meBtn);
		assignEl.appendChild(unBtn);
		actions.appendChild(assignEl);

		editor.appendChild(actions);

		if(CerbUI.Switcher)
			editorAssign = new CerbUI.Switcher(assignEl, {}); // per-column persistence handled in open/submit

		addBtn.addEventListener('click', submitEditor);
		cancelBtn.addEventListener('click', closeEditor);
		input.addEventListener('input', function() { input.value = stripNewlines(input.value); autoGrow(input); });
		input.addEventListener('keydown', function(ev) {
			if(ev.key === 'Enter') { ev.preventDefault(); submitEditor(); }
			else if(ev.key === 'Escape') { ev.preventDefault(); closeEditor(); }
		});
	}

	// (Re)build the editor's project control for the current focus. Candidates = the selected projects
	// (what's on the board right now), or all configured if nothing is selected. ≤5 → a Switcher of
	// pips (one-click); more → the SelectMenu. Defaults to the column's last-used project, else the first.
	function buildProjectControl(col) {
		let candidates = currentSelected
			.map(function(id) { return projects.find(function(p) { return String(p.id) === String(id); }); })
			.filter(Boolean);
		if(!candidates.length) candidates = projects.slice();

		editorProjectWrap.textContent = '';
		editorSelect = null; editorSM = null; editorProjectSwitcher = null;
		if(!candidates.length) return;

		const last = localStorage.getItem('dtb.lastProject.' + tabId + '.' + col);
		const def = (last && candidates.some(function(p) { return String(p.id) === String(last); }))
			? String(last) : String(candidates[0].id);

		if(candidates.length <= DTB_PROJECT_SWITCHER_MAX && CerbUI.Switcher) {
			editorProjectMode = 'switcher';
			const sw = document.createElement('div');
			sw.className = 'cerb-ui-switcher dtb-editor-project-switcher';
			candidates.forEach(function(p) {
				const b = document.createElement('button');
				b.type = 'button';
				b.setAttribute('data-value', String(p.id));
				b.title = p.label;
				const pip = document.createElement('span');
				pip.className = 'cerb-ui-pip';
				pip.style.color = p.color || 'var(--cerb-color-tag-gray)';
				pip.style.marginRight = '0.4em';
				b.appendChild(pip);
				b.appendChild(document.createTextNode(p.label));
				sw.appendChild(b);
			});
			editorProjectWrap.appendChild(sw);
			editorProjectSwitcher = new CerbUI.Switcher(sw, { value: def });
		} else {
			editorProjectMode = 'select';
			const sel = document.createElement('select');
			sel.className = 'dtb-editor-project';
			candidates.forEach(function(p) {
				const o = document.createElement('option');
				o.value = p.id;
				o.textContent = p.label;
				o.setAttribute('data-color', p.color);
				sel.appendChild(o);
			});
			editorProjectWrap.appendChild(sel);
			editorSelect = sel;
			if(CerbUI.SelectMenu) {
				editorSM = new CerbUI.SelectMenu(sel, {
					onRender: function(el, option) {
						const pip = document.createElement('span');
						pip.className = 'cerb-ui-pip';
						pip.style.color = option.getAttribute('data-color') || 'var(--cerb-color-tag-gray)';
						pip.style.marginRight = '0.5em';
						el.insertBefore(pip, el.firstChild);
					},
				});
			}
			if(editorSM) editorSM.setValue(def); else sel.value = def;
		}
	}

	// The chosen project id, from whichever control is live.
	function getEditorProject() {
		if(editorProjectMode === 'switcher')
			return editorProjectSwitcher ? editorProjectSwitcher.getValue() : null;
		return editorSM ? editorSM.getValue() : (editorSelect ? editorSelect.value : null);
	}

	function openEditor(lane, position, beforeCard) {
		if(!projects.length) return; // nothing to assign to
		ensureEditor();
		editorLane = lane;

		if(beforeCard) lane.insertBefore(editor, beforeCard);
		else if(position === 'top') lane.insertBefore(editor, lane.firstChild);
		else lane.appendChild(editor);

		// The editor replaces the empty-lane "click to add" hint while it's open.
		const prompt = lane.querySelector('[data-cerb-dtb-prompt]');
		if(prompt) prompt.hidden = true;

		// Build the project control for the current focus (Switcher vs SelectMenu), defaulting to the
		// column's last-used project.
		const col = lane.getAttribute('data-column');
		buildProjectControl(col);

		// Default assignee = last used in this column (per board), else Me.
		if(editorAssign) {
			const a = localStorage.getItem('dtb.assignMode.' + tabId + '.' + col);
			editorAssign.setValue(a === 'unassigned' ? 'unassigned' : 'me');
		}

		const input = editor.querySelector('.dtb-editor-title');
		input.value = '';
		autoGrow(input);
		input.focus();
	}

	function closeEditor() {
		if(editor && editor.parentNode) editor.parentNode.removeChild(editor);
		editorLane = null;
		updateAddPrompts(); // re-show the "click to add" hint if the lane is still empty
	}

	function submitEditor() {
		if(!editorLane) return;
		const lane = editorLane;
		const input = editor.querySelector('.dtb-editor-title');
		const title = stripNewlines(input.value).trim();
		const projectId = getEditorProject();
		if(!title || !projectId) { input.focus(); return; }

		const col = lane.getAttribute('data-column');
		// Lane day-key — used server-side only for a future stash_day add (the reopen-until date).
		const toDate = lane.getAttribute('data-board-date') || 0;
		// Me / Unassigned from the switcher.
		const assign = editorAssign ? editorAssign.getValue() : 'me';
		const ownerId = (assign === 'unassigned') ? 0 : dtbWorkerId;

		// Insert index = cards before the editor; order = the lane's existing card ids (for importance).
		let idx = 0;
		for(let sib = editor.previousElementSibling; sib; sib = sib.previousElementSibling)
			if(sib.classList && sib.classList.contains('dtb-card')) idx++;
		const order = Array.prototype.map.call(lane.querySelectorAll('.dtb-card'), function(c) { return c.getAttribute('data-task-id'); });

		dtbInvoke('addTask', {
			title: title, project_id: projectId, column: col, index: idx, order: order, to_date: toDate, assign: assign
		}, function(json) {
			if(!json || json.status !== 'ok') return;
			// New card lands directly below the editor, regardless of the project sort.
			const card = buildCard(json.id, projectId, json.title, col === 'done', ownerId);
			lane.insertBefore(card, editor.nextSibling);
			const s = CerbUI.Sortable && CerbUI.Sortable.from(lane);
			if(s && s.refresh) s.refresh();
			localStorage.setItem('dtb.lastProject.' + tabId + '.' + col, String(projectId));
			localStorage.setItem('dtb.assignMode.' + tabId + '.' + col, assign);
			input.value = '';
			input.focus();
			refreshCounts();
			refreshJumpPips(); // a future-day quick-add creates a stash → reflect it on the calendar
		});
	}

	// Open the add editor: column (+) → top; empty lane click → top if above the first card, else bottom.
	root.addEventListener('click', function(e) {
		const menuBtn = e.target.closest('[data-cerb-dtb="card-menu"]');
		if(menuBtn) {
			e.stopPropagation();
			menuCard = menuBtn.closest('.dtb-card');
			menuAnchor = menuBtn;
			if(confirmMenu && confirmMenu.isOpen()) confirmMenu.close();
			if(cardMenu) { cardMenu.isOpen() ? cardMenu.close() : cardMenu.open(menuBtn); }
			return;
		}

		// One-click "assign to me" on an unassigned card (write-gated server-side).
		const claimBtn = e.target.closest('[data-cerb-dtb="claim"]');
		if(claimBtn) {
			e.stopPropagation();
			const card = claimBtn.closest('.dtb-card');
			const id = card && card.getAttribute('data-task-id');
			if(!id) return;
			dtbInvoke('assignTask', { task_id: id }, function(json) {
				if(!json || json.status !== 'ok') return;
				card.setAttribute('data-owner-id', json.owner_id);
				dtbWorkerMeta[json.owner_id] = dtbWorkerMeta[json.owner_id] || { name: json.owner_name, avatar: '' };
				// Swap the claim chip for my avatar IN PLACE — don't re-filter/re-sort, so the card stays
				// put while you triage down the unassigned list (you focus afterward to see only yours).
				claimBtn.replaceWith(dtbOwnerImg(json.owner_id));
			});
			return;
		}

		const addBtn = e.target.closest('[data-cerb-dtb="add"]');
		if(addBtn) {
			const lane = addBtn.closest('.dtb-column').querySelector('[data-cerb-dtb-column]');
			if(lane) openEditor(lane, 'top');
			return;
		}

		// Click on the empty lane background → add (but not on a prior-day Done log, which is read-only)
		if(e.target.matches('[data-cerb-dtb-column]') && !e.target.closest('.dtb-column--log')) {
			const lane = e.target;
			const firstCard = lane.querySelector('.dtb-card');
			if(firstCard && e.clientY < firstCard.getBoundingClientRect().top) openEditor(lane, 'top');
			else openEditor(lane, 'bottom');
		}
	});

	// Open the shared board-config dialog (project set + colors + order). Non-modal.
	function openConfigDialog() {
		if(!CerbUI.Dialog) return;
		CerbUI.Dialog.fromAjax(
			'c=pages&a=invokeTab&tab_id=' + tabId + '&action=renderConfig',
			{ title: 'Configure projects', width: 560, closeWarnOnUnsavedChanges: true, namespace: 'dtb-config-' + tabId }
		);
	}

	// Persist the per-worker view (which projects are on + their priority order), debounced. Carries the
	// current focus state too, so a project change doesn't drop it.
	let saveViewTimer = null;
	function saveView(state) {
		clearTimeout(saveViewTimer);
		saveViewTimer = setTimeout(function() {
			dtbInvoke('saveView', { selected: state.selected, order: state.order, focus_mine: focusMine ? 1 : 0 });
		}, 250);
	}

	// Reload the whole tab — used after a stash reopen-date change so the board re-derives/re-segments.
	function dtbReloadTab() {
		const cerbTabs = window.CerbUI?.Tabs?.from(document.getElementById('pageTabs{$workspace_page->id}'));
		if(cerbTabs) { cerbTabs.refresh(); }
		else { window.location.reload(); } // fallback
	}

	// ── Project picker (top toolbar) ──
	let pp = null;
	if(CerbUI.PriorityPicker) {
		const ppEl = document.getElementById('{$dtb_id}-projects');
		let ppItems = [];
		try { ppItems = JSON.parse(document.getElementById('{$dtb_id}-pp-data').textContent || '[]'); } catch(e) {}

		// A gear in the popover header (top-right) opens board config — writeable viewers only.
		let headerActions = null;
		{if $is_writeable}
			headerActions = document.createElement('button');
			headerActions.type = 'button';
			headerActions.className = 'cerb-ui-button cerb-ui-button--subtle';
			headerActions.title = 'Configure projects';
			headerActions.innerHTML = '<span class="cerb-icons cerb-icon-gear"></span>';
			headerActions.addEventListener('click', function() { if(pp) pp.close(); openConfigDialog(); });
		{/if}

		pp = new CerbUI.PriorityPicker(ppEl, {
			items: ppItems,
			icon: 'collection',
			headerLabel: 'Projects',
			emptyText: ppItems.length ? 'None selected' : 'No projects yet',
			headerActions: headerActions,
			onChange: function(state) { applyProjectView(state.selected); saveView(state); },
		});
		applyProjectView(pp.getSelected());
	}

	// The empty-state note's button -- same dialog as the picker's gear.
	const dtbConfigBtn = root.querySelector('[data-cerb-dtb="configure"]');
	if(dtbConfigBtn) dtbConfigBtn.addEventListener('click', openConfigDialog);

	// ── Focus "my tasks": float my owned cards to the top of TODO / In Progress (others stay below). ──
	const focusSwitcherEl = root.querySelector('[data-cerb-dtb="focus-switcher"]');
	if(focusSwitcherEl && CerbUI.Switcher) {
		new CerbUI.Switcher(focusSwitcherEl, {
			value: focusMine ? 'mine' : 'all',
			onSelect: function(value) {
				focusMine = (value === 'mine');
				applyProjectView(currentSelected);
				// Persist independently of the project view (read-modify-write keeps selected/order intact).
				dtbInvoke('saveFocus', { focus_mine: focusMine ? 1 : 0 });
			}
		});
	}

	// ── Stash column: a single element that opens INSIDE a day's board (left of TODO) and MOVES between
	//    days as you toggle the archive pill. Close button (or re-toggling its host day) hides it. ──
	const stashCol = root.querySelector('[data-cerb-dtb-stash]');
	const stashKey = 'dtb.stashOpen.' + tabId;

	function closeStash() {
		if(!stashCol) return;
		const host = stashCol.closest('.dtb-columns');
		if(host) host.classList.remove('dtb-columns--stash');
		stashCol.hidden = true;
		try { localStorage.setItem(stashKey, '0'); } catch(e) {}
	}
	// Moving the same element keeps its one Sortable lane + connectWith intact, so drag in/out still
	// works from any day even though the stash now lives inside one day's column row.
	function openStashOn(dayEl) {
		if(!stashCol || !dayEl) return;
		const cols = dayEl.querySelector('.dtb-columns');
		if(!cols) return;
		root.querySelectorAll('.dtb-columns--stash').forEach(function(c) { c.classList.remove('dtb-columns--stash'); });
		cols.insertBefore(stashCol, cols.firstChild);
		cols.classList.add('dtb-columns--stash');
		stashCol.hidden = false;
		try { localStorage.setItem(stashKey, '1'); } catch(e) {}
	}
	root.addEventListener('click', function(e) {
		if(e.target.closest('[data-cerb-dtb="stash-close"]')) { closeStash(); return; }
		const toggle = e.target.closest('[data-cerb-dtb="stash-toggle"]');
		if(!toggle) return;
		const dayEl = toggle.closest('[data-cerb-dtb-day]');
		const cols = dayEl && dayEl.querySelector('.dtb-columns');
		// Re-toggling the day that already hosts the stash closes it; otherwise (re)open on this day.
		if(!stashCol.hidden && cols && stashCol.closest('.dtb-columns') === cols) closeStash();
		else openStashOn(dayEl);
	});
	// Restore: reopen on today's board if it was open last session.
	try {
		if(localStorage.getItem(stashKey) === '1')
			openStashOn(root.querySelector('[data-cerb-dtb-day][data-is-today="1"]') || root.querySelector('[data-cerb-dtb-day]'));
	} catch(e) {}

	const stashSearch = root.querySelector('[data-cerb-dtb-stash-search]');
	if(stashSearch) stashSearch.addEventListener('input', function() { stashQuery = stashSearch.value || ''; applyStashFilters(); });

	// ── Stash scheduling is per-card (no popup). Each stash card carries a clickable pill; a freeform
	//    date editor opens inline on the card. Dropping just stashes indefinitely. ──

	// Strip any schedule pill a card carries. Indefinite stash cards show no pill (their reopen-date is
	// read/set only via the double-click editor); only server-rendered "until" cards have a pill, which a
	// reload re-creates. So entering the stash as indefinite, or leaving it, just removes the pill.
	function setStashAffordance(card) {
		const existing = card.querySelector('.dtb-card--when');
		if(existing) existing.remove();
	}

	// Give an "until" stash card its clock + date pill (create or update), matching the server markup. The
	// `when` label comes from the server (relative/absolute date string).
	function setUntilPill(card, whenText) {
		let pill = card.querySelector('.dtb-card--when');
		if(!pill) {
			pill = document.createElement('button');
			pill.type = 'button';
			pill.className = 'dtb-card--when';
			pill.title = 'Change reopen date';
			pill.setAttribute('data-cerb-dtb', 'stash-schedule');
			const meta = card.querySelector('.dtb-card--meta');
			if(meta) meta.appendChild(pill);
			else card.insertBefore(pill, card.querySelector('.dtb-card--menu') || null);
		}
		pill.style.display = '';
		pill.innerHTML = '<span class="cerb-icons cerb-icon-clock"></span> ';
		pill.appendChild(document.createTextNode(whenText || ''));
	}

	// Tuck a just-dropped card at the top of the right stash section — "until" if it carries a wake date
	// (data-reopen), else "indefinite". Server re-segments/orders on reload.
	function tuckIntoStash(card) {
		if(!stashLane) return;
		const hasDate = !!(card.getAttribute('data-reopen') || '').trim();
		const label = stashLane.querySelector('[data-cerb-dtb-stash-section="' + (hasDate ? 'until' : 'indefinite') + '"]');
		if(label) stashLane.insertBefore(card, label.nextSibling);
		else stashLane.appendChild(card);
	}

	// Click a card's schedule pill (until cards only) → open the combined editor focused on the reopen date.
	root.addEventListener('click', function(e) {
		const pill = e.target.closest('[data-cerb-dtb="stash-schedule"]');
		if(!pill) return;
		e.stopPropagation();
		const card = pill.closest('.dtb-card');
		if(card) startInlineEdit(card, 'reopen');
	});

	// Load-time pass: collapse days that are all-Done / empty (once, never mid-session).
	updateDayStates(true);
	updateAddPrompts();

	// Drop handler shared by every day-lane Sortable (the source lane handles a drop into any target).
	function onCardSorted(info) {
		const card = info.item;
		const dest = info.to;
		const destCol = dest.getAttribute('data-column');
		const fromCol = info.from && info.from.getAttribute('data-column');
		setCardDoneState(card, destCol === 'done');

		if(destCol === 'stash') {
			// The stash isn't sortable — a drop lands at the top of its section (an incoming day-card
			// becomes indefinite; an existing stash card keeps its until/indefinite).
			if(fromCol !== 'stash') setStashAffordance(card);
			tuckIntoStash(card);
			// A within-stash drag just snaps to its section — nothing to persist.
			if(fromCol === 'stash') { refreshCounts(); return; }
		} else if(destCol === 'stash_day') {
			// A future-day stash column: drop-only (append). The server sets reopen_at to that day; the
			// day itself conveys the date, so the card carries no schedule pill here.
			if(fromCol === 'stash') setStashAffordance(card);
			dest.appendChild(card);
		} else {
			if(fromCol === 'stash') setStashAffordance(card); // dragged OUT of the stash → drop the pill
			// DONE is a drop-zone like the stash — always append to the BOTTOM (no precise insertion).
			// TODO / In Progress keep the precise spot they were dropped at (importance, per-project).
			if(destCol === 'done') dest.appendChild(card);
		}

		// Ephemeral reorders write nothing: In-Progress order is by convention transient, and while
		// focused on my tasks ANY reorder is a personal view (toggle off to PM-sort the shared TODO).
		// These are pure within-lane moves with no status change.
		if(fromCol === destCol && (destCol === 'in_progress' || (destCol === 'todo' && focusMine))) {
			refreshCounts();
			return;
		}

		// Only TODO (focus off) persists a manual order (importance); everything else sends an empty
		// order so the server keeps the shared importance and just applies the status change.
		const order = (destCol === 'todo' && !focusMine)
			? Array.prototype.map.call(dest.querySelectorAll('.dtb-card'), function(c) { return c.getAttribute('data-task-id'); })
			: [];
		dtbInvoke('moveTask', {
			task_id:   card.getAttribute('data-task-id'),
			to_date:   dest.getAttribute('data-board-date'),
			to_column: dest.getAttribute('data-column'),
			order:     order
		});
		refreshCounts();
		refreshJumpPips(); // a move can change done/stash days → keep the calendar pips honest
	}

	// ── Drag: every lane (day columns + stash) is one connected Sortable group. Day columns sort with
	//    precision; the stash's cards drag OUT into a day column, but a drop back INTO the stash just
	//    snaps to its section (no manual reorder) and dropping in lands at the top of the right section. ──
	if(CerbUI.Sortable) {
		lanes.forEach(function(lane) {
			new CerbUI.Sortable(lane, {
				items: '> .dtb-card',
				connectWith: lanes.filter(function(other) { return other !== lane; }),
				ghostOrigin: true, // origin slot = a dimmed clone of the card, not a dashed box
				onSorted: onCardSorted,
			});
		});
	}

	// Add a dynamically-inserted lane (a jumped-to day) to the connected drag group. connectWith is
	// resolved lazily at drag-start, so pushing into existing instances' arrays is enough.
	function addLaneToSortGroup(lane) {
		if(!CerbUI.Sortable || lanes.indexOf(lane) !== -1) return;
		lanes.forEach(function(other) {
			const inst = CerbUI.Sortable.from(other);
			if(inst && inst.opts.connectWith.indexOf(lane) === -1) inst.opts.connectWith.push(lane);
		});
		new CerbUI.Sortable(lane, {
			items: '> .dtb-card',
			connectWith: lanes.slice(), // all current lanes (this one isn't registered yet)
			ghostOrigin: true,
			onSorted: onCardSorted,
		});
		lanes.push(lane);
	}

	// ── "Jump to Date": a DatePicker (element-trigger) with per-month activity pips. Selecting a day
	//    loads that day's board into the timeline (insert in date order, expand, scroll). Today scrolls
	//    to the existing row; a past day loads a Done log; a future day loads a stash-until column. ──
	function dtbPad2(n) { return (n < 10 ? '0' : '') + n; }
	function dtbYmd(date) { return date.getFullYear() + '-' + dtbPad2(date.getMonth() + 1) + '-' + dtbPad2(date.getDate()); }

	function jumpToDay(date) {
		const ymd = dtbYmd(date);

		// Already on the board (incl. today) → reveal + scroll, no fetch.
		const existing = root.querySelector('.dtb-day[data-day-ymd="' + ymd + '"]');
		if(existing) {
			existing.classList.remove('dtb-day--collapsed');
			existing.dataset.manual = '1';
			existing.scrollIntoView({ behavior: 'smooth', block: 'start' });
			return;
		}

		// Past → a Done log; future → a stash-until column. (Today is always the `existing` case above.)
		dtbInvoke('renderDay', { year: date.getFullYear(), month: date.getMonth() + 1, day: date.getDate() }, function(json) {
			if(!json || json.status !== 'ok' || !json.html) return;
			const tmp = document.createElement('div');
			tmp.innerHTML = json.html.trim();
			const dayEl = tmp.firstElementChild;
			if(!dayEl) return;

			// Timeline is newest-first: insert before the first existing day older than this one.
			const newYmd = json.date_ymd || ymd;
			const days = Array.prototype.slice.call(root.querySelectorAll('.dtb-day'));
			let before = null;
			for(let i = 0; i < days.length; i++) {
				if(days[i].getAttribute('data-day-ymd') < newYmd) { before = days[i]; break; }
			}
			if(before) before.parentNode.insertBefore(dayEl, before);
			else root.appendChild(dayEl);

			// Wire the new lane(s) into the drag group, then filter/sort/color to the current view.
			dayEl.querySelectorAll('[data-cerb-dtb-column]').forEach(addLaneToSortGroup);
			applyProjectView(currentSelected);

			dayEl.classList.remove('dtb-day--collapsed');
			dayEl.dataset.manual = '1';
			dayEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
		});
	}

	const jumpBtn = root.querySelector('[data-cerb-dtb="jump-date"]');
	if(jumpBtn && CerbUI.DatePicker) {
		dtbPicker = new CerbUI.DatePicker(jumpBtn, {
			trigger: 'element',
			loadIndicators: function(y, m) {
				return new Promise(function(resolve) {
					dtbInvoke('monthData', { year: y, month: m + 1 }, function(j) {
						resolve(j && j.status === 'ok' ? { done: j.done, stash: j.stash } : { done: [], stash: [] });
					});
				});
			},
			onSelect: function(date) { jumpToDay(date); },
		});
	}
})();
</script>
