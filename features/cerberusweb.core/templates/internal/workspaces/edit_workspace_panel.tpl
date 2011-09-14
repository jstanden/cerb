<form action="#" method="post" id="frmEditWorkspace" onsubmit="return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="doEditWorkspace">
<input type="hidden" name="id" value="{$workspace->id}">
{if !empty($workspace)}<input type="hidden" name="do_delete" value="0">{/if}

<table cellpadding="2" cellspacing="0" border="0" width="100%" style="margin-bottom:5px;">
	<tr>
		<td width="1%" nowrap="nowrap" align="right">
			<b>{'common.name'|devblocks_translate|capitalize}:</b>
		</td>
		<td width="99%">
			<input type="text" name="rename_workspace" value="{$workspace->name}" size="35" style="width:100%;">
		</td>
	</tr>
	
	{if empty($workspace)}
	<tr>
		<td width="1%" nowrap="nowrap" align="right">
			<b>{'common.owner'|devblocks_translate|capitalize}:</b>
		</td>
		<td width="99%">
			<select name="owner">
				<option value="">me</option>
				
				{if !empty($owner_groups)}
				{foreach from=$owner_groups item=group key=group_id}
					<option value="g_{$group_id}">Group: {$group->name}</option>
				{/foreach}
				{/if}
				
				{if !empty($owner_roles)}
				{foreach from=$owner_roles item=role key=role_id}
					<option value="r_{$role_id}">Role: {$role->name}</option>
				{/foreach}
				{/if}
			</select>
		</td>
	</tr>
	{/if}
</table>

<fieldset>
	<legend>Worklists</legend>
	
	{foreach from=$worklists item=worklist name=worklists key=worklist_id}
	{assign var=worklist_view value=$worklist->list_view}
	<div class="column">
		<span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;cursor:move;"></span><!--
		--><a href="javascript:;" onclick="if(confirm('Are you sure you want to delete this worklist?')) { $(this).closest('div').remove(); }"><span class="ui-icon ui-icon-trash" style="display:inline-block;vertical-align:middle;"></span></a><!--
		--><input type="hidden" name="ids[]" value="{$worklist->id}"><!--
		--><input type="text" name="names[]" value="{$worklist_view->title}" size="45"><!--
		--><span>{if isset($contexts.{$worklist->context})}{$contexts.{$worklist->context}->name}{/if}</span>	
	</div>
	{/foreach}
</fieldset>

<fieldset>
	<legend>Add Worklist</legend>
	<div>
		<select name="add_context">
			{foreach from=$contexts item=mft key=mft_id}
			{if isset($mft->params['options'][0]['workspace'])}
			<option value="{$mft_id}">{$mft->name}</option>
			{/if}
			{/foreach}
		</select>
		<button type="button" class="add" onclick="">+</button>
	</div>
</fieldset>

<button type="button" onclick="genericAjaxPopupPostCloseReloadView('peek','frmEditWorkspace','',false,'workspace_save');"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')}</button>
{if !empty($workspace)}<button type="button" onclick="if(!confirm('Are you sure you want to delete this workspace?')) { return false; }; $('#frmEditWorkspace').find('input:hidden[name=do_delete]').val('1');genericAjaxPopupPostCloseReloadView('peek','frmEditWorkspace','',false,'workspace_delete');"><span class="cerb-sprite2 sprite-cross-circle-frame"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{if !empty($workspace)}{'dashboard.edit'|devblocks_translate|capitalize}{else}Create Workspace{/if}");
		$('#frmEditWorkspace').sortable({ items: 'DIV.column', placeholder:'ui-state-highlight' });
		
		$frm = $('#frmEditWorkspace');
		$frm.find('button.add').click(function() {
			$this = $(this);
			$columns = $(this.form).find('fieldset').first();
			$new_column = $('<div class="column"></div>');
			
			$select = $(this).siblings('select[name=add_context]');
			
			$('<span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;"></span>').appendTo($new_column);
			$('<a href="javascript:;" onclick="if(confirm(\'Are you sure you want to delete this worklist?\')) { $(this).closest(\'div\').remove(); }"><span class="ui-icon ui-icon-trash" style="display:inline-block;vertical-align:middle;"></span></a>').appendTo($new_column);
			$('<input type="hidden" name="ids[]" value="'+$select.val()+'">').appendTo($new_column);
			$('<input type="text" name="names[]" value="'+$select.find(':selected').text()+'" size="45">').appendTo($new_column);
			$('<span>'+$select.find(':selected').text()+'</span>').appendTo($new_column);
			
			$new_column.appendTo($columns);;
		});
		
		$frm.find('input:text:first').focus().select();
	});
</script>
