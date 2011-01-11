<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmEditWorkspace">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="doEditWorkspace">
<input type="hidden" name="id" value="{$workspace->id}">
<input type="hidden" name="do_delete" value="0">

<b>{'common.name'|devblocks_translate|capitalize}:</b><br>
<input type="text" name="rename_workspace" value="{$workspace->name}" size="35" style="width:100%;"><br>
<br>

{foreach from=$worklists item=worklist name=worklists key=worklist_id}
{assign var=worklist_view value=$worklist->list_view}
<div class="column">
			<span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;"></span>
			<a href="javascript:;" onclick="if(confirm('Are you sure you want to delete this worklist?')) { $(this).closest('div').remove(); }"><span class="ui-icon ui-icon-trash" style="display:inline-block;vertical-align:middle;"></span></a>
			<input type="hidden" name="ids[]" value="{$worklist->id}">
			<input type="text" name="names[]" value="{$worklist_view->title}" size="45">
			{if isset($contexts.{$worklist->context})}{$contexts.{$worklist->context}->name}{/if}	
	</tr>
</div>
{/foreach}
<br>

<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>
<button type="button" onclick="if(!confirm('Are you sure you want to delete this workspace?')) { return false; }; $frm=$(this.form);$frm.find('input:hidden[name=do_delete]').val('1');$frm.submit();"><span class="cerb-sprite sprite-delete"></span> {'common.delete'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"{'dashboard.edit'|devblocks_translate|capitalize}");
		$('#frmEditWorkspace').sortable({ items: 'DIV.column', placeholder:'ui-state-highlight' });
	});
</script>
