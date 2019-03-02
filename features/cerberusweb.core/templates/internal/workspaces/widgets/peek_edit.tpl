{$peek_context = CerberusContexts::CONTEXT_WORKSPACE_WIDGET}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
{$widget_extension = $widget_extensions[$model->extension_id]}
{$tab = $model->getWorkspaceTab()}

<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="workspace_widget">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{if !$model->id}
	{if $tab}
	<input type="hidden" name="workspace_tab_id" value="{$tab->id}">
	{else}
	<table cellspacing="0" cellpadding="2" border="0" width="98%">
		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				<b>{'dashboard'|devblocks_translate|capitalize}:</b>
			</td>
			<td width="99%">
				<button type="button" class="chooser-abstract" data-field-name="workspace_tab_id" data-context="{CerberusContexts::CONTEXT_WORKSPACE_TAB}" data-single="true" data-query="type:&quot;core.workspace.tab.dashboard&quot;" data-autocomplete="type:&quot;core.workspace.tab.dashboard&quot;" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
				
				<ul class="bubbles chooser-container">
					{if $tab}
						<li><input type="hidden" name="workspace_tab_id" value="{$tab->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_WORKSPACE_TAB}" data-context-id="{$tab->id}">{$tab->name}</a></li>
					{/if}
				</ul>
			</td>
		</tr>
	</table>
	{/if}
{/if}

<div class="cerb-tabs">
	{if !$model->id}
	<ul>
		{if $packages}<li><a href="#widget-library">{'common.library'|devblocks_translate|capitalize}</a></li>{/if}
		<li><a href="#widget-builder">{'common.build'|devblocks_translate|capitalize}</a></li>
		<li><a href="#widget-import">{'common.import'|devblocks_translate|capitalize}</a></li>
	</ul>
	{/if}
	
	{if !$model->id && $packages}
	<div id="widget-library" class="package-library">
		{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.tpl"}
	</div>
	{/if}
	
	{if !$model->id}
	<div id="widget-import">
		<textarea name="import_json" style="width:100%;height:250px;white-space:pre;word-wrap:normal;" rows="10" cols="45" spellcheck="false" placeholder="Paste a dashboard widget in JSON format"></textarea>
		
		<div>
			<button type="button" class="import"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.import'|devblocks_translate|capitalize}</button>
		</div>
	</div>
	{/if}
	
	<div id="widget-builder">
		<table cellspacing="0" cellpadding="2" border="0" width="98%">
			<tbody>
				<tr>
					<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate}:</b></td>
					<td width="99%">
						<input type="text" name="name" value="{$model->label}" style="width:98%;" autofocus="autofocus">
					</td>
				</tr>
				
				<tr>
					<td width="1%" nowrap="nowrap" valign="top">
						<b>{'common.type'|devblocks_translate|capitalize}:</b>
					</td>
					<td width="99%" valign="top">
						{if $model->id}
							{$widget_extension = $model->getExtension()}
							{$widget_extension->manifest->name}
						{else}
							<select name="extension_id">
								<option value="">-- {'common.choose'|devblocks_translate|lower} --</option>
								{foreach from=$widget_extensions item=widget_extension}
								{if DevblocksPlatform::strStartsWith($widget_extension->name, '(Deprecated)')}
								{else}
								<option value="{$widget_extension->id}">{$widget_extension->name}</option>
								{/if}
								{/foreach}
							</select>
						{/if}
					</td>
				</tr>
				
				{if $model->id}
				<tr>
					<td width="1%" nowrap="nowrap" valign="top">
						<b>{'dashboard'|devblocks_translate|capitalize}:</b>
					</td>
					<td width="99%">
						<button type="button" class="chooser-abstract" data-field-name="workspace_tab_id" data-context="{CerberusContexts::CONTEXT_WORKSPACE_TAB}" data-single="true" data-query="type:&quot;core.workspace.tab.dashboard&quot;" data-autocomplete="type:&quot;core.workspace.tab.dashboard&quot;" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
						
						<ul class="bubbles chooser-container">
							{if $tab}
								<li><input type="hidden" name="workspace_tab_id" value="{$tab->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_WORKSPACE_TAB}" data-context-id="{$tab->id}">{$tab->name}</a></li>
							{/if}
						</ul>
					</td>
				</tr>
				{/if}
				
				<tr>
					<td width="1%" nowrap="nowrap" valign="top">
						<b>{'common.width'|devblocks_translate|capitalize}:</b>
					</td>
					<td width="99%" valign="top">
						{$widths = [4 => '100%', 3 => '75%', 2 => '50%', 1 => '25%']}
						{$current_width = $model->width_units|default:2}
						<select name="width_units">
							{foreach from=$widths item=width_label key=width}
							<option value="{$width}" {if $current_width == $width}selected="selected"{/if}>{$width_label}</option>
							{/foreach}
						</select>
					</td>
				</tr>
			</tbody>
			
			{if !empty($custom_fields)}
			{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
			{/if}
		</table>
		
		{* The rest of config comes from the widget *}
		<div class="cerb-widget-params">
		{$widget_extension = Extension_WorkspaceWidget::get($widget_extension->id, true)}
		{if $widget_extension && method_exists($widget_extension,'renderConfig')}
		{$widget_extension->renderConfig($model)}
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
				Are you sure you want to permanently delete this workspace widget?
			</div>
			
			<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
			<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
		</fieldset>
		{/if}
		
		<div class="buttons" style="margin-top:10px;">
			<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
			{if $model->id}<button type="button" class="save-continue"><span class="glyphicons glyphicons-circle-arrow-right" style="color:rgb(0,180,0);"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>{/if}
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
		$popup.dialog('option','title',"{'common.workspace.widget'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.import').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.save-continue').click({ mode: 'continue' }, Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		// Close confirmation
		
		$popup.on('dialogbeforeclose', function(e, ui) {
			var keycode = e.keyCode || e.which;
			if(keycode == 27)
				return confirm('{'warning.core.editor.close'|devblocks_translate}');
		});
		
		// Toolbar
		var $toolbar = $popup.find('.cerb-placeholder-menu').detach();
		var $params = $popup.find('.cerb-widget-params');
		
		// Package Library
		
		{if !$model->id}
			var $tabs = $popup.find('.cerb-tabs').tabs();
			
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
		
		// Abstract choosers
		$popup.find('button.chooser-abstract').cerbChooserTrigger();
		
		// Abstract peeks
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		
		// Switching extension params
		var $select = $popup.find('select[name=extension_id]');
		
		$select.on('change', function(e) {
			var extension_id = $select.val();
			
			$toolbar.detach();
			
			if(0 == extension_id.length) {
				$params.hide().empty();
				return;
			}
			
			// Fetch via Ajax
			genericAjaxGet($params, 'c=profiles&a=handleSectionAction&section=workspace_widget&action=getWidgetParams&id={$model->id}&extension=' + encodeURIComponent(extension_id), function(html) {
				$params.find('button.chooser-abstract').cerbChooserTrigger();
				$params.find('.cerb-peek-trigger').cerbPeekTrigger();
			});
		});
		
		// Placeholder toolbar
		$popup.delegate(':text.placeholders, textarea.placeholders, pre.placeholders', 'focus', function(e) {
			e.stopPropagation();
			
			var $target = $(e.target);
			var $parent = $target.closest('.ace_editor');
			
			if(0 != $parent.length) {
				$toolbar.find('div.tester').html('');
				$toolbar.find('ul.menu').hide();
				$toolbar.show().insertAfter($parent);
				$toolbar.data('src', $parent);
				
			} else {
				if(0 == $target.nextAll($toolbar).length) {
					$toolbar.find('div.tester').html('');
					$toolbar.find('ul.menu').hide();
					$toolbar.show().insertAfter($target);
					$toolbar.data('src', $target);
					
					// If a markItUp editor, move to parent
					if($target.is('.markItUpEditor')) {
						$target = $target.closest('.markItUp').parent();
						$toolbar.find('button.tester').hide();
						
					} else {
						$toolbar.find('button.tester').show();
					}
				}
			}
		});
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>
