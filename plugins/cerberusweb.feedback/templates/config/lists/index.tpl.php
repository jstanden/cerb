<table cellpadding="0" cellspacing="5" border="0" width="100%">
	<tr>
		
		<td width="0%" nowrap="nowrap" valign="top">
		
			<div class="block">
			<table cellpadding="2" cellspacing="0" border="0">
				<tr>
					<td><h2>Lists</h2></td>
				</tr>
				<tr>
					<td nowrap="nowrap">
						[ <a href="javascript:;" onclick="genericAjaxGet('configList','c=config&a=handleTabAction&tab=feedback.config.tab&action=getList&id=0');">add new list</a> ]
					</td>
				</tr>
				<tr>
					<td>
						{if !empty($lists)}
						<div style="margin:0px;padding:3px;width:200px;overflow:auto;">
							{foreach from=$lists item=list}
							&#187; <a href="javascript:;" onclick="genericAjaxGet('configList','c=config&a=handleTabAction&tab=feedback.config.tab&action=getList&id={$list->id}');">{$list->name}</a><br>
							{/foreach}
						</div>
						{/if}
					</td>
				</tr>
			</table>
			</div>
			
		</td>
		
		<td width="100%" valign="top">
			<form action="{devblocks_url}{/devblocks_url}" method="post" id="configList">
				{include file="$path/config/lists/edit_list.tpl.php" list=null}
			</form>
		</td>
		
	</tr>
</table>
