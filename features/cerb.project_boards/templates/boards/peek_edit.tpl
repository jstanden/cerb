{$peek_context = Context_ProjectBoard::ID}
{$peek_context_id = $model->id}
{$form_id = uniqid()}

<form action="{devblocks_url}{/devblocks_url}" method="post" id="{$form_id}" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="project_board">
<input type="hidden" name="action" value="savePeekJson">
<input type="hidden" name="view_id" value="{$view_id}">
{if !empty($model) && !empty($model->id)}<input type="hidden" name="id" value="{$model->id}">{/if}
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="cerb-tabs">
	{if !$model->id && $packages}
	<ul>
		<li><a href="#board-library">{'common.library'|devblocks_translate|capitalize}</a></li>
		<li><a href="#board-builder">{'common.build'|devblocks_translate|capitalize}</a></li>
	</ul>
	{/if}
	
	{if !$model->id && $packages}
	<div id="board-library" class="package-library">
		{include file="devblocks:cerberusweb.core::internal/package_library/editor_chooser.tpl"}
	</div>
	{/if}
	
	<div id="board-builder">
		<table cellspacing="0" cellpadding="2" border="0" width="98%" style="margin-bottom:10px;">
			<tbody>
				<tr>
					<td width="1%" nowrap="nowrap"><b>{'common.name'|devblocks_translate}:</b></td>
					<td width="99%">
						<input type="text" name="name" value="{$model->name}" style="width:98%;" autofocus="autofocus">
					</td>
				</tr>
				
				{if !empty($custom_fields)}
				{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false tbody=true}
				{/if}
				
				<tr>
					<td colspan="2" width="100%">
						<fieldset class="peek cerb-contexts-selection">
							<legend>Allow these record types in the project:</legend>
							{foreach from=$contexts item=context}
							{$enabled = false}
							{if is_array($model->params.contexts) && in_array($context->id, $model->params.contexts)}
							{$enabled = true}
							{/if}
							<div>
								<label><input type="checkbox" name="params[contexts][]" value="{$context->id}" {if $enabled}checked="checked"{/if}> {$context->name}</label>
								<div class="cerb-contexts-params" style="margin:0px 0px 10px 20px;{if $enabled}display:block;{else}display:none;{/if};">
									<div>
										<b>Quick search query for adding cards:</b><br>
										<input type="text" name="params[card_queries][{$context->id}]" value="{$model->params.card_queries[{$context->id}]}" style="width:100%;" class="cerb-query-trigger" data-context="{$context->id}">
									</div>
									<div>
										<b>Card custom template:</b> (optional)<br>
										<textarea name="params[card_templates][{$context->id}]" style="width:100%;height:100px;" class="cerb-template-trigger" data-context="{$context->id}">{$model->params.card_templates[{$context->id}]}</textarea>
									</div>
								</div>
							</div>
							{/foreach}
						</fieldset>
						
						{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=$peek_context context_id=$model->id}
					</td>
				</tr>
			</tbody>
		</table>
		
		{if !empty($model->id)}
		<fieldset style="display:none;" class="delete">
			<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
			
			<div>
				Are you sure you want to permanently delete this project board?
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
</div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'projects.common.board'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		
		// Buttons
		
		$popup.find('button.submit').click(Devblocks.callbackPeekEditSave);
		$popup.find('button.delete').click({ mode: 'delete' }, Devblocks.callbackPeekEditSave);
		
		// Package Library
		
		{if !$model->id && $packages}
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
		
		// Query builders
		
		$popup.find('.cerb-query-trigger')
			.cerbQueryTrigger()
			.on('cerb-query-saved', function(e) {
				//var $trigger = $(this);
				//$trigger.val(e.worklist_quicksearch);
			})
		;
		
		// Template builders
		
		$popup.find('textarea.cerb-template-trigger')
			.cerbTemplateTrigger()
			.on('cerb-template-saved', function(e) {
				//var $trigger = $(this);
				//$trigger.val(e.worklist_quicksearch);
			})
		;
		
		// Checkboxes
		
		$popup.find('fieldset.cerb-contexts-selection').find('input[type=checkbox]').on('change', function(e) {
			var $checkbox = $(this);
			var $div = $checkbox.closest('div').find('div.cerb-contexts-params');
			
			if($checkbox.is(':checked')) {
				$div.fadeIn();
			} else {
				$div.fadeOut();
			}
		});
		
		// [UI] Editor behaviors
		{include file="devblocks:cerberusweb.core::internal/peek/peek_editor_common.js.tpl" peek_context=$peek_context peek_context_id=$peek_context_id}
	});
});
</script>
