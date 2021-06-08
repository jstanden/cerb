{$column_dict = DevblocksDictionaryDelegate::instance([
	'caller_name' => 'cerb.toolbar.projectBoardColumn',
	
	'column__context' => Context_ProjectBoardColumn::ID,
	'column_id' => $column->id,

	'worker__context' => CerberusContexts::CONTEXT_WORKER,
	'worker_id' => $active_worker->id
])}

{$toolbar = DevblocksPlatform::services()->ui()->toolbar()->parse($column->toolbar_kata, $column_dict)}

<div class="cerb-board-column-toolbar">
	<div class="cerb-board-column-toolbar-buttons">
		<div data-cerb-toolbar>
			{if $toolbar}
				{DevblocksPlatform::services()->ui()->toolbar()->render($toolbar)}
			{/if}
		</div>
	</div>
	<div style="text-align:left;">
		<span class="glyphicons glyphicons-menu-hamburger" style="margin-left:5px;cursor:move;color:rgb(150,150,150);font-size:0.9em;vertical-align:middle;"></span>
		<a href="javascript:" class="cerb-board-column-edit no-underline" style="margin-left:2px;font-weight:bold;font-size:1.4em;color:rgb(0,0,0);vertical-align:middle;" data-context="{Context_ProjectBoardColumn::ID}" data-context-id="{$column->id}">
			{$column->name}
		</a>
	</div>
</div>

{$script_uid = uniqid('script')}
<script type="text/javascript" id="{$script_uid}">
$(function() {
	var $script = $('#{$script_uid}');
	var $toolbar = $script.closest('.cerb-board-column').find('[data-cerb-toolbar]');

	$toolbar.cerbToolbar({
		caller: {
			name: 'cerb.toolbar.projectBoardColumn',
			params: {
				column_id: '{$column->id}',
				board_id: '{$column->board_id}'
			}
		},
		start: function(formData) {
		},
		done: function(e) {
			e.stopPropagation();

			var $target = e.trigger;

			var $column = $target.closest('.cerb-board-column');

			$column.trigger('cerb-refresh');
		}
	});
});
</script>

<form action="#" style="width:100%;padding-top:10px;min-height:500px;max-height:500px;overflow:scroll;">
{foreach from=$column->getCards() item=card}
<div class="cerb-board-card" data-context="{$card->_context}" data-context-id="{$card->id}">
{include file="devblocks:cerb.project_boards::boards/board/card.tpl"}
</div>
{/foreach}
</form>
