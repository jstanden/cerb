<form action="#" method="post" id="frmEditWorkspaceTab" onsubmit="return false;">
<input type="hidden" name="c" value="pages">
<input type="hidden" name="a" value="doEditWorkspaceTab">
<input type="hidden" name="id" value="{$workspace_tab->id}">
<input type="hidden" name="workspace_page_id" value="{$workspace_tab->workspace_page_id}">
{if !empty($workspace_tab)}<input type="hidden" name="do_delete" value="0">{/if}

<table cellpadding="2" cellspacing="0" border="0" width="100%" style="margin-bottom:5px;">
	<tr>
		<td width="1%" nowrap="nowrap" align="right" valign="top">
			<b>{'common.name'|devblocks_translate|capitalize}:</b>
		</td>
		<td width="99%">
			<input type="text" name="name" value="{$workspace_tab->name}" size="35" style="width:100%;">
		</td>
	</tr>
	
	{if !empty($workspace_tab->extension_id)}
	<tr>
		<td>
			<b>{'common.type'|devblocks_translate|capitalize}:</b>
		</td>
		<td>
			{$tab_extension = DevblocksPlatform::getExtension($workspace_tab->extension_id, false)}
			{if $tab_extension}
				{$tab_extension->params.label|devblocks_translate|capitalize}
			{/if}
		</td>
	</tr>
	{/if}
	
</table>

{if empty($workspace_tab->extension_id)}
<fieldset>
	<legend>Worklists</legend>
	
	<b>{'common.add'|devblocks_translate|capitalize}:</b> 
	<select name="add_context">
		<option value="">- {'common.choose'|devblocks_translate|lower} -</option>
		{foreach from=$contexts item=mft key=mft_id}
		{if isset($mft->params['options'][0]['workspace'])}
		<option value="{$mft_id}">{$mft->name}</option>
		{/if}
		{/foreach}
	</select>
	
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
{/if}

<button type="button" onclick="genericAjaxPopupPostCloseReloadView(null,'frmEditWorkspaceTab','',false,'workspace_save');"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')}</button>
{if !empty($workspace_tab)}<button type="button" onclick="if(!confirm('Are you sure you want to delete this tab?')) { return false; }; $('#frmEditWorkspaceTab').find('input:hidden[name=do_delete]').val('1');genericAjaxPopupPostCloseReloadView(null,'frmEditWorkspaceTab','',false,'workspace_delete');"><span class="cerb-sprite2 sprite-cross-circle"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{if !empty($workspace_tab)}Edit Tab{else}Add Tab{/if}");
		$('#frmEditWorkspaceTab').sortable({ items: 'DIV.column', placeholder:'ui-state-highlight' });
		
		$frm = $('#frmEditWorkspaceTab');
		
		{if empty($workspace_tab->extension_id)}
			$frm.find('select[name=add_context]').change(function() {
				$select = $(this);
				
				if($select.val() == '')
					return;
				
				$columns = $(this.form).find('fieldset').first();
				$new_column = $('<div class="column"></div>');
				
				$('<span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;"></span>').appendTo($new_column);
				$('<a href="javascript:;" onclick="if(confirm(\'Are you sure you want to delete this worklist?\')) { $(this).closest(\'div\').remove(); }"><span class="ui-icon ui-icon-trash" style="display:inline-block;vertical-align:middle;"></span></a>').appendTo($new_column);
				$('<input type="hidden" name="ids[]" value="'+$select.val()+'">').appendTo($new_column);
				$('<input type="text" name="names[]" value="'+$select.find(':selected').text()+'" size="45">').appendTo($new_column);
				$('<span>'+$select.find(':selected').text()+'</span>').appendTo($new_column);
				
				$select.val('');

				$new_column.appendTo($columns);
				
				$new_column.find('input:text:first').select().focus();
			});
		{/if}
		
		$frm.find('input:text:first').focus().select();
	});
</script>
