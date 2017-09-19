{* [TODO] Move to cerb.css/scss *}
<style type="text/css">
div.cerb-board div.cerb-board-columns {
	white-space: nowrap;
}

div.cerb-board div.cerb-board-column-add {
	text-align: center;
	vertical-align: top;
	position: relative;
	display: inline-block;
	width: 300px;
	background-color: rgb(255,255,255);
	border: 1px dashed rgb(220,220,220);
	border-radius: 5px;
	min-height: 100px;
	margin: 5px;
	padding: 5px 0px;
}

div.cerb-board div.cerb-board-column-add p {
	position: absolute;
	margin: 0;
	top: 50%;
	left: 50%;
	margin-right: -50%;
	transform: translate(-50%, -50%);
	color: rgb(200,200,200);
	font-size: 1.5em;
}

div.cerb-board div.cerb-board-column-add p a {
	text-decoration: none;
}

div.cerb-board div.cerb-board-column-add p a:hover {
	text-decoration: underline;
}

div.cerb-board div.cerb-board-column {
	white-space: normal;
	text-align: center;
	vertical-align: top;
	display: inline-block;
	width: 300px;
	background-color: rgb(250,250,250);
	border: 1px solid rgb(230,230,230);
	border-radius: 5px;
	min-height: 500px;
	margin: 5px;
	padding: 5px 0px;
}

div.cerb-board div.cerb-board-column div.cerb-board-column-toolbar-buttons {
	float: right;
	margin: 0px 5px 0px 0px;
}

div.cerb-board div.cerb-board-column h2 {
	text-align: left;
	color: rgb(50,50,50);
	margin-left: 10px;
	font-size: 130%;
	cursor: move;
}

div.cerb-board div.cerb-board-column div.cerb-board-card {
	text-align: left;
	border-radius: 5px;
	background-color: rgb(255,255,255);
	border: 1px solid rgb(230,230,230);
	display: inline-block;
	width: 280px;
	min-height: 50px;
	margin: 5px;
	padding: 0;
	cursor: move;
	position:relative;
}

div.cerb-board div.cerb-board-column div.cerb-board-card > div {
	margin: 10px;
}

div.cerb-board div.cerb-board-column div.cerb-board-card h3 {
	margin: 0px 0px 5px 0px;
	padding: 0;
	font-size: 120%;
}

div.cerb-board div.cerb-board-column div.cerb-board-card h3 a {
	color: rgb(45,110,180);
	text-decoration: none;
}

div.cerb-board div.cerb-board-column div.cerb-board-card h3 a:hover {
	text-decoration: underline;
}

div.cerb-board div.cerb-board-column div.cerb-board-card div.cerb-board-card-type {
	float: right;
	background-color:rgb(255,255,255);
	border: 1px solid rgb(230,230,230);
	color:gray;
	padding:3px;
	font-size:90%;
	text-transform: uppercase;
}
</style>

{capture name="link_contexts"}
{foreach from=$contexts item=context}
{if empty($board->params.contexts) || (is_array($board->params.contexts) && in_array($context->id, $board->params.contexts))}
<li data-context="{$context->id}" data-query="{$board->params.card_queries[{$context->id}]|escape}"><b>{$context->name}</b></li>
{/if}
{/foreach}
{/capture}

{$columns = $board->getColumns()}

<div>
	<div id="board{$tab->id}" class="cerb-board">
		<div style="width:100%;overflow-x:auto;">
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
	
	{if $smarty.capture.link_contexts|trim|count > 0}
	<ul class="menu cerb-float" style="width:200px;margin-top:-5px;display:none;">
		{$smarty.capture.link_contexts nofilter}
	</ul>
	{/if}
</div>

<script type="text/javascript">
$(function() {
	var $board = $('#board{$tab->id}');
	
	document.title = "{$board->name|escape:'javascript' nofilter} - {$settings->get('cerberusweb.core','helpdesk_title')|escape:'javascript' nofilter}";
	
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
			
			var $popup = genericAjaxPopup("chooser{uniqid()}",'c=internal&a=chooserOpen&context=' + encodeURIComponent(context) + '&link_context=' + from_context + '&link_context_id=' + from_context_id + '&q=' + encodeURIComponent(query) + '&qr=' + encodeURIComponent(query_req),null,false,'90%');
			$popup.one('chooser_save', function(event) {
				event.stopPropagation();
				
				// [TODO] Use a custom action that can run column behaviors before reloading
				var $data = [ 
					'c=internal',
					'a=contextAddLinksJson',
					'from_context=' + from_context,
					'from_context_id=' + from_context_id,
					'context=' + context
				];
				
				for(idx in event.values)
					$data.push('context_id[]='+encodeURIComponent(event.values[idx]));
				
				var options = { };
				options.type = 'POST';
				options.data = $data.join('&');
				options.url = DevblocksAppPath+'ajax.php',
				options.cache = false;
				options.success = function(json) {
					$column.trigger('cerb-refresh');
				};
				
				if(null == options.headers)
					options.headers = {};
				
				options.headers['X-CSRF-Token'] = $('meta[name="_csrf_token"]').attr('content');
				
				$.ajax(options);
			});
		})
	;
	
	var sortable_options = {
		connectWith: '.cerb-board-column',
		tolerance: 'pointer',
		items: '.cerb-board-card',
		opacity: 0.7,
		receive: function(event, ui) {
			var $card = $(ui.item);
			
			var $a = $card.find('h3 a');
			var card_context = $a.attr('data-context');
			var card_id = $a.attr('data-context-id');
			
			var $from_column = $(ui.sender);
			var from_column_id = $from_column.attr('data-column-id');

			var $to_column = $(this);
			var to_column_id = $to_column.attr('data-column-id');
			
			genericAjaxGet('', 'c=profiles&a=handleSectionAction&section=project_board&action=moveCard&context=' + encodeURIComponent(card_context) + '&id=' + encodeURIComponent(card_id) + '&from=' + encodeURIComponent(from_column_id) + '&to=' + encodeURIComponent(to_column_id), function() {
				//console.log("Moved from ", ui.sender, "To ", $column);
				$card.trigger('cerb-refresh');
			});
		},
		update: function(event, ui) {
			var $column = $(this);
			var column_id = $column.attr('data-column-id');
			
			var $form = $column.find('form');
			
			genericAjaxPost($form, '', 'c=profiles&a=handleSectionAction&section=project_board&action=reorderColumn&column_id=' + encodeURIComponent(column_id), function() {
				//console.log("Reordered ", $column);
			});
		}
	};
	
	$(document).on('cerb-project-column-changed cerb-project-column-deleted', function(e) {
		// Event for the same board
		if(e.board_id != {$board->id|default:0})
			return;
		
		// Find the column and refresh it
		var $column = $board.find('div.cerb-board-column[data-column-id=' + e.column_id + ']');
		
		if(e.type == 'cerb-project-column-deleted') {
			$column.remove();
		} else {
			$column.trigger('cerb-refresh');
		}
	});
	
	$(document).on('cerb-peek-saved', function(e) {
		var $card = $board.find('div.cerb-board-card a.cerb-peek-trigger[data-context="' + e.context + '"][data-context-id=' + e.id + ']')
			.closest('div.cerb-board-card')
			;
		
		$card.trigger('cerb-refresh');
	});
	
	$(document).on('cerb-peek-deleted', function(e) {
		var $card = $board.find('div.cerb-board-card a.cerb-peek-trigger[data-context="' + e.context + '"][data-context-id=' + e.id + ']')
			.closest('div.cerb-board-card')
			;
		$card.remove();
	});
	
	$board.find('> div')
		.sortable({
			tolerance: 'pointer',
			items: '.cerb-board-column',
			helper: 'clone',
			//handle: '.cerb-board-column-toolbar',
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
		
		genericAjaxGet('', 'c=profiles&a=handleSectionAction&section=project_board&action=reorderBoard&id={$board->id|escape:'url'}&columns=' + encodeURIComponent(column_ids), function() {
		});
	});
	
	$board.on('cerb-refresh', 'div.cerb-board-column', function(e) {
		e.stopPropagation();
		
		var $column = $(this);
		var column_id = $column.attr('data-column-id');
		
		genericAjaxGet($column, 'c=profiles&a=handleSectionAction&section=project_board&action=refreshColumn&column_id=' + encodeURIComponent(column_id), function() {
			//console.log("Moved from ", ui.sender, "To ", $column);
			
			// [TODO] Redundant
			$column.unbind().find('button.cerb-board-column-edit')
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
			
			$column
				.sortable('destroy')
				.sortable(sortable_options)
				;
			
			$column
				.find('.cerb-bot-trigger')
					.cerbBotTrigger()
					;
			$column
				.find('.cerb-peek-trigger')
					.cerbPeekTrigger()
					;
			;
		});
	});
	
	$board.on('cerb-refresh', 'div.cerb-board-card', function(e) {
		e.stopPropagation();
		
		var $card = $(this);

		var $trigger = $card.find('h3 a.cerb-peek-trigger');
		var context = $trigger.attr('data-context');
		var context_id = $trigger.attr('data-context-id');
		
		genericAjaxGet($card, 'c=profiles&a=handleSectionAction&section=project_board&action=refreshCard&board_id={$board->id}&context=' + encodeURIComponent(context) + '&id=' + encodeURIComponent(context_id), function() {
			$card
				.find('.cerb-bot-trigger')
					.cerbBotTrigger()
			;
			$card
				.find('.cerb-peek-trigger')
					.cerbPeekTrigger()
			;
		});
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
		.sortable(sortable_options)
		.disableSelection()
	;
	
	$board.find('button.cerb-board-column-edit')
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
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function(e) {
			var $placeholder = $(this).closest('div.cerb-board-column-add');
			
			$column = $('<div class="cerb-board-column"/>')
				.attr('data-column-id', e.id)
				.insertBefore($placeholder)
				.sortable(sortable_options)
				.disableSelection()
				.trigger('cerb-refresh')
				;
			
			// Persist column order
			$board.trigger('cerb-persist');
		})
		;
	
	var $cards = $board.find('div.cerb-board-card');
	$cards.find('.cerb-bot-trigger')
		.cerbBotTrigger()
		;
	$cards.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;
});
</script>