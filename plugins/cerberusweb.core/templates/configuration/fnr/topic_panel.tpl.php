<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formAddressPeek" name="formAddressPeek">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="doFnrTopic">
<input type="hidden" name="delete" value="0">

{if !empty($topic)}
	<h2>Modify Topic</h2>
{else}
	<h2>Add Topic</h2>
{/if}
<input type="hidden" name="id" value="{$topic->id}">

<b>Topic Name:</b> (e.g. product name, company name, etc.)<br>
<input type="text" name="name" size="35" value="{$topic->name}" style="width:98%;"><br>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>

{if !empty($topic->id)}
	<button type="button" onclick="{literal}if(confirm('Are you sure you want to permanently delete this topic?')){this.form.delete.value='1';this.form.submit();}{/literal}"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete2.gif{/devblocks_url}" align="top"> {$translate->_('common.remove')|capitalize}</button>
{/if}

</form>