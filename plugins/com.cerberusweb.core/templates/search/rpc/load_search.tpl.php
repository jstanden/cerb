<input type="hidden" name="c" value="core.module.search">
<input type="hidden" name="a" value="loadSearch">
{if !empty($searches)}
<b>Load:</b> <select name="search_id">
	{foreach from=$searches item=search}
		<option value="{$search->id}">{$search->name}
	{/foreach}
</select>
<br>
<input type="submit" value="Load Search">
{else}
No saved searches.
<br>
{/if}
<input type="button" value="Cancel" onclick="clearDiv('{$divName}_control');">
