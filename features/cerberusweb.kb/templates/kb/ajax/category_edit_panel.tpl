<form action="javascript:;" method="POST" id="frmKbCategoryEdit" onsubmit="return false;">
<input type="hidden" name="c" value="kb.ajax">
<input type="hidden" name="a" value="saveKbCategoryEditPanelJson">
<input type="hidden" name="id" value="{$category->id}">
<input type="hidden" name="delete_box" value="0">

<b>Name:</b><br>
<input type="text" name="name" value="{$category->name}" style="width:99%;border:solid 1px rgb(180,180,180);"><br>
<br>

{if !empty($category)}
	<input type="hidden" name="parent_id" value="{$category->parent_id}">
{elseif !empty($root_id)}
	<input type="hidden" name="parent_id" value="{$root_id}">
{else}
	<input type="hidden" name="parent_id" value="0">
{/if}

<fieldset id="deleteCategory" class="delete" style="display:none;">
	<legend>Delete this category?</legend>
	<div>
		This will remove this category and all its subcategories. Your article content will not be deleted, but articles will be removed from these categories.
	</div>
	<button type="button" class="delete-confirm red">{'common.yes'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="$(this).closest('form').find('input:hidden[name=delete_box]').val('0');$(this).closest('fieldset').fadeOut().siblings('div.toolbar').fadeIn();">{'common.no'|devblocks_translate|capitalize}</button>
</fieldset>

<div class="toolbar">
	{if $active_worker->hasPriv('core.kb.categories.modify')}<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> {$translate->_('common.save_changes')|capitalize}</button> {/if}
	{if $active_worker->hasPriv('core.kb.categories.modify') && !empty($category)}<button type="button" onclick="$(this).closest('div.toolbar').fadeOut();$('#deleteCategory').fadeIn();"><span class="cerb-sprite2 sprite-cross-circle"></span> {$translate->_('common.delete')|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"Knowledgebase Category");
		
		$frm = $('#frmKbCategoryEdit');

		// Delete
		$frm.find('button.delete-confirm').click(function(e) {
			$frm = $(this).closest('form');
			$frm.find('input:hidden[name=delete_box]').val('1');
			$frm.find('button.submit').click();
		});
		
		// Submit
		$frm.find('button.submit').click(function(e) {
			genericAjaxPost('frmKbCategoryEdit', '', null, function(json) {
				$popup = genericAjaxPopupFetch('peek');
				
				event = jQuery.Event('kb_category_save');
				if(json && json.id)
					event.id = json.id;
				
				genericAjaxPopupClose('peek', event);
			});
		});
		
		$frm.find('input:text:first').focus().select();
	});
</script>
