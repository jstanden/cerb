{if !empty($visit)}
<div id="tourHeaderMenu"></div>

<ul class="navmenu">
	{* [TODO] Cache!! *}
	{$workspace_pages = DAO_WorkspacePage::getWhere()}

	{$menu_json = DAO_WorkerPref::get($active_worker->id, 'menu_json','')}
	{$menu = json_decode($menu_json, true)}
	
	{if is_array($menu) && !empty($menu)}
	{foreach from=$menu item=workspace_page_id}
		{$workspace_page = $workspace_pages.$workspace_page_id}
		{if $workspace_page && $workspace_page->isReadableByWorker($active_worker)}
		<li class="{if $page->id=='core.page.pages' && isset($response_path[1]) && intval($response_path[1])==$workspace_page->id}selected{/if} drag" page_id="{$workspace_page->id}">
			<a href="{devblocks_url}c=pages&page={$workspace_page->id}-{$workspace_page->name|devblocks_permalink}{/devblocks_url}">{$workspace_page->name|lower}</a>
		</li>
		{/if}
	{/foreach}
	{/if}
	
	<li style="border-right:0;" class="add {if $page->id=='core.page.pages' && count($response_path)==1}selected{/if}">
		<a href="{devblocks_url}c=pages{/devblocks_url}" style="font-weight:normal;text-decoration:none;">{if $page->id=='core.page.pages' && count($response_path)==1}<span class="cerb-sprite sprite-arrow-down-white" style="height:12px;width:12px;"></span>{else}<span class="cerb-sprite sprite-arrow-down-black" style="height:12px;width:12px;"></span>{/if}</a>
	</li>
	
	{if $active_worker->is_superuser}
	<li class="{if $page->id=='core.page.configuration'}selected{/if}" style="float:right;">
		<a href="{devblocks_url}c=config{/devblocks_url}">{$translate->_('header.config')|lower}</a>				
	</li>
	{/if}

	<li class="{if $page->id=='core.page.search'}selected{/if}" style="float:right;">
		<a href="javascript:;" class="submenu">{'common.search'|devblocks_translate|lower} <span class="cerb-sprite {if $page->id=='core.page.search'}sprite-arrow-down-white{else}sprite-arrow-down-black{/if}" style="height:12px;width:12px;"></span></a>
		<ul class="cerb-popupmenu cerb-float" style="">
			{foreach from=$contexts item=context key=context_id}
			{if isset($context->params.options.0.workspace)}
			<li><a href="{devblocks_url}c=search&context={if isset($context->params.alias)}{$context->params.alias}{else}{$context_id}{/if}{/devblocks_url}">{$context->name}</a></li>
			{/if}
			{/foreach}
		</ul>
	</li>

	{if !empty($active_worker_memberships)}
	<li class="{if $page->id=='core.page.groups'}selected{/if}" style="float:right;">
		<a href="{devblocks_url}c=groups{/devblocks_url}">{$translate->_('common.groups')|lower}</a>				
	</li>
	{/if}
</ul>
<div style="clear:both;background-color:rgb(100,135,225);height:5px;"></div>

<script type="text/javascript">
	$menu = $('UL.navmenu');

	$menu.sortable({
		items:'> li.drag',
		update:function(e) {
			$pages = $(this).find('li.drag[page_id=*]');
			page_ids = $pages.map(function(e) {
				return $(this).attr('page_id');
			}).get().join(',');

			genericAjaxGet('', 'c=pages&a=setPageOrder&pages=' + page_ids);
		}
	});
	
	$menu.find('> LI A.submenu')
		.closest('li')
		.hoverIntent({
			sensitivity:10,
			interval:100,
			over:function(e) {
				$menu = $(this).find('ul:first');
				$menu
					.show()
					.css('position','absolute')
					.css('top',$(this).offset().top+7+($(this).height())+'px')
					.css('left',$(this).offset().left+10-($menu.width()-$(this).width())+'px')
				;
			},
			timeout:0,
			out:function(e) {
				$(this).find('ul:first').hide();
			}
		})
		.find('.cerb-popupmenu > li')
			.click(function(e) {
				e.stopPropagation();
				if(!$(e.target).is('li'))
					return;

				$link = $(this).find('a');

				if($link.length > 0)
					window.location.href = $link.attr('href');
				
				$(this).closest('.cerb-popupmenu').hide();
			})
		;
</script>
{/if}
