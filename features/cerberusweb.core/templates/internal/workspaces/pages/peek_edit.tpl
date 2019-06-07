{$peek_context = CerberusContexts::CONTEXT_WORKSPACE_PAGE}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="pages">
<input type="hidden" name="a" value="saveWorkspacePagePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{if $model}
{$page_extension = $model->getExtension()}
{else}
{$page_extension = null}
{/if}

{if !$model->id}
<table cellspacing="0" cellpadding="2" border="0" width="98%">
	<tr>
		<td width="1%" nowrap="nowrap" align="right" valign="top">
			<b>{'common.owner'|devblocks_translate|capitalize}:</b>
		</td>
		<td width="99%">
			{include file="devblocks:cerberusweb.core::internal/peek/menu_actor_owner.tpl" model=$model}
		</td>
	</tr>
</table>
{/if}

<div class="cerb-tabs">
	{if !$model->id}
	<ul>
		{if $packages}<li><a href="#page-library">{'common.library'|devblocks_translate|capitalize}</a></li>{/if}
		<li><a href="#page-builder">{'common.build'|devblocks_translate|capitalize}</a></li>
		<li><a href="#page-import">{'common.import'|devblocks_translate|capitalize}</a></li>
	</ul>
	{/if}
	
	{if !$model->id && $packages}
	<div id="page-library" class="package-library">
		{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.tpl"}
	</div>
	{/if}
	
	{if !$model->id}
	<div id="page-import">
		<textarea name="import_json" style="width:100%;height:250px;white-space:pre;word-wrap:normal;" rows="10" cols="45" spellcheck="false" placeholder="Paste a workspace page in JSON format"></textarea>
		
		<div>
			<button type="button" class="import"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.import'|devblocks_translate|capitalize}</button>
		</div>
	</div>
	{/if}
	
	<div id="page-builder">
		<table cellspacing="0" cellpadding="2" border="0" width="98%">
			<tbody>
				<tr>
					<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate}:</b></td>
					<td width="99%">
						<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
					</td>
				</tr>
				
				<tr>
					<td width="1%" nowrap="nowrap" align="top">
						<b>{'common.type'|devblocks_translate|capitalize}:</b>
					</td>
					<td width="99%">
						{if !empty($model)}
							{if $page_extension}
								{$page_extension->manifest->params.label|devblocks_translate|capitalize}
							{/if}
						{else}
							<select name="extension_id">
								{if !empty($page_extensions)}
									{foreach from=$page_extensions item=page_extension}
										<option value="{$page_extension->id}">{$page_extension->params.label|devblocks_translate|capitalize}</option>
									{/foreach}
								{/if}
							</select>
						{/if}
					</td>
				</tr>
				
				{if $model->id}
				<tr>
					<td width="1%" nowrap="nowrap" valign="top">
						<b>{'common.owner'|devblocks_translate|capitalize}:</b>
					</td>
					<td width="99%">
						{include file="devblocks:cerberusweb.core::internal/peek/menu_actor_owner.tpl" model=$model}
					</td>
				</tr>
				{/if}
			</tbody>
		
			{if !empty($custom_fields)}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
			{/if}
		</table>
		
		{* The rest of config comes from the extension *}
		<div class="cerb-page-params">
		{if $page_extension && method_exists($page_extension,'renderConfig')}
		{$page_extension->renderConfig($model)}
		{/if}
		</div>
		
		<div class="cerb-placeholder-menu" style="display:none;">
		{include file="devblocks:cerberusweb.core::internal/workspaces/tabs/dashboard/toolbar.tpl"}
		</div>
		
		{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}
		
		{if !empty($model->id)}
		<fieldset style="display:none;" class="delete">
			<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
			
			<div>
				Are you sure you want to permanently delete this workspace page?
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
		$popup.dialog('option','title',"{'common.workspace.page'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');
		
		var $owners_menu = $popup.find('ul.owners-menu');
		
		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.import').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		// Package Library
		
		{if !$model->id}
			var $tabs = $popup.find('.cerb-tabs').tabs();
			
			// [TODO] Show a spinner (on all of these, in an abstract way)
			
			{if $packages}
				var $library_container = $tabs;
				{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.js.tpl"}
				
				$library_container.on('cerb-package-library-form-submit', function(e) {
					$popup.one('peek_saved peek_error', function(e) {
						$library_container.triggerHandler('cerb-package-library-form-submit--done');
					});
					
					$popup.find('button.submit').click();
				});
			{/if}
		{/if}
		
		// Owners
		
		var $ul = $owners_menu.siblings('ul.chooser-container');
		
		$ul.on('bubble-remove', function(e, ui) {
			e.stopPropagation();
			$(e.target).closest('li').remove();
			$ul.hide();
			$owners_menu.show();
		});
		
		$owners_menu.menu({
			select: function(event, ui) {
				var token = ui.item.attr('data-token');
				var label = ui.item.attr('data-label');
				
				if(undefined == token || undefined == label)
					return;
				
				$owners_menu.hide();
				
				// Build bubble
				
				var context_data = token.split(':');
				var $li = $('<li/>');
				var $label = $('<a href="javascript:;" class="cerb-peek-trigger no-underline" />').attr('data-context',context_data[0]).attr('data-context-id',context_data[1]).text(label);
				$label.cerbPeekTrigger().appendTo($li);
				var $hidden = $('<input type="hidden">').attr('name', 'owner').attr('value',token).appendTo($li);
				ui.item.find('img.cerb-avatar').clone().prependTo($li);
				var $a = $('<a href="javascript:;" onclick="$(this).trigger(\'bubble-remove\');"><span class="glyphicons glyphicons-circle-remove"></span></a>').appendTo($li);
				
				$ul.find('> *').remove();
				$ul.append($li);
				$ul.show();
			}
		});
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>
