{$columns = $board->getColumns()}

<div>
	<div id="board{$board->id}_{$widget->id}" class="cerb-board">
		<div style="float:right;">
			<span class="glyphicons glyphicons-edit"></span>
			<a href="javascript:;" class="cerb-button-edit-board" data-context="project_board" data-context-id="{$board->id}">
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

				{if $active_worker->hasPriv("contexts.{Context_ProjectBoardColumn::ID}.create")}
				<div class="cerb-board-column-add">
					<p><span class="glyphicons glyphicons-circle-plus"></span> <a href="javascript:;" data-context="{Context_ProjectBoardColumn::ID}" data-context-id="0" data-edit="board.id:{$board->id}">{'common.add'|devblocks_translate|capitalize}</a></p>
				</div>
				{/if}
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $board = $('#board{$board->id}_{$widget->id}');
	
	var $menu = $board.siblings('ul.menu')
		.menu()
		// Catch menu item clicks
		.on('click', function(e, ui) {
			e.stopPropagation();
			$menu.hide();
			var $target = $(e.target);
			
			if($target.is('b'))
				$target = $target.closest('li');
			
			if(!$target.is('li'))
				return;
			
			var context = $target.attr('data-context');
			var from_context = '{Context_ProjectBoardColumn::ID}';
			var from_context_id = $menu.attr('data-column-id');
			var $column = $board.find('div.cerb-board-column[data-column-id=' + from_context_id + ']');
			
			var query_req = 'links.project_board_column:!(board.id:{$board->id})';
			var query = $target.attr('data-query');
			
			$menu.attr('data-column-id', null);
			
			var $popup = genericAjaxPopup("chooser{uniqid()}",'c=internal&a=invoke&module=records&action=chooserOpen&context=' + encodeURIComponent(context) + '&link_context=' + from_context + '&link_context_id=' + from_context_id + '&q=' + encodeURIComponent(query) + '&qr=' + encodeURIComponent(query_req),null,false,'90%');
			$popup.one('chooser_save', function(event) {
				event.stopPropagation();

				var formData = new FormData();
				formData.set('c', 'internal');
				formData.set('a', 'invoke');
				formData.set('module', 'records');
				formData.set('action', 'contextAddLinksJson');
				formData.set('from_context', from_context);
				formData.set('from_context_id', from_context_id);
				formData.set('context', context);

				for(var idx in event.values)
					formData.append('context_id[]', event.values[idx]);

				genericAjaxPost(formData, '', '', function() {
					$column.trigger('cerb-refresh');
				});
			});
		})
	;
	
	var sortable_options = {
		connectWith: '.cerb-board-column form',
		tolerance: 'pointer',
		items: '.cerb-board-card',
		opacity: 0.7,
		receive: function(event, ui) {
			var $card = $(ui.item);
			
			var card_context = $card.attr('data-context');
			var card_id = $card.attr('data-context-id');
			
			var $from_column = $(ui.sender).parent();
			var from_column_id = $from_column.attr('data-column-id');

			var $to_column = $(this).parent();
			var to_column_id = $to_column.attr('data-column-id');

			var formData = new FormData();
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'project_board');
			formData.set('action', 'moveCard');
			formData.set('context', card_context);
			formData.set('id', card_id);
			formData.set('from', from_column_id);
			formData.set('to', to_column_id);

			genericAjaxPost(formData, '', null, function() {
				$card.trigger('cerb-refresh');
			});
		},
		update: function() {
			var $column = $(this).parent();
			var column_id = $column.attr('data-column-id');
			
			var formData = new FormData($column.find('form').get(0));
			formData.set('c', 'profiles');
			formData.set('a', 'invoke');
			formData.set('module', 'project_board');
			formData.set('action', 'reorderColumn');
			formData.set('column_id', column_id);

			genericAjaxPost(formData, '', '', function() {
			});
		}
	};

	$board.find('.cerb-button-edit-board')
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function() {
			// [TODO] Refresh board
		})
	;

	$board.find('> div')
		.sortable({
			tolerance: 'pointer',
			items: '.cerb-board-column',
			helper: 'clone',
			handle: '.cerb-board-column-toolbar .glyphicons-menu-hamburger',
			opacity: 0.7,
			update: function(event, ui) {
				$board.trigger('cerb-persist');
			}
		})
	;
	
	$board.on('cerb-persist', function(e) {
		e.stopPropagation();
		
		var column_ids = $board.find('div.cerb-board-columns > div.cerb-board-column')
			.map(function() {
				return $(this).attr('data-column-id')
			})
			.get()
			.join()
		;

		var formData = new FormData();
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

		var $column = $(this);

		$column.empty().append(Devblocks.getSpinner());

		var column_id = $column.attr('data-column-id');

		var formData = new FormData();
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
					var $column = $(this).closest('div.cerb-board-column');
					$column.trigger('cerb-refresh');
				})
				.on('cerb-peek-deleted', function() {
					var $column = $(this).closest('div.cerb-board-column');
					$column.remove();
				})
			;
			
			$column.find('> form')
				.disableSelection()
				.sortable(sortable_options)
				;
		});
	});
	
	$board.on('cerb-refresh', 'div.cerb-board-card', function(e) {
		e.stopPropagation();
		
		var $card = $(this);
		
		var context = $card.attr('data-context');
		var context_id = $card.attr('data-context-id');

		var formData = new FormData();
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
		
		var $target = $(e.target);
		var $column = $target.closest('div.cerb-board-column');
		var column_id = $column.attr('data-column-id');
		
		// Check if the menu only has one type
		if($menu.find('> li').length == 1) {
			$menu
				.attr('data-column-id', column_id)
				.find('> li:first')
					.click()
			;
			return;
		}
		
		if($menu.attr('data-column-id') == column_id) {
			$menu.attr('data-column-id', null).hide();
			
		} else {
			var $position = $target.position();
			
			$menu.attr('data-column-id', column_id)
				.css('top', $position.top + 25)
				.css('left', $position.left)
				.show()
			;
		}
	});
	
	$board.find('div.cerb-board-column')
		.find('> form')
		.disableSelection()
		.sortable(sortable_options)
	;
	
	$board.find('.cerb-board-column-edit')
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function() {
			var $column = $(this).closest('div.cerb-board-column');
			$column.trigger('cerb-refresh');
		})
		.on('cerb-peek-deleted', function() {
			var $column = $(this).closest('div.cerb-board-column');
			$column.remove();
		})
		;
	
	$board.find('div.cerb-board-column-add a')
		.css('cursor', 'pointer')
		.cerbPeekTrigger()
		.on('cerb-peek-created', function(e) {
			var $this = $(this).closest('div.cerb-board-column-add');
			
			$('<div class="cerb-board-column"/>')
				.attr('data-column-id', e.id)
				.insertBefore($this)
				.trigger('cerb-refresh')
				;
			
			// Persist column order
			$board.trigger('cerb-persist');
		})
		.on('cerb-peek-saved', function(e) {
			var $column = $board.find('[data-column-id=' + e.id + ']');
			$column.trigger('cerb-refresh');
		})
		.on('cerb-peek-deleted', function(e) {
			var $column = $board.find('[data-column-id=' + e.id + ']');
			$column.remove();
		})
		;
});
</script>