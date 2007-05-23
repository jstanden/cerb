{include file="$path/feeds/menu.tpl.php"}

<div class="block">
{if empty($feed->id)}
<h2>Create Feed (RSS 2.0)</h2>
{else}
<h2>Modify Feed '{$feed->title}'</h2>
{/if}
<br>

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="feeds">
<input type="hidden" name="a" value="saveFeed">
<input type="hidden" name="id" value="{$feed->id}">

<b>Feed Title:</b><br>
<input type="text" name="title" size="45" value="{$feed->title|escape:"htmlall"}"><br>
<br>

<h3>Stream these events:</h3>
<br>
{foreach from=$plugins item=plugin key=plugin_id name=plugins}
	{if !empty($plugin->event_points)}
	<b>{$plugin->name}:</b><br>
	<blockquote style="margin-top:0px;">
	{foreach from=$plugin->event_points item=point name=points key=point_id}
		<label><input type="checkbox" name="events[]" value="{$point_id}" {if isset($feed->params.$point_id)}checked{/if}> {$point->name}</label><br> 
	{/foreach}
	</blockquote>
	{/if}
{/foreach}

<br>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>
<button type="button" onclick="javascript:document.location='{devblocks_url}c=feeds{/devblocks_url}';"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/delete.gif{/devblocks_url}" align="top"> {$translate->_('common.cancel')|capitalize}</button>

</form>
</div>