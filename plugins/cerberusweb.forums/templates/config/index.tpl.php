<div class="block">

<form action="{devblocks_url}{/devblocks_url}" method="POST">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveTab">
<input type="hidden" name="ext_id" value="forums.config.tab">
<input type="hidden" name="plugin_id" value="{$plugin->id}">

<h2>{$translate->_('forums.ui.forums')}</h2>

<blockquote>

{if !empty($sources)}
<table>
	<tr>
		<td><b>{$translate->_('forums.ui.cfg.name')}</b></td>
		<td><b>{$translate->_('common.url')|upper}</b></td>
		<td><b>{$translate->_('forums.ui.cfg.secret_key')}</b></td>
		<td><b>{$translate->_('common.delete')|capitalize}</b></td>
	</tr>
	{foreach from=$sources item=source key=source_id}
			<tr>
				<td>
					<input type="hidden" name="ids[]" value="{$source->id|escape}">
					<input type="text" name="names[]" size="32" value="{$source->name|escape}">
				</td>
				<td><input type="text" name="urls[]" size="64" value="{$source->url|escape}"></td>
				<td><input type="text" name="keys[]" size="16" value="{$source->secret_key|escape}"></td>
				<td><input type="checkbox" name="deletes[]" value="{$source_id}"></td>
			</tr>
	{/foreach}
</table>
{else}
	{$translate->_('forums.ui.cfg.no_forums')|lower}<br>
{/if}
	<br>
	<b>{$translate->_('forums.ui.cfg.workers')}:</b><br>
	<textarea style="height:80px;width:400px;" name="poster_workers">{$poster_workers_str}</textarea><br>
</blockquote>

<h2>{$translate->_('forums.ui.cfg.add_forum')}</h2>

<blockquote>
	<b>{$translate->_('forums.ui.cfg.name')}:</b><br>
	<input type="text" name="name" size="64"><br>
	<br>
	
	<b>{$translate->_('common.url')|upper}:</b><br>
	<input type="text" name="url" size="64"><br>
	<br>
	
	<b>{$translate->_('forums.ui.cfg.secret_key')}:</b><br>
	<input type="text" name="secret_key" size="64"><br>
	<br>
</blockquote>

<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>

</form>

</div>
