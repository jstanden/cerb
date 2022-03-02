{$uniqid = uniqid()}
{$context_ext = Extension_DevblocksContext::get($card->_context)}
<div id="{$uniqid}">
	<input type="hidden" name="cards[]" value="{$card->_context}:{$card->id}">
	{$board->renderCard($card, $column)}
</div>

{if isset($card_is_removed) && $card_is_removed}
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