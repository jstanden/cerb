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
		<span class="glyphicons glyphicons-menu-hamburger"></span>
		<a href="javascript:" class="cerb-board-column-edit no-underline" data-context="{Context_ProjectBoardColumn::ID}" data-context-id="{$column->id}">
			{$column->name}
		</a>
	</div>
</div>

<form action="#" style="width:100%;padding-top:10px;min-height:500px;max-height:500px;overflow:auto;">
{$cards = $column->getCards()}
{include file="devblocks:cerb.project_boards::boards/board/cards.tpl"}
{if $column->getLimit() == count($cards)}
<div style="cursor:pointer;text-decoration:underline;padding:10px;" data-cerb-column-cards-more>
	<b>(show more)</b>
</div>
{/if}
</form>

{$script_uid = uniqid('script')}
<script type="text/javascript" id="{$script_uid}">
$(function() {
	var $script = $('#{$script_uid}');
	
	var $column = $script.closest('.cerb-board-column');
	var $toolbar = $column.find('[data-cerb-toolbar]');

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

			if (e.eventData.exit === 'error') {

			} else if(e.eventData.exit === 'return') {
				Devblocks.interactionWorkerPostActions(e.eventData);
			}

			var $column = $target.closest('.cerb-board-column');

			$column.trigger('cerb-refresh');
		}
	});
	
	var $more = $script.siblings('form').find('[data-cerb-column-cards-more]');
	
	$more.on('click', function(e) {
		e.stopPropagation();
		
		var $last_card = $column.find('.cerb-board-card').last();
		var last_card_id = $last_card.find('[name="cards[]"]').val();
		var limit = {$column->getLimit()|round};

		var formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'project_board_column');
		formData.set('action', 'loadCards');
		formData.set('column_id', '{$column->id}');
		formData.set('since', last_card_id);
		
		genericAjaxPost(formData, null, null, function(html) {
			var $new_cards = $(html);
			
			if($new_cards.filter('.cerb-board-card').length < limit)
				$more.hide();

			$new_cards.insertAfter($last_card);
		});
	});
});
</script>
