<div>
{if !$interactions}
	No interactions are available.
{else}
	<input type="hidden" name="interaction" value="">
	{foreach from=$interactions item=interaction key=interaction_key}
		{if !$interaction.button.hidden}
		<button type="button" data-label="{$interaction_key}">
			{if $interaction.button.icon}<span class="glyphicons glyphicons-{$interaction.button.icon}"></span>{/if}
			{$interaction.button.label|default:$interaction_key}
		</button>
		{/if}
	{/foreach}
{/if}
</div>

{$script_id = uniqid('script')}
<script type="text/javascript" id="{$script_id}">
$(function() {
	var $this = $('#{$script_id}')
	var $widget = $this.closest('[id^=cardWidget]');
	var $form = $widget.find('> form');
	
	$form.find('button').on('click', function(e) {
		e.stopPropagation();
		
		var $button = $(this);
		$form.find('input[name=interaction]').val($button.attr('data-label'));
		
		var evt = $.Event('cerb-form-builder-submit');
		$form.triggerHandler(evt);
	});
});
</script>