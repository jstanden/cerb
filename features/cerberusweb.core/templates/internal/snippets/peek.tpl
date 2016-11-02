<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formSnippetsPeek" name="formSnippetsPeek" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="saveSnippetsPeek">
<input type="hidden" name="id" value="{$snippet->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate|capitalize}</legend>
	
	<table cellpadding="2" cellspacing="0" border="0" width="100%">
		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				<b>{'common.title'|devblocks_translate|capitalize}:</b><br>
			</td>
			<td width="99%">
				<input type="text" name="title" value="{$snippet->title}" style="border:1px solid rgb(180,180,180);padding:2px;width:98%;"><br>
			</td>
		</tr>
		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				<b>{'common.type'|devblocks_translate|capitalize}:</b><br>
			</td>
			<td width="99%">
				<select name="context">
					<option value="" {if empty($snippet->id)}selected="selected"{/if}>Plaintext</option>
					{foreach from=$contexts item=ctx key=k}
					{if is_array($ctx->params.options.0) && isset($ctx->params.options.0.snippets)}
					<option value="{$k}" {if $snippet->context==$k}selected="selected"{/if}>{$ctx->name}</option>
					{/if}
					{/foreach}
				</select>
			</td>
		</tr>
		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				<b>{'common.owner'|devblocks_translate|capitalize}:</b>
			</td>
			<td width="99%">
				{include file="devblocks:cerberusweb.core::internal/peek/menu_actor_owner.tpl" model=$snippet}
			</td>
		</tr>
	</table>
	
	<b>{'common.content'|devblocks_translate|capitalize}:</b><br>
	<textarea name="content" style="width:98%;height:200px;border:1px solid rgb(180,180,180);padding:2px;">{$snippet->content}</textarea>
	<div class="toolbar"></div>
	
</fieldset>

<fieldset class="peek placeholders" style="margin-top:10px;">
	<legend>Prompted Placeholders</legend>
	
	<table cellspacing="2" cellpadding="1" border="0" width="100%">
		{foreach from=$snippet->custom_placeholders item=placeholder key=placeholder_key name=placeholders}
		{$type_code = $placeholder.type}
		<tr class="sortable">
			<td valign="top" width="1%" nowrap="nowrap"><span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;cursor:move;"></span></td>
			<td valign="top" width="98%">
				<div>
					{$types.$type_code}
				</div>
			
				<div style="margin-left:20px;">
					<input type="hidden" name="placeholder_types[]" value="{$placeholder.type}">
					<input type="hidden" name="placeholder_deletes[]" value="">
					
					<table width="100%">
						<tr>
							<td width="1%" nowrap="nowrap" align="right">
								<b>Placeholder:</b>
							</td>
							<td>
								<input type="text" name="placeholder_keys[]" value="{$placeholder.key}" placeholder="prompt_placeholder" size="35" style="width:100%;">
							</td>
						</tr>
						<tr>
							<td width="1%" nowrap="nowrap" align="right">
								<b>Prompt:</b>
							</td>
							<td>
								<input type="text" name="placeholder_labels[]" value="{$placeholder.label}" placeholder="This label prompts the worker:" style="width:100%;">
							</td>
						</tr>
						<tr>
							<td width="1%" nowrap="nowrap" align="right">
								<b>Default value:</b>
							</td>
							<td>
								<input type="text" name="placeholder_defaults[]" value="{$placeholder.default}" placeholder="This is the default value of the placeholder" size="35" style="width:100%;">
							</td>
						</tr>
					</table>
				</div>
				
			</td>
			<td width="1%" valign="top" nowrap="nowrap">
				<span class="glyphicons glyphicons-circle-minus delete" style="color:rgb(200,0,0);margin-left:5px;cursor:pointer;">
			</td>
		</tr>
		{/foreach}
	
		<tr class="placeholders-add-template sortable" style="display:none;">
			<td width="1%" valign="top" nowrap="nowrap"><span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;cursor:move;"></span></td>
			<td width="98%" valign="top" nowrap="nowrap">
				<div>
					<select name="placeholder_types[]" class="context-picker">
						<option value="{Model_CustomField::TYPE_CHECKBOX}">Checkbox</option>
						<option value="{Model_CustomField::TYPE_SINGLE_LINE}">Text: Single Line</option>
						<option value="{Model_CustomField::TYPE_MULTI_LINE}">Text: Multiple Lines</option>
					</select>
				</div>
				
				<div style="margin-left:20px;">
					<input type="hidden" name="placeholder_deletes[]" value="">
					
					<table width="100%">
						<tr>
							<td width="1%" nowrap="nowrap" align="right">
								<b>Placeholder:</b>
							</td>
							<td>
								<input type="text" name="placeholder_keys[]" value="" placeholder="prompt_placeholder" size="35" style="width:100%;">
							</td>
						</tr>
						<tr>
							<td width="1%" nowrap="nowrap" align="right">
								<b>Prompt:</b>
							</td>
							<td>
								<input type="text" name="placeholder_labels[]" value="" placeholder="This label prompts the worker:" style="width:100%;">
							</td>
						</tr>
						<tr>
							<td width="1%" nowrap="nowrap" align="right">
								<b>Default value:</b>
							</td>
							<td>
								<input type="text" name="placeholder_defaults[]" value="" placeholder="This is the default value of the placeholder" size="35" style="width:100%;">
							</td>
						</tr>
					</table>

				</div>
			</td>
			<td width="1%" valign="top" nowrap="nowrap">
				<span class="glyphicons glyphicons-circle-minus delete" style="color:rgb(200,0,0);margin-left:5px;cursor:pointer;">
			</td>
		</tr>
		
		<tr>
			<td colspan="4">
				<button type="button" class="add"><span class="glyphicons glyphicons-circle-plus" style="color:rgb(0,180,0);"></span></button>
			</td>
		</tr>
	</table>
	
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek" style="margin-top:10px;">
	<legend>{'common.custom_fields'|devblocks_translate}</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=false}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_SNIPPET context_id=$snippet->id}

{if isset($snippet->id)}
<fieldset class="delete" style="display:none;">
	<legend>Delete this snippet?</legend>
	<p>Are you sure you want to permanently delete this snippet?</p>
	<button type="button" class="green delete"> {'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" class="red" onclick="$(this).closest('fieldset').hide().next('div.buttons').show();"> {'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="buttons">
{if $active_worker->hasPriv('core.snippets.actions.create')}
	<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate}</button>
{else}
	<fieldset class="delete" style="font-weight:bold;">
		{'error.core.no_acl.edit'|devblocks_translate}
	</fieldset>
{/if}
{if !empty($snippet->id)}
	{if $snippet->isWriteableByWorker($active_worker)}
	<button type="button" onclick="$(this).closest('div.buttons').hide().prev('fieldset.delete').show();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>
	{/if}
{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('{$layer}');
	
	$popup.one('popup_open',function(event,ui) {
		$popup.css('overflow', 'inherit');

		var $textarea = $popup.find('textarea[name=content]');
		
		{if empty($snippet->id)}
		$popup.dialog('option','title', 'Create Snippet');
		{else}
		$popup.dialog('option','title', 'Modify Snippet');
		{/if}

		var $owners_menu = $popup.find('ul.owners-menu');
		var $ul = $owners_menu.siblings('ul.chooser-container');
		
		$popup.find('.cerb-peek-trigger').cerbPeekTrigger();
		
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
		
		$popup.find('button.submit').click(function() {
			genericAjaxPost('formSnippetsPeek', 'view{$view_id}', null, function() {
				genericAjaxPopupClose($popup);
			});
		});
		
		$popup.find('button.delete').click(function() {
			$(this).closest('form').find('input:hidden[name=do_delete]').val('1');
			
			genericAjaxPost('formSnippetsPeek', 'view{$view_id}', null, function() {
				genericAjaxPopupClose($popup);
			});
		});
		
		// Change
		
		var $change_dropdown = $popup.find("form select[name=context]");
		$change_dropdown.change(function(e) {
			var ctx = $(this).val();
			genericAjaxGet($popup.find('DIV.toolbar'), 'c=internal&a=showSnippetsPeekToolbar&context=' + ctx);
		});
		
		// If editing and a target context is known
		genericAjaxGet($popup.find('DIV.toolbar'), 'c=internal&a=showSnippetsPeekToolbar&context={$snippet->context}');
		
		$popup.find('input:text:first').focus().select();
		
		$popup.find('fieldset.placeholders button.add').click(function() {
			var $parent = $(this).closest('tr');
			var $template = $parent.siblings('.placeholders-add-template');
			var $tr = $template.clone();
			$tr.removeClass('placeholders-add-template');
			$tr.insertBefore($template).fadeIn();
			$tr.find('input:text:first').focus();
		});
		
		$popup.find('fieldset.placeholders table').sortable({ 
			items:'TR.sortable',
			helper: 'original',
			forceHelperSize: true,
			handle: 'span.ui-icon-arrowthick-2-n-s'
		});
		
		// Snippet syntax
		$textarea
			.atwho({
				{literal}at: '{%',{/literal}
				limit: 20,
				{literal}displayTpl: '<li>${content} <small style="margin-left:10px;">${name}</small></li>',{/literal}
				{literal}insertTpl: '${name}',{/literal}
				data: atwho_twig_commands,
				suffix: ''
			})
			.atwho({
				{literal}at: '|',{/literal}
				limit: 20,
				startWithSpace: false,
				searchKey: "content",
				{literal}displayTpl: '<li>${content} <small style="margin-left:10px;">${name}</small></li>',{/literal}
				{literal}insertTpl: '|${name}',{/literal}
				data: atwho_twig_modifiers,
				suffix: ''
			})
			;
		
		// Placeholder deletion
		$popup.find('fieldset.placeholders table').on('click', 'span.delete', function() {
			$tr = $(this).closest('tr');
			
			// Check if the row is being deleted, and if so, undelete
			$del = $tr.find('input:hidden[name^=placeholder_deletes]');

			if($del.length == 0)
				return;
			
			// Undelete
			if($del.val() == '1') {
				$tr.fadeTo('fast', 1.0);
				$del.val('');
				
			// Delete
			} else {
				if($tr.find('select[name^=placeholder_types]').length > 0) {
					$tr.fadeOut('fast', function() {
						$(this).remove();
					});
					
				} else {
					$tr.fadeTo('fast', 0.3);
					$del.val('1');
				}
			}
		});
	});
});
</script>