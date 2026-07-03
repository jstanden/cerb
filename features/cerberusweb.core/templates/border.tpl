{include file="devblocks:cerberusweb.core::header.tpl"}

{if !empty($prebody_renderers)}
	{foreach from=$prebody_renderers item=renderer}
		{if !empty($renderer)}{$renderer->render()}{/if}
	{/foreach}
{/if}

{if $active_worker}
<div class="cerb-no-print" style="display:flex;flex-flow:row wrap;">
	<div style="flex:2 2;">
		<a href="{devblocks_url}{/devblocks_url}"><div id="cerb-logo"></div></a>
	</div>
	<div style="flex:1 1 250px;display:flex;align-items:center;justify-content:flex-end;gap:5px;padding-bottom:5px;margin-top:auto;">
			<span class="cerb-ui-pill">
				<img src="{devblocks_url}c=avatars&context=worker&context_id={$active_worker->id}{/devblocks_url}?v={$active_worker->updated}" style="height:1.75em;width:1.75em;border-radius:0.875em;vertical-align:middle;">
				<a id="lnkSignedIn" class="cerb-u-bold cerb-u-underline-hover" data-worker-id="{$active_worker->id}" data-worker-name="{$active_worker->getName()}">{$active_worker->getName()}</a><span class="cerb-icons cerb-icon-chevron-down"></span>
				{if $visit->isImposter()}
					<span class="cerb-ui-pill"><span class="cerb-icons cerb-icon-eye-open"></span><a id="aImposter">{$visit->getImposter()->getName()}</a></span>
				{/if}
			</span>

			{if $pref_dark_mode}
				<button type="button" id="cerb-theme" data-theme="dark" title="Switch to light mode"><span class="cerb-icons cerb-icon-moon"></span></button>
			{else}
				<button type="button" id="cerb-theme" data-theme="light" title="Switch to dark mode"><span class="cerb-icons cerb-icon-sun"></span></button>
			{/if}
			
			<button id="badgeNotifications" class="red" style="display:none;"></button>
			
			<ul id="menuSignedIn" hidden>
				<li data-icon="user"><a href="{devblocks_url}c=profiles&w=worker&me=me{/devblocks_url}">{'header.my_profile'|devblocks_translate|lower}</a></li>
				<li data-icon="id-card"><a class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$active_worker->id}">{'header.my_card'|devblocks_translate|lower}</a></li>
				<li data-icon="gear"><a href="{devblocks_url}c=profiles&w=worker&me=me&tab=settings{/devblocks_url}">{'common.settings'|devblocks_translate|lower}</a></li>
				<li data-icon="bell"><a class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_NOTIFICATION}" data-layer="notifications_me" data-query="*" data-query-required="worker.id:{$active_worker->id}">{'home.tab.my_notifications'|devblocks_translate|lower}</a></li>
				<li data-icon="history"><a class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_ACTIVITY_LOG}" data-query="actor.worker:(id:{$active_worker->id}) created:&quot;-1 day&quot;">{'common.activity_log'|devblocks_translate|lower}</a></li>
				<li data-icon="sign-out"><a data-cerb-action="signout">{'header.signoff'|devblocks_translate|lower}</a></li>
				<li data-icon="sign-out"><a data-cerb-action="signout-all">{'header.signoff.all.my'|devblocks_translate|lower}</a></li>
			</ul>
	</div>
</div>
{elseif $response_path[0] != 'login'}
<div style="text-align:center;">
	<a href="{devblocks_url}{/devblocks_url}"><div id="cerb-logo" style="background-position:center;"></div></a>
</div>
{/if}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	{if !empty($visit) && $visit->isImposter()}
	$('#aImposter').click(function(e) {
		e.stopPropagation();

		let formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'worker');
		formData.set('action', 'suRevert');

		genericAjaxPost(formData,'', '', function() {
			window.location.reload();
		});
	});
	{/if}
	
	const $menu = $('#menuSignedIn');

	// Bind the peek/search plugins onto the (hidden) source links that onSelect clicks
	$menu.find('.cerb-peek-trigger').cerbPeekTrigger();
	$menu.find('.cerb-search-trigger').cerbSearchTrigger();

	const signedInMenu = new CerbUI.Menu($menu[0], {
		onRenderItem: function(rendered, source) {
			const icon = source.dataset.icon;
			if(icon) {
				const ico = document.createElement('span');
				ico.className = 'cerb-icons cerb-icon-' + icon;
				ico.setAttribute('aria-hidden', 'true');
				ico.style.marginRight = '0.5em';
				rendered.insertBefore(ico, rendered.firstChild);
			}
		},
		onSelect: function(rendered, source) {
			const a = source.querySelector('a');
			const action = a ? a.getAttribute('data-cerb-action') : null;

			// Sign off via HTTP POST
			if(action === 'signout' || action === 'signout-all') {
				const formData = new FormData();
				formData.set('c', 'login');
				formData.set('a', 'signout');
				if(action === 'signout-all')
					formData.set('scope', 'all');
				genericAjaxPost(formData, '', '', function() {
					window.document.location.reload();
				});
				return;
			}

			// Navigate href links
			const href = a ? a.getAttribute('href') : null;
			if(href && '#' !== href) {
				window.location.href = href;
				return;
			}

			// Otherwise click the source link (cerb-peek-trigger / cerb-search-trigger)
			if(a) a.click();
		}
	});

	const $trigger = $('#lnkSignedIn');
	$trigger
		.click(function(e) {
			e.stopPropagation();
			signedInMenu.isOpen() ? signedInMenu.close() : signedInMenu.open($trigger[0]);
		})
	;
	if(window.CerbUI && CerbUI.utils) CerbUI.utils.disableSelection($trigger[0]);
	
	var $theme = $('#cerb-theme');
	
	$theme
		.on('click', function() {
			var formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'worker');
			formData.set('action', 'themeToggle');
			
			genericAjaxPost(formData, '', '', function() {
				window.document.location.reload();
			});
		})
	;
});
</script>

{include file="devblocks:cerberusweb.core::menu.tpl"}

{if !empty($page) && $page->isVisible()}
	{$page->render()}
{else}
	{'header.no_page'|devblocks_translate}
{/if}

{if !empty($postbody_renderers)}
	{foreach from=$postbody_renderers item=renderer}
		{if !empty($renderer)}{$renderer->render()}{/if}
	{/foreach}
{/if}

{include file="devblocks:cerberusweb.core::footer.tpl"}
