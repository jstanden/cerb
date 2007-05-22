<H1>Community</H1>
<br>

<div class="block">
<H2>Add Community Tool</H2>
<br>

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="community">
<input type="hidden" name="a" value="addCommunityTool">

<b>Community:</b><br>
<select name="community_id">
	{foreach from=$communities item=community}
	<option value="{$community->id}">{$community->name}</option>
	{/foreach}
</select><br>
<br>

<b>Tool:</b><br>
<select name="extension_id">
	{foreach from=$tool_manifests item=tool}
	<option value="{$tool->id}">{$tool->name}</option>
	{/foreach}
</select><br>
<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
<button type="button" onclick="javascript:document.location='{devblocks_url}c=community{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>
</form>
</div>
