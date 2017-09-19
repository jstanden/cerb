{$context_ext = Extension_DevblocksContext::get($card->_context)}
<div class="cerb-board-card-type">{$context_ext->manifest->name}</div>
<div>
	<input type="hidden" name="cards[]" value="{$card->_context}:{$card->id}">
	<h3><a href="javascript:;" class="cerb-peek-trigger" data-context="{$card->_context}" data-context-id="{$card->id}">{$card->_label}</a></h3>
	{$board->renderCard($card)}
</div>
