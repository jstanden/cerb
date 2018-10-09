{$uniqid = uniqid()}
{$context_ext = Extension_DevblocksContext::get($card->_context)}
<div class="cerb-board-card-type">{$context_ext->manifest->name}</div>
<div id="{$uniqid}">
	<input type="hidden" name="cards[]" value="{$card->_context}:{$card->id}">
	<h3><a href="javascript:;" class="cerb-peek-trigger" data-context="{$card->_context}" data-context-id="{$card->id}">{$card->_label}</a></h3>
	{$board->renderCard($card)}
</div>

{if $card_is_removed}
<script type="text/javascript">
$(function() {
	var $div = $('#{$uniqid}');
	var $card = $div.closest('.cerb-board-card');
	
	$card.toggle({ 
		effect:'scale',
		duration: 500,
		complete: function() {
			$card.remove();
		}
	});
});
{/if}
</script>