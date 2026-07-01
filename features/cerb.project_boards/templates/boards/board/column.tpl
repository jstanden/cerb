{$column_dict = DevblocksDictionaryDelegate::instance([
	'caller_name' => 'cerb.toolbar.projectBoardColumn',
	
	'column__context' => CerberusContexts::CONTEXT_PROJECT_BOARD_COLUMN,
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
		<span class="cerb-icons cerb-icon-menu-hamburger"></span>
		<a class="cerb-board-column-edit no-underline" data-context="{CerberusContexts::CONTEXT_PROJECT_BOARD_COLUMN}" data-context-id="{$column->id}">
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
<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript" id="{$script_uid}">
$(function() {
	let $script = $('#{$script_uid}');
	
	let $column = $script.closest('.cerb-board-column');
	let $toolbar = $column.find('[data-cerb-toolbar]');

	let column_toolbar_ul = $toolbar.find('ul.cerb-ui-toolbar')[0];
	if(column_toolbar_ul && window.CerbUI && CerbUI.Toolbar)
	new CerbUI.Toolbar(column_toolbar_ul, {
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

			let $target = e.trigger;

			if (e.eventData.exit === 'error') {

			} else if(e.eventData.exit === 'return') {
				Devblocks.interactionWorkerPostActions(e.eventData);
			}

			let $column = $target.closest('.cerb-board-column');

			$column.trigger('cerb-refresh');
		}
	});
	
	let $more = $script.siblings('form').find('[data-cerb-column-cards-more]');
	
	$more.on('click', function(e) {
		e.stopPropagation();
		
		let $last_card = $column.find('.cerb-board-card').last();
		let last_card_id = $last_card.find('[name="cards[]"]').val();
		let limit = {$column->getLimit()|round};

		let formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'project_board_column');
		formData.set('action', 'loadCards');
		formData.set('column_id', '{$column->id}');
		formData.set('since', last_card_id);
		
		genericAjaxPost(formData, null, null, function(html) {
			let $new_cards = $(html);
			
			if($new_cards.filter('.cerb-board-card').length < limit)
				$more.hide();

			$new_cards.insertAfter($last_card);
		});
	});
});
</script>
