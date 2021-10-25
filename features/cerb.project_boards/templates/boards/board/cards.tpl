{foreach from=$cards item=card}
<div class="cerb-board-card" data-context="{$card->_context}" data-context-id="{$card->id}">
    {include file="devblocks:cerb.project_boards::boards/board/card.tpl"}
</div>
{/foreach}
