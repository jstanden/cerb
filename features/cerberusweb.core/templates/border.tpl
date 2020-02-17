{include file="devblocks:cerberusweb.core::header.tpl"}

{if !empty($prebody_renderers)}
	{foreach from=$prebody_renderers item=renderer}
		{if !empty($renderer)}{$renderer->render()}{/if}
	{/foreach}
{/if}

{if !empty($tour_enabled)}{include file="devblocks:cerberusweb.core::internal/tour/banner.tpl"}{/if}

{if $active_worker}
<div class="cerb-no-print" style="display:flex;flex-flow:row wrap;">
	<div style="flex:2 2;">
		<a href="{devblocks_url}{/devblocks_url}"><div id="cerb-logo"></div></a>
	</div>
	<div style="flex:1 1 250px;text-align:right;padding-bottom:5px;margin-top:auto;">
			<img src="{devblocks_url}c=avatars&context=worker&context_id={$active_worker->id}{/devblocks_url}?v={$active_worker->updated}" style="height:1.75em;width:1.75em;border-radius:0.875em;vertical-align:middle;">
			<b><a href="javascript:;" id="lnkSignedIn" data-worker-id="{$active_worker->id}" data-worker-name="{$active_worker->getName()}">{$active_worker->getName()}</a></b><span class="glyphicons glyphicons-chevron-down" style="margin:2px 0px 0px 2px;"></span>
			{if $visit->isImposter()}
				[ <a href="javascript:;" id="aImposter">{$visit->getImposter()->getName()}</a> ]
			{/if}
			
			<span id="badgeNotifications"><a href="javascript:;"></a></span>
			
			<ul id="menuSignedIn" class="cerb-popupmenu cerb-float">
				<li><a href="{devblocks_url}c=profiles&w=worker&me=me{/devblocks_url}">{'header.my_profile'|devblocks_translate|lower}</a></li>
				<li><a href="javascript:;" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$active_worker->id}">{'header.my_card'|devblocks_translate|lower}</a></li>
				<li><a href="{devblocks_url}c=profiles&w=worker&me=me&tab=settings{/devblocks_url}">{'common.settings'|devblocks_translate|lower}</a></li>
				<li><a href="javascript:;" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_NOTIFICATION}" data-layer="notifications_me" data-query="*" data-query-required="worker.id:{$active_worker->id}">{'home.tab.my_notifications'|devblocks_translate|lower}</a></li>
				<li><a href="javascript:;" class="cerb-search-trigger" data-context="{CerberusContexts::CONTEXT_ACTIVITY_LOG}" data-query="actor.worker:(id:{$active_worker->id}) created:&quot;-1 day&quot;">{'common.activity_log'|devblocks_translate|lower}</a></li>
				<li><a href="javascript:;" data-cerb-action="signout">{'header.signoff'|devblocks_translate|lower}</a></li>
				<li><a href="javascript:;" data-cerb-action="signout-all">{'header.signoff.all.my'|devblocks_translate|lower}</a></li>
			</ul>
	</div>
</div>
{else}
<div style="text-align:center;">
	<a href="{devblocks_url}{/devblocks_url}"><div id="cerb-logo" style="background-position:center;"></div></a>
</div>
{/if}

<script type="text/javascript">
$(function(e) {
	{if !empty($visit) && $visit->isImposter()}
	$('#aImposter').click(function(e) {
		genericAjaxGet('','c=internal&a=suRevert',function(o) {
			window.location = window.location;
		});
	});
	{/if}
	
	var $menu = $('#menuSignedIn');
	$menu.appendTo('body');
	$menu.find('> li')
		.click(function(e) {
			e.stopPropagation();
			
			if(!$(e.target).is('li'))
				return;

			var $link = $(this).find('a:first');
			
			if($link.attr('href') != 'javascript:;') {
				window.location.href = $link.attr('href');
			} else {
				$link.click();
				$menu.hide();
			}
		})
		;
	
	$('#lnkSignedIn')
		.click(function(e) {
			if($menu.is(':visible')) {
				$menu.hide();
				return;
			}
			
			$menu
				.show()
				.position({ my: "left top", at: "left bottom", of: $('#lnkSignedIn'), collision: "fit" })
			;
		});

	$menu
		.hover(
			function(e) {},
			function(e) {
				$('#menuSignedIn')
					.hide()
				;
			}
		)
		;

	$menu.find('[data-cerb-action=signout]').on('click', function() {
		var formData = new FormData();
		formData.set('c', 'login');
		formData.set('a', 'signout');
		genericAjaxPost(formData, '', '', function() {
			window.document.location.reload();
		});
	});

	$menu.find('[data-cerb-action=signout-all]').on('click', function() {
		var formData = new FormData();
		formData.set('c', 'login');
		formData.set('a', 'signout');
		formData.set('scope', 'all');
		genericAjaxPost(formData, '', '', function() {
			window.document.location.reload();
		});
	});

	$menu.find('.cerb-peek-trigger').cerbPeekTrigger();
	$menu.find('.cerb-search-trigger').cerbSearchTrigger();
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
