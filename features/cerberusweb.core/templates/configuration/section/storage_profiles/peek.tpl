{if !empty($profile->id) && !empty($storage_schema_stats)}
<div class="error-box">
	<h1>
		<span class="cerb-icons cerb-icon-alert"></span>
		Warning!
	</h1>
	<p>
		You are changing the configuration of an active storage profile.  Unless you are very careful you may lose content.  You cannot delete this profile until you've migrated its content to another location.
	</p>
</div>
{/if}

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formStorageProfilePeek" name="formStorageProfilePeek">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="storage_profiles">
<input type="hidden" name="action" value="saveStorageProfilePeek">
<input type="hidden" name="id" value="{$profile->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<b>{'common.name'|devblocks_translate|capitalize}:</b><br>
<input type="text" name="name" value="{$profile->name}" style="width:98%;" autofocus="true"><br>
<br>

<fieldset>

{if empty($profile->id)}
	<legend>Create a new storage profile</legend>
	
	<b>Storage Engine:</b> 
	<select name="extension_id">
		{foreach from=$engines item=engine_mft key=engine_id}
		<option value="{$engine_id}" {if $profile->extension_id==$engine_id}selected="selected"{/if}>{$engine_mft->name}</option>
		{/foreach}
	</select>
{else}
	{$profile_extid = $profile->extension_id}
	{if isset($engines.$profile_extid)}
		<legend>{$engines.$profile_extid->name} ({$profile->extension_id})</legend>
	{else}
		<legend>{$profile->extension_id}</legend>
	{/if}
	<input type="hidden" name="extension_id" value="{$profile->extension_id}">
{/if}

<div id="divStorageEngineSettings" style="margin:5px 0px 0px 10px;display:{if 1}block{else}none{/if};">
	{if !empty($storage_engine) && $storage_engine instanceof Extension_DevblocksStorageEngine}
		{$storage_engine->renderConfig($profile)}
	{/if}
</div>

</fieldset>

{if !empty($storage_schema_stats)}
Used by:<br>
{foreach from=$storage_schema_stats item=stats key=schema_id}
	<b>{$storage_schemas.{$schema_id}->name}</b>: {$stats.count} objects ({$stats.bytes|devblocks_prettybytes})<br>
{/foreach}
<br>
{/if}

{if $active_worker->is_superuser}
	<button type="button" value="saveStorageProfilePeek" class="submit"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate}</button>
	{if !empty($profile->id) && empty($storage_schema_stats)}<button type="button" class="delete"><span class="cerb-icons cerb-icon-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
{else}
	<div class="error">{'error.core.no_acl.edit'|devblocks_translate}</div>	
{/if}
<button type="button" class="tester" value="testProfileJson"><span class="cerb-icons cerb-icon-gear"></span> Test</button>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#formStorageProfilePeek');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);
	
	$popup.one('popup_open', function() {
		$(this).dialog('option','title',"Storage Profile");

		$frm.find('select[name=extension_id]').on('change', function(e) {
			e.stopPropagation();
			genericAjaxGet('divStorageEngineSettings','c=config&a=invoke&module=storage_profiles&action=showStorageProfileConfig&ext_id='+encodeURIComponent(selectValue(this))+'&id='+encodeURIComponent(this.form.id.value));
		});

		$frm.find('BUTTON.submit').on('click', function(e) {
			e.stopPropagation();
			$(this.form).find('input:hidden[name=action]').val($(this).val());
			genericAjaxPopupPostCloseReloadView(null,'formStorageProfilePeek', '{$view_id}');
		});

		$frm.find('BUTTON.delete').on('click', function(e) {
			e.stopPropagation();

			confirmPopup(
				'Delete',
				'Are you sure you want to permanently delete this storage profile?',
				function () {
					$frm.find('input[name=do_delete]').val('1');
					genericAjaxPopupPostCloseReloadView(null,'formStorageProfilePeek', '{$view_id}');
				}
			);
		});

		$frm.find('BUTTON.tester')
		.click(function(e) {
			e.stopPropagation();
			Devblocks.clearAlerts();

			let $btn = $(this);

			$frm.find('input:hidden[name=action]').val($btn.val());
			
			genericAjaxPost('formStorageProfilePeek',null,null,function(json) {
				if(json && typeof json == 'object') {
					if (json.error) {
						Devblocks.createAlertError(json.error);
					} else if (json.hasOwnProperty('message')) {
						Devblocks.createAlert(json.message, null, 5000);
					} else {
						Devblocks.createAlert('Saved!', null, 5000);
					}
				}
			});			
		});
	});
});
</script>
