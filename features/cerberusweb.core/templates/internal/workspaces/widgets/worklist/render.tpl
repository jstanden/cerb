<div id="view{$view_id}">
{$view->render()}
</div>

<script type="text/javascript">
var on_refresh = function() {
	$worklist = $('#view{$view->id}').find('TABLE.worklist');
	$worklist.hide();
	
	$widget = $worklist.closest('div.dashboard-widget');
	
	var $worklist_links = $('<div style="margin-bottom:5px;float:right;visibility:hidden;"></div>');

	$('#view{$view_id}').on('mouseover mouseout', function(e) {
		if(e.type=='mouseover') {
			$worklist_links.css('visibility','visible');
		} else {
			$worklist_links.css('visibility', 'hidden');
		}
	});
	
	$header_links = $worklist.find('> tbody > tr:first td:nth(1)');
	$header_links.children().each(function(e) {
		if($(this).is('a.minimal') && 0 != $(this).find('span.sprite-plus-circle-frame').length) //, span.sprite-gear
			$(this).css('margin-right','5px').appendTo($worklist_links);
	});

	$worklist_body = $('#view{$view->id}').find('TABLE.worklistBody');

	$worklist_body
		.attr('cellpadding', '1')
		.attr('cellspacing', '0')
		;
	
	// Hide watchers column (if exists)
	if($worklist_body.find('tr:first th:first:contains(Watchers)').length > 0) {
		$worklist_body.find('tr:first th:first').remove();
		$worklist_body.find('tbody').find('> tr > td:first').remove();
	}
	
	$worklist_body.find("tr:first th").each(function(e) {
		var $this = $(this);

		if($this.find('a').length == 0) {
			var idx = e;
			$this.remove();
		}
	});

	$worklist_body.find('tr:first th')
		.css('background', 'none')
		.css('border', '0')
		;

	$worklist_body.find('td')
		.css('display', 'block')
		.css('min-width', '')
		.css('font-weight', 'normal')
		.removeAttr('align', '')
		.each(function(e) {
			var $td = $(this);

			if($td.attr('colspan') != undefined || $td.attr('rowspan') != undefined) {
				if($(this).is('[rowspan]')) {
					$(this).remove();
					return;
				}
				
				// Ignore spanning cells
				$(this)
					.removeAttr('colspan', '')
					;
				
			} else {
				if($td.find('a.subject, b.subject').length > 0)
					return;
				
				// Hide cells with no content
				/*
				if(0==$.trim($td.text()).length) {
					$(this).hide();
					return;
				}
				*/
				
				var $ths = $td.closest('table.worklistBody').find('th');
				var $siblings = $td.closest('tr').find('td');
				var idx = $siblings.index($td);
				
				var txt = $ths.filter(':nth(' + (idx) + ')').text().trim();
				
				var $label = $('<span style="color:rgb(120,120,120);margin-left:15px;">' 
					+ txt 
					+ ': </span>');
				$label.prependTo($td);
			}
		})
		;

	$worklist_body.find('a.subject')
		.addClass('no-underline')
		;
	
	$worklist_body.find('button.peek')
		.css('position', 'absolute')
		;
	
	$worklist_body.find('tbody')
		.each(function(e) {
			$cols = null;
			
			if($(this).find('tr').length > 1) {
				$cols = $(this).find('tr:gt(0) td');
				
			} else {
				$cols = $(this).find('td:gt(0)');
			}
		})
		;
	
	$sort_links = $('<div style="margin-bottom:5px;"></div>');
	
	$worklist_body.find('tr:first th')
		.each(function(e) {
			$(this).find('> a')
				.css('font-weight', 'bold')
				.css('text-decoration', 'underline')
				.css('color', 'rgb(51,92,142)')
			;
	
			$span = $('<span style="margin-right:10px;"></span>');
			$(this).children().appendTo($span);
			$span.appendTo($sort_links);
		})
		.closest('thead')
			.remove()
			;
	
	$sort_links.insertBefore($worklist_body);
	$worklist_links.insertBefore($sort_links);
	
	$actions = $('#{$view->id}_actions');
	$actions.find('.action-always-show').hide();
}

on_refresh();

$view = $('#view{$view_id}');
$widget = $view.closest('div.dashboard-widget');

$widget.undelegate('DIV[id^=view]','view_refresh');
$widget.delegate('DIV[id^=view]','view_refresh', on_refresh);
</script>