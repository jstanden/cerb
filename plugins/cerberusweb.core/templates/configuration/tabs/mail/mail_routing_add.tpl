<input type="text" name="add_pattern[]" size="45">
<select name="add_team_id[]">
{if !empty($teams)}
{foreach from=$teams item=team key=team_id}
	<option value="{$team_id}">{$team->name}
{/foreach}
{/if}
</select>
