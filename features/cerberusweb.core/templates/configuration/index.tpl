<div class="cerb-menu">
	<ul>
		<li>
			<div>
				<a class="menu"><span class="cerb-icons cerb-icon-gear"></span> {'common.configure'|devblocks_translate|capitalize} <span class="cerb-icons cerb-icon-chevron-down"></span></a>
				<ul hidden>
					<li><a href="{devblocks_url}c=config&a=license{/devblocks_url}">Subscription</a></li>
					<li><hr></li>
					<li><a href="{devblocks_url}c=config&a=branding{/devblocks_url}">Branding</a></li>
					<li><a href="{devblocks_url}c=config&a=plugins{/devblocks_url}">{'common.plugins'|devblocks_translate|capitalize}</a></li>
					<li><a href="{devblocks_url}c=config&a=scheduler{/devblocks_url}">Scheduler</a></li>
					{if !$smarty.const.DEVBLOCKS_CACHE_ENGINE_PREVENT_CHANGE}<li><a href="{devblocks_url}c=config&a=cache{/devblocks_url}">Cache</a></li>{/if}
					<li><a href="{devblocks_url}c=config&a=localization{/devblocks_url}">Localization</a></li>

					{$exts = Extension_PageMenuItem::getExtensions(true, 'core.page.configuration','core.setup.menu.settings')}
					{if !empty($exts)}
						<li><hr></li>
					{/if}
					{foreach from=$exts item=menu_item}
						{if method_exists($menu_item,'render')}<li>{$menu_item->render()}</li>{/if}
					{/foreach}
				</ul>
			</div>
		</li>
		<li>
			<div>
				<a class="menu"><span class="cerb-icons cerb-icon-shield"></span> {'common.security'|devblocks_translate|capitalize} <span class="cerb-icons cerb-icon-chevron-down"></span></a>
				<ul hidden>
					<li><a href="{devblocks_url}c=config&a=security{/devblocks_url}">{'common.configure'|devblocks_translate|capitalize}</a></li>
					<li><a href="{devblocks_url}c=config&a=auth{/devblocks_url}">{'common.authentication'|devblocks_translate|capitalize}</a></li>
					<li><a href="{devblocks_url}c=config&a=service_tokens{/devblocks_url}">Service Tokens</a></li>
					<li><a href="{devblocks_url}c=config&a=sessions{/devblocks_url}">Active Sessions</a></li>

					{$exts = Extension_PageMenuItem::getExtensions(true, 'core.page.configuration','core.setup.menu.security')}
					{if !empty($exts)}
						<li><hr></li>
					{/if}
					{foreach from=$exts item=menu_item}
						{if method_exists($menu_item,'render')}<li>{$menu_item->render()}</li>{/if}
					{/foreach}
				</ul>
			</div>
		</li>
		<li>
			<div>
				<a class="menu"><span class="cerb-icons cerb-icon-collection"></span> {'common.records'|devblocks_translate|capitalize} <span class="cerb-icons cerb-icon-chevron-down"></span></a>
				<ul hidden>
					<li><a href="{devblocks_url}c=config&a=records{/devblocks_url}">Overview</a></li>

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
				<a class="menu"><span class="cerb-icons cerb-icon-users"></span> {'common.team'|devblocks_translate|capitalize} <span class="cerb-icons cerb-icon-chevron-down"></span></a>
				<ul hidden>
					<li><a href="{devblocks_url}c=config&a=team&w=config{/devblocks_url}">{'common.configure'|devblocks_translate|capitalize}</a></li>
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
				<a class="menu"><span class="cerb-icons cerb-icon-mail"></span> {'common.mail'|devblocks_translate|capitalize} <span class="cerb-icons cerb-icon-chevron-down"></span></a>
				<ul hidden>
					<li data-icon="inbox">{'common.mail.incoming'|devblocks_translate|capitalize}
						<ul>
							<li><a href="{devblocks_url}c=config&a=mail_incoming&tab=settings{/devblocks_url}">{'common.settings'|devblocks_translate|capitalize}</a></li>
							<li><a href="{devblocks_url}c=config&a=mail_incoming&tab=mailboxes{/devblocks_url}">{'common.mailboxes'|devblocks_translate|capitalize}</a></li>
							<li><a href="{devblocks_url}c=config&a=mail_incoming&tab=filtering{/devblocks_url}">{'common.mail.filtering'|devblocks_translate|capitalize}</a></li>
							<li><a href="{devblocks_url}c=config&a=mail_incoming&tab=routing{/devblocks_url}">{'common.mail.routing'|devblocks_translate|capitalize}</a></li>
							<li><a href="{devblocks_url}c=config&a=mail_incoming&tab=html{/devblocks_url}">HTML</a></li>
							<li><a href="{devblocks_url}c=config&a=mail_incoming&tab=import{/devblocks_url}">{'common.import'|devblocks_translate|capitalize}</a></li>
							<li><a href="{devblocks_url}c=config&a=mail_incoming&tab=failed{/devblocks_url}">Failed Messages</a></li>
							<li><a href="{devblocks_url}c=config&a=mail_incoming&tab=relay{/devblocks_url}">External Relay</a></li>
							<li><a href="{devblocks_url}c=config&a=mail_incoming&tab=log{/devblocks_url}">{'common.log'|devblocks_translate|capitalize}</a></li>
						</ul>
					</li>
					<li data-icon="send">{'common.mail.outgoing'|devblocks_translate|capitalize}
						<ul>
							<li><a href="{devblocks_url}c=config&a=mail_outgoing&tab=transports{/devblocks_url}">{'common.email_transports'|devblocks_translate|capitalize}</a></li>
							<li><a href="{devblocks_url}c=config&a=mail_outgoing&tab=senders{/devblocks_url}">{'common.sender_addresses'|devblocks_translate|capitalize}</a></li>
							<li><a href="{devblocks_url}c=config&a=mail_outgoing&tab=settings{/devblocks_url}">{'common.settings'|devblocks_translate|capitalize}</a></li>
							<li><a href="{devblocks_url}c=config&a=mail_outgoing&tab=templates{/devblocks_url}">Automated Email Templates</a></li>
							<li><a href="{devblocks_url}c=config&a=mail_outgoing&tab=queue{/devblocks_url}">{'common.queue'|devblocks_translate|capitalize}</a></li>
							<li><a href="{devblocks_url}c=config&a=mail_outgoing&tab=log{/devblocks_url}">{'common.log'|devblocks_translate|capitalize}</a></li>
						</ul>
					</li>

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
				<a class="menu"><span class="cerb-icons cerb-icon-cube"></span> {'common.packages'|devblocks_translate|capitalize} <span class="cerb-icons cerb-icon-chevron-down"></span></a>
				<ul hidden>
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
				<a class="menu"><span class="cerb-icons cerb-icon-database"></span> {'common.storage'|devblocks_translate|capitalize} <span class="cerb-icons cerb-icon-chevron-down"></span></a>
				<ul hidden>
					<li><a href="{devblocks_url}c=config&a=storage_content{/devblocks_url}">Overview</a></li>
					{if !$smarty.const.DEVBLOCKS_STORAGE_ENGINE_PREVENT_CHANGE}<li><a href="{devblocks_url}c=config&a=storage_profiles{/devblocks_url}">{'common.profiles'|devblocks_translate|capitalize}</a></li>{/if}

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
				<a class="menu"><span class="cerb-icons cerb-icon-console"></span> {'common.developers'|devblocks_translate|capitalize} <span class="cerb-icons cerb-icon-chevron-down"></span></a>
				<ul hidden>
					<li><a href="{devblocks_url}c=config&a=agent_filesystem_terminal{/devblocks_url}">Agent Filesystem Terminal</a></li>
					<li><a href="{devblocks_url}c=config&a=automation_events{/devblocks_url}">Automation Events</a></li>
					<li><a href="{devblocks_url}c=config&a=automation_logs{/devblocks_url}">Automation Logs</a></li>
					<li><a href="{devblocks_url}c=config&a=bot_scripting_tester{/devblocks_url}">Automation Scripting Tester</a></li>
					<li><a href="{devblocks_url}c=config&a=data_query_tester{/devblocks_url}">Data Query Tester</a></li>
					<li><a href="{devblocks_url}c=config&a=database_schema{/devblocks_url}">Database Schema</a></li>
					{if DevblocksPlatform::isPluginEnabled('cerb.behaviors.legacy')}<li><a href="{devblocks_url}c=config&a=export_bots{/devblocks_url}">Export Bots</a></li>{/if}
					<li><a href="{devblocks_url}c=config&a=icon_builder{/devblocks_url}">Icon Builder</a></li>
					<li><a href="{devblocks_url}c=config&a=llm_agent_transcripts{/devblocks_url}">LLM Agent Transcripts</a></li>
					<li><a href="{devblocks_url}c=config&a=oauth2_token_generator{/devblocks_url}">OAuth2 Token Generator</a></li>
					<li><a href="{devblocks_url}c=config&a=platform{/devblocks_url}">Platform</a></li>
					<li><a href="{devblocks_url}c=config&a=requirements{/devblocks_url}">Requirements Checker</a></li>
					<li><a href="{devblocks_url}c=config&a=sheet_builder{/devblocks_url}">Sheet Builder</a></li>
					<li><a href="{devblocks_url}c=config&a=toolbars{/devblocks_url}">Toolbars</a></li>
					<li><a href="{devblocks_url}c=config&a=ui_reference{/devblocks_url}">UI Reference</a></li>
					<li><a href="{devblocks_url}c=config&a=workflow_builder{/devblocks_url}">Workflow Builder</a></li>

					{$exts = Extension_PageMenuItem::getExtensions(true, 'core.page.configuration','core.setup.menu.developers')}
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
<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--warn cerb-u-mt-2">
	<div class="cerb-ui-header cerb-ui-header--center">
		<div class="cerb-ui-callout">
			<span class="cerb-icons cerb-icon-alert cerb-ui-callout--icon"></span>
			<div>
				<div class="cerb-ui-header--title-sm">Install Directory</div>
				<div class="cerb-ui-header--subtitle"><strong>Warning:</strong> The 'install' directory still exists.  This is a potential security risk.  Please delete it.</div>
			</div>
		</div>
	</div>
</div>
{/if}

{if !empty($subpage) && $subpage instanceof Extension_PageSection}
<div class="cerb-subpage" style="margin-top:10px;">
	{$subpage_result = $subpage->render()}
</div>
{/if}

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	if(!(window.CerbUI && CerbUI.Menu))
		return;

	// Each setup category opens on hover; only one is open at a time (shared hover group)
	document.querySelectorAll('.cerb-menu > ul > li').forEach(function(li) {
		const trigger = li.querySelector(':scope > div > a.menu');
		const submenu = li.querySelector(':scope > div > ul');

		if(!trigger || !submenu)
			return;

		submenu.hidden = true; // hide the source UL (covers legacy cerb-popupmenu extension menus)

		new CerbUI.Menu(submenu, {
			maxHeight: 'viewport',    // grow into the available viewport height (no scroll for one extra item)
			hoverTrigger: li,
			hoverGroup: 'setupmenu',
			onRenderItem: function(rendered, source) {
				const icon = source.dataset.icon;
				if(icon) {
					const ico = document.createElement('span');
					ico.className = 'cerb-icons cerb-icon-' + icon;
					ico.setAttribute('aria-hidden', 'true');
					ico.style.marginRight = '0.5em';
					rendered.insertBefore(ico, rendered.firstChild);
				}
			}
			// default onSelect clicks the source <a href> -> navigates
		});
	});
});
</script>