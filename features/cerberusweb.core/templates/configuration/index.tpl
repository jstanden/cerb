<div class="cerb-menu">
	<ul>
		<li>
			<div>
				<a href="javascript:;" class="menu">{'common.configure'|devblocks_translate|capitalize} <span class="glyphicons glyphicons-chevron-down" style="color:white;"></span></a>
				<ul class="cerb-popupmenu cerb-float">
					<li><b>System</b></li>
					<li><a href="{devblocks_url}c=config&a=auth{/devblocks_url}">{'common.authentication'|devblocks_translate|capitalize}</a></li>
					<li><a href="{devblocks_url}c=config&a=branding{/devblocks_url}">Branding</a></li>
					{if !$smarty.const.DEVBLOCKS_CACHE_ENGINE_PREVENT_CHANGE}<li><a href="{devblocks_url}c=config&a=cache{/devblocks_url}">Cache</a></li>{/if}
					<li><a href="{devblocks_url}c=config&a=license{/devblocks_url}">License</a></li>
					<li><a href="{devblocks_url}c=config&a=localization{/devblocks_url}">Localization</a></li>
					<li><a href="{devblocks_url}c=config&a=scheduler{/devblocks_url}">Scheduler</a></li>
					{if !$smarty.const.DEVBLOCKS_SEARCH_ENGINE_PREVENT_CHANGE}<li><a href="{devblocks_url}c=config&a=search{/devblocks_url}">{'common.search'|devblocks_translate|capitalize}</a></li>{/if}
					<li><a href="{devblocks_url}c=config&a=security{/devblocks_url}">Security</a></li>
					<li><a href="{devblocks_url}c=config&a=sessions{/devblocks_url}">Sessions</a></li>

					{$exts = Extension_PageMenuItem::getExtensions(true, 'core.page.configuration','core.setup.menu.settings')}
					{if !empty($exts)}
						<li><hr></li>
						<li><b>{'common.plugins'|devblocks_translate|capitalize}</b></li>
					{/if}
					{foreach from=$exts item=menu_item}
						{if method_exists($menu_item,'render')}<li>{$menu_item->render()}</li>{/if}
					{/foreach}
				</ul>
			</div>
		</li>
		<li>
			<div>
				<a href="javascript:;" class="menu">{'common.records'|devblocks_translate|capitalize} <span class="glyphicons glyphicons-chevron-down" style="color:white;"></span></a>
				<ul class="cerb-popupmenu cerb-float">
					<li><a href="{devblocks_url}c=config&a=avatars{/devblocks_url}">Avatars</a></li>
					<li><a href="{devblocks_url}c=config&a=fields{/devblocks_url}">{'common.custom_fields'|devblocks_translate|capitalize}</a></li>
					<li><a href="{devblocks_url}c=config&a=snippets{/devblocks_url}">{'common.snippets'|devblocks_translate|capitalize}</a></li>

					{$exts = Extension_PageMenuItem::getExtensions(true, 'core.page.configuration','core.setup.menu.records')}
					{if !empty($exts)}<li><hr></li>{/if}
					{foreach from=$exts item=menu_item}
						{if method_exists($menu_item,'render')}<li>{$menu_item->render()}</li>{/if}
					{/foreach}
				</ul>
			</div>
		</li>
		<li>
			<div>
				<a href="javascript:;" class="menu">{'common.team'|devblocks_translate|capitalize} <span class="glyphicons glyphicons-chevron-down" style="color:white;"></span></a>
				<ul class="cerb-popupmenu cerb-float">
					<li><a href="{devblocks_url}c=config&a=team&w=roles{/devblocks_url}">{'common.roles'|devblocks_translate|capitalize}</a></li>
					<li><a href="{devblocks_url}c=config&a=team&w=groups{/devblocks_url}">{'common.groups'|devblocks_translate|capitalize}</a></li>
					<li><a href="{devblocks_url}c=config&a=team&w=workers{/devblocks_url}">{'common.workers'|devblocks_translate|capitalize}</a></li>

					{$exts = Extension_PageMenuItem::getExtensions(true, 'core.page.configuration','core.setup.menu.team')}
					{if !empty($exts)}<li><hr></li>{/if}
					{foreach from=$exts item=menu_item}
						{if method_exists($menu_item,'render')}<li>{$menu_item->render()}</li>{/if}
					{/foreach}
				</ul>
			</div>
		</li>
		<li>
			<div>
				<a href="javascript:;" class="menu">{'common.mail'|devblocks_translate|capitalize} <span class="glyphicons glyphicons-chevron-down" style="color:white;"></span></a>
				<ul class="cerb-popupmenu cerb-float">
					<li><a href="{devblocks_url}c=config&a=mail_incoming{/devblocks_url}">{'common.mail.incoming'|devblocks_translate|capitalize}</a></li>
					<li><a href="{devblocks_url}c=config&a=mail_outgoing{/devblocks_url}">{'common.mail.outgoing'|devblocks_translate|capitalize}</a></li>

					{$exts = Extension_PageMenuItem::getExtensions(true, 'core.page.configuration','core.setup.menu.mail')}
					{if !empty($exts)}<li><hr></li>{/if}
					{foreach from=$exts item=menu_item}
						{if method_exists($menu_item,'render')}<li>{$menu_item->render()}</li>{/if}
					{/foreach}
				</ul>
			</div>
		</li>
		<li>
			<div>
				<a href="javascript:;" class="menu">{'common.packages'|devblocks_translate|capitalize} <span class="glyphicons glyphicons-chevron-down" style="color:white;"></span></a>
				<ul class="cerb-popupmenu cerb-float">
					<li><a href="{devblocks_url}c=config&a=package_library{/devblocks_url}">{'common.library'|devblocks_translate|capitalize}</a></li>
					<li><a href="{devblocks_url}c=config&a=package_import{/devblocks_url}">{'common.import'|devblocks_translate|capitalize}</a></li>

					{$exts = Extension_PageMenuItem::getExtensions(true, 'core.page.configuration','core.setup.menu.packages')}
					{if !empty($exts)}<li><hr></li>{/if}
					{foreach from=$exts item=menu_item}
						{if method_exists($menu_item,'render')}<li>{$menu_item->render()}</li>{/if}
					{/foreach}
				</ul>
			</div>
		</li>
		<li>
			<div>
				<a href="javascript:;" class="menu">{'common.storage'|devblocks_translate|capitalize} <span class="glyphicons glyphicons-chevron-down" style="color:white;"></span></a>
				<ul class="cerb-popupmenu cerb-float">
					<li><a href="{devblocks_url}c=config&a=storage_content{/devblocks_url}">Overview</a></li>
					{if !$smarty.const.DEVBLOCKS_STORAGE_ENGINE_PREVENT_CHANGE}<li><a href="{devblocks_url}c=config&a=storage_profiles{/devblocks_url}">{'common.profiles'|devblocks_translate|capitalize}</a></li>{/if}
					<li><a href="{devblocks_url}c=config&a=storage_attachments{/devblocks_url}">{'common.objects'|devblocks_translate|capitalize}</a></li>

					{$exts = Extension_PageMenuItem::getExtensions(true, 'core.page.configuration','core.setup.menu.storage')}
					{if !empty($exts)}<li><hr></li>{/if}
					{foreach from=$exts item=menu_item}
						{if method_exists($menu_item,'render')}<li>{$menu_item->render()}</li>{/if}
					{/foreach}
				</ul>
			</div>
		</li>
		<li>
			<div>
				<a href="javascript:;" class="menu">{'common.developers'|devblocks_translate|capitalize} <span class="glyphicons glyphicons-chevron-down" style="color:white;"></span></a>
				<ul class="cerb-popupmenu cerb-float">
					<li><a href="{devblocks_url}c=config&a=bot_scripting_tester{/devblocks_url}">Bot Scripting Tester</a></li>
					<li><a href="{devblocks_url}c=config&a=data_query_tester{/devblocks_url}">Data Query Tester</a></li>
					<li><a href="{devblocks_url}c=config&a=oauth2_token_generator{/devblocks_url}">OAuth2 Token Generator</a></li>
					<li><a href="{devblocks_url}c=config&a=reference_icons{/devblocks_url}">Icon Reference</a></li>
					<li><a href="{devblocks_url}c=config&a=export_bots{/devblocks_url}">Export Bots</a></li>

					{$exts = Extension_PageMenuItem::getExtensions(true, 'core.page.configuration','core.setup.menu.developers')}
					{if !empty($exts)}<li><hr></li>{/if}
					{foreach from=$exts item=menu_item}
						{if method_exists($menu_item,'render')}<li>{$menu_item->render()}</li>{/if}
					{/foreach}
				</ul>
			</div>
		</li>
		<li>
			<div>
				<a href="javascript:;" class="menu">{'common.plugins'|devblocks_translate|capitalize} <span class="glyphicons glyphicons-chevron-down" style="color:white;"></span></a>
				<ul class="cerb-popupmenu cerb-float">
					<li><a href="{devblocks_url}c=config&a=plugins{/devblocks_url}">{'common.configure'|devblocks_translate|capitalize}</a></li>

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
	{$subpage_result = $subpage->render()}
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