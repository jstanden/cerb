<div class="cerb-menu">
	<ul>
		<li>
			<div>
				<a href="javascript:;" class="menu">Configure <span class="cerb-sprite sprite-arrow-down-white"></span></a>
				<ul class="cerb-popupmenu cerb-float">
					<li><a href="{devblocks_url}c=config&a=branding{/devblocks_url}">Logo &amp; Title</a></li>
					{if !$smarty.const.ONDEMAND_MODE}<li><a href="{devblocks_url}c=config&a=security{/devblocks_url}">Security</a></li>{/if}
					<li><a href="{devblocks_url}c=config&a=fields{/devblocks_url}">Custom Fields</a></li>
					<li><a href="{devblocks_url}c=config&a=license{/devblocks_url}">License</a></li>
					{if !$smarty.const.ONDEMAND_MODE}<li><a href="{devblocks_url}c=config&a=scheduler{/devblocks_url}">Scheduler</a></li>{/if}
					<li><a href="{devblocks_url}c=config&a=snippets{/devblocks_url}">Snippets</a></li>
					<li><a href="{devblocks_url}c=config&a=portals{/devblocks_url}">Community Portals</a></li>
					<li><a href="{devblocks_url}c=config&a=attendants{/devblocks_url}">Virtual Attendants</a></li>
					<li><a href="{devblocks_url}c=config&a=scheduled_behavior{/devblocks_url}">Scheduled Behavior</a></li>
					
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
				<a href="javascript:;" class="menu">{'common.workers'|devblocks_translate|capitalize} &amp; {'common.groups'|devblocks_translate|capitalize} <span class="cerb-sprite sprite-arrow-down-white"></span></a>
				<ul class="cerb-popupmenu cerb-float">
					<li><a href="{devblocks_url}c=config&a=groups{/devblocks_url}">{'common.groups'|devblocks_translate|capitalize}</a></li>
					<li><a href="{devblocks_url}c=config&a=acl{/devblocks_url}">{'common.roles'|devblocks_translate|capitalize}</a></li>
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
				<a href="javascript:;" class="menu">Mail <span class="cerb-sprite sprite-arrow-down-white"></span></a>
				<ul class="cerb-popupmenu cerb-float">
					<li><b>Incoming Mail</b></li>
					<li><a href="{devblocks_url}c=config&a=mail_incoming{/devblocks_url}">Settings</a></li>
					<li><a href="{devblocks_url}c=config&a=mail_pop3{/devblocks_url}">POP3 Accounts</a></li>
					<li><a href="{devblocks_url}c=config&a=mail_routing{/devblocks_url}">Routing</a></li>
					<li><a href="{devblocks_url}c=config&a=mail_filtering{/devblocks_url}">Filtering</a></li>
					<li><hr></li>
					
					<li><b>Outgoing Mail</b></li>
					<li><a href="{devblocks_url}c=config&a=mail_smtp{/devblocks_url}">SMTP Server</a></li>
					<li><a href="{devblocks_url}c=config&a=mail_from{/devblocks_url}">Reply-To Addresses</a></li>
					<li><a href="{devblocks_url}c=config&a=mail_queue{/devblocks_url}">Queue</a></li>
					
					{$exts = Extension_PageMenuItem::getExtensions(true, 'core.page.configuration','core.setup.menu.mail')}
					{if !empty($exts)}
						<li><hr></li>
						<li><b>Plugins</b></li>
					{/if}
					{foreach from=$exts item=menu_item}
						{if method_exists($menu_item,'render')}<li>{$menu_item->render()}</li>{/if}
					{/foreach}
				</ul>
			</div>
		</li>
		<li>
			<div>
				<a href="javascript:;" class="menu">Storage <span class="cerb-sprite sprite-arrow-down-white"></span></a>
				<ul class="cerb-popupmenu cerb-float">
					{if !$smarty.const.ONDEMAND_MODE}
						<li><a href="{devblocks_url}c=config&a=storage_content{/devblocks_url}">Content</a></li>
						<li><a href="{devblocks_url}c=config&a=storage_profiles{/devblocks_url}">Profiles</a></li>
					{/if}
					<li><a href="{devblocks_url}c=config&a=storage_attachments{/devblocks_url}">Attachments</a></li>
					
					{if !$smarty.const.ONDEMAND_MODE}
						{$exts = Extension_PageMenuItem::getExtensions(true, 'core.page.configuration','core.setup.menu.storage')}
						{if !empty($exts)}<li><hr></li>{/if}
						{foreach from=$exts item=menu_item}
							{if method_exists($menu_item,'render')}<li>{$menu_item->render()}</li>{/if}
						{/foreach}
					{/if}
				</ul>
			</div>
		</li>
		<li>
			<div>
				<a href="javascript:;" class="menu">Plugins <span class="cerb-sprite sprite-arrow-down-white"></span></a>
				<ul class="cerb-popupmenu cerb-float">
					<li><a href="{devblocks_url}c=config&a=plugins&tab=installed{/devblocks_url}">Installed Plugins</a></li>
					<li><a href="{devblocks_url}c=config&a=plugins&tab=library{/devblocks_url}">Plugin Library</a></li>
					
					{$exts = Extension_PageMenuItem::getExtensions(true, 'core.page.configuration','core.setup.menu.plugins')}
					{if !empty($exts)}<li><hr></li>{/if}
					{foreach from=$exts item=menu_item}
						{if method_exists($menu_item,'render')}<li>{$menu_item->render()}</li>{/if}
					{/foreach}
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
