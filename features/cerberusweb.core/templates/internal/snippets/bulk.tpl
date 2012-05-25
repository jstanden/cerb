<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="doSnippetBulkUpdate">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="ids" value="{$ids}">

<fieldset>
	<legend>{$translate->_('common.bulk_update.with')|capitalize}</legend>
	<label><input type="radio" name="filter" value="" {if empty($ids)}checked{/if}> {$translate->_('common.bulk_update.filter.all')}</label> 
 	{if !empty($ids)}
		<label><input type="radio" name="filter" value="checks" {if !empty($ids)}checked{/if}> {$translate->_('common.bulk_update.filter.checked')}</label> 
	{else}
		<label><input type="radio" name="filter" value="sample"> {'common.bulk_update.filter.random'|devblocks_translate} </label><input type="text" name="filter_sample_size" size="5" maxlength="4" value="100" class="input_number">
	{/if}
</fieldset>

<fieldset>
	<legend>Set Fields</legend>
	<table cellspacing="0" cellpadding="2" width="100%">
		{if $active_worker->is_superuser}
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'common.owner'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<select name="owner">
					<option value=""></option>
					
					{if !empty($roles)}
					{foreach from=$roles item=role key=role_id}
						<option value="{CerberusContexts::CONTEXT_ROLE}:{$role_id}">Role: {$role->name}</option>
					{/foreach}
					{/if}
					
					{if !empty($groups)}
					{foreach from=$groups item=group key=group_id}
						<option value="{CerberusContexts::CONTEXT_GROUP}:{$group_id}">Group: {$group->name}</option>
					{/foreach}
					{/if}
					
					{foreach from=$workers item=worker key=worker_id}
						{if empty($worker->is_disabled)}
						<option value="{CerberusContexts::CONTEXT_WORKER}:{$worker_id}">Worker: {$worker->getName()}</option>
						{/if}
					{/foreach}
				</select>				
			</td>
		</tr>
		{/if}
	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset>
	<legend>Set Custom Fields</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=true}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/macros/behavior/bulk.tpl" macros=$macros}

<button type="button" onclick="genericAjaxPopupClose('peek');genericAjaxPost('formBatchUpdate','view{$view_id}');"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')|capitalize}</button>
<br>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{$translate->_('common.bulk_update')|capitalize}");
	});
</script>
