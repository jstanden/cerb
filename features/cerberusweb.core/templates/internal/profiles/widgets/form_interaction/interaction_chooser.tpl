<div>
{if !$interactions}
	No interactions are configured.
{else}
	<input type="hidden" name="interaction" value="">
	{foreach from=$interactions item=interaction key=interaction_label}
	<button type="button" data-label="{$interaction_label}">{$interaction_label}</button>
	{/foreach}
{/if}
</div>

<script type="text/javascript">
$(function() {
	var $widget = $('#widget{$widget->id}');
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