<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmWebApiCredentials" name="frmWebApiCredentials" onsubmit="return false;">
<input type="hidden" name="c" value="preferences">
<input type="hidden" name="a" value="handleTabAction">
<input type="hidden" name="tab" value="rest.preferences.tab.api">
<input type="hidden" name="action" value="savePeekPopup">
<input type="hidden" name="id" value="{$model->id|default:'0'}">
<input type="hidden" name="do_delete" value="0">

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate}</legend>
	
	<table cellpadding="0" cellspacing="5" border="0" width="98%">
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right"><b>{'common.label'|devblocks_translate|capitalize}:</b></td>
			<td width="100%">
				<input type="text" name="label" style="width:98%;" value="{$model->label}">
				<div>
					<i>(e.g. "Server monitoring", "Call center integration")</i>
				</div>
			</td>
		</tr>
		
		{if !empty($model)}
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right"><b>{'dao.webapi_credentials.access_key'|devblocks_translate|capitalize}:</b></td>
			<td width="100%">
				<span class="tag tag-gray">{$model->access_key}</span>
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right"><b>{'dao.webapi_credentials.secret_key'|devblocks_translate|capitalize}:</b></td>
			<td width="100%">
				<span class="tag tag-gray">{$model->secret_key}</span>
				<div>
					<label><input type="checkbox" name="regenerate_keys" value="1"> Generate new keys</label>
				</div>
			</td>
		</tr>
		{/if}
		
		<tr>
			<td width="0%" nowrap="nowrap" valign="top" align="right"><b>Allowed paths:</b></td>
			<td width="100%">
				<textarea rows="8" cols="60" style="width:100%;" name="params[allowed_paths]">{if empty($model)}*{else}{$model->params.allowed_paths|implode:"\n"}{/if}</textarea>
				<div>
					<i>(one per line; use * for wildcards; e.g. tasks/*)</i>
				</div>
			</td>
		</tr>
		
	</table>
</fieldset>

<fieldset class="delete" style="display:none;">
	<legend>Are you sure you want to delete these credentials?</legend>
	<p>
		Any applications or services that are using these credentials will no longer be able to access the API.
	</p>
	<button type="button" class="red" onclick="$frm=$('#frmWebApiCredentials'); $frm.find('input[name=do_delete]').val('1'); $frm.find('button.submit').click();">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('fieldset').nextAll('.toolbar').fadeIn();$(this).closest('fieldset').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>

{if 1}
	<div class="toolbar">
		<button type="button" class="submit" onclick="genericAjaxPopupPostCloseReloadView(null,'frmWebApiCredentials','{$view_id}',false,'webapi_save');"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')}</button>
		{if !empty($model)}<button type="button" onclick="$toolbar=$(this).closest('div.toolbar').fadeOut();$toolbar.siblings('fieldset.delete').fadeIn();"><span class="cerb-sprite2 sprite-cross-circle"></span> {$translate->_('common.delete')|capitalize}</button>{/if}
	</div>
{else}
	<fieldset class="delete">
		{'error.core.no_acl.edit'|devblocks_translate}
	</fieldset>
{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title','{'webapi.common.api_credentials'|devblocks_translate|capitalize|escape:'javascript'}');
		$('#frmWebApiCredentials :input:text:first').focus().select();
	});
</script>
