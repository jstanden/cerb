{$peek_context = CerberusContexts::CONTEXT_CUSTOM_FIELDSET}
{$is_writeable = !$custom_fieldset->id || Context_CustomFieldset::isWriteableByActor($custom_fieldset, $active_worker)}

{if !$is_writeable || !$active_worker->hasPriv("contexts.{$peek_context}.update")}
<div class="error-box">
	{'error.core.no_acl.edit'|devblocks_translate}
</div>
{/if}

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmCustomFieldsetPeek" name="frmCustomFieldsetPeek" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="custom_fieldsets">
<input type="hidden" name="action" value="saveCustomFieldsetPeek">
<input type="hidden" name="id" value="{$custom_fieldset->id}">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="do_delete" value="0">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek cfield-props">
	<legend>{'common.properties'|devblocks_translate|capitalize}</legend>
	
	<table cellpadding="2" cellspacing="0" border="0" width="100%">
		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				<b>{'common.name'|devblocks_translate|capitalize}:</b><br>
			</td>
			<td width="99%">
				<input type="text" name="name" value="{$custom_fieldset->name}" style="border:1px solid rgb(180,180,180);padding:2px;width:98%;"><br>
			</td>
		</tr>
		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				<b>{'common.type'|devblocks_translate|capitalize}:</b><br>
			</td>
			<td width="99%">
				{if !empty($custom_fieldset->id)}
					<input type="hidden" name="context" value="{$custom_fieldset->context}">
					{if $contexts.{$custom_fieldset->context}}
						{$contexts.{$custom_fieldset->context}->name}
					{/if}
				{else}
				<select name="context">
					{foreach from=$contexts item=ctx key=k}
					<option value="{$k}" {if $custom_fieldset->context==$k}selected="selected"{/if}>{$ctx->name}</option>
					{/foreach}
				</select>
				{/if}
			</td>
		</tr>
		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				<b>{'common.owner'|devblocks_translate|capitalize}:</b>
			</td>
			<td width="99%">
				{include file="devblocks:cerberusweb.core::internal/peek/menu_actor_owner.tpl" model=$custom_fieldset}
			</td>
		</tr>
	</table>
</fieldset>

<fieldset class="peek cfields">
	<legend>{'common.custom_fields'|devblocks_translate|capitalize}</legend>

	<table cellspacing="2" cellpadding="1" border="0" width="100%">
	{foreach from=$custom_fields item=f key=field_id name=fields}
		{assign var=type_code value=$f->type}
		<tr class="sortable">
			<td valign="top" width="1%" nowrap="nowrap">{if $is_writeable && $active_worker->hasPriv("contexts.{$peek_context}.update")}<span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;cursor:move;"></span>{/if}</td>
			<td valign="top" width="1%" nowrap="nowrap">{$types.$type_code}</td>
			<td valign="top" width="98%">
				<input type="hidden" name="types[]" value="{$f->type}">
				<input type="hidden" name="ids[]" value="{$field_id}">
				<input type="hidden" name="deletes[]" value="">
				<input type="text" name="names[]" value="{$f->name}" placeholder="Enter a name for this custom field" size="35" style="width:100%;">
				{if $type_code == 'D' || $type_code == 'X'}
					<div class="options" style="">
						<textarea cols="35" rows="6" name="params[{$field_id}][options]" style="width:100%;" placeholder="Enter choices (one per line)">{foreach from=$f->params.options item=opt}{$opt|cat:"\r\n"}{/foreach}</textarea>
					</div>
				{elseif $type_code == 'L'}
					of type 
					<input type="hidden" name="params[{$field_id}][context]" value="{$f->params.context}">
					{$link_ctx_ext = Extension_DevblocksContext::get($f->params.context)}
					<b>{$link_ctx_ext->manifest->name}</b>
				{elseif $type_code == 'W'}
					<label><input type="checkbox" name="params[{$field_id}][send_notifications]" value="1" {if $f->params.send_notifications}checked="checked"{/if}> Send watcher notifications</label>
				{/if}
			</td>
			<td width="1%" valign="top" nowrap="nowrap">
				{if $is_writeable && $active_worker->hasPriv("contexts.{$peek_context}.update")}<span class="glyphicons glyphicons-circle-minus delete" style="color:rgb(200,0,0);margin-left:5px;cursor:pointer;"></span>{/if}
			</td>
		</tr>
	{/foreach}
	
	<tr class="cfields-add-template sortable" style="display:none;">
		<td width="1%" valign="top" nowrap="nowrap"><span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;cursor:move;"></span></td>
		<td width="1%" valign="top" nowrap="nowrap">
			<select name="types[]" class="context-picker">
				{foreach from=$types item=type key=type_code}
				<option value="{$type_code}">{$type}</option>
				{/foreach}
			</select>
		</td>
		<td width="97%" valign="top" nowrap="nowrap">
			<input type="hidden" name="ids[]" value="new_##id##">
			<input type="hidden" name="deletes[]" value="">
			<input type="text" name="names[]" value="" placeholder="Enter a name for this custom field" size="35" style="width:100%;">
			<div class="params params-D params-X" style="display:none;">
				<textarea cols="35" rows="6" name="params[new_##id##][options]" style="width:100%;" placeholder="Enter choices (one per line)"></textarea>
			</div>
			<div class="params params-L" style="display:none;">
				of type 
				<select name="params[new_##id##][context]">
					{foreach from=$link_contexts item=link_context}
					<option value="{$link_context->id}">{$link_context->name}</option>
					{/foreach}
				</select>
			</div>
			<div class="params params-W" style="display:none;">
				<label><input type="checkbox" name="params[new_##id##][send_notifications]" value="1"> Send watcher notifications</label>
			</div>
		</td>
		<td width="1%" valign="top" nowrap="nowrap">
			<span class="glyphicons glyphicons-circle-minus delete" style="color:rgb(200,0,0);margin-left:5px;cursor:pointer;">
		</td>
	</tr>
	
	<tr>
		<td colspan="4">
			{if $is_writeable && $active_worker->hasPriv("contexts.{$peek_context}.update")}<button type="button" class="add"><span class="glyphicons glyphicons-circle-plus" style="color:rgb(0,180,0);"></span></button>{/if}
		</td>
	</tr>
	</table>
	
</fieldset>

{if !empty($custom_fieldset->id) && $is_writeable}
<fieldset class="delete" style="display:none;">
	<legend>Delete this custom fieldset?</legend>
	<p>Are you sure you want to permanently delete this custom fieldset?  All custom fields and their values will be removed.</p>
	<button type="button" class="green" onclick="var $frm=$(this).closest('form');$frm.find('input:hidden[name=do_delete]').val('1');$frm.find('button.submit').click();"> {'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" class="red" onclick="$(this).closest('fieldset').hide().next('div.buttons').show();"> {'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="buttons">
{if $active_worker->hasPriv("contexts.{$peek_context}.update") && (empty($custom_fieldset->id) || $is_writeable)}
	<button type="button" class="submit" onclick="genericAjaxPopupPostCloseReloadView('{$layer}','frmCustomFieldsetPeek','{$view_id}',false,'custom_fieldset_save');"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate}</button>
{/if}
{if !empty($custom_fieldset->id) && $is_writeable && $active_worker->hasPriv("contexts.{$peek_context}.delete")}
	<button type="button" onclick="$(this).closest('div.buttons').hide().prev('fieldset.delete').show();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.delete'|devblocks_translate|capitalize}</button>
{/if}
</div>

</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFetch('{$layer}');
	
	$popup.one('popup_open',function(event,ui) {
		var $popup = genericAjaxPopupFetch('{$layer}');
		$popup.css('overflow', 'inherit');
		
		{if empty($custom_fieldset->id)}
		$popup.dialog('option','title', 'Create Custom Fieldset');
		{else}
		$popup.dialog('option','title', 'Modify Custom Fieldset');
		{/if}

		{if $is_writeable && $active_worker->hasPriv("contexts.{$peek_context}.update")}
		$popup.find('input:text:first').focus().select();
		{else}
		$popup.find('input,select,textarea').attr('disabled','disabled');
		{/if}
		
		// Owners
		
		var $owners_menu = $popup.find('ul.owners-menu');
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
		
		$popup.find('fieldset.cfields button.add').click(function() {
			var $parent = $(this).closest('tr');
			var $template = $parent.siblings('.cfields-add-template');
			var $tr = $template.clone();
			$tr.removeClass('cfields-add-template');
			$tr.insertBefore($template).fadeIn();
			$tr.find('input:text:first').focus();
			
			var temp_id = new Date().getTime();
			
			// Generate a temporary ID
			$tr.find('[name]').each(function() {
				var $this = $(this);
				$this.attr('name', $this.attr('name').replace('##id##', temp_id));
			});
			$tr.find('[value]').each(function() {
				var $this = $(this);
				$this.attr('value', $this.attr('value').replace('##id##', temp_id));
			});
			
			// Show contextual options
			$tr.find('select.context-picker').change(function() {
				var $this = $(this);
				var val = $this.val();
				
				// Reset all options, show the current one
				$this.closest('tr').find('div.params').hide().filter('.params-' + val).fadeIn();
			});
			
		});
		
		$popup.find('fieldset.cfields table').sortable({ 
			items:'TR.sortable',
			helper: 'original',
			forceHelperSize: true,
			handle: 'span.ui-icon-arrowthick-2-n-s'
		});
		
		// Custom field deletion
		$popup.find('fieldset.cfields table').on('click', 'span.delete', function() {
			var $tr = $(this).closest('tr');
			
			// Check if the row is being deleted, and if so, undelete
			var $del = $tr.find('input:hidden[name^=deletes]');

			if($del.length == 0)
				return;
			
			// Undelete
			if($del.val() == '1') {
				$tr.fadeTo('fast', 1.0);
				$del.val('');
				
			// Delete
			} else {
				if($tr.find('select[name^=types]').length > 0) {
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