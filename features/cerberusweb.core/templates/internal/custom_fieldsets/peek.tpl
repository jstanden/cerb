{$is_writeable = !$custom_fieldset->id || $custom_fieldset->isWriteableByWorker($active_worker)}

{if !$is_writeable}
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
				{if $is_writeable}
				<select name="owner">
					{if !empty($custom_fieldset->id)}
						<option value=""> - transfer - </option>
					{/if}
					
					<option value="w_{$active_worker->id}" {if $custom_fieldset->owner_context==CerberusContexts::CONTEXT_WORKER && $active_worker->id==$custom_fieldset->owner_context_id}selected="selected"{/if}>{'common.me'|devblocks_translate|lower}</option>

					<option value="a_0" {if $custom_fieldset->owner_context==CerberusContexts::CONTEXT_APPLICATION}selected="selected"{/if}>Application: Cerb</option>

					{if !empty($owner_roles)}
					{foreach from=$owner_roles item=role key=role_id}
						<option value="r_{$role_id}" {if $custom_fieldset->owner_context==CerberusContexts::CONTEXT_ROLE && $role_id==$custom_fieldset->owner_context_id}selected="selected"{/if}>{'common.role'|devblocks_translate|capitalize}: {$role->name}</option>
					{/foreach}
					{/if}
					
					{if !empty($owner_groups)}
					{foreach from=$owner_groups item=group key=group_id}
						<option value="g_{$group_id}" {if $custom_fieldset->owner_context==CerberusContexts::CONTEXT_GROUP && $group_id==$custom_fieldset->owner_context_id}selected="selected"{/if}>{'common.group'|devblocks_translate|capitalize}: {$group->name}</option>
					{/foreach}
					{/if}
					
					{if $active_worker->is_superuser}
					{foreach from=$workers item=worker key=worker_id}
						{if !$worker->is_disabled}
						<option value="w_{$worker_id}" {if $custom_fieldset->owner_context==CerberusContexts::CONTEXT_WORKER && $worker_id==$custom_fieldset->owner_context_id && $active_worker->id != $worker_id}selected="selected"{/if}>{'common.worker'|devblocks_translate|capitalize}: {$worker->getName()}</option>
						{/if}
					{/foreach}
					{/if}
					
					{foreach from=$virtual_attendants item=va key=va_id}
						{if $va->isWriteableByActor($active_worker)}
						<option value="v_{$va_id}" {if $custom_fieldset->owner_context==CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT && $va_id==$custom_fieldset->owner_context_id}selected="selected"{/if}>{'common.virtual_attendant'|devblocks_translate|capitalize}: {$va->name}</option>
						{/if}
					{/foreach}
				</select>
				{/if}
				
				{if !empty($custom_fieldset->id)}
				<ul class="bubbles">
					<li>
					
					{if $custom_fieldset->owner_context==CerberusContexts::CONTEXT_ROLE && isset($roles.{$custom_fieldset->owner_context_id})}
					<b>{$roles.{$custom_fieldset->owner_context_id}->name}</b> ({'common.role'|devblocks_translate|capitalize})
					{/if}
					
					{if $custom_fieldset->owner_context==CerberusContexts::CONTEXT_GROUP && isset($groups.{$custom_fieldset->owner_context_id})}
					<b>{$groups.{$custom_fieldset->owner_context_id}->name}</b> ({'common.group'|devblocks_translate|capitalize})
					{/if}
					
					{if $custom_fieldset->owner_context==CerberusContexts::CONTEXT_WORKER && isset($workers.{$custom_fieldset->owner_context_id})}
					<b>{$workers.{$custom_fieldset->owner_context_id}->getName()}</b> ({'common.worker'|devblocks_translate|capitalize})
					{/if}
					
					{if $custom_fieldset->owner_context==CerberusContexts::CONTEXT_VIRTUAL_ATTENDANT && isset($virtual_attendants.{$custom_fieldset->owner_context_id})}
					<b>{$virtual_attendants.{$custom_fieldset->owner_context_id}->name}</b> ({'common.virtual_attendant'|devblocks_translate|capitalize})
					{/if}
					
					{if $custom_fieldset->owner_context==CerberusContexts::CONTEXT_APPLICATION}
					<b>Application</b>
					{/if}
					</li>
				</ul>
				{/if}
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
			<td valign="top" width="1%" nowrap="nowrap">{if $is_writeable}<span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;cursor:move;"></span>{/if}</td>
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
				{if $is_writeable}<span class="cerb-sprite2 sprite-minus-circle delete" style="margin-left:5px;cursor:pointer;"></span>{/if}
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
			<span class="cerb-sprite2 sprite-minus-circle delete" style="margin-left:5px;cursor:pointer;"></span>
		</td>
	</tr>
	
	<tr>
		<td colspan="4">
			{if $is_writeable}<button type="button" class="add"><span class="cerb-sprite2 sprite-plus-circle"></span></button>{/if}
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
{if empty($custom_fieldset->id) || $is_writeable}
	<button type="button" class="submit" onclick="genericAjaxPopupPostCloseReloadView('{$layer}','frmCustomFieldsetPeek','{$view_id}',false,'custom_fieldset_save');"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate}</button>
{/if}
{if !empty($custom_fieldset->id) && $is_writeable}
	<button type="button" onclick="$(this).closest('div.buttons').hide().prev('fieldset.delete').show();"><span class="cerb-sprite2 sprite-cross-circle"></span> {'common.delete'|devblocks_translate|capitalize}</button>
{/if}
</div>

</form>

<script type="text/javascript">
	var $popup = genericAjaxPopupFetch('{$layer}');
	$popup.one('popup_open',function(event,ui) {
		var $popup = genericAjaxPopupFetch('{$layer}');
		var $this = $(this);
		
		{if empty($custom_fieldset->id)}
		$this.dialog('option','title', 'Create Custom Fieldset');
		{else}
		$this.dialog('option','title', 'Modify Custom Fieldset');
		{/if}

		{if $is_writeable}
		$this.find('input:text:first').focus().select();
		{else}
		$this.find('input,select,textarea').attr('disabled','disabled');
		{/if}
		
		$this.find('fieldset.cfields button.add').click(function() {
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
		
		$this.find('fieldset.cfields table').sortable({ 
			items:'TR.sortable',
			helper: 'original',
			forceHelperSize: true,
			handle: 'span.ui-icon-arrowthick-2-n-s'
		});
		
		// Custom field deletion
		$this.find('fieldset.cfields table').on('click', 'span.delete', function() {
			$tr = $(this).closest('tr');
			
			// Check if the row is being deleted, and if so, undelete
			$del = $tr.find('input:hidden[name^=deletes]');

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
	} );
</script>
