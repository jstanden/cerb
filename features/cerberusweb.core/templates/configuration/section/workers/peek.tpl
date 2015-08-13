<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formWorkerPeek" name="formWorkerPeek" onsubmit="return false;">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="workers">
<input type="hidden" name="action" value="saveWorkerPeek">
<input type="hidden" name="id" value="{$worker->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate|capitalize}</legend>
	
	<table cellpadding="0" cellspacing="2" border="0" width="98%">
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="middle"><b>{'worker.first_name'|devblocks_translate|capitalize}:</b> </td>
			<td width="100%"><input type="text" name="first_name" value="{$worker->first_name}" class="required" style="width:98%;"></td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="middle">{'worker.last_name'|devblocks_translate|capitalize}: </td>
			<td width="100%"><input type="text" name="last_name" value="{$worker->last_name}" style="width:98%;"></td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="middle">{'worker.title'|devblocks_translate|capitalize}: </td>
			<td width="100%"><input type="text" name="title" value="{$worker->title}" style="width:98%;"></td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="middle"><b>{'common.email'|devblocks_translate}</b>: </td>
			<td width="100%"><input type="text" name="email" value="{$worker->email}" class="required email" style="width:98%;"></td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="middle">{'worker.at_mention_name'|devblocks_translate}: </td>
			<td width="100%"><input type="text" name="at_mention_name" value="{$worker->at_mention_name}" style="width:98%;" placeholder="UserNickname"></td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'common.status'|devblocks_translate|capitalize}: </td>
			<td width="100%">
				{if $active_worker->id == $worker->id}
					<input type="hidden" name="is_disabled" value="{$worker->is_disabled}">
					{if $worker->is_disabled}{'common.inactive'|devblocks_translate|capitalize}{else}{'common.active'|devblocks_translate|capitalize}{/if}
				{else}
					<label><input type="radio" name="is_disabled" value="0" {if !$worker->is_disabled}checked="checked"{/if}>{'common.active'|devblocks_translate|capitalize}</label>
					<label><input type="radio" name="is_disabled" value="1" {if $worker->is_disabled}checked="checked"{/if}>{'common.inactive'|devblocks_translate|capitalize}</label>
				{/if}
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'common.privileges'|devblocks_translate|capitalize}: </td>
			<td width="100%">
				{if $active_worker->id == $worker->id}
					<input type="hidden" name="is_superuser" value="{$worker->is_superuser}">
					{if !$worker->is_superuser}{'common.worker'|devblocks_translate|capitalize}{else}{'worker.is_superuser'|devblocks_translate|capitalize}{/if}
				{else}
					<label><input type="radio" name="is_superuser" value="0" {if !$worker->is_superuser}checked="checked"{/if}>{'common.worker'|devblocks_translate|capitalize}</label>
					<label><input type="radio" name="is_superuser" value="1" {if $worker->is_superuser}checked="checked"{/if}>{'worker.is_superuser'|devblocks_translate|capitalize}</label>
				{/if}
			</td>
		</tr>
	</table>
</fieldset>

<fieldset class="peek">
	<legend>{'common.localization'|devblocks_translate|capitalize}</legend>
	
	<table cellpadding="0" cellspacing="2" border="0" width="98%">
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="middle">{'worker.language'|devblocks_translate}: </td>
			<td width="100%">
				<select name="lang_code">
					{foreach from=$languages key=lang_code item=lang_name}
					<option value="{$lang_code}" {if $worker->language==$lang_code}selected="selected"{/if}>{$lang_name}</option>
					{/foreach}
				</select>
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="middle">{'worker.timezone'|devblocks_translate}: </td>
			<td width="100%">
				<select name="timezone">
					{foreach from=$timezones item=timezone}
					<option value="{$timezone}" {if $worker->timezone==$timezone}selected="selected"{/if}>{$timezone}</option>
					{/foreach}
				</select>
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="middle">{'worker.time_format'|devblocks_translate}: </td>
			<td width="100%">
				<select name="time_format">
					{$timeformats = ['D, d M Y h:i a', 'D, d M Y H:i']}
					{foreach from=$timeformats item=timeformat}
						<option value="{$timeformat}" {if $worker->time_format==$timeformat}selected{/if}>{time()|devblocks_date:$timeformat}</option>
					{/foreach}
				</select>
			</td>
		</tr>
	</table>
</fieldset>
	
<fieldset class="peek">
	<legend>Login</legend>
	
	<table cellpadding="0" cellspacing="2" border="0" width="98%">
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top"><b>Authentication</b>: </td>
			<td width="100%">
				<select name="auth_extension_id">
					{foreach from=$auth_extensions item=auth_ext_mft key=auth_ext_id}
					<option value="{$auth_ext_id}" {if $worker->auth_extension_id==$auth_ext_id}selected="selected"{/if}>{$auth_ext_mft->name}</option>
					{/foreach}
				</select>
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">New Password: </td>
			<td width="100%">
				<input type="password" name="password_new" value=""  style="width:90%;" placeholder="{if $worker->id}(leave blank for unchanged){else}(leave blank to send a random password by email){/if}">
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">Verify Password: </td>
			<td width="100%">
				<input type="password" name="password_verify" value="" style="width:90%;">
			</td>
		</tr>
	</table>
</fieldset>

<fieldset class="peek">
	<legend>{'common.availability'|devblocks_translate|capitalize}</legend>
	
	<b>{'preferences.account.availability.calendar_id'|devblocks_translate}</b><br>
	
	<div style="margin-left:10px;">
		<select name="calendar_id">
			<option value="">- always unavailable -</option>
			{foreach from=$calendars item=calendar}
			<option value="{$calendar->id}" {if $calendar->id==$worker->calendar_id}selected="selected"{/if}>{$calendar->name}</option>
			{foreachelse}
			<option value="new" {if empty($worker->id)}selected="selected"{/if}>Create a new calendar</option>
			{/foreach}
		</select>
	</div>
</fieldset>

<fieldset class="peek">
	<legend>{'common.groups'|devblocks_translate|capitalize}</legend>
	
	{if $worker->id}{assign var=workerGroups value=$worker->getMemberships()}{/if}
	{foreach from=$groups item=group key=group_id}
	{assign var=member value=$workerGroups.$group_id}
	<input type="hidden" name="group_ids[]" value="{$group->id}">
	<select name="group_roles[]" {if $disabled} disabled="disabled"{/if}>
		<option value="">&nbsp;</option>
		<option value="1" {if $member && !$member->is_manager}selected{/if}>Member</option>
		<option value="2" {if $member && $member->is_manager}selected{/if}>Manager</option>
	</select>
	{$group->name}<br>
	{/foreach}
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_WORKER context_id=$worker->id}

{if $active_worker->is_superuser}
	<button type="button" onclick="if($('#formWorkerPeek').validate().form()) { genericAjaxPopupPostCloseReloadView(null,'formWorkerPeek', '{$view_id}', false, 'worker_save'); } "><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate}</button>
	{if !$disabled}
		{if !empty($worker->id)}{if $active_worker->is_superuser && $active_worker->id != $worker->id}<button type="button" onclick="if(confirm('Are you sure you want to delete this worker and their history?')) { this.form.do_delete.value='1';genericAjaxPopupPostCloseReloadView(null,'formWorkerPeek', '{$view_id}'); } "><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}{/if}
	{/if}
{else}
	<div class="error">{'error.core.no_acl.edit'|devblocks_translate}</div>	
{/if}

<br>
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('peek');
	
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"Worker");
		
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
		});
		
		$(this).find('input:text:first').select().focus();
	});
});
</script>