{$peek_context = CerberusContexts::CONTEXT_PROFILE_TAB}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="profile_tab">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-tabs">
	{if !$model->id && $model->context && $packages}
	<ul>
		<li><a href="#package-library">{'common.library'|devblocks_translate|capitalize}</a></li>
		<li><a href="#tab-build">{'common.build'|devblocks_translate|capitalize}</a></li>
	</ul>
	{/if}
	
	{if !$model->id && $model->context && $packages}
	<div id="package-library" class="package-library">
		<input type="hidden" name="package_context" value="{$model->context}">
		{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.tpl"}
	</div>
	{/if}
	
	<div id="tab-build">
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
						{$model_context = $model->getContextExtension(false)}
						{if $model_context}
							{$model_context->name}
						{else}
							{$model->context}
						{/if}
					{else}
						<select name="context">
							<option value=""></option>
							{foreach from=$context_mfts item=context_mft}
							<option value="{$context_mft->id}" {if $context_mft->id == $model->context}selected="selected"{/if}>{$context_mft->name}</option>
							{/foreach}
						</select>
					{/if}
				</td>
			</tr>
			
			<tbody class="cerb-tab-extension" style="{if $model->context}{else}display:none;{/if}">
			<tr>
				<td width="1%" nowrap="nowrap"><b>{'common.type'|devblocks_translate|capitalize}:</b></td>
				<td width="99%">
					{if $model->id}
						{$tab_extension = $model->getExtension()}
						{if $tab_extension}
							{$tab_extension->manifest->name}
						{else}
							{$model->extension_id}
						{/if}
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
			</tbody>
		
			<tbody>
			{if !empty($custom_fields)}
			{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
			{/if}
			</tbody>
		</table>
		
		{* The rest of config comes from the tab *}
		<div class="cerb-tab-params">
		{if $model->id}
			{$tab_extension = $model->getExtension()}
			{if $tab_extension && method_exists($tab_extension,'renderConfig')}
				{$tab_extension->renderConfig($model)}
			{/if}
		{/if}
		</div>
		
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
		$popup.dialog('option','title',"{'common.profile.tab'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');
		
		var $select_context = $popup.find('select[name=context]');
		var $select_extension = $popup.find('select[name=extension_id]');
		var $tbody_extension = $popup.find('.cerb-tab-extension');
		var $params = $popup.find('.cerb-tab-params');
		
		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		// Close confirmation
		
		$popup.on('dialogbeforeclose', function(e, ui) {
			var keycode = e.keyCode || e.which;
			if(keycode == 27)
				return confirm('{'warning.core.editor.close'|devblocks_translate}');
		});
		
		// Package Library
		
		{if !$model->id && $model->context && $packages}
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
		
		// Events
		$select_context.on('change', function(e) {
			var context = $select_context.val();
			
			$select_extension.hide().empty();
			
			if(0 == context.length) {
				$tbody_extension.hide();
				
			} else {
				genericAjaxGet('', 'c=profiles&a=handleSectionAction&section=profile_tab&action=getExtensionsByContextJson&context=' + encodeURIComponent(context), function(json) {
					for(k in json) {
						if(json.hasOwnProperty(k)) {
							var $option = $('<option/>')
								.attr('value', k)
								.text(json[k])
								;
							
							$option.appendTo($select_extension);
						}
					}
					
					$select_extension.fadeIn();
					$params.fadeIn();
				});
				
				$tbody_extension.show();
			}
		});
		
		// Load per-extension configuration on change
		$select_extension.on('change', function(e) {
			var extension_id = $select_extension.val();
			$params.empty();
			
			if(0 == extension_id)
				return;
			
			genericAjaxGet($params, 'c=profiles&a=handleSectionAction&section=profile_tab&action=getExtensionConfig&extension_id=' + encodeURIComponent(extension_id), function() {
				$params.fadeIn();
			});
		});
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>
