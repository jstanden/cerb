<div class="cerb-menu" style="">
	<ul>
		<li>
			<div>
				<a href="javascript:;" class="menu">{$active_worker->getName()} <span class="cerb-sprite sprite-arrow-down-white"></span></a>
				<ul class="cerb-popupmenu cerb-float">
					<li><a href="{devblocks_url}c=workspaces&a=me{/devblocks_url}">My Workspace</a></li>
					
					{$exts = Extension_PageMenuItem::getExtensions(true, 'core.page.workspaces','core.workspaces.menu.me')}
					{if !empty($exts)}<li><hr></li>{/if}
					{foreach from=$exts item=menu_item}
						{if method_exists($menu_item,'render')}<li>{$menu_item->render()}</li>{/if}
					{/foreach}
				</ul>
			</div>
		</li>
		
		{if !empty($groups)}
		<li>
			<div>
				<a href="javascript:;" class="menu">{'common.groups'|devblocks_translate|capitalize} <span class="cerb-sprite sprite-arrow-down-white"></span></a>
				<ul class="cerb-popupmenu cerb-float">
					{foreach from=$groups item=group key=group_id}
					{if $active_worker->isGroupMember($group_id)}
					<li><a href="{devblocks_url}c=workspaces&a=group&id={$group->id}-{$group->name|devblocks_permalink}{/devblocks_url}">{$group->name}</a></li>
					{/if}
					{/foreach}
					
					{$exts = Extension_PageMenuItem::getExtensions(true, 'core.page.workspaces','core.workspaces.menu.group')}
					{if !empty($exts)}<li><hr></li>{/if}
					{foreach from=$exts item=menu_item}
						{if method_exists($menu_item,'render')}<li>{$menu_item->render()}</li>{/if}
					{/foreach}
				</ul>
			</div>
		</li>
		{/if}
		
		{if !empty($roles)}
		<li>
			<div>
				<a href="javascript:;" class="menu">{'common.roles'|devblocks_translate|capitalize} <span class="cerb-sprite sprite-arrow-down-white"></span></a>
				<ul class="cerb-popupmenu cerb-float">
					{foreach from=$roles item=role key=role_id}
					<li><a href="{devblocks_url}c=workspaces&a=role&id={$role->id}-{$role->name|devblocks_permalink}{/devblocks_url}">{$role->name}</a></li>
					{/foreach}
					
					{$exts = Extension_PageMenuItem::getExtensions(true, 'core.page.workspaces','core.workspaces.menu.role')}
					{if !empty($exts)}<li><hr></li>{/if}
					{foreach from=$exts item=menu_item}
						{if method_exists($menu_item,'render')}<li>{$menu_item->render()}</li>{/if}
					{/foreach}
				</ul>
			</div>
		</li>
		{/if}
		
		<li>
			<div>
				<a href="javascript:;" class="menu">{'common.search'|devblocks_translate|capitalize} <span class="cerb-sprite sprite-arrow-down-white"></span></a>
				<ul class="cerb-popupmenu cerb-float">
					{foreach from=$contexts item=context key=context_id}
					{if isset($context->params.options.0.workspace)}
					<li><a href="{devblocks_url}c=workspaces&a=context&context={if isset($context->params.alias)}{$context->params.alias}{else}{$context_id}{/if}{/devblocks_url}">{$context->name}</a></li>
					{/if}
					{/foreach}
				</ul>
			</div>
		</li>
		
	</ul>
</div>
<br clear="all" style="clear:both;">

{if !empty($subpage) && $subpage instanceof Extension_PageSection}
<div class="cerb-subpage" style="margin-top:10px;">
	{$subpage->render()}
</div>
{/if}

<script type="text/javascript">
	$('DIV.cerb-menu DIV A.menu')
		.closest('li')
		.hover(
			function(e) {
				$(this).find('ul:first').show();
			},
			function(e) {
				$(this).find('ul:first').hide();
			}
		)
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
