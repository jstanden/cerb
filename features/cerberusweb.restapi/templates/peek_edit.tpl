{$peek_context = CerberusContexts::CONTEXT_WEBAPI_CREDENTIAL}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="webapi_credentials">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellpadding="0" cellspacing="5" border="0" width="98%">
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right"><b>{'common.name'|devblocks_translate}:</b></td>
		<td width="100%">
			<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
			<div>
				<i>(e.g. "Server monitoring", "Call center integration")</i>
			</div>
		</td>
	</tr>
	
	{* Owner *}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top" align="right"><b>{'common.worker'|devblocks_translate|capitalize}:</b></td>
		<td width="99%" valign="top">
			{if !empty($model)}
				<ul class="bubbles chooser-container">
					{$owner = $model->getWorker()}
					{if $owner}
						<li><img class="cerb-avatar" src="{devblocks_url}c=avatars&context=worker&context_id={$owner->id}{/devblocks_url}?v={$owner->updated}"><input type="hidden" name="owner_id" value="{$owner->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_WORKER}" data-context-id="{$owner->id}">{$owner->getName()}</a></li>
					{/if}
				</ul>
			{else}
				<button type="button" class="chooser-abstract" data-field-name="worker_id" data-context="{CerberusContexts::CONTEXT_WORKER}" data-single="true" data-query="isDisabled:n" data-autocomplete="" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
				
				<ul class="bubbles chooser-container">
				</ul>
			{/if}
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
	{*
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right"><b>QR Code:</b></td>
		<td width="100%">
			<div class="qrcode"></div>
		</td>
	</tr>
	*}
	{/if}
	
	<tr>
		<td width="0%" nowrap="nowrap" valign="top" align="right"><b>Allowed Endpoints:</b></td>
		<td width="100%">
			<textarea rows="8" cols="60" style="width:100%;" name="params[allowed_paths]">{if empty($model)}*{elseif is_array($model->params.allowed_paths)}{$model->params.allowed_paths|implode:"\n"}{/if}</textarea>
			<div>
				<i>(one per line; use * for wildcards; e.g. tasks/*)</i>
			</div>
		</td>
	</tr>
</table>
	
{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete these Web API credentials? 
		Any applications or services that are using these credentials will no longer be able to access the API.
	</div>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'webapi.common.api_credentials'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		// Abstract choosers
		$popup.find('button.chooser-abstract').cerbChooserTrigger();
		
		// Abstract peeks
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		
		{if !empty($model)}
		// QR Code
		/*
		var options = { width:150, height:150, text:"{$model->access_key}:{$model->secret_key}" };
		var hasCanvasSupport = !!window.CanvasRenderingContext2D;

		// If no <canvas> tag, use <table> instead
		if(!hasCanvasSupport)
			options.render = 'table';

		$popup.find('div.qrcode').qrcode(options);
		*/
		{/if}
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>
