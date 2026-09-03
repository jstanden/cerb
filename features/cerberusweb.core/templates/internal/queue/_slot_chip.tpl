{* The "waiting on a concurrency slot" chip, shared by every host that can be throttled by the pool.

   Background work and LLM agent turns share one set of `queue_slot_N` advisory locks sized by the
   license. When it's saturated, work is QUEUED rather than failed -- invisible unless something says
   so, and it reads as a hung widget. This is that something, in one place so the hosts can't drift.

   INCLUDE THIS INSIDE A <script> BLOCK. It defines one function in the including scope:

     renderSlotChip(el, used, total, waiting, throttled)  ->  true when the chip is showing

   `throttled` is for the case where we KNOW we are being made to wait but cannot count the pool: the
   background FPM pool refusing a drain (nginx 529) is a different resource from `queue_slot_N`, and the
   server cannot see it -- only the client learns of it, from the refusal. Pass it and the chip shows
   with whatever numbers exist, none included.

   Positional args, not an options object, because `{key:` in a .tpl parses as a Smarty tag.

   The split: this owns the chip's CONTENT (classes, hue, head, cells, title, and the saturation rule
   that decides whether there's anything worth saying). The HOST owns placement and visibility -- one
   detaches its element, another toggles a class -- and decides from the return value. Pass a zeroed
   pool to clear it. `total` of 0 means occupancy couldn't be read at all: say nothing rather than
   assert an idle pool. The `waiting` cell is omitted when zero. *}
function renderSlotChip(el, used, total, waiting, throttled) {
    if(!el)
        return false;

    used = parseInt(used, 10) || 0;
    total = parseInt(total, 10) || 0;
    waiting = parseInt(waiting, 10) || 0;

    el.classList.add('cerb-ui-chip', 'cerb-ui-chip--orange');
    el.textContent = '';

    // A partly-busy pool doesn't explain a wait; only saturation does -- unless the caller has been
    // refused outright, which explains it regardless of what the slot numbers say.
    if(!throttled && (!total || used < total)) {
        el.removeAttribute('title');
        return false;
    }

    el.setAttribute('title', (total && used >= total)
        ? ('Background work and AI agent turns share ' + total + ' concurrency slots. '
            + 'This resumes as soon as one frees.')
        : 'Every background worker is busy. This resumes as soon as one frees.');

    // Built, never markup: every value goes in via textContent.
    const cell = function(label, value) {
        const wrap = document.createElement('div');

        const l = document.createElement('div');
        l.className = 'cerb-ui-chip--label';
        l.textContent = label;

        const v = document.createElement('div');
        v.className = 'cerb-ui-chip--value';
        v.textContent = value;

        wrap.appendChild(l);
        wrap.appendChild(v);

        return wrap;
    };

    // The head SAYS the state. "Slots 1/1" alone left the reader to infer what was happening, which is
    // the one thing someone stuck behind a queue shouldn't have to do. It PULSES because the polls
    // behind this back off to seconds apart, so the chip is also the liveness cue.
    const head = document.createElement('div');
    head.className = 'cerb-ui-chip--head';
    head.textContent = 'Waiting in line';
    el.appendChild(head);

    // Omitted when the pool couldn't be counted: a refusal from the FPM layer says WE are waiting, not
    // how many slots exist, and inventing a denominator would be worse than saying nothing.
    if(total > 0)
        el.appendChild(cell('Slots', used + '/' + total));

    if(waiting > 0)
        el.appendChild(cell('Waiting', waiting));

    return true;
}
