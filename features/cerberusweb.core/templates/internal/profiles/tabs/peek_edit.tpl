{$peek_context = 'cerberusweb.contexts.profile.tab'}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="profile_tab">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<table cellspacing="0" cellpadding="2" border="0" width="98%">
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.record'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{if $model->id}
				{$model->context}
			{else}
				<select name="context">
					{foreach from=$context_mfts item=context_mft}
					<option value="{$context_mft->id}" {if $context_mft->id == $model->context}selected="selected"{/if}>{$context_mft->name}</option>
					{/foreach}
				</select>
			{/if}
		</td>
	</tr>
	<tr>
		<td width="1%" nowrap="nowrap"><b>{'common.type'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{if $model->id}
				{$model->extension_id}
			{else}
				<select name="extension_id">
					<option value=""></option>
					{foreach from=$tab_manifests item=tab_manifest}
					<option value="{$tab_manifest->id}">{$tab_manifest->name}</option>
					{/foreach}
				</select>
			{/if}
		</td>
	</tr>

	{if !empty($custom_fields)}
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
	{/if}
</table>

{* The rest of config comes from the tab *}
<div class="cerb-widget-params">
{if $model->id}
	{$tab_extension = $model->getExtension()}
	{if $tab_extension && method_exists($tab_extension,'renderConfig')}
		{$tab_extension->renderConfig($model)}
	{/if}
{/if}
</div>

{*
<div class="cerb-placeholder-menu" style="display:none;">
{include file="devblocks:cerberusweb.core::internal/profiles/tabs/dashboard/toolbar.tpl"}
</div>
*}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}

{if !empty($model->id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	<div>
		Are you sure you want to permanently delete this profile tab?
	</div>
	
	<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="status"></div>

<div class="buttons" style="margin-top:10px;">
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'Profile Tab'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');
		
		var $select = $popup.find('select[name=extension_id]');
		var $params = $popup.find('.cerb-widget-params');
		
		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		// Events
		
		// Load per-extension configuration on change
		$select.on('change', function(e) {
			var extension_id = $select.val();
			genericAjaxGet($params, 'c=profiles&a=handleSectionAction&section=profile_tab&action=getExtensionConfig&extension_id=' + encodeURIComponent(extension_id));
		});
	});
});
</script>
