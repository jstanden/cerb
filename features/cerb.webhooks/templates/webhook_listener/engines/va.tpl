{$uniq_id = uniqid()}

<div style="padding:5px 10px;">
	A bot behavior will handle all HTTP requests to this webhook. 
</div>

{if $model->extension_params.behavior_id}
{$model_behavior = DAO_TriggerEvent::get($model->extension_params.behavior_id)} 
{/if}

<div style="padding:5px 10px;" id="{$uniq_id}">
	<b>Behavior:</b>
	<p style="margin-left:5px;">
		<select class="cerb-select-bot">
			<option value=""></option>
			{foreach from=$bots item=bot}
			<option value="{$bot->id}" {if $model_behavior->bot_id==$bot->id}selected="selected"{/if}>{$bot->name}</option>
			{/foreach}
		</select>
		
		<select style="display:none;" class="cerb-select-behavior-options">
			{foreach from=$behaviors item=behavior}
			<option value="{$behavior->id}" bot-id="{$behavior->bot_id}">{$behavior->title}</option>
			{/foreach}
		</select>
		
		<select name="extension_params[{$engine->id}][behavior_id]" class="cerb-select-behavior">
			{foreach from=$behaviors item=behavior}
				{if $model_behavior->bot_id == $behavior->bot_id}
				<option value="{$behavior->id}" bot-id="{$behavior->bot_id}" {if $model_behavior->id==$behavior->id}selected="selected"{/if}>{$behavior->title}</option>
				{/if}
			{/foreach}
		</select>
	</p>
</div>

<script type="text/javascript">
$(function() {
	var $div = $('#{$uniq_id}');
	var $select_behavior_options = $div.find('select.cerb-select-behavior-options');
	var $select_bot = $div.find('select.cerb-select-bot');
	var $select_behavior = $div.find('select.cerb-select-behavior');
	
	$select_bot.on('change', function() {
		$select_behavior.find('option').remove();
		
		var $options = $select_behavior_options.find('option[bot-id=' + $(this).val() + ']');
		
		$options.each(function() {
			$select_behavior.append($(this).clone());
		});
	});
});
</script>
