<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="contact">
<input type="hidden" name="action" value="startBulkUpdateJson">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="ids" value="{$ids}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>{'common.bulk_update.with'|devblocks_translate|capitalize}</legend>
	<label><input type="radio" name="filter" value="" {if empty($ids)}checked{/if}> {'common.bulk_update.filter.all'|devblocks_translate}</label> 
	{if !empty($ids)}
		<label><input type="radio" name="filter" value="checks" {if !empty($ids)}checked{/if}> {'common.bulk_update.filter.checked'|devblocks_translate}</label> 
	{else}
		<label><input type="radio" name="filter" value="sample"> {'common.bulk_update.filter.random'|devblocks_translate} </label><input type="text" name="filter_sample_size" size="5" maxlength="4" value="100" class="input_number">
	{/if}
</fieldset>

<fieldset class="peek">
	<legend>Set Fields</legend>
	<table cellspacing="0" cellpadding="2" width="100%">

		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'common.organization'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<div class="cerb-ui-record-chooser" data-cerb-chooser="org_id"></div>
			</td>
		</tr>
		
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
			<td width="0%" nowrap="nowrap" align="right">{'common.gender'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<select name="gender" data-cerb-bulk-shortcuts="1,2">
					<option value=""></option>
					<option value="M">{'common.gender.male'|devblocks_translate|capitalize}</option>
					<option value="F">{'common.gender.female'|devblocks_translate|capitalize}</option>
					<option value="U">({'common.clear'|devblocks_translate|lower})</option>
				</select>
			</td>
		</tr>
		
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">Add watchers:</td>
			<td width="100%">
				<div>
					<div class="cerb-ui-record-chooser" data-cerb-chooser="do_watcher_add_ids"></div>
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="0%" nowrap="nowrap" align="right" valign="top">Remove watchers:</td>
			<td width="100%">
				<div>
					<div class="cerb-ui-record-chooser" data-cerb-chooser="do_watcher_remove_ids"></div>
				</div>
			</td>
		</tr>
		
		{if $active_worker->hasPriv("contexts.{CerberusContexts::CONTEXT_CONTACT}.delete")}
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right">{'common.status'|devblocks_translate|capitalize}:</td>
			<td width="100%"><select name="status" data-cerb-bulk-shortcuts="1">
				<option value=""></option>
				<option value="deleted">{'status.deleted'|devblocks_translate|capitalize}</option>
			</select>
			</td>
		</tr>
		{/if}
		
	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>Set Custom Fields</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=true}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/bulk_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_CONTACT}

{if $active_worker->hasPriv('contexts.cerberusweb.contexts.contact.broadcast')}
{include file="devblocks:cerberusweb.core::internal/views/bulk_broadcast.tpl" context=CerberusContexts::CONTEXT_CONTACT}
{/if}

<button type="button" class="submit"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
<br>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $popup = genericAjaxPopupFind('#formBatchUpdate');
	Devblocks.formDisableSubmit($popup);
	
	$popup.css('overflow', 'inherit');
	
	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'common.bulk_update'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		
		$popup.find('button.submit').click(function() {
			genericAjaxPost('formBatchUpdate', '', null, function(json) {
				if(json.job_id) {
					cerbOpenQueueJobPeek(json.job_id, '{$view_id}');
					$('#{$view_id}_tips').html('').hide();
				}
				
				genericAjaxPopupClose($popup);
			});
		});

		// Select shortcuts
		$popup.find('select[data-cerb-bulk-shortcuts]').cerbSelectShortcuts({ "attr": "data-cerb-bulk-shortcuts" });

		// Abstract choosers
		if(window.CerbUI && CerbUI.RecordChooser) {
			new CerbUI.RecordChooser($popup.find('[data-cerb-chooser="org_id"]')[0], { context: '{CerberusContexts::CONTEXT_ORG}', name: 'org_id', emptyIcon: 'building-office' });
			new CerbUI.RecordChooser($popup.find('[data-cerb-chooser="do_watcher_add_ids"]')[0], { context: '{CerberusContexts::CONTEXT_WORKER}', name: 'do_watcher_add_ids', multiple: true, emptyIcon: 'user', query: 'isDisabled:n' });
			new CerbUI.RecordChooser($popup.find('[data-cerb-chooser="do_watcher_remove_ids"]')[0], { context: '{CerberusContexts::CONTEXT_WORKER}', name: 'do_watcher_remove_ids', multiple: true, emptyIcon: 'user', query: 'isDisabled:n' });
		}
		
		{include file="devblocks:cerberusweb.core::internal/views/bulk_broadcast_jquery.tpl"}
	});
});
</script>