<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmEditWorkspace">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="doEditWorkspace">
<input type="hidden" name="id" value="{$workspace->id}">
<input type="hidden" name="request" value="{$request}">
<input type="hidden" name="do_delete" value="0">

<b>{'common.name'|devblocks_translate|capitalize}:</b><br>
<input type="text" name="rename_workspace" value="{$workspace->name}" size="35" style="width:100%;"><br>
<br>

<fieldset>
	<legend>Worklists</legend>
	
	{foreach from=$worklists item=worklist name=worklists key=worklist_id}
	{assign var=worklist_view value=$worklist->list_view}
	<div class="column">
		<span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;"></span><!--
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

<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>
<button type="button" onclick="if(!confirm('Are you sure you want to delete this workspace?')) { return false; }; $frm=$(this.form);$frm.find('input:hidden[name=do_delete]').val('1');$frm.submit();"><span class="cerb-sprite sprite-delete"></span> {'common.delete'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{'dashboard.edit'|devblocks_translate|capitalize}");
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
	});
</script>
