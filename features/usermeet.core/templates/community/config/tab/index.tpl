<table cellpadding="0" cellspacing="5" border="0" width="100%">
	<tr>
		
		<td width="0%" nowrap="nowrap" valign="top">
		
			<div class="block">
			<table cellpadding="2" cellspacing="0" border="0">
				<tr>
					<td>
						<h2>{$translate->_('usermeet.ui.community.cfg.communities')}</h2>
						[ <a href="javascript:;" onclick="genericAjaxGet('configCommunity','c=config&a=handleTabAction&tab=usermeet.config.tab.communities&action=getCommunity&id=0');">{$translate->_('usermeet.ui.community.cfg.add_community')|lower}</a> ]
					</td>
				</tr>
				<tr>
					<td>
						<div style="margin:0px;padding:3px;width:200px;overflow:auto;">
						{if !empty($communities)}
						{foreach from=$communities item=community key=community_id}
							<a href="javascript:;" onclick="genericAjaxGet('configCommunity','c=config&a=handleTabAction&tab=usermeet.config.tab.communities&action=getCommunity&id={$community->id}');"><b>{$community->name}</b></a><br>
							
							{assign var=tools value=$community_tools.$community_id}
								
							{if !empty($tools)}
							{foreach from=$tools item=tool key=tool_code}
								{assign var=tool_extid value=$tool->extension_id}
								{assign var=tool_mft value=$tool_manifests.$tool_extid}
								{if !empty($tool)}
								&#187;<a href="javascript:;" onclick="genericAjaxGet('configCommunity','c=config&a=handleTabAction&tab=usermeet.config.tab.communities&action=getCommunityTool&portal={$tool_code}');">{if !empty($tool->name)}{$tool->name}{else}{$tool_mft->name}{/if}</a><br>
								{/if}
							{/foreach}
							{/if}
						<br>
						{/foreach}
						{/if}
						</div>
					</td>
				</tr>
			</table>
			</div>
			
		</td>
		
		<td width="100%" valign="top">
			<div id="configCommunity">
				{include file="$path/community/config/tab/community_config.tpl" community=null}
			</div>
		</td>
		
	</tr>
</table>

<br>
