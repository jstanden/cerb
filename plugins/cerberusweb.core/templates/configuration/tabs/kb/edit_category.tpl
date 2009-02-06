<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveKbCategory">
<input type="hidden" name="id" value="{$category->id}">
<div class="block">
<table cellpadding="2" cellspacing="0" border="0">
	<tr>
		<td colspan="2">
			{if empty($category->id)}
			<h2>Add Topic</h2>
			{else}
			<h2>{$category->name}</h2>
			{/if}
		</td>
	</tr>
	<tr>
		<td width="0%" nowrap="nowrap" valign="top"><b>Name:</b></td>
		<td width="100%"><input type="text" name="category_name" value="{$category->name|escape}" size="45"></td>
	</tr>

	{*
	<tr>
		<td width="0%" nowrap="nowrap" valign="top"><b>Permissions:</b></td>
		<td width="100%">
			{foreach from=$groups item=group key=group_id}
				<input type="hidden" name="group_id[]" value="{$group_id}">
				<select name="group_acl[]">
					<option value="0"></option>
					<option value="1">Read Only</option>
					<option value="2">Read/Write</option>
				</select>
				{$group->name}<br>
			{/foreach}
		</td>
	</tr>
	*}
	
	<tr>
		<td colspan="2">
			<input type="hidden" name="delete_box" value="0">
			<div id="deleteCategory" style="display:none;">
				<div style="background-color:rgb(255,220,220);border:1px solid rgb(200,50,50);margin:10px;padding:5px;">
					<h3>Delete Category</h3>
					This will remove this category and all its subcategories. Your 
					article content will not be deleted, but articles will be removed  
					from these categories.<br>
					<button type="button" onclick="this.form.delete_box.value='1';this.form.submit();">Delete</button>
					<button type="button" onclick="this.form.delete_box.value='0';toggleDiv('deleteCategory','none');">Cancel</button>
				</div>
				<br>
			</div>
		
			<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
			{if !empty($category)}<button type="button" onclick="toggleDiv('deleteCategory','block');"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.remove')|capitalize}</button>{/if}
		</td>
	</tr>

</table>
</div>
