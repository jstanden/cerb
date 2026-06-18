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
// data-avatar="Jane Doe"  data-avatar-seed="worker:5"  data-avatar-image="/avatar/worker/5"  data-avatar-size="32"{/literal}</pre>
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

		{* Example: graybox → photo — instant monogram placeholder, real image swaps in on load *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Graybox &rarr; photo &mdash; pass <code>imageUrl</code> and the monogram shows <strong>instantly</strong> as a placeholder, then the real picture swaps in once it loads. This is how a long list of profile images paints without flashing empty</div>
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
	// enqueue: chooserCore's bounded loader — throttles a 1,000-row list so it never bursts the server
});{/literal}</pre>
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

	// Enhance in place: paint every server-rendered [data-avatar] within the scope
	CerbUI.Avatar.enhance('#uiref-avatar-enhance');

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
