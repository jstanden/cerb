<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="viewBuildRss">
<input type="hidden" name="view_id" value="{$view_id}">

<H3>Create RSS Feed</H3>
<br>

<b>Feed Title:</b><br>
<input type="text" name="title" value="{$view->name}" size="45">
<br>

<br>
<button type="button" onclick="this.form.submit();" style=""><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" border="0" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
<button type="button" onclick="toggleDiv('{$view_id}_tips','none');clearDiv('{$view_id}_tips');" style=""><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> Cancel</button>

</form>