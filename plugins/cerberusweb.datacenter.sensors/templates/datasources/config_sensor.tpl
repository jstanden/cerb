<b>Sensor:</b>
<select name="params[sensor_id]">
	{foreach from=$sensors item=sensor}
	<option value="{$sensor->id}" {if $params.sensor_id==$sensor->id}selected="selected"{/if}>{$sensor->name}</option>
	{/foreach}
</select>
