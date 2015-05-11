{$uniq_id = uniqid()}

<div id="{$uniq_id}">
	<div>
		<b>Worklist columns:</b> (leave blank for default)
	</div>
	
	{foreach from=$history_params.columns item=selected_token}
	<div style="margin:3px;" class="column">
		<span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;cursor:move;" title="Click and drag to rearrange"></span>
	
		<select name="history_columns[]">
		<option value=""></option>
		{foreach from=$history_columns item=column key=token}
			<option value="{$token}" {if $token==$selected_token}selected="selected"{/if}>{$column->db_label|capitalize}</option>
		{/foreach}
		</select>
		
		<button type="button" onclick="$(this).closest('div').remove();"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span></button>
	</div>
	{/foreach}
	
	<div style="margin:3px;display:none;" class="column template">
		<span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;cursor:move;" title="Click and drag to rearrange"></span>
	
		<select name="history_columns[]">
		<option value=""></option>
		{foreach from=$history_columns item=column key=token}
			<option value="{$token}">{$column->db_label|capitalize}</option>
		{/foreach}
		</select>
		
		<button type="button" onclick="$(this).closest('div').remove();"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span></button>
	</div>
	
	<button type="button" class="add-column"><span class="glyphicons glyphicons-circle-plus"></span></button>
	
</div>

<script type="text/javascript">
$(function(e) {
	var $container = $('#{$uniq_id}');
		
	$container
		.sortable({
			items: 'DIV.column',
			handle: 'span.ui-icon-arrowthick-2-n-s',
			placeholder:'ui-state-highlight'
		})
		;
	
	$container
		.find('button.add-column')
		.click(function(e) {
			var $template = $container.find('div.template');
			$template.clone().removeClass('template').insertBefore($template).focus().fadeIn();
		})
		;
});
</script>