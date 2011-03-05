<div class="cerb-menu" style="margin-top:-5px;">
	<ul>
		<li>
			<div>
				<a href="javascript:;" class="menu">Settings <span>&#x25be;</span></a>
				<ul class="cerb-popupmenu cerb-float" style="display:none;">
					<li><a href="{devblocks_url}c=config&a=branding{/devblocks_url}">Logo &amp; Title</a></li>
					{if !$smarty.const.ONDEMAND_MODE}<li><a href="{devblocks_url}c=config&a=security{/devblocks_url}">Security</a></li>{/if}
					<li><a href="{devblocks_url}c=config&a=fields{/devblocks_url}">Custom Fields</a></li>
					<li><a href="{devblocks_url}c=config&a=license{/devblocks_url}">License</a></li>
					{if !$smarty.const.ONDEMAND_MODE}<li><a href="{devblocks_url}c=config&a=scheduler{/devblocks_url}">Scheduler</a></li>{/if}
					
					{$exts = Extension_PageMenuItem::getExtensions(true, 'core.page.configuration','core.setup.menu.settings')}
					{if !empty($exts)}<li><hr></li>{/if}
					{foreach from=$exts item=menu_item}
						{if method_exists($menu_item,'render')}<li>{$menu_item->render()}</li>{/if}
					{/foreach}
				</ul>
			</div>
		</li>
		<li>
			<div>
				<a href="javascript:;" class="menu">{'common.workers'|devblocks_translate|capitalize} &amp; {'common.groups'|devblocks_translate|capitalize} <span>&#x25be;</span></a>
				<ul class="cerb-popupmenu cerb-float" style="display:none;">
					<li><a href="{devblocks_url}c=config&a=groups{/devblocks_url}">{'common.groups'|devblocks_translate|capitalize}</a></li>
					<li><a href="{devblocks_url}c=config&a=acl{/devblocks_url}">Permissions</a></li>
					<li><a href="{devblocks_url}c=config&a=workers{/devblocks_url}">{'common.workers'|devblocks_translate|capitalize}</a></li>
					
					{$exts = Extension_PageMenuItem::getExtensions(true, 'core.page.configuration','core.setup.menu.workers')}
					{if !empty($exts)}<li><hr></li>{/if}
					{foreach from=$exts item=menu_item}
						{if method_exists($menu_item,'render')}<li>{$menu_item->render()}</li>{/if}
					{/foreach}
				</ul>
			</div>
		</li>
		<li>
			<div>
				<a href="javascript:;" class="menu">Mail <span>&#x25be;</span></a>
				<ul class="cerb-popupmenu cerb-float" style="display:none;">
					<li><a href="{devblocks_url}c=config&a=mail_incoming{/devblocks_url}">Incoming Mail</a></li>
					<li><a href="{devblocks_url}c=config&a=mail_outgoing{/devblocks_url}">Outgoing Mail</a></li>
					<li><a href="{devblocks_url}c=config&a=mail_pop3{/devblocks_url}">POP3 Accounts</a></li>
					<li><a href="{devblocks_url}c=config&a=mail_routing{/devblocks_url}">Routing</a></li>
					<li><a href="{devblocks_url}c=config&a=mail_filtering{/devblocks_url}">Filtering</a></li>
					<li><a href="{devblocks_url}c=config&a=mail_queue{/devblocks_url}">Queue</a></li>
					
					{$exts = Extension_PageMenuItem::getExtensions(true, 'core.page.configuration','core.setup.menu.mail')}
					{if !empty($exts)}<li><hr></li>{/if}
					{foreach from=$exts item=menu_item}
						{if method_exists($menu_item,'render')}<li>{$menu_item->render()}</li>{/if}
					{/foreach}
				</ul>
			</div>
		</li>
		{if !$smarty.const.ONDEMAND_MODE}
		<li>
			<div>
				<a href="javascript:;" class="menu">Storage <span>&#x25be;</span></a>
				<ul class="cerb-popupmenu cerb-float" style="display:none;">
					<li><a href="{devblocks_url}c=config&a=storage_content{/devblocks_url}">Content</a></li>
					<li><a href="{devblocks_url}c=config&a=storage_profiles{/devblocks_url}">Profiles</a></li>
					<li><a href="{devblocks_url}c=config&a=storage_attachments{/devblocks_url}">Attachments</a></li>
					
					{$exts = Extension_PageMenuItem::getExtensions(true, 'core.page.configuration','core.setup.menu.storage')}
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
				<a href="javascript:;" class="menu">Community Portals <span>&#x25be;</span></a>
				<ul class="cerb-popupmenu cerb-float" style="display:none;">
					<li><a href="{devblocks_url}c=config&a=portals{/devblocks_url}">Configure</a></li>
					
					{$exts = Extension_PageMenuItem::getExtensions(true, 'core.page.configuration','core.setup.menu.portals')}
					{if !empty($exts)}<li><hr></li>{/if}
					{foreach from=$exts item=menu_item}
						{if method_exists($menu_item,'render')}<li>{$menu_item->render()}</li>{/if}
					{/foreach}
				</ul>
			</div>
		</li>
		<li>
			<div>
				<a href="javascript:;" class="menu">Plugins <span>&#x25be;</span></a>
				<ul class="cerb-popupmenu cerb-float" style="display:none;">
					<li><a href="{devblocks_url}c=config&a=plugins{/devblocks_url}">Manage</a></li>
					<li><a href="https://github.com/cerb5-plugins" target="_blank">Find More Plugins...</a></li>
					
					{$exts = Extension_PageMenuItem::getExtensions(true, 'core.page.configuration','core.setup.menu.plugins')}
					{if !empty($exts)}<li><hr></li>{/if}
					{foreach from=$exts item=menu_item}
						{if method_exists($menu_item,'render')}<li>{$menu_item->render()}</li>{/if}
					{/foreach}
					{*
					<li><a href="{devblocks_url}c=config&a=freshbooks{/devblocks_url}">Freshbooks</a></li>
					<li><a href="{devblocks_url}{/devblocks_url}">Notifications Emailer</a></li>
					<li><a href="{devblocks_url}c=config&a=timetracking.activities{/devblocks_url}">Time Tracking</a></li>
					<li><a href="{devblocks_url}c=config&a=translations{/devblocks_url}">Translation Editor</a></li>
					<li><a href="{devblocks_url}c=config&a=watchers{/devblocks_url}">Watchers</a></li>
					*}
				</ul>
			</div>
		</li>
		
		{$exts = Extension_PageMenu::getExtensions(true, 'core.page.configuration')}
		{foreach from=$exts item=menu key=menu_id}
		<li>
			<div>
				{if method_exists($menu,'render')}{$menu->render()}{/if}
			</div>
		</li>
		{/foreach}
	</ul>
</div>
<br clear="all" style="clear:both;">

{if $install_dir_warning && !$smarty.const.DEVELOPMENT_MODE}
<div class="ui-widget">
	<div class="ui-state-error ui-corner-all" style="padding:0 0.5em;margin:0.5em;"> 
		<p>
			<span class="ui-icon ui-icon-alert" style="float:left;margin-right:0.3em"></span> 
			<strong>Warning:</strong> The 'install' directory still exists.  This is a potential security risk.  Please delete it.
		</p>
	</div>
</div>
{/if}

{if !empty($subpage) && $subpage instanceof Extension_PageSection}
<div class="cerb-subpage" style="margin-top:10px;">
	{$subpage->render()}
</div>
{/if}

{*
	{if $active_worker->hasPriv('core.home.workspaces')}
		{$enabled_workspaces = DAO_Workspace::getByEndpoint($point, $active_worker->id)}
		{foreach from=$enabled_workspaces item=enabled_workspace}
			{$tabs[] = 'w_'|cat:$enabled_workspace->id}
			<li><a href="{devblocks_url}ajax.php?c=internal&a=showWorkspaceTab&point={$point}&id={$enabled_workspace->id}&request={$response_uri|escape:'url'}{/devblocks_url}"><i>{$enabled_workspace->name}</i></a></li>
		{/foreach}
		
		{$tabs[] = "+"}
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showAddTab&point={$point}&request={$response_uri|escape:'url'}{/devblocks_url}"><i>+</i></a></li>
	{/if}
*}

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
			})
		;
</script>
