<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formWorkerPeek" name="formWorkerPeek" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveWorkerPeek">
<input type="hidden" name="id" value="{$worker->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">

<table cellpadding="0" cellspacing="2" border="0" width="98%">
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top"><b>{$translate->_('worker.first_name')|capitalize}:</b> </td>
		<td width="100%"><input type="text" name="first_name" value="{$worker->first_name|escape}" class="required" style="width:98%;"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">{$translate->_('worker.last_name')|capitalize}: </td>
		<td width="100%"><input type="text" name="last_name" value="{$worker->last_name|escape}" style="width:98%;"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">{$translate->_('worker.title')|capitalize}: </td>
		<td width="100%"><input type="text" name="title" value="{$worker->title|escape}" style="width:98%;"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top"><b>{$translate->_('common.email')}</b>: </td>
		<td width="100%"><input type="text" name="email" value="{$worker->email|escape}" class="required" style="width:98%;"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top"><b>{$translate->_('common.password')|capitalize}</b>: </td>
		<td width="100%">
			<input id="password" type="password" name="password" value="" style="width:98%;"><br>
			{if empty($worker->id)}&nbsp;(Leave blank to e-mail a randomly-generated password.){/if}
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top">{$translate->_('Password (again)')}: </td>
		<td width="100%"><input id="password2" type="password" name="password2" value="" style="width:98%;"></td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right">{$translate->_('worker.is_superuser')|capitalize}: </td>
		<td width="100%">
			{if $active_worker->id == $worker->id}
				<input type="hidden" name="is_superuser" value="{$worker->is_superuser}">
				{if !$worker->is_superuser}{$translate->_('common.no')|capitalize}{else}{$translate->_('common.yes')|capitalize}{/if}
			{else}
				<select name="is_superuser">
					<option value="0" {if !$worker->is_superuser}selected{/if}>{$translate->_('common.no')|capitalize}</option>
					<option value="1" {if $worker->is_superuser}selected{/if}>{$translate->_('common.yes')|capitalize}</option>
				</select>
			{/if}
		</td>
	</tr>
	
	{if $active_worker->id == $worker->id}
		<input type="hidden" name="is_disabled" value="{$worker->is_disabled|escape}">
	{else}
	<tr>
		<td width="0%" nowrap="nowrap" align="right">{$translate->_('common.disabled')|capitalize}: </td>
		<td width="100%">
			<select name="is_disabled">
				<option value="0" {if !$worker->is_disabled}selected{/if}>{$translate->_('common.no')|capitalize}</option>
				<option value="1" {if $worker->is_disabled}selected{/if}>{$translate->_('common.yes')|capitalize}</option>
			</select>
		</td>
	</tr>
	{/if}
	
	<tr>
		<td width="100%" colspan="2">&nbsp;</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" align="right" valign="top"><b>{$translate->_('common.groups')|capitalize}:</b> </td>
		<td width="100%">
			{if $worker->id}{assign var=workerTeams value=$worker->getMemberships()}{/if}
			{foreach from=$teams item=team key=team_id}
			{assign var=member value=$workerTeams.$team_id}
			<input type="hidden" name="group_ids[]" value="{$team->id}">
			<select name="group_roles[]" {if $disabled} disabled="disabled"{/if}>
				<option value="">&nbsp;</option>
				<option value="1" {if $member && !$member->is_manager}selected{/if}>Member</option>
				<option value="2" {if $member && $member->is_manager}selected{/if}>Manager</option>
			</select>
			{$team->name}<br>
			{/foreach}
		</td>
	</tr>
</table>

{include file="file:$core_tpl/internal/custom_fields/bulk/form.tpl" bulk=false}
<br>

{if $active_worker->is_superuser}
	<button type="button" onclick="if($('#formWorkerPeek').validate().form()) { genericAjaxPopupPostCloseReloadView('peek','formWorkerPeek', '{$view_id}'); } "><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>
	{if !$disabled}
		{if !empty($worker)}{if $active_worker->is_superuser && $active_worker->id != $worker->id}<button type="button" onclick="if(confirm('Are you sure you want to delete this worker and their history?')) { this.form.do_delete.value='1';genericAjaxPopupPostCloseReloadView('peek','formWorkerPeek', '{$view_id}'); } "><span class="cerb-sprite sprite-delete2"></span> {$translate->_('common.delete')|capitalize}</button>{/if}{/if}
	{/if}
{else}
	<div class="error">{$translate->_('error.core.no_acl.edit')}</div>	
{/if}

<br>
</form>

<script type="text/javascript" language="JavaScript1.2">
	var $popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"Worker");
		$("#formWorkerPeek").validate( {
			rules: {
				password2: {
					equalTo: "#password"
				}
			},
			messages: {
				password2: {
					equalTo: "The passwords don't match."
				}
			}		
		} );
	} );
</script>

