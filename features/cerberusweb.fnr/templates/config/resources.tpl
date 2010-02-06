<table cellpadding="0" cellspacing="5" border="0" width="100%">
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">
			<div class="block">
			<table cellpadding="2" cellspacing="0" border="0">
				<tr>
					<td><h2>{$translate->_('fnr.ui.cfg.resources')|capitalize}</h2></td>
				</tr>
				<tr>
					<td nowrap="nowrap">
						[ <a href="javascript:;" onclick="genericAjaxGet('configFnrResource','c=config&a=handleTabAction&tab=fnr.config.tab&action=getFnrResource&id=0');">{$translate->_('fnr.ui.cfg.resources.add')}</a> ]
					</td>
				</tr>
				<tr>
					<td>
						{if !empty($fnr_topics) && !empty($fnr_resources)}
							{foreach from=$fnr_topics key=fnr_topic_id item=fnr_topic}
							<b>{$fnr_topic->name}</b><br>
								{foreach from=$fnr_resources item=fnr_resource}
								{if $fnr_topic_id==$fnr_resource->topic_id}
									&#187; <a href="javascript:;" onclick="genericAjaxGet('configFnrResource','c=config&a=handleTabAction&tab=fnr.config.tab&action=getFnrResource&id={$fnr_resource->id}');">{$fnr_resource->name}</a><br>
								{/if}
								{/foreach}
							{/foreach}
						{/if}
					</td>
				</tr>
			</table>
			</div>
		</td>
		
		<td width="100%" valign="top">
			<form action="{devblocks_url}{/devblocks_url}" method="post" id="configFnrResource">
				{include file="$path/config/edit_fnr_resource.tpl" fnr_resource=null}
			</form>
		</td>
		
	</tr>
</table>
