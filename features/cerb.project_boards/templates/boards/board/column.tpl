<div class="cerb-board-column-toolbar">
	<div class="cerb-board-column-toolbar-buttons">
		<button type="button" class="cerb-board-column-edit" data-context="{Context_ProjectBoardColumn::ID}" data-context-id="{$column->id}"><span class="glyphicons glyphicons-cogwheel"></span></button>
		<button type="button" class="cerb-board-card-add" data-context="{CerberusContexts::CONTEXT_TASK}" data-query="isCompleted:no"><span class="glyphicons glyphicons-circle-plus"></span></button>
	</div>
	<h2>{$column->name}</h2>
</div>
<form action="#">
{foreach from=$column->getCards() item=card}
<div class="cerb-board-card">
{include file="devblocks:cerb.project_boards::boards/board/card.tpl"}
</div>
{/foreach}
</form>
