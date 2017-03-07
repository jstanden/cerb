{* [TODO] Move to cerb.css/scss *}
<style type="text/css">
div.cerb-board {

}
div.cerb-board div.cerb-board-dropzones {
}

div.cerb-board div.cerb-board-columns {
	width: {315 * 6}px;
}
div.cerb-board div.cerb-board-dropzones div.cerb-board-dropzone {
	position: relative;
	width: 150px;
	height: 75px;
	text-align: center;
	vertical-align: top;
	display: inline-block;
	background-color: rgb(230,230,230);
	border: 3px dashed rgb(200,200,200);
	border-radius: 5px;
	padding: 5px;
	margin-left: auto;
	margin-right: auto;
}

div.cerb-board div.cerb-board-dropzones div.cerb-board-dropzone h2 {
	color: rgb(50,50,50);
	font-size: 120%;
	position: absolute;
	top: 50%;
	left: 50%;
	margin-right: -50%;
	transform: translate(-50%, -50%);
}

div.cerb-board div.cerb-board-column {
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
}

div.cerb-board div.cerb-board-column div.cerb-board-card {
	text-align: left;
	border-radius: 5px;
	display: inline-block;
	width: 280px;
	min-height: 140px;
	margin: 5px;
	cursor: move;
}
</style>

<div>
	<div id="board{$tab->id}" class="cerb-board">
		{*
		<div class="cerb-board-dropzones">
			<div class="cerb-board-dropzone">
				<h2>Send it to the cornfield, Anthony</h2>
			</div>
		</div>
		*}
		
		<div style="width:100%;overflow-x:auto;">
			<div class="cerb-board-columns">
				<div class="cerb-board-column">
					<div class="cerb-board-column-toolbar">
						<div class="cerb-board-column-toolbar-buttons">
							<button type="button" class="cerb-board-column-edit"><span class="glyphicons glyphicons-cogwheel"></span></button>
							<button type="button" class="cerb-board-card-add"><span class="glyphicons glyphicons-circle-plus"></span></button>
						</div>
						<h2>TODO</h2>
					</div>
					<div class="cerb-board-card" style="background-color:red;"></div>
					<div class="cerb-board-card" style="background-color:blue;"></div>
					<div class="cerb-board-card" style="background-color:green;"></div>
					<div class="cerb-board-card" style="background-color:orange;"></div>
					<div class="cerb-board-card" style="background-color:gray;"></div>
				</div>
				<div class="cerb-board-column">
					<div class="cerb-board-column-toolbar">
						<div class="cerb-board-column-toolbar-buttons">
							<button type="button" class="cerb-board-column-edit"><span class="glyphicons glyphicons-cogwheel"></span></button>
							<button type="button" class="cerb-board-card-add"><span class="glyphicons glyphicons-circle-plus"></span></button>
						</div>
						<h2>Waiting</h2>
					</div>
				</div>
				<div class="cerb-board-column">
					<div class="cerb-board-column-toolbar">
						<div class="cerb-board-column-toolbar-buttons">
							<button type="button" class="cerb-board-column-edit"><span class="glyphicons glyphicons-cogwheel"></span></button>
							<button type="button" class="cerb-board-card-add"><span class="glyphicons glyphicons-circle-plus"></span></button>
						</div>
						<h2>In Progress</h2>
					</div>
				</div>
				<div class="cerb-board-column">
					<div class="cerb-board-column-toolbar">
						<div class="cerb-board-column-toolbar-buttons">
							<button type="button" class="cerb-board-column-edit"><span class="glyphicons glyphicons-cogwheel"></span></button>
							<button type="button" class="cerb-board-card-add"><span class="glyphicons glyphicons-circle-plus"></span></button>
						</div>
						<h2>Resolved</h2>
					</div>
				</div>
				<div class="cerb-board-column">
					<div class="cerb-board-column-toolbar">
						<div class="cerb-board-column-toolbar-buttons">
							<button type="button" class="cerb-board-column-edit"><span class="glyphicons glyphicons-cogwheel"></span></button>
							<button type="button" class="cerb-board-card-add"><span class="glyphicons glyphicons-circle-plus"></span></button>
						</div>
						<h2>Column</h2>
					</div>
				</div>
				<div class="cerb-board-column">
					<div class="cerb-board-column-toolbar">
						<div class="cerb-board-column-toolbar-buttons">
							<button type="button" class="cerb-board-column-edit"><span class="glyphicons glyphicons-cogwheel"></span></button>
							<button type="button" class="cerb-board-card-add"><span class="glyphicons glyphicons-circle-plus"></span></button>
						</div>
						<h2>Column</h2>
					</div>
				</div>
			</div>
		</div>
		
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $board = $('#board{$tab->id}');
	
	$('div.cerb-board-column', $board)
		.sortable({
			connectWith: '.cerb-board-column, .cerb-board-dropzone',
			tolerance: 'pointer',
			items: '.cerb-board-card',
			opacity: 0.7
		})
		.disableSelection()
	;
	
	$('div.cerb-board-dropzone')
		.sortable({
			connectWith: '.cerb-board-column, .cerb-board-dropzone',
			tolerance: 'pointer',
			items: '.cerb-board-card',
			opacity: 0.7,
			receive: function(event, ui) {
				// [TODO] Ajax
				ui.item.remove();
			}
		})
		.disableSelection()
	;
	
	$board.find('button.cerb-board-card-add').click(function() {
		var $button = $(this);
		var $column = $button.closest('div.cerb-board-column');
		var $toolbar = $column.find('div.cerb-board-column-toolbar');
		
		var $card = $('<div class="cerb-board-card"/>')
			.hide()
			.css('background-color', 'purple')
			.insertAfter($toolbar)
			.fadeIn()
		;
	});
});
</script>