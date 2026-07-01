{$columns = $board->getColumns()}

<div>
	<div id="board{$board->id}_{$widget->id}" class="cerb-board">
		<div class="cerb-ui-pill" style="float:right;">
			<span class="cerb-icons cerb-icon-edit"></span>
			<a class="cerb-button-edit-board" data-context="project_board" data-context-id="{$board->id}">
				edit board
			</a>
		</div>
		<div class="cerb-board-columns-set">
			<div class="cerb-board-columns">
				{foreach from=$columns item=column}
				<div class="cerb-board-column" data-column-id="{$column->id}">
				{include file="devblocks:cerb.project_boards::boards/board/column.tpl"}
				</div>
				{/foreach}

				{if $active_worker->hasPriv("contexts.{CerberusContexts::CONTEXT_PROJECT_BOARD_COLUMN}.create")}
				<div class="cerb-board-column-add">
					<p><span class="cerb-icons cerb-icon-circle-plus"></span> <a data-context="{CerberusContexts::CONTEXT_PROJECT_BOARD_COLUMN}" data-context-id="0" data-edit="board.id:{$board->id}">{'common.add'|devblocks_translate|capitalize}</a></p>
				</div>
				{/if}
			</div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $board = $('#board{$board->id}_{$widget->id}');
	
	let $cardMenuUl = $board.siblings('ul.menu');

	// Open the record chooser for a card type, adding the picked records to a column
	function openCardAddChooser(typeLi, column_id) {
		let context = typeLi.getAttribute('data-context');
		let from_context = '{CerberusContexts::CONTEXT_PROJECT_BOARD_COLUMN}';
		let $column = $board.find('div.cerb-board-column[data-column-id=' + column_id + ']');
		let query_req = 'links.project_board_column:!(board.id:{$board->id})';
		let query = typeLi.getAttribute('data-query');

		let $popup = genericAjaxPopup("chooser{uniqid()}",'c=internal&a=invoke&module=records&action=chooserOpen&context=' + encodeURIComponent(context) + '&link_context=' + from_context + '&link_context_id=' + column_id + '&q=' + encodeURIComponent(query) + '&qr=' + encodeURIComponent(query_req),null,false,'90%');
		$popup.one('chooser_save', function(event) {
			event.stopPropagation();

			let formData = new FormData();
			formData.set('c', 'internal');
			formData.set('a', 'invoke');
			formData.set('module', 'records');
			formData.set('action', 'contextAddLinksJson');
			formData.set('from_context', from_context);
			formData.set('from_context_id', column_id);
			formData.set('context', context);

			for(let idx in event.values)
				formData.append('context_id[]', event.values[idx]);

			genericAjaxPost(formData, '', '', function() {
				$column.trigger('cerb-refresh');
			});
		});
	}

	// The card-type picker (one menu, shared by every column's "+ add" button); opened anchored to the
	// clicked button, carrying the target column id on cardMenu._columnId.
	let cardMenu = ($cardMenuUl.length && window.CerbUI && CerbUI.Menu)
		? new CerbUI.Menu($cardMenuUl.hide()[0], {
			onSelect: function(rendered, source) {
				openCardAddChooser(source, cardMenu._columnId);
			}
		})
		: null;

	// Persist a column's card order
	function reorderColumn($column) {
		let formData = new FormData($column.find('form').get(0));
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'project_board');
		formData.set('action', 'reorderColumn');
		formData.set('column_id', $column.attr('data-column-id'));
		genericAjaxPost(formData, '', '', function() {});
	}

	// Instantiate the card sortable on a column's <form>; connectWith links every column for cross-moves
	function makeCardSortable(formEl) {
		if(window.CerbUI && CerbUI.Sortable && formEl && !CerbUI.Sortable.from(formEl))
			new CerbUI.Sortable(formEl, {
				connectWith: '.cerb-board-column form',
				tolerance: 'pointer',
				items: '.cerb-board-card',
				helper: 'clone' // card bg comes from a descendant selector; clone (body-anchored) keeps a solid bg
			});
	}

	// One handler for every card drop (the sorted event bubbles once, from the source column's form)
	$board[0].addEventListener('cerb-ui-sortable:sorted', function(e) {
		let info = e.detail;
		if(!$(info.item).hasClass('cerb-board-card')) return; // column reorders persist via cerb-persist

		let $fromColumn = $(info.from).closest('.cerb-board-column');
		let $toColumn   = $(info.to).closest('.cerb-board-column');

		if(info.from !== info.to) {
			// Moved to another column → update the card's column membership, then persist the source order
			let formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'project_board');
			formData.set('action', 'moveCard');
			formData.set('context', $(info.item).attr('data-context'));
			formData.set('id', $(info.item).attr('data-context-id'));
			formData.set('from', $fromColumn.attr('data-column-id'));
			formData.set('to', $toColumn.attr('data-column-id'));
			genericAjaxPost(formData, '', null, function() {
				$(info.item).trigger('cerb-refresh');
			});

			reorderColumn($fromColumn);
		}

		reorderColumn($toColumn);
	});

	$board.find('.cerb-button-edit-board')
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function() {
			// [TODO] Refresh board
		})
	;

	if(window.CerbUI && CerbUI.Sortable)
		new CerbUI.Sortable($board.find('div.cerb-board-columns').get(0), {
			tolerance: 'pointer',
			items: '.cerb-board-column',
			helper: 'clone',
			handle: '.cerb-board-column-toolbar .cerb-icon-menu-hamburger',
			onSorted: function() {
				$board.trigger('cerb-persist');
			}
		});
	
	$board.on('cerb-persist', function(e) {
		e.stopPropagation();
		
		let column_ids = $board.find('div.cerb-board-columns > div.cerb-board-column')
			.map(function() {
				return $(this).attr('data-column-id')
			})
			.get()
			.join()
		;

		let formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'project_board');
		formData.set('action', 'reorderBoard');
		formData.set('id', '{$board->id}');
		formData.set('columns', column_ids);

		genericAjaxPost(formData, '', '', function() {
		});
	});
	
	$board.on('cerb-refresh', 'div.cerb-board-column', function(e) {
		e.stopPropagation();

		let $column = $(this);

		$column.empty().append(Devblocks.getSpinner());

		let column_id = $column.attr('data-column-id');

		let formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'project_board');
		formData.set('action', 'refreshColumn');
		formData.set('column_id', column_id);

		genericAjaxPost(formData, $column, '', function() {
			//console.log("Moved from ", ui.sender, "To ", $column);
			
			$column.unbind().find('.cerb-board-column-edit')
				.cerbPeekTrigger()
				.on('cerb-peek-saved', function() {
					let $column = $(this).closest('div.cerb-board-column');
					$column.trigger('cerb-refresh');
				})
				.on('cerb-peek-deleted', function() {
					let $column = $(this).closest('div.cerb-board-column');
					$column.remove();
				})
			;
			
			makeCardSortable($column.find('> form').get(0));
		});
	});
	
	$board.on('cerb-refresh', 'div.cerb-board-card', function(e) {
		e.stopPropagation();
		
		let $card = $(this);
		
		let context = $card.attr('data-context');
		let context_id = $card.attr('data-context-id');

		let formData = new FormData();
		formData.set('c', 'profiles');
		formData.set('a', 'invoke');
		formData.set('module', 'project_board');
		formData.set('action', 'refreshCard');
		formData.set('board_id', '{$board->id}');
		formData.set('context', context);
		formData.set('id', context_id);

		genericAjaxPost(formData, $card, '');
	});
	
	$board.on('click', 'button.cerb-board-card-add', function(e) {
		e.stopPropagation();

		let $column = $(e.target).closest('div.cerb-board-column');
		let column_id = $column.attr('data-column-id');
		let typeLis = $cardMenuUl.find('> li');

		// Single card type → skip the menu, open its chooser directly
		if(typeLis.length == 1) {
			openCardAddChooser(typeLis.get(0), column_id);
			return;
		}

		if(!cardMenu) return;

		// Toggle: a second click on the same column's button closes the open menu
		if(cardMenu.isOpen() && cardMenu._columnId == column_id) {
			cardMenu.close();
			return;
		}

		cardMenu._columnId = column_id;
		cardMenu.open(this);
	});
	
	$board.find('div.cerb-board-column > form').each(function() {
		makeCardSortable(this);
	});
	
	$board.find('.cerb-board-column-edit')
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function() {
			let $column = $(this).closest('div.cerb-board-column');
			$column.trigger('cerb-refresh');
		})
		.on('cerb-peek-deleted', function() {
			let $column = $(this).closest('div.cerb-board-column');
			$column.remove();
		})
		;
	
	$board.find('div.cerb-board-column-add a')
		.css('cursor', 'pointer')
		.cerbPeekTrigger()
		.on('cerb-peek-created', function(e) {
			let $this = $(this).closest('div.cerb-board-column-add');
			
			$('<div class="cerb-board-column"/>')
				.attr('data-column-id', e.id)
				.insertBefore($this)
				.trigger('cerb-refresh')
				;
			
			// Persist column order
			$board.trigger('cerb-persist');
		})
		.on('cerb-peek-saved', function(e) {
			let $column = $board.find('[data-column-id=' + e.id + ']');
			$column.trigger('cerb-refresh');
		})
		.on('cerb-peek-deleted', function(e) {
			let $column = $board.find('[data-column-id=' + e.id + ']');
			$column.remove();
		})
		;
});
</script>