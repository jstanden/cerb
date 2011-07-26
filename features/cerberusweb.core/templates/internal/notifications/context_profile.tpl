{$context_notifications = DAO_Notification::getUnreadByContextAndWorker($context, $context_id, $active_worker->id, true)}

{if !empty($context_notifications)}
<fieldset class="properties" style="color:rgb(100,100,100);">
	<legend>{'header.notifications.unread'|devblocks_translate:{$context_notifications|count}}</legend>

	<table cellpadding="3" cellspacing="2" border="0" width="100%">
	{foreach from=$context_notifications item=v key=k name=notifications}
		<tr {if $smarty.foreach.notifications.iteration > 5}style="display:none;"{/if}>
			<td valign="top" align="right" width="1%" nowrap="nowrap">
				<div class="badge badge-lightgray" title="{$v->created_date|devblocks_date}">{$v->created_date|devblocks_prettytime}</div>
			</td>
			<td width="99%">
				{$v->message}
			</td>
		</tr>
	{/foreach}
	
	{if $smarty.foreach.notifications.total > 5}
		<tr>
			<td></td>
			<td>
				<a href="javascript:;" style="font-weight:bold;" onclick="$(this).closest('fieldset').find('table tr:hidden').show();$(this).remove();">show all {$smarty.foreach.notifications.total}</a>
			</td>
		</tr>
	{/if}
	</table>
	
	
</fieldset>
{/if}