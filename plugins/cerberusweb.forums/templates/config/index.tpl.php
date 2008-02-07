<div class="block">

<form action="{devblocks_url}{/devblocks_url}" method="POST">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveConfigurePlugin">
<input type="hidden" name="plugin_id" value="{$plugin->id}">

<h2>Forums</h2>

<blockquote>
{if !empty($sources)}
	{foreach from=$sources item=source key=source_id}
		<h3>{$source->name}</h3>
		<input type="hidden" name="ids[]" value="{$source->id|escape}">
		<input type="text" name="names[]" size="32" value="{$source->name|escape}">
		<input type="text" name="urls[]" size="64" value="{$source->url|escape}">
		<input type="text" name="keys[]" size="16" value="{$source->secret_key|escape}">
		<input type="checkbox" name="deletes[]" value="{$source_id}">
		<br>
	{/foreach}
{else}
	No forums defined.<br>
{/if}
	<br>
	<b>Treat these forum posters as helpdesk workers:</b><br>
	<textarea style="height:80px;width:98%;" name="poster_workers">{$poster_workers_str}</textarea><br>
</blockquote>

<h2>Add Forum</h2>

<blockquote>
	<b>Name:</b><br>
	<input type="text" name="name" size="64"><br>
	<br>
	
	<b>URL:</b><br>
	<input type="text" name="url" size="64"><br>
	<br>
	
	<b>Secret Key:</b><br>
	<input type="text" name="secret_key" size="64"><br>
	<br>
</blockquote>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>

</form>

</div>