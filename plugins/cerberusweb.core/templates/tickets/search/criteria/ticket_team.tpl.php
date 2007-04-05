In Teams:<br>
{foreach from=$teams item=team}
<label><input name="team_id[]" type="checkbox" value="{$team->id}">{$team->name}</label><br>
{/foreach}
