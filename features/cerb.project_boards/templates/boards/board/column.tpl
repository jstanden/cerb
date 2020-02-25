<div class="cerb-board-column-toolbar">
	<div class="cerb-board-column-toolbar-buttons">
		<button type="button" class="cerb-board-column-edit" data-context="{Context_ProjectBoardColumn::ID}" data-context-id="{$column->id}"><span class="glyphicons glyphicons-cogwheel"></span></button>
		<button type="button" class="cerb-board-card-add" data-context="{CerberusContexts::CONTEXT_TASK}" data-query="isCompleted:no"><span class="glyphicons glyphicons-circle-plus"></span></button>
	</div>
	<div style="text-align:left;">
		<b style="margin-left:10px;font-size:1.4em;color:rgb(0,0,0);">
			<span class="glyphicons glyphicons-menu-hamburger" style="vertical-align:top;cursor:move;color:rgb(150,150,150);font-size:1.2em;"></span>
			{$column->name}
		</b>
	</div>
</div>
<form action="#">
{foreach from=$column->getCards() item=card}
<div class="cerb-board-card" data-context="{$card->_context}" data-context-id="{$card->id}">
{include file="devblocks:cerb.project_boards::boards/board/card.tpl"}
</div>
{/foreach}
</form>
