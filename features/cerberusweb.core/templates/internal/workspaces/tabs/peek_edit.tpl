{$peek_context = CerberusContexts::CONTEXT_WORKSPACE_TAB}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
{$page = $model->getWorkspacePage()}
{$tab_extension = $tab_extensions[$model->extension_id]}

<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="workspace_tab">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

{if !$model->id}
	{if $page}
	<input type="hidden" name="workspace_page_id" value="{$page->id}">
	{else}
		<table cellspacing="0" cellpadding="2" border="0" width="98%">
			<tr>
				<td width="1%" nowrap="nowrap" align="right" valign="top">
					<b>{'common.page'|devblocks_translate|capitalize}:</b>
				</td>
				<td width="99%">
					<button type="button" class="chooser-abstract" data-field-name="workspace_page_id" data-context="{CerberusContexts::CONTEXT_WORKSPACE_PAGE}" data-single="true" data-query="type:&quot;core.workspace.page.workspace&quot;" data-autocomplete="type:&quot;core.workspace.page.workspace&quot;" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
					
					<ul class="bubbles chooser-container">
						{if $model->workspace_page_id && $page}
							<li><input type="hidden" name="workspace_page_id" value="{$page->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_WORKSPACE_PAGE}" data-context-id="{$page->id}">{$page->name}</a></li>
						{/if}
					</ul>
				</td>
			</tr>
		</table>
	{/if}
{/if}

<div class="cerb-tabs">
	{if !$id}
	<ul>
		{if $packages}<li><a href="#tab-library">{'common.library'|devblocks_translate|capitalize}</a></li>{/if}
		<li><a href="#tab-builder">{'common.build'|devblocks_translate|capitalize}</a></li>
		<li><a href="#tab-import">{'common.import'|devblocks_translate|capitalize}</a></li>
	</ul>
	{/if}
	
	<div id="tab-builder">
		<table cellspacing="0" cellpadding="2" border="0" width="98%">
			<tbody>
				<tr>
					<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate}:</b></td>
					<td width="99%">
						<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
					</td>
				</tr>
				
				<tr>
					<td width="1%" nowrap="nowrap" align="right" valign="top">
						<b>{'common.type'|devblocks_translate|capitalize}:</b>
					</td>
					<td width="99%">
						{if $model->id && $tab_extension}
							{$tab_extension->params.label|devblocks_translate|capitalize}
						{else}
							<select name="extension_id">
								<option value="">-- {'common.choose'|devblocks_translate|lower} --</option>
								{foreach from=$tab_extensions item=tab_extension}
									<option value="{$tab_extension->id}">{$tab_extension->params.label|devblocks_translate|capitalize}</option>
								{/foreach}
							</select>
						{/if}
					</td>
				</tr>
				
				{if !empty($custom_fields)}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
				{/if}
			</tbody>
		</table>
		
		<div class="cerb-tab-params" style="padding-top:10px;">
		{$tab_extension = Extension_WorkspaceTab::get($tab_extension->id, true)}
		{if $tab_extension && method_exists($tab_extension,'renderTabConfig')}
			{$tab_extension->renderTabConfig($page, $model)}
		{/if}
		</div>
			
		{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}
		
		{if !empty($model->id)}
		<fieldset style="display:none;" class="delete">
			<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
			
			<div>
				Are you sure you want to permanently delete this workspace tab and all of its content?
			</div>
			
			<button type="button" class="delete red">{'common.yes'|devblocks_translate|capitalize}</button>
			<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();">{'common.no'|devblocks_translate|capitalize}</button>
		</fieldset>
		{/if}
		
		<div class="buttons">
			<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
			{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
		</div>
	</div>
	
	{if !$id && $packages}
	<div id="tab-library" class="package-library">
		{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.tpl"}
	</div>
	{/if}
	
	{if !$id}
	<div id="tab-import">
		<textarea name="import_json" style="width:100%;height:250px;white-space:pre;word-wrap:normal;" rows="10" cols="45" spellcheck="false" placeholder="Paste a workspace tab in JSON format"></textarea>
		
		<div>
			<button type="button" class="import"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.import'|devblocks_translate|capitalize}</button>
		</div>
	</div>
	{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	var $params = $frm.find('div.cerb-tab-params');
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.workspace.tab'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');

		// Buttons
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		$popup.find('button.import').click(Devblocks.callbackPeekEditSave);
		
		// Abstract choosers
		$popup.find('button.chooser-abstract').cerbChooserTrigger();
		
		// Abstract peeks
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		
		// Switching extension params
		var $select = $popup.find('select[name=extension_id]');
		
		// Package Library
		
		{if !$id}
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
		
		$select.on('change', function(e) {
			var extension_id = $select.val();
			
			// Fetch via Ajax
			genericAjaxGet($params, 'c=profiles&a=invoke&module=workspace_tab&action=getTabParams&page_id={$page->id}&tab_id={$model->id}&extension=' + encodeURIComponent(extension_id), function(html) {
				$params.find('button.chooser-abstract').cerbChooserTrigger();
				$params.find('.cerb-peek-trigger').cerbPeekTrigger();
			});
		});
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>
