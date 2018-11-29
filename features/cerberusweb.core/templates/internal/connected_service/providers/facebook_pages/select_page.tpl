<select name="params[page_id]">
	{foreach from=$pages item=page key=page_id}
	<option value="{$page_id}">{$page.name}</option>
	{/foreach}
</select>