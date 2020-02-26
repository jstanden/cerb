<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="worker">
<input type="hidden" name="action" value="startBulkUpdateJson">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="ids" value="{$ids}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>{'common.bulk_update.with'|devblocks_translate|capitalize}</legend>
	
	<label><input type="radio" name="filter" value="" {if empty($ids)}checked{/if}> {'common.bulk_update.filter.all'|devblocks_translate}</label> 
	<label><input type="radio" name="filter" value="checks" {if !empty($ids)}checked{/if}> {'common.bulk_update.filter.checked'|devblocks_translate}</label> 
</fieldset>

<fieldset class="peek">
	<legend>Set Fields</legend>
	<table cellspacing="0" cellpadding="2" width="100%">
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'common.title'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<input type="text" name="title" size="45" value="" style="width:90%;">
			</td>
		</tr>
		
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'common.location'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<input type="text" name="location" size="45" value="" style="width:90%;">
			</td>
		</tr>
		
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'common.gender'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<select name="gender">
					<option value=""></option>
					<option value="M">{'common.gender.male'|devblocks_translate|capitalize}</option>
					<option value="F">{'common.gender.female'|devblocks_translate|capitalize}</option>
				</select>
				<button type="button" onclick="this.form.gender.selectedIndex = 1;">{'common.gender.male'|devblocks_translate|lower}</button>
				<button type="button" onclick="this.form.gender.selectedIndex = 2;">{'common.gender.female'|devblocks_translate|lower}</button>
			</td>
		</tr>
		
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'common.language'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<select name="language">
					<option value=""></option>
					{foreach from=$languages item=lang key=lang_code}
					<option value="{$lang_code}">{$lang}</option>
					{/foreach}
				</select>
			</td>
		</tr>
	
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'common.timezone'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<select name="timezone">
					<option value=""></option>
					{foreach from=$timezones item=tz}
					<option value="{$tz}">{$tz}</option>
					{/foreach}
				</select>
			</td>
		</tr>
	
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'common.disabled'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<select name="is_disabled">
					<option value="">&nbsp;</option>
					<option value="0">{'common.no'|devblocks_translate}</option>
					<option value="1">{'common.yes'|devblocks_translate}</option>
				</select>
				
				<button type="button" onclick="this.form.is_disabled.selectedIndex=1;">{'common.no'|devblocks_translate}</button>
				<button type="button" onclick="this.form.is_disabled.selectedIndex=2;">{'common.yes'|devblocks_translate}</button>
			</td>
		</tr>
		
		<tr>
			<td width="0%" nowrap="nowrap" align="right">
				{'worker.is_password_disabled'|devblocks_translate|capitalize}:
				<span class="glyphicons glyphicons-circle-question-mark" title="When a worker's password is disabled, they may only log in using a trusted Single Sign-On (SSO) identity."></span>
			</td>
			<td width="100%">
				<select name="is_password_disabled">
					<option value="">&nbsp;</option>
					<option value="0">{'common.no'|devblocks_translate}</option>
					<option value="1">{'common.yes'|devblocks_translate}</option>
				</select>
				
				<button type="button" onclick="this.form.is_password_disabled.selectedIndex=1;">{'common.no'|devblocks_translate}</button>
				<button type="button" onclick="this.form.is_password_disabled.selectedIndex=2;">{'common.yes'|devblocks_translate}</button>
			</td>
		</tr>
		
		<tr>
			<td width="0%" nowrap="nowrap" align="right">
				{'worker.is_mfa_required'|devblocks_translate|capitalize}:
				<span class="glyphicons glyphicons-circle-question-mark" title="Multi-Factor Authentication (MFA) requires both a password (something you know) and a one-time code from a device in your physical possession (something you have)."></span>
			</td>
			<td width="100%">
				<select name="is_mfa_required">
					<option value="">&nbsp;</option>
					<option value="0">{'common.no'|devblocks_translate}</option>
					<option value="1">{'common.yes'|devblocks_translate}</option>
				</select>
				
				<button type="button" onclick="this.form.is_mfa_required.selectedIndex=1;">{'common.no'|devblocks_translate}</button>
				<button type="button" onclick="this.form.is_mfa_required.selectedIndex=2;">{'common.yes'|devblocks_translate}</button>
			</td>
		</tr>
		
	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>Set Custom Fields</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=true}	
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_WORKER bulk=true}

{if $active_worker->hasPriv('contexts.cerberusweb.contexts.worker.broadcast')}
{include file="devblocks:cerberusweb.core::internal/views/bulk_broadcast.tpl" context=CerberusContexts::CONTEXT_WORKER}
{/if}

<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#formBatchUpdate');
	$popup.css('overflow', 'inherit');
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.bulk_update'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		
		$popup.find('button.submit').click(function() {
			genericAjaxPost('formBatchUpdate', '', null, function(json) {
				if(json.cursor) {
					// Pull the cursor
					var $tips = $('#{$view_id}_tips').html('');
					$('<span class="cerb-ajax-spinner"/>').appendTo($tips);

					var formData = new FormData();
					formData.set('c', 'internal');
					formData.set('a', 'invoke');
					formData.set('module', 'worklists');
					formData.set('action', 'viewBulkUpdateWithCursor');
					formData.set('view_id', '{$view_id}');
					formData.set('cursor', json.cursor);

					genericAjaxPost(formData, $tips, null);
				}
				
				genericAjaxPopupClose($popup);
			});
		});
		
		{include file="devblocks:cerberusweb.core::internal/views/bulk_broadcast_jquery.tpl"}
	});
});
</script>