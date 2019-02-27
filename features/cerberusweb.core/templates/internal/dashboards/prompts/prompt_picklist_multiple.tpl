{$uniqid = uniqid()}
{$prompt_value = $tab_prefs.{$prompt.placeholder}|default:$prompt.default}

<div id="{$uniqid}" style="display:inline-block;vertical-align:middle;">
	<div class="bubble cerb-filter-editor" style="padding:5px;display:block;">
		<b>{$prompt.label}</b> 
		
		<div class="cerb-item-container bubbles">
			<button type="button" class="cerb-menu--trigger">
				<span class="glyphicons glyphicons-search" style="cursor:pointer;"></span>
			</button>
			
			{foreach from=$prompt.params.options item=option key=option_key}
				{if is_array($prompt_value) && in_array($option, $prompt_value)}
				<div class="bubble">
					{if is_string($option_key)}
						{$option_key}
					{else}
						{$option}
					{/if}
				</div>
				{/if}
			{/foreach}
		</div>
		
		<div class="cerb-popupmenu cerb-float">
			<span class="glyphicons glyphicons-circle-remove cerb-button--close" style="font-size:150%;cursor:pointer;position:absolute;top:-5px;right:-5px;"></span>
			{foreach from=$prompt.params.options item=option key=option_key}
			<div>
				<label>
					<input type="checkbox" name="prompts[{$prompt.placeholder}][]" value="{$option}" {if is_array($prompt_value) && in_array($option, $prompt_value)}checked="checked"{/if}>
					{if is_string($option_key)}
						{$option_key}
					{else}
						{$option}
					{/if}
				</label>
			</div>
			{/foreach}
		</div>
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $filter = $('#{$uniqid}');
	var $filter_menu = $filter.find('div.cerb-popupmenu');
	var $filter_menu_trigger = $filter.find('button.cerb-menu--trigger')
	var $filter_container = $filter.find('div.cerb-item-container');
	
	$filter_menu_trigger
		.click(function(e) {
			$filter_menu.toggle();
		})
	;
	
	$filter_menu.on('change', function(e) {
		e.stopPropagation();
		
		$filter_container.find('div.bubble').remove();
		
		// Copy selections to the filter bubbles
		$filter_menu.find('input:checkbox').each(function() {
			var $checkbox = $(this);
			
			if($checkbox.is(':checked')) {
				var $bubble = $('<div class="bubble"/>')
					.text($.trim($checkbox.parent().text()))
					.appendTo($filter_container)
				;
			}
		});
	})
	
	$filter.find('.cerb-button--close')
		.click(function(e) {
			$filter_menu.fadeOut();
		})
	;
});
</script>