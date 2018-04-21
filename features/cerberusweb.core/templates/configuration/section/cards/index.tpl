<h2>{'common.cards'|devblocks_translate|capitalize}</h2>

<div id="setupCards" class="block" style="column-width:200px;">
{if !empty($context_manifests)}
	{foreach from=$context_manifests item=manifest key=manifest_id}
	<div style="padding:3px;">
		<a href="javascript:;" data-context="{$manifest->id}" style="font-weight:bold;">{$manifest->name}</a>
	</div>
	{/foreach}
{/if}
</div>

<script type="text/javascript">
$(function() {
	var $config = $('#setupCards');
	
	$config.on('click', function(e) {
		e.stopPropagation();
		
		var $target = $(e.target);
		var context = $target.attr('data-context');
		
		if(null != context) {
			var $popup = genericAjaxPopup('card', 'c=config&a=handleSectionAction&section=cards&action=showCardPopup&context=' + encodeURIComponent(context), null, false, '75%');
		}
	});
});
</script>