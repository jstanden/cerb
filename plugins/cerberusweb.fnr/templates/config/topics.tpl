<table cellpadding="0" cellspacing="5" border="0" width="100%">
	<tr>
		<td width="0%" nowrap="nowrap" valign="top">
			<div class="block">
			<table cellpadding="2" cellspacing="0" border="0">
				<tr>
					<td><h2>{$translate->_('fnr.ui.cfg.topics')|capitalize}</h2></td>
				</tr>
				<tr>
					<td nowrap="nowrap">
						[ <a href="javascript:;" onclick="genericAjaxGet('configFnrTopic','c=config&a=handleTabAction&tab=fnr.config.tab&action=getFnrTopic&id=0');">{$translate->_('fnr.ui.cfg.topics.add')}</a> ]
					</td>
				</tr>
				<tr>
					<td>
						{if !empty($fnr_topics)}
						<div style="margin:0px;padding:3px;width:200px;overflow:auto;">
							{foreach from=$fnr_topics item=fnr_topic}
							&#187; <a href="javascript:;" onclick="genericAjaxGet('configFnrTopic','c=config&a=handleTabAction&tab=fnr.config.tab&action=getFnrTopic&id={$fnr_topic->id}');">{$fnr_topic->name}</a><br>
							{/foreach}
						</div>
						{/if}
					</td>
				</tr>
			</table>
			</div>
		</td>
		
		<td width="100%" valign="top">
			<form action="{devblocks_url}{/devblocks_url}" method="post" id="configFnrTopic">
				{include file="$path/config/edit_fnr_topic.tpl" fnr_topic=null}
			</form>
		</td>
		
	</tr>
</table>
