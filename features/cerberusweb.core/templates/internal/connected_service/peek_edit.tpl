{$peek_context = CerberusContexts::CONTEXT_CONNECTED_SERVICE}
{$peek_context_id = $model->id}
{$form_id = uniqid()}

{$service_ext = $model->getExtension()}

<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="connected_service">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-tabs">
	{if !$id}
	<ul>
		{if $packages}<li><a href="#service-library">{'common.library'|devblocks_translate|capitalize}</a></li>{/if}
		<li><a href="#service-builder">{'common.build'|devblocks_translate|capitalize}</a></li>
	</ul>
	{/if}
	
	{if !$id && $packages}
	<div id="service-library" class="package-library">
		{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.tpl"}
	</div>
	{/if}
	
	<div id="service-builder">
		<table cellspacing="0" cellpadding="2" border="0" width="98%" style="margin-bottom:10px;">
			<tbody>
				<tr>
					<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate|capitalize}:</b></td>
					<td width="99%">
						<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
					</td>
				</tr>
				
				<tr>
					<td width="1%" valign="top" nowrap="nowrap"><b><abbr title="The alias used for this service in callback URLs, etc. Must only contain letters, numbers, and dashes.">{'common.uri'|devblocks_translate}:</b></abbr></td>
					<td width="99%" valign="top">
						<input type="text" name="uri" value="{$model->uri}" style="width:98%;">
						<div>
							<small>(letters, numbers, and dashes)</small>
						</div>
					</td>
				</tr>
				
				<tr>
					<td width="1%" nowrap="nowrap"><b>{'common.type'|devblocks_translate|capitalize}:</b></td>
					<td width="99%">
					{if 0 == $model->id}
						<select name="extension_id">
							<option value=""></option>
							{foreach from=$service_exts item=service_ext}
							<option value="{$service_ext->id}">{$service_ext->name}</option>
							{/foreach}
						</select>
					{elseif $service_ext}
						{$service_ext->manifest->name}
					{/if}
					</td>
				</tr>
			
				{if !empty($custom_fields)}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
				{/if}
				
				<tr>
					<td colspan="2">
						<div id="{$form_id}Params">
						{if $service_ext}
							{$service_ext->renderConfigForm($model)}
						{/if}
						</div>
						
						{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}
					</td>
				</tr>
			</tbody>
		</table>
		
		{if !empty($model->id)}
		<fieldset style="display:none;" class="delete">
			<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
			
			<div>
				Are you sure you want to permanently delete this connected service?
			</div>
			
			<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
			<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
		</fieldset>
		{/if}
		
		<div class="buttons" style="margin-top:10px;">
			<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
			{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
		</div>
	</div>
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.connected_service'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');
		
		var $params = $('#{$form_id}Params');

		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		// Select
		var $select = $popup.find('select[name=extension_id]')
			.on('change', function(e) {
				var extension_id = $(this).val();
				genericAjaxGet($params, 'c=profiles&a=handleSectionAction&section=connected_service&action=getExtensionParams&id=' + encodeURIComponent(extension_id));
			})
		;
		
		// Package Library
		
		{if !$id}
			var $tabs = $popup.find('.cerb-tabs').tabs();
			var $library_container = $tabs;
			{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.js.tpl"}
			
			$library_container.on('cerb-package-library-form-submit', function(e) {
				$popup.one('peek_saved peek_error', function(e) {
					$library_container.triggerHandler('cerb-package-library-form-submit--done');
				});
				
				$popup.find('button.submit').click();
			});
		{/if}
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>
