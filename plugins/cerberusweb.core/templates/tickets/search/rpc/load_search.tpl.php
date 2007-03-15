<input type="hidden" name="c" value="tickets">
<input type="hidden" name="a" value="loadSearch">
{if !empty($searches)}
<b>Load:</b> <select name="search_id">
	{foreach from=$searches item=search}
		<option value="{$search->id}">{$search->name}
	{/foreach}
</select>
<br>
<input type="submit" value="{$translate->say('common.load')|capitalize}">
{else}
{$translate->say('search.no_saved')}<br>
{/if}
<input type="button" value="{$translate->say('common.cancel')|capitalize}" onclick="clearDiv('{$divName}_control');">
