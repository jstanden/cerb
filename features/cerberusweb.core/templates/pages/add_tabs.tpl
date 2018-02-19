{if Context_WorkspacePage::isWriteableByActor($page, $active_worker)}
<form action="#" onsubmit="return false;">
<div class="help-box" style="padding:5px;border:0;">
	<h1 style="margin-bottom:5px;text-align:left;">Let's add some tabs to your page</h1>
	
	<p>
		Once you've created a new workspace page you can add tabs to organize your content.
	</p>

	<p>
		Depending on the plugins you have installed, a tab can be one of several <b>types</b>.  The default is <i>Worklists</i>, which displays as many lists of specific information as you want.  The other tab types are specialized for specific purposes, such as informational dashboards and browsing the knowledgebase by category.
	</p>
</div>
</form>
{/if}

{$uniq_id = uniqid()}
<form id="{$uniq_id}" action="{devblocks_url}{/devblocks_url}" method="post" onsubmit="return false;">
<button type="button" class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_WORKSPACE_TAB}" data-context-id="0" data-edit="page.id:{$page->id}"><span class="glyphicons glyphicons-circle-plus"></span> {'common.add'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$uniq_id}');
	
	$frm.find('button.cerb-peek-trigger')
		.cerbPeekTrigger()
			.on('cerb-peek-saved', function(e) {
				var $this = $(this);
				var $tabs = $('#pageTabs{$page->id}');
				
				var $new_tab = $('<li class="drag"/>').attr('tab_id',e.id);
				$new_tab.append($('<a/>').attr('href',e.tab_url).append($('<span/>').text(e.label)));
				
				$new_tab.insertBefore($tabs.find('.ui-tabs-nav > li:last'));
				
				$tabs.tabs('refresh');
				
				$this.effect('transfer', { to:$new_tab, className:'effects-transfer' }, 500, function() { });
				
				$tabs.tabs('option', 'active', -2);
			})
		;
});
</script>