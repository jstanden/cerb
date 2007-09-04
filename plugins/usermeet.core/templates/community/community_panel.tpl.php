<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="0%" nowrap="nowrap" style="padding-right:5px;"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/history.gif{/devblocks_url}" align="absmiddle"></td>
		<td align="left" width="100%" nowrap="nowrap">
			{if empty($community)}
				<h1>Add Community</h1>
			{else}
				<h1>Modify Community</h1>
			{/if}
		</td>
	</tr>
</table>

<form action="{devblocks_url}{/devblocks_url}" method="POST">
<input type="hidden" name="c" value="community">
<input type="hidden" name="a" value="editCommunity">
<input type="hidden" name="id" value="{$community->id}">
<input type="hidden" name="delete" value="0">

<b>Community Name:</b><br>
<input type="text" name="name" size="" value="{$community->name}" style="width:98%;">

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="absmiddle"> {$translate->_('common.save_changes')|capitalize}</button>
{if !empty($community)}<button type="button" onclick="{literal}if(confirm('Are you sure you want to permanently delete this community?')){this.form.delete.value='1';this.form.submit();}{/literal}"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete2.gif{/devblocks_url}" align="absmiddle"> {$translate->_('common.delete')|capitalize}</button>{/if}

</form>

<br>
