{include file="$tpl_path/preferences/menu.tpl.php"}

<div class="block">
<h2>Notifications</h2>
<br>

<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="preferences">
<input type="hidden" name="a" value="saveNotifications">

{foreach from=$plugins item=plugin key=plugin_id name=plugins}
	{if !empty($plugin->event_points)}
	<b>{$plugin->name}:</b><br>
	<blockquote style="margin-top:0px;">
	{foreach from=$plugin->event_points item=point name=points key=point_id}
		<label><input type="checkbox" name="events[]" value="{$point_id}" {if isset($notifications.$point_id)}checked{/if}> {$point->name}</label><br> 
	{/foreach}
	</blockquote>
	{/if}
{/foreach}

<input type="submit" value="{$translate->_('common.save_changes')}">
</form>
</div>

