	<div class="cerb-uiref-component" id="avatar">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-user"></span>Avatar</div>

		{* Example: monograms — initials + a hash-locked color (stable per seed) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Monogram &mdash; initials over a <strong>hash-locked color</strong> (the same <code>seed</code> always paints the same hue, so a list stays stable across reloads). The engine behind the RecordChooser avatars and the Setup &rarr; Records nav rail</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div id="uiref-avatar-monograms" class="cerb-u-flex cerb-u-flex-wrap cerb-u-items-center cerb-u-gap-2"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// ── Build a fresh element (every option; defaults shown) ──
const el = CerbUI.Avatar.create({
	label:     '',          // text → initials ('Tickets' → 'TI'; 'Jane Doe' → 'JD')
	seed:      '',          // hashes to the color; stable per identity (defaults to label if omitted)
	imageUrl:  '',          // optional photo — the monogram shows first, the image swaps in on load
	icon:      '',          // a cerb-icons name → paints that glyph instead of initials (e.g. 'bot', 'calendar')
	color:     '',          // any CSS color → forces the background instead of the seed-derived hue (e.g. a category's configured color)
	size:      0,           // px; scales the circle + font together (0 = CSS default, 22px)
	className: '',          // extra class(es) added alongside cerb-ui-avatar
	tag:       'span',      // the element tag to create
	enqueue:   null,        // (url, onload) — route the image load through a bounded queue (chooserCore's) for long lists
});                         // → a ready &lt;span class="cerb-ui-avatar"&gt;; append it where you need it

// ── Enhance markup you already rendered (reads data-avatar*; opts override) ──
new CerbUI.Avatar(el);                      // one element
CerbUI.Avatar.enhance(scope, selector);     // every match within scope (default selector '[data-avatar]')
CerbUI.Avatar.from(el);                      // → the instance for an enhanced element

// ── Static helpers (no element needed) ──
CerbUI.Avatar.initials('Jane Doe');         // 'JD'
CerbUI.Avatar.color('worker:5');            // a stable css color for that seed
CerbUI.Avatar.hash('worker:5');             // the 32-bit seed hash

// ── data-* attributes read by the enhancer ──
// data-avatar="Jane Doe"  data-avatar-seed="worker:5"  data-avatar-image="/avatar/worker/5"  data-avatar-size="32"  data-avatar-icon="bot"  data-avatar-color="#c0392b"{/literal}</pre>
			</div>
		</div>

		{* Example: sizes — the `size` option scales the circle + font together *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Sizes &mdash; <code>size</code> (px) scales the circle and its initials together; default is 22px</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div id="uiref-avatar-sizes" class="cerb-u-flex cerb-u-items-center cerb-u-gap-3"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}CerbUI.Avatar.create({ label: 'Acme', seed: 'org:42', size: 48 });{/literal}</pre>
			</div>
		</div>

		{* Example: tile — the --tile modifier swaps the circle for a rounded square *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Tile &mdash; add <code>cerb-ui-avatar--tile</code> for a rounded square instead of a circle. Reads better at larger sizes (profile / card headers). The radius is a percentage so it scales with <code>size</code>; override per-instance with <code>--cerb-ui-avatar-radius</code></div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div id="uiref-avatar-tile" class="cerb-u-flex cerb-u-flex-wrap cerb-u-items-center cerb-u-gap-3"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}CerbUI.Avatar.create({ label: 'Acme', seed: 'org:42', size: 48, className: 'cerb-ui-avatar--tile' });

// …or on server-rendered markup:
// &lt;span class="cerb-ui-avatar cerb-ui-avatar--tile" data-avatar="Acme" data-avatar-seed="org:42" data-avatar-size="48"&gt;&lt;/span&gt;{/literal}</pre>
			</div>
		</div>

		{* Example: icon — a cerb-icons glyph in place of initials, inside the same hash-locked circle *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Icon &mdash; pass <code>icon</code> (a <a href="#icon">cerb-icons</a> name) and the avatar paints that glyph instead of initials, still over the <strong>hash-locked color</strong> from <code>seed</code>. Use it for record-type / category avatars that read better as a symbol than a monogram. The glyph inherits the avatar's foreground color</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div id="uiref-avatar-icons" class="cerb-u-flex cerb-u-flex-wrap cerb-u-items-center cerb-u-gap-2"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}CerbUI.Avatar.create({ icon: 'bot', seed: 'cerb.contexts.bot', size: 32 });

// …or on server-rendered markup via the enhancer:
// &lt;span class="cerb-ui-avatar" data-avatar-icon="bot" data-avatar-seed="cerb.contexts.bot"&gt;&lt;/span&gt;{/literal}</pre>
			</div>
		</div>

		{* Example: static color — `color` forces the background, overriding the seed-derived hue *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Static color &mdash; pass <code>color</code> (any CSS color) to <strong>force the background</strong> instead of the hash-locked hue from <code>seed</code>. Use it when a record carries its own configured color (a category, a status, a calendar). Pairs with <code>icon</code> or initials; the foreground stays readable</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div id="uiref-avatar-colors" class="cerb-u-flex cerb-u-flex-wrap cerb-u-items-center cerb-u-gap-2"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}CerbUI.Avatar.create({ icon: 'calendar', color: '#c0392b', size: 32 });

// …or on server-rendered markup via the enhancer:
// &lt;span class="cerb-ui-avatar" data-avatar-icon="calendar" data-avatar-color="#c0392b"&gt;&lt;/span&gt;{/literal}</pre>
			</div>
		</div>

		{* Example: text color — a literal glyph color, or 'auto' for whichever of black/white reads better *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Text color &mdash; <code>textColor</code> sets the glyph/monogram color. Pass <code>auto</code> and it picks whichever of near-black or white has more contrast on the background actually painted &mdash; so a pale or vivid <code>color</code> stays legible without hand-picking a foreground. A background it can't measure (a CSS variable on a detached element) defers to the stylesheet's white</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div id="uiref-avatar-textcolors" class="cerb-u-flex cerb-u-flex-wrap cerb-u-items-center cerb-u-gap-2"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}CerbUI.Avatar.create({ icon: 'todo', color: '#ffd400', textColor: 'auto', size: 32 });

// &lt;span class="cerb-ui-avatar" data-avatar-icon="todo" data-avatar-color="#ffd400" data-avatar-text-color="auto"&gt;&lt;/span&gt;{/literal}</pre>
			</div>
		</div>

		{* Example: graybox → photo — instant monogram placeholder, real image swaps in on load *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Graybox &rarr; photo &mdash; pass <code>imageUrl</code> and a skeleton paints <strong>instantly</strong>, then the real picture swaps in once it loads. This is how a long list of profile images paints without flashing empty</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div id="uiref-avatar-photos" class="cerb-u-flex cerb-u-flex-wrap cerb-u-items-center cerb-u-gap-2"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}CerbUI.Avatar.create({
	label:    name,                       // the placeholder initials
	seed:     'worker:' + id,             // stable placeholder color
	imageUrl: '/avatar/worker/' + id,     // swaps in on load; falls back to the monogram if it never loads
	size:     32,
	// enqueue: an alternate loader (chooserCore passes one that also cancels rows scrolled out of view)
});{/literal}</pre>
			</div>
		</div>

		{* Example: loading skeleton — the pulsing block an avatar shows while its image is in flight *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Loading skeleton &mdash; an avatar with an <code>imageUrl</code> paints a pulsing block until the picture lands, so <em>still loading</em> is never mistaken for the record's real art. Loads run <strong>6 at a time</strong> with a 15s timeout and up to 3 attempts, so a view with dozens of avatars can't burst the server; if the retries run out the skeleton clears and the monogram or icon underneath is what remains. The pulse is <code>cerb-u-anim-pulse</code>, so it stops under <code>prefers-reduced-motion</code></div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-u-flex cerb-u-flex-wrap cerb-u-items-center cerb-u-gap-3">
					{* Held open (no imageUrl) so the state stays visible -- the live row below is over in a blink *}
					<span class="cerb-ui-avatar cerb-ui-avatar--loading cerb-u-anim-pulse" style="width:32px;height:32px;" title="Circle"></span>
					<span class="cerb-ui-avatar cerb-ui-avatar--tile cerb-ui-avatar--loading cerb-u-anim-pulse" style="width:32px;height:32px;" title="Tile"></span>
					<span class="cerb-ui-avatar cerb-ui-avatar--art cerb-ui-avatar--loading cerb-u-anim-pulse" style="width:96px;height:54px;" title="Art (16:9)"></span>
					<span class="cerb-u-text-muted">held open</span>
				</div>

				<div class="cerb-u-flex cerb-u-flex-wrap cerb-u-items-center cerb-u-gap-2 cerb-u-mt-3">
					<div id="uiref-avatar-skeleton-live" class="cerb-u-flex cerb-u-flex-wrap cerb-u-items-center cerb-u-gap-2"></div>
					<button type="button" id="uiref-avatar-skeleton-reload" class="cerb-ui-button cerb-ui-button--subtle"><span class="cerb-icons cerb-icon-refresh"></span> Replay</button>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}// Nothing to opt into — any avatar with an imageUrl gets the skeleton and the batched loader
CerbUI.Avatar.enhance('#package-list');

// Tuning (shared by every avatar on the page, chooser rows included)
CerbUI.Avatar.MAX_INFLIGHT = 6;      // requests in flight at once
CerbUI.Avatar.TIMEOUT_MS   = 15000;  // a hung request can't hold a slot longer than this
CerbUI.Avatar.MAX_ATTEMPTS = 3;      // a failure goes to the back of the line, then gives up{/literal}</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- The skeleton is two classes, if you ever need to hold one open yourself --&gt;
&lt;span class="cerb-ui-avatar cerb-ui-avatar--loading cerb-u-anim-pulse" style="width:32px;height:32px;"&gt;&lt;/span&gt;</pre>
			</div>
		</div>

		{* Example: enhance in place — server-rendered elements carry data-avatar*, JS paints them *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Enhance in place &mdash; render the elements with <code>data-avatar*</code> on the server, then <code>new CerbUI.Avatar(el)</code> (or <code>CerbUI.Avatar.enhance(scope)</code> for a whole list) paints them. No need to build each one in JS</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div id="uiref-avatar-enhance" class="cerb-u-flex cerb-u-flex-wrap cerb-u-items-center cerb-u-gap-2">
					<span class="cerb-ui-avatar" data-avatar="Tickets" data-avatar-seed="cerb.contexts.ticket"></span>
					<span class="cerb-ui-avatar" data-avatar="Messages" data-avatar-seed="cerb.contexts.message"></span>
					<span class="cerb-ui-avatar" data-avatar="Workers" data-avatar-seed="cerb.contexts.worker"></span>
					<span class="cerb-ui-avatar" data-avatar="Organizations" data-avatar-seed="cerb.contexts.org"></span>
					<span class="cerb-ui-avatar" data-avatar="Tasks" data-avatar-seed="cerb.contexts.task"></span>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;span class="cerb-ui-avatar" data-avatar="Tickets" data-avatar-seed="cerb.contexts.ticket"&gt;&lt;/span&gt;
&lt;span class="cerb-ui-avatar" data-avatar="Jane Doe" data-avatar-seed="worker:5" data-avatar-image="/avatar/worker/5" data-avatar-size="32"&gt;&lt;/span&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}new CerbUI.Avatar(el);                  // one element (data-avatar* → label/seed/image/size)
CerbUI.Avatar.enhance('#people-list');  // every [data-avatar] within the scope{/literal}</pre>
			</div>
		</div>

		{* Example: avatar stack — overlapping avatars + a +N overflow bubble *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Avatar stack (<code>CerbUI.AvatarStack</code>) &mdash; overlapping avatars with a trailing <strong>+N</strong> for the overflow. Enhance a container of <code>data-avatar</code> children, or pass <code>{literal}{ items, max, size }{/literal}</code>. <code>max</code> caps the total footprint (avatars + the +N)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-3">
					<div id="uiref-avatar-stack" class="cerb-ui-avatar-stack" data-max="5">
						<span data-avatar="Jane Doe" data-avatar-seed="worker:1"></span>
						<span data-avatar="Ravi Patel" data-avatar-seed="worker:2"></span>
						<span data-avatar="Mia Wong" data-avatar-seed="worker:3"></span>
						<span data-avatar="Sam Lee" data-avatar-seed="worker:4"></span>
						<span data-avatar="Ana Cruz" data-avatar-seed="worker:5"></span>
						<span data-avatar="Tom Reed" data-avatar-seed="worker:6"></span>
						<span data-avatar="Lia Park" data-avatar-seed="worker:7"></span>
						<span data-avatar="Omar Diaz" data-avatar-seed="worker:8"></span>
					</div>
					<span style="color:var(--cerb-color-background-contrast-150);">8 assignees, <code>data-max="5"</code></span>
				</div>

				<div id="uiref-avatar-stack-photos" class="cerb-u-mt-3"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;div class="cerb-ui-avatar-stack" data-max="5"&gt;
	&lt;span data-avatar="Jane Doe" data-avatar-seed="worker:1"&gt;&lt;/span&gt;
	&lt;span data-avatar="Ravi Patel" data-avatar-seed="worker:2"&gt;&lt;/span&gt;
	&lt;!-- … --&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>{literal}new CerbUI.AvatarStack(el);   // enhance the data-avatar children (reads data-max / data-size)

// …or data-driven (photos lazy-swap over their monogram placeholders):
new CerbUI.AvatarStack(el2, {
	max:  4,
	size: 32,
	items: [
		{ label: 'Jane Doe', seed: 'worker:1', imageUrl: '/avatar/worker/1' },
		{ label: 'Ravi Patel', seed: 'worker:2', imageUrl: '/avatar/worker/2' },
		// …
	],
});{/literal}</pre>
			</div>
		</div>

		{* Example: badged avatar — a circular status pill pinned to the avatar's corner *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Badged &mdash; wrap an avatar in <code>cerb-avatar-badged</code> and drop a <a href="#pill">cerb-ui-pill--circle</a> alongside it to pin a status to the bottom-right corner. The badge sits outside the avatar's clip and gets a ring in the page background. Used for the conversation timeline (sent / received / draft / comment)</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div id="uiref-avatar-badged" class="cerb-u-flex cerb-u-flex-wrap cerb-u-items-center cerb-u-gap-3">
					<span class="cerb-avatar-badged">
						<span class="cerb-ui-avatar" data-avatar="Jane Doe" data-avatar-seed="worker:1" data-avatar-size="48"></span>
						<span class="cerb-ui-pill cerb-ui-pill--circle cerb-ui-pill--red" title="Received"><span class="cerb-icons cerb-icon-download"></span></span>
					</span>
					<span class="cerb-avatar-badged">
						<span class="cerb-ui-avatar" data-avatar="Ravi Patel" data-avatar-seed="worker:2" data-avatar-size="48"></span>
						<span class="cerb-ui-pill cerb-ui-pill--circle cerb-ui-pill--green" title="Sent"><span class="cerb-icons cerb-icon-upload"></span></span>
					</span>
					<span class="cerb-avatar-badged">
						<span class="cerb-ui-avatar" data-avatar="Mia Wong" data-avatar-seed="worker:3" data-avatar-size="48"></span>
						<span class="cerb-ui-pill cerb-ui-pill--circle cerb-ui-pill--gray" title="Draft"><span class="cerb-icons cerb-icon-edit"></span></span>
					</span>
					<span class="cerb-avatar-badged">
						<span class="cerb-ui-avatar" data-avatar="Sam Lee" data-avatar-seed="worker:4" data-avatar-size="48"></span>
						<span class="cerb-ui-pill cerb-ui-pill--circle cerb-ui-pill--blue" title="Comment"><span class="cerb-icons cerb-icon-comments"></span></span>
					</span>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;span class="cerb-avatar-badged"&gt;
	&lt;span class="cerb-ui-avatar" data-avatar="Jane Doe" data-avatar-seed="worker:1" data-avatar-size="48"&gt;&lt;/span&gt;
	&lt;span class="cerb-ui-pill cerb-ui-pill--circle cerb-ui-pill--green" title="Sent"&gt;&lt;span class="cerb-icons cerb-icon-upload"&gt;&lt;/span&gt;&lt;/span&gt;
&lt;/span&gt;</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	if(!(window.CerbUI && CerbUI.Avatar)) return;

	// Monograms: a mix of single-word record types (2-letter initials) and people (first-of-each-word)
	(function() {
		const host = document.getElementById('uiref-avatar-monograms');
		if(!host) return;
		const seeds = [
			{ label: 'Tickets', seed: 'cerb.contexts.ticket' },
			{ label: 'Messages', seed: 'cerb.contexts.message' },
			{ label: 'Workers', seed: 'cerb.contexts.worker' },
			{ label: 'Organizations', seed: 'cerb.contexts.org' },
			{ label: 'Calendar', seed: 'cerb.contexts.calendar' },
			{ label: 'Jane Doe', seed: 'worker:1' },
			{ label: 'Ravi Patel', seed: 'worker:2' },
			{ label: 'Mia Wong', seed: 'worker:3' }
		];
		seeds.forEach(function(s) {
			const el = CerbUI.Avatar.create(s);
			el.setAttribute('title', s.label);
			host.appendChild(el);
		});
	})();

	// Sizes: the same identity at a few scales
	(function() {
		const host = document.getElementById('uiref-avatar-sizes');
		if(!host) return;
		[22, 32, 48, 64].forEach(function(size) {
			host.appendChild(CerbUI.Avatar.create({ label: 'Acme', seed: 'org:42', size: size }));
		});
	})();

	// Tile: the --tile modifier (rounded square), demoed across a monogram, an icon, and a few sizes
	(function() {
		const host = document.getElementById('uiref-avatar-tile');
		if(!host) return;
		[
			{ label: 'Acme', seed: 'org:42', size: 32 },
			{ label: 'Acme', seed: 'org:42', size: 48 },
			{ label: 'Acme', seed: 'org:42', size: 64 },
			{ icon: 'building-office', seed: 'cerb.contexts.org', size: 64 }
		].forEach(function(s) {
			s.className = 'cerb-ui-avatar--tile';
			host.appendChild(CerbUI.Avatar.create(s));
		});
	})();

	// Icon: a cerb-icons glyph in place of initials, still over the seed's hash-locked color
	(function() {
		const host = document.getElementById('uiref-avatar-icons');
		if(!host) return;
		const icons = [
			{ icon: 'bot', seed: 'cerb.contexts.bot', title: 'Bots' },
			{ icon: 'calendar', seed: 'cerb.contexts.calendar', title: 'Calendar' },
			{ icon: 'building-office', seed: 'cerb.contexts.org', title: 'Organizations' },
			{ icon: 'comments', seed: 'cerb.contexts.comment', title: 'Comments' },
			{ icon: 'database', seed: 'cerb.contexts.datastore', title: 'Datastore' },
			{ icon: 'gear', seed: 'cerb.contexts.setup', title: 'Setup' }
		];
		icons.forEach(function(i) {
			const el = CerbUI.Avatar.create({ icon: i.icon, seed: i.seed, size: 32 });
			el.setAttribute('title', i.title);
			host.appendChild(el);
		});
	})();

	// Static color: `color` forces the background, paired with an icon or initials
	(function() {
		const host = document.getElementById('uiref-avatar-colors');
		if(!host) return;
		const swatches = [
			{ icon: 'calendar', color: '#c0392b', title: 'Holidays' },
			{ icon: 'calendar', color: '#2980b9', title: 'On-call' },
			{ icon: 'tag', color: '#27ae60', title: 'Billing' },
			{ icon: 'tag', color: '#8e44ad', title: 'Escalations' },
			{ label: 'Acme', color: '#d35400', title: 'Acme (static)' }
		];
		swatches.forEach(function(s) {
			const el = CerbUI.Avatar.create({ icon: s.icon, label: s.label, color: s.color, size: 32 });
			el.setAttribute('title', s.title);
			host.appendChild(el);
		});
	})();

	// Text color: a pale/vivid background with `textColor: 'auto'` next to the same one left default
	(function() {
		const host = document.getElementById('uiref-avatar-textcolors');
		if(!host) return;
		const swatches = [
			{ icon: 'todo', color: '#ffd400', title: "auto -- near-black on yellow" },
			{ icon: 'todo', color: '#ffd400', textColor: '', title: 'default -- white on yellow' },
			{ icon: 'kanban', color: '#e8eaed', title: 'auto -- near-black on pale gray' },
			{ icon: 'kanban', color: '#1f2937', title: 'auto -- white on charcoal' },
			{ icon: 'clock', color: '#0088e6', textColor: '#ffd400', title: 'a literal textColor' }
		];
		swatches.forEach(function(s) {
			const el = CerbUI.Avatar.create({
				icon: s.icon,
				color: s.color,
				textColor: ('textColor' in s) ? s.textColor : 'auto',
				size: 32
			});
			el.setAttribute('title', s.title);
			host.appendChild(el);
		});
	})();

	// Graybox → photo: monogram placeholder, then a bundled person image swaps in
	(function() {
		const host = document.getElementById('uiref-avatar-photos');
		if(!host) return;
		const people = [
			{ label: 'Jane Doe', seed: 'worker:1', imageUrl: '{devblocks_url}c=resource&p=cerberusweb.core&f=images/avatars/person1.png{/devblocks_url}' },
			{ label: 'Ravi Patel', seed: 'worker:2', imageUrl: '{devblocks_url}c=resource&p=cerberusweb.core&f=images/avatars/person2.png{/devblocks_url}' },
			{ label: 'Mia Wong', seed: 'worker:3', imageUrl: '{devblocks_url}c=resource&p=cerberusweb.core&f=images/avatars/person3.png{/devblocks_url}' },
			{ label: 'Sam Lee', seed: 'worker:4', imageUrl: '{devblocks_url}c=resource&p=cerberusweb.core&f=images/avatars/person4.png{/devblocks_url}' },
			{ label: 'Ana Cruz', seed: 'worker:5', imageUrl: '{devblocks_url}c=resource&p=cerberusweb.core&f=images/avatars/person5.png{/devblocks_url}' },
			{ label: 'Tom Reed', seed: 'worker:6', imageUrl: '{devblocks_url}c=resource&p=cerberusweb.core&f=images/avatars/person6.png{/devblocks_url}' }
		];
		people.forEach(function(p) {
			const el = CerbUI.Avatar.create({ label: p.label, seed: p.seed, imageUrl: p.imageUrl, size: 32 });
			el.setAttribute('title', p.label);
			host.appendChild(el);
		});
	})();

	// Loading skeleton: replay skeleton -> photo on demand. The URL is cache-busted per run, otherwise
	// the second click paints from cache with no visible transition at all.
	(function() {
		const host = document.getElementById('uiref-avatar-skeleton-live');
		const btn = document.getElementById('uiref-avatar-skeleton-reload');
		if(!host || !btn) return;

		const people = [
			{ label: 'Jane Doe', seed: 'worker:1', imageUrl: '{devblocks_url}c=resource&p=cerberusweb.core&f=images/avatars/person1.png{/devblocks_url}' },
			{ label: 'Ravi Patel', seed: 'worker:2', imageUrl: '{devblocks_url}c=resource&p=cerberusweb.core&f=images/avatars/person2.png{/devblocks_url}' },
			{ label: 'Mia Wong', seed: 'worker:3', imageUrl: '{devblocks_url}c=resource&p=cerberusweb.core&f=images/avatars/person3.png{/devblocks_url}' },
			{ label: 'Sam Lee', seed: 'worker:4', imageUrl: '{devblocks_url}c=resource&p=cerberusweb.core&f=images/avatars/person4.png{/devblocks_url}' },
			{ label: 'Ana Cruz', seed: 'worker:5', imageUrl: '{devblocks_url}c=resource&p=cerberusweb.core&f=images/avatars/person5.png{/devblocks_url}' },
			{ label: 'Tom Reed', seed: 'worker:6', imageUrl: '{devblocks_url}c=resource&p=cerberusweb.core&f=images/avatars/person6.png{/devblocks_url}' }
		];

		let run = 0;

		const paint = function() {
			run++;
			host.replaceChildren();
			people.forEach(function(p, i) {
				const bust = (p.imageUrl.indexOf('?') > -1 ? '&' : '?') + '_uiref=' + run + '.' + i;
				const el = CerbUI.Avatar.create({
					label: p.label,
					seed: p.seed,
					imageUrl: p.imageUrl + bust,
					size: 32
				});
				el.setAttribute('title', p.label);
				host.appendChild(el);
			});
		};

		btn.addEventListener('click', paint);
		paint();
	})();

	// Enhance in place: paint every server-rendered [data-avatar] within the scope
	CerbUI.Avatar.enhance('#uiref-avatar-enhance');

	// Badged: paint the avatars that carry a corner status pill
	CerbUI.Avatar.enhance('#uiref-avatar-badged');

	// Avatar stack: enhance the data-avatar children (data-max caps the footprint with a +N)
	if(CerbUI.AvatarStack) {
		const stack = document.getElementById('uiref-avatar-stack');
		if(stack) new CerbUI.AvatarStack(stack);

		// …and a data-driven stack with photos that swap over their monogram placeholders
		const host = document.getElementById('uiref-avatar-stack-photos');
		if(host) {
			new CerbUI.AvatarStack(host, {
				max: 4,
				size: 32,
				items: [
					{ label: 'Jane Doe', seed: 'worker:1', imageUrl: '{devblocks_url}c=resource&p=cerberusweb.core&f=images/avatars/person1.png{/devblocks_url}' },
					{ label: 'Ravi Patel', seed: 'worker:2', imageUrl: '{devblocks_url}c=resource&p=cerberusweb.core&f=images/avatars/person2.png{/devblocks_url}' },
					{ label: 'Mia Wong', seed: 'worker:3', imageUrl: '{devblocks_url}c=resource&p=cerberusweb.core&f=images/avatars/person3.png{/devblocks_url}' },
					{ label: 'Sam Lee', seed: 'worker:4', imageUrl: '{devblocks_url}c=resource&p=cerberusweb.core&f=images/avatars/person4.png{/devblocks_url}' },
					{ label: 'Ana Cruz', seed: 'worker:5', imageUrl: '{devblocks_url}c=resource&p=cerberusweb.core&f=images/avatars/person5.png{/devblocks_url}' },
					{ label: 'Tom Reed', seed: 'worker:6', imageUrl: '{devblocks_url}c=resource&p=cerberusweb.core&f=images/avatars/person6.png{/devblocks_url}' }
				]
			});
		}
	}
})();
</script>
