{$peek_context = CerberusContexts::CONTEXT_WORKSPACE_WIDGET}
{$peek_context_id = $model->id|default:0}
{$form_id = uniqid()}
{$widget_extension = $widget_extensions[$model->extension_id]}
{$tab = $model->getWorkspaceTab()}

<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="workspace_widget">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if $peek_context_id}<input type="hidden" name="id" value="{$peek_context_id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{if !$peek_context_id}
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
	{if !$peek_context_id}
	<ul>
		{if $packages}<li><a href="#widget-library">{'common.library'|devblocks_translate|capitalize}</a></li>{/if}
		<li><a href="#widget-builder">{'common.build'|devblocks_translate|capitalize}</a></li>
		<li><a href="#widget-import">{'common.import'|devblocks_translate|capitalize}</a></li>
	</ul>
	{/if}
	
	{if !$peek_context_id && $packages}
	<div id="widget-library" class="package-library">
		{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.tpl"}
	</div>
	{/if}
	
	{if !$peek_context_id}
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
						{if $peek_context_id}
							{$widget_extension->name}
						{else}
							<select name="extension_id">
								<option value="">-- {'common.choose'|devblocks_translate|lower} --</option>
								{foreach from=$widget_extensions item=widget_ext}
								{if DevblocksPlatform::strStartsWith($widget_ext->name, '(Deprecated)')}
								{else}
								<option value="{$widget_ext->id}">{$widget_ext->name}</option>
								{/if}
								{/foreach}
							</select>
						{/if}
					</td>
				</tr>
				
				{if $peek_context_id}
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
		{if $widget_extension}
			{$widget_ext = Extension_WorkspaceWidget::get($widget_extension->id, true)}
			{if $widget_ext && method_exists($widget_ext,'renderConfig')}
			{$widget_ext->renderConfig($model)}
			{/if}
		{/if}
		</div>
		
		<div class="cerb-placeholder-menu" style="display:none;">
		{include file="devblocks:cerberusweb.core::internal/workspaces/tabs/dashboard/toolbar.tpl"}
		</div>
		
		{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$peek_context_id}
		
		{if !empty($peek_context_id)}
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
			<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
			{if $peek_context_id}<button type="button" class="save-continue"><span class="glyphicons glyphicons-circle-arrow-right"></span> {'common.save_and_continue'|devblocks_translate|capitalize}</button>{/if}
			{if !empty($peek_context_id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
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
		
		// Toolbar
		var $toolbar = $popup.find('.cerb-placeholder-menu').detach();
		var $params = $popup.find('.cerb-widget-params');
		
		// Package Library
		
		{if !$peek_context_id}
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
			genericAjaxGet($params, 'c=profiles&a=invoke&module=workspace_widget&action=getWidgetParams&id={$peek_context_id}&extension=' + encodeURIComponent(extension_id), function() {
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
					$toolbar.find('button.tester').show();
				}
			}
		});
		
	});
});
</script>
