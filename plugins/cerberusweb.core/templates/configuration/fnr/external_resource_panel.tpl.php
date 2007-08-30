<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formAddressPeek" name="formAddressPeek">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="doFnrResource">
<input type="hidden" name="delete" value="0">

{if !empty($resource)}
	<h2>Edit External Knowledge Resource</h2>
{else}
	<h2>Add External Knowledge Resource</h2>
{/if}
<input type="hidden" name="id" value="{$resource->id}">

<b>Topic:</b> (e.g. product name, company name, etc.)<br>
<select name="topic_id" onchange="toggleDiv('fnrAddTopic',(selectValue(this)=='')?'inline':'none');">
	{foreach from=$topics item=topic key=topic_id name=topics}
		<option value="{$topic_id}" {if $resource->topic_id==$topic_id}selected{/if}>{$topic->name}</option>
	{/foreach}
	<option value=""> -- add new topic: (below) -- </option>
</select>
<div id="fnrAddTopic" style="display:{if empty($topics)}inline{else}none{/if};"> <input type="text" name="topic_name" size="24" value="" style="width:98%;"></div>
<br>
<br>

<b>Name:</b> (e.g. "Community Forums")<br>
<input type="text" name="name" size="24" value="{$resource->name}" style="width:98%;"><br>
<br>

<b>Search Adapter URL:</b> <a href="javascript:;" title="what's this?"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/help.gif{/devblocks_url}" border="0" align="top"></a><br>
<input type="text" name="url" size="24" value="{$resource->url}" style="width:98%;"><br>
(use <b>#find#</b> in the URL to represent search terms)<br>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
{if !empty($resource->id)}
	<button type="button" onclick="this.form.delete.value='1';this.form.submit();"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete2.gif{/devblocks_url}" align="top"> {$translate->_('common.remove')|capitalize}</button>
{/if}

</form>