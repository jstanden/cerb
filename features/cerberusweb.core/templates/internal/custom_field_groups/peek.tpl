<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmCustomFieldGroupPeek" name="frmCustomFieldGroupPeek" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="custom_field_groups">
<input type="hidden" name="action" value="saveCustomFieldGroupPeek">
<input type="hidden" name="id" value="{$custom_field_group->id}">
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
				<input type="text" name="name" value="{$custom_field_group->name}" style="border:1px solid rgb(180,180,180);padding:2px;width:98%;"><br>
			</td>
		</tr>
		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				<b>{'common.type'|devblocks_translate|capitalize}:</b><br>
			</td>
			<td width="99%">
				{if !empty($custom_field_group->id)}
					<input type="hidden" name="context" value="{$custom_field_group->context}">
					{if $contexts.{$custom_field_group->context}}
						{$contexts.{$custom_field_group->context}->name}
					{/if}
				{else}
				<select name="context">
					{foreach from=$contexts item=ctx key=k}
					<option value="{$k}" {if $custom_field_group->context==$k}selected="selected"{/if}>{$ctx->name}</option>
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
				<select name="owner">
					{if !empty($custom_field_group->id)}
						<option value=""> - transfer - </option>
					{/if}
					
					<option value="w_{$active_worker->id}" {if $custom_field_group->owner_context==CerberusContexts::CONTEXT_WORKER && $active_worker->id==$custom_field_group->owner_context_id}selected="selected"{/if}>me</option>

					{if !empty($owner_roles)}
					{foreach from=$owner_roles item=role key=role_id}
						<option value="r_{$role_id}" {if $custom_field_group->owner_context==CerberusContexts::CONTEXT_ROLE && $role_id==$custom_field_group->owner_context_id}selected="selected"{/if}>Role: {$role->name}</option>
					{/foreach}
					{/if}
					
					{if !empty($owner_groups)}
					{foreach from=$owner_groups item=group key=group_id}
						<option value="g_{$group_id}" {if $custom_field_group->owner_context==CerberusContexts::CONTEXT_GROUP && $group_id==$custom_field_group->owner_context_id}selected="selected"{/if}>Group: {$group->name}</option>
					{/foreach}
					{/if}
					
					{if $active_worker->is_superuser}
					{foreach from=$workers item=worker key=worker_id}
						{if empty($worker->is_disabled)}
						<option value="w_{$worker_id}" {if $custom_field_group->owner_context==CerberusContexts::CONTEXT_WORKER && $worker_id==$custom_field_group->owner_context_id && $active_worker->id != $worker_id}selected="selected"{/if}>Worker: {$worker->getName()}</option>
						{/if}
					{/foreach}
					{/if}
				</select>
				
				{if !empty($custom_field_group->id)}
				<ul class="bubbles">
					<li>
					{if $custom_field_group->owner_context==CerberusContexts::CONTEXT_ROLE && isset($roles.{$custom_field_group->owner_context_id})}
					<b>{$roles.{$custom_field_group->owner_context_id}->name}</b> (Role)
					{/if}
					
					{if $custom_field_group->owner_context==CerberusContexts::CONTEXT_GROUP && isset($groups.{$custom_field_group->owner_context_id})}
					<b>{$groups.{$custom_field_group->owner_context_id}->name}</b> (Group)
					{/if}
					
					{if $custom_field_group->owner_context==CerberusContexts::CONTEXT_WORKER && isset($workers.{$custom_field_group->owner_context_id})}
					<b>{$workers.{$custom_field_group->owner_context_id}->getName()}</b> (Worker)
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
	{counter name=field_pos start=0 print=false}
	{foreach from=$custom_fields item=f key=field_id name=fields}
		{assign var=type_code value=$f->type}
		<tr class="sortable">
			<td valign="top" width="1%" nowrap="nowrap"><span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;cursor:move;"></span></td>
			<td valign="top" width="1%" nowrap="nowrap">{$types.$type_code}</td>
			<td valign="top" width="98%">
				<input type="hidden" name="types[]" value="{$f->type}">
				<input type="hidden" name="ids[]" value="{$field_id}">
				<input type="hidden" name="deletes[]" value="">
				<input type="text" name="names[]" value="{$f->name}" placeholder="Enter a name for this custom field" size="35" style="width:100%;">
				{if $type_code != 'D' && $type_code != 'X'}
					<input type="hidden" name="options[]" value="">
				{else}
				<div class="options" style="">
				<textarea cols="35" rows="6" name="options[]" style="width:100%;" placeholder="Enter choices (one per line)">{foreach from=$f->options item=opt}{$opt|cat:"\r\n"}{/foreach}</textarea>
				</div>
				{/if}
			</td>
			<td width="1%" valign="top" nowrap="nowrap">
				<span class="cerb-sprite2 sprite-minus-circle delete" style="margin-left:5px;cursor:pointer;"></span>
			</td>
		</tr>
	{/foreach}
	
	<tr class="cfields-add-template sortable" style="display:none;">
		<td width="1%" valign="top" nowrap="nowrap"><span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;cursor:move;"></span></td>
		<td width="1%" valign="top" nowrap="nowrap">
			<select name="types[]" onchange="var val = selectValue(this); var $div = $(this).closest('tbody').find('div.options'); if(val=='X' || val=='D') $div.fadeIn(); else $div.fadeOut();">
				{foreach from=$types item=type key=type_code}
				<option value="{$type_code}">{$type}</option>
				{/foreach}
			</select>
		</td>
		<td width="97%" valign="top" nowrap="nowrap">
			<input type="hidden" name="ids[]" value="">
			<input type="hidden" name="deletes[]" value="">
			<input type="text" name="names[]" value="" placeholder="Enter a name for this custom field" size="35" style="width:100%;">
			<div class="options" style="display:none;">
			<textarea cols="35" rows="6" name="options[]" style="width:100%;" placeholder="Enter choices (one per line)"></textarea>
			</div>
		</td>
		<td width="1%" valign="top" nowrap="nowrap">
			<span class="cerb-sprite2 sprite-minus-circle delete" style="margin-left:5px;cursor:pointer;"></span>
		</td>
	</tr>
	
	<tr>
		<td colspan="4">
			<button type="button" class="add"><span class="cerb-sprite2 sprite-plus-circle"></span></button>
		</td>
	</tr>
	</table>
	
</fieldset>

{if !empty($custom_field_group->id) && $custom_field_group->isWriteableByWorker($active_worker)}
<fieldset class="delete" style="display:none;">
	<legend>Delete this custom field group?</legend>
	<p>Are you sure you want to permanently delete this custom field group?  All custom fields and their values will be removed.</p>
	<button type="button" class="green" onclick="var $frm=$(this).closest('form');$frm.find('input:hidden[name=do_delete]').val('1');$frm.find('button.submit').click();"> {'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" class="red" onclick="$(this).closest('fieldset').hide().next('div.buttons').show();"> {'common.no'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="buttons">
{if empty($custom_field_group->id) || $custom_field_group->isWriteableByWorker($active_worker)}
	<button type="button" class="submit" onclick="genericAjaxPopupPostCloseReloadView('{$layer}','frmCustomFieldGroupPeek','{$view_id}',false,'custom_field_group_save');"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')}</button>
{else}
	<fieldset class="delete" style="font-weight:bold;">
		{'error.core.no_acl.edit'|devblocks_translate}
	</fieldset>
{/if}
{if !empty($custom_field_group->id) && $custom_field_group->isWriteableByWorker($active_worker)}
	<button type="button" onclick="$(this).closest('div.buttons').hide().prev('fieldset.delete').show();"><span class="cerb-sprite2 sprite-cross-circle"></span> {$translate->_('common.delete')|capitalize}</button>
{/if}
</div>

</form>

<script type="text/javascript">
	var $popup = genericAjaxPopupFetch('{$layer}');
	$popup.one('popup_open',function(event,ui) {
		var $popup = genericAjaxPopupFetch('{$layer}');
		var $this = $(this);
		
		{if empty($custom_field_group->id)}
		$this.dialog('option','title', 'Create Custom Field Group');
		{else}
		$this.dialog('option','title', 'Modify Custom Field Group');
		{/if}

		$this.find('input:text:first').focus().select();
		
		$this.find('fieldset.cfields button.add').click(function() {
			var $parent = $(this).closest('tr');
			var $template = $parent.siblings('.cfields-add-template');
			var $tbody = $template.clone();
			$tbody.removeClass('cfields-add-template');
			$tbody.insertBefore($template).fadeIn();
			$tbody.find('input:text:first').focus();
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
