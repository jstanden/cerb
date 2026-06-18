	<div class="cerb-uiref-component" id="context-chooser">
		<div class="cerb-uiref-component--label"><span class="cerb-icons cerb-icon-search"></span>ContextChooser</div>

		{* RecordChooser + a leading chip-head that switches the record TYPE, then autocompletes within it. *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Multi-record-type picker &mdash; the chip-head switches the type (Global / Role / Group / Worker), then autocomplete searches that type. A <code>fixedId</code> type (Global = app:0) is a direct pick, no search</div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div id="uiref-contextchooser"></div>
				<span class="cerb-uiref-result" style="margin-left:0.7em;">Owner: <b id="uiref-contextchooser-result">&mdash;</b></span>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>new CerbUI.ContextChooser(el, {
	contexts: [
		{ alias:'cerberusweb.contexts.app',    label:'Everyone', icon:'globe', fixedId:0, image_url:'…&amp;c=avatars&amp;context=app&amp;context_id=0' },  // forced pick; avatar looked up, no peek (id 0)
		{ alias:'cerberusweb.contexts.role',   label:'Role',   icon:'shield' },
		{ alias:'cerberusweb.contexts.group',  label:'Group',  icon:'users' },
		{ alias:'cerberusweb.contexts.worker', label:'Worker', icon:'user' },
	],
	name: 'owner',                     // single &rarr; &lt;input name="owner" value="context:id"&gt;
	// defaultContext: 'cerberusweb.contexts.role',  // initial searchable type (does NOT reorder the menu)
	// value: 'cerberusweb.contexts.worker:5',                                  // the "context:id" you already have, or…
	// value: { context:'cerberusweb.contexts.worker', id:5, label:'Kim Li', image_url:'/avatar/…' }, // …the resolved object
	onSelect: function(item) { /* { context, id, label, image_url } */ },
});

// Aliases are FULL context ids (getByAlias resolves them); the posted value is "context:id".
// API is RecordChooser's: getValue / setValue / clear / destroy. Requires CerbUI.Menu.

// The Owner factory wraps exactly this (me/everyone/role/group/worker, name:'owner'); "Me" and
// "Everyone" are direct picks whose avatars are looked up server-side:
CerbUI.ContextChooser.Owner(el, { meWorkerId: 5, meImageUrl: '…', allowApp: true, appImageUrl: '…' });</pre>
			</div>
		</div>

		{* Default the value — seed markup (the old <ul><li> idea) the chooser enhances *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Default the value &mdash; you usually have a <code>context:id</code> from the record. Server-render the resolved label/avatar into a <code>[data-context-id]</code> child and the chooser enhances it (then clears the markup). No id&rarr;label endpoint exists, so resolve label/avatar via <code>Extension_DevblocksContext::get($ctx)-&gt;getMeta($id)</code> (sanctioned in-template &mdash; that class is in Smarty's <code>registerClass</code> allow-list) plus the <code>c=avatars</code> URL. For the common <b>owner</b> field this is already a one-liner: <code>{literal}{include file="…internal/peek/menu_actor_owner.tpl" model=$model}{/literal}</code></div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div id="uiref-contextchooser-seed" class="cerb-ui-record-chooser">
					<li data-context="cerberusweb.contexts.worker" data-context-id="{$active_worker->id}" data-label="{$active_worker->getName()}" data-image="{devblocks_url}c=avatars&context=worker&context_id={$active_worker->id}{/devblocks_url}?v={$smarty.const.APP_BUILD}"></li>
				</div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>&lt;!-- Seed markup: one [data-context-id] child per value (the old &lt;ul&gt;&lt;li&gt; idea). Server-render the
     resolved label/avatar; the chooser reads these, builds the chips, then clears the markup. --&gt;
&lt;div class="cerb-ui-record-chooser"&gt;
	&lt;li data-context="cerberusweb.contexts.role" data-context-id="1"
	    data-label="Administrators"
	    data-image="…&amp;c=avatars&amp;context=role&amp;context_id=1"&gt;&lt;/li&gt;
&lt;/div&gt;</pre>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>new CerbUI.ContextChooser(el, { contexts:[/* … */], name:'owner' });  // no `value` → reads the seed markup</pre>
			</div>
		</div>

		{* Multiple, any context — a tag input spanning record types (e.g. for record links) *}
		<div class="cerb-ui-header">
			<div class="cerb-ui-header--label">Multiple, any record type &mdash; selections across types stack as tiles (the shape a record-<b>links</b> picker would use). Dedup is by <code>context:id</code>; posts <code>name[]</code> = <code>"context:id"</code></div>
		</div>
		<div class="cerb-uiref-example">
			<div class="cerb-uiref-demo">
				<div id="uiref-contextchooser-multi"></div>
			</div>

			<div class="cerb-uiref-code">
				<button type="button" class="cerb-uiref-copy" data-cerb-uiref-copy title="Copy to clipboard"><span class="cerb-icons cerb-icon-copy"></span></button>
				<pre data-cerb-uiref-source>new CerbUI.ContextChooser(el, {
	contexts: [
		{ alias:'cerberusweb.contexts.ticket', label:'Ticket', icon:'ticket' },
		{ alias:'cerberusweb.contexts.task',   label:'Task',   icon:'checked' },
		{ alias:'cerberusweb.contexts.worker', label:'Worker', icon:'user' },
	],
	multiple: true,                    // tiles span types; switch the chip-head between adds
	name: 'links',
});</pre>
			</div>
		</div>
	</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}">
(function() {
	const el = document.getElementById('uiref-contextchooser');
	const out = document.getElementById('uiref-contextchooser-result');
	if(el && window.CerbUI && CerbUI.ContextChooser) {
		new CerbUI.ContextChooser(el, {
			contexts: [
				{ alias:'cerberusweb.contexts.app',    label:'Everyone', icon:'globe', fixedId:0, image_url:'{devblocks_url}c=avatars&context=app&context_id=0{/devblocks_url}?v={$smarty.const.APP_BUILD}' },
				{ alias:'cerberusweb.contexts.role',   label:'Role',   icon:'shield' },
				{ alias:'cerberusweb.contexts.group',  label:'Group',  icon:'users' },
				{ alias:'cerberusweb.contexts.worker', label:'Worker', icon:'user' }
			],
			onSelect: function(item) { if(out) out.textContent = item.label + '  (' + item.context + ':' + item.id + ')'; }
		});
	}

	const elSeed = document.getElementById('uiref-contextchooser-seed');
	if(elSeed && window.CerbUI && CerbUI.ContextChooser) {
		new CerbUI.ContextChooser(elSeed, {
			contexts: [
				{ alias:'cerberusweb.contexts.role',   label:'Role',   icon:'shield' },
				{ alias:'cerberusweb.contexts.group',  label:'Group',  icon:'users' },
				{ alias:'cerberusweb.contexts.worker', label:'Worker', icon:'user' }
			],
			name: 'owner'
		});
	}

	const elMulti = document.getElementById('uiref-contextchooser-multi');
	if(elMulti && window.CerbUI && CerbUI.ContextChooser) {
		new CerbUI.ContextChooser(elMulti, {
			contexts: [
				{ alias:'cerberusweb.contexts.ticket', label:'Ticket', icon:'ticket' },
				{ alias:'cerberusweb.contexts.task',   label:'Task',   icon:'checked' },
				{ alias:'cerberusweb.contexts.worker', label:'Worker', icon:'user' }
			],
			multiple: true
		});
	}
})();
</script>
