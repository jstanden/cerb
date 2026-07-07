{$peek_context = CerberusContexts::CONTEXT_WORKSPACE_PAGE}
{$peek_context_id = $model->id}
{$form_id = uniqid()}
<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" data-cerb-placeholders>
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="workspace_page">
<input type="hidden" name="action" value="savePeekJson">
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
<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">{'common.owner'|devblocks_translate|capitalize}</label>
			<div>
				{include file="devblocks:cerberusweb.core::internal/peek/menu_actor_owner.tpl" model=$model}
			</div>
		</div>
	</div>
</div>
{/if}

<div class="cerb-tabs">
	{if !$model->id}
	<ul>
		{if $packages}<li><a href="#page-library_{$form_id}">{'common.library'|devblocks_translate|capitalize}</a></li>{/if}
		<li><a href="#page-builder_{$form_id}">{'common.build'|devblocks_translate|capitalize}</a></li>
		<li><a href="#page-import_{$form_id}">{'common.import'|devblocks_translate|capitalize}</a></li>
	</ul>
	{/if}
	
	{if !$model->id && $packages}
	<div id="page-library_{$form_id}" class="package-library">
		{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.tpl"}
	</div>
	{/if}
	
	{if !$model->id}
	<div id="page-import_{$form_id}">
		<textarea name="import_json" style="width:100%;height:250px;box-sizing:border-box;white-space:pre;word-wrap:normal;" rows="10" cols="45" spellcheck="false" placeholder="Paste a workspace page in JSON format"></textarea>
		
		<div>
			<button type="button" class="cerb-ui-button import"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.import'|devblocks_translate|capitalize}</button>
		</div>
	</div>
	{/if}

	<div id="page-builder_{$form_id}">
		<div class="cerb-ui-panel cerb-ui-panel--spaced">
			<div class="cerb-ui-form">
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.name'|devblocks_translate|capitalize}</label>
					<input type="text" name="name" value="{$model->name}" autofocus="autofocus">
				</div>

				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.type'|devblocks_translate|capitalize}</label>
					{if !empty($model)}
						<div>{if $page_extension}{$page_extension->manifest->params.label|devblocks_translate|capitalize}{/if}</div>
					{else}
						<select name="extension_id">
							{if !empty($page_extensions)}
								{foreach from=$page_extensions item=page_extension}
									<option value="{$page_extension->id}">{$page_extension->params.label|devblocks_translate|capitalize}</option>
								{/foreach}
							{/if}
						</select>
					{/if}
				</div>

				{if $model->id}
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.owner'|devblocks_translate|capitalize}</label>
					<div>
						{include file="devblocks:cerberusweb.core::internal/peek/menu_actor_owner.tpl" model=$model}
					</div>
				</div>
				{/if}
			</div>
		</div>

		{if !empty($custom_fields)}
		<div class="cerb-ui-panel cerb-ui-panel--spaced">
			<div class="cerb-ui-form">
				{include file="devblocks:cerberusweb.core::internal/custom_fields/form.tpl" custom_fields=$custom_fields}
			</div>
		</div>
		{/if}

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
			{include file="devblocks:cerberusweb.core::internal/peek/delete_confirm.tpl" noun="workspace page"}
		{/if}

		<div class="buttons" style="margin-top:10px;">
			<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
			{if !empty($model->id) && $active_worker->hasPriv("contexts.{$peek_context}.delete")}<button type="button" class="cerb-ui-button cerb-ui-button--subtle delete-prompt"><span class="cerb-icons cerb-icon-trash"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
		</div>
	</div>
</div>

</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $popup = genericAjaxPopupFind($frm);

	Devblocks.formDisableSubmit($frm);
	
	$popup.one('popup_open', function() {
		$popup.dialog('option','title',"{'common.workspace.page'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');
		
		// Buttons
		$popup.find('button.save').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.import').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		if(window.CerbUI && CerbUI.Form) CerbUI.Form.ConfirmDelete($popup[0]);

		// Type — native <select> keeps the POST value; enhance with type-to-filter
		if(window.CerbUI && CerbUI.SelectMenu)
			$popup.find('form select[name=extension_id]').each(function() { new CerbUI.SelectMenu(this); });

		// Package Library
		
		{if !$model->id}
			var $tabs = $popup.find('.cerb-tabs');
			$tabs.find('> ul').each(function() {
				if(window.CerbUI && CerbUI.Tabs) new CerbUI.Tabs(this);
			});
			
			// [TODO] Show a spinner (on all of these, in an abstract way)
			
			{if $packages}
				var $library_container = $tabs;
				{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.js.tpl"}
				
				$library_container.on('cerb-package-library-form-submit', function(e) {
					$popup.one('peek_saved peek_error', function(e) {
						$library_container.triggerHandler('cerb-package-library-form-submit--done');
					});

					$popup.find('button.save').click();
				});
			{/if}
		{/if}
		
		
	});
});
</script>
