<div id="view{$view_id}">
{$view->render()}
</div>

<script type="text/javascript">
$(function() {
	
var on_refresh = function() {
	var $worklist = $('#view{$view->id}').find('TABLE.worklist');
	$worklist.hide();
	
	var $widget = $worklist.closest('div.dashboard-widget');
	
	var $worklist_links = $('<div style="margin-bottom:5px;float:right;visibility:hidden;"></div>');

	$('#view{$view_id}').on('mouseover mouseout', function(e) {
		if(e.type=='mouseover') {
			$worklist_links.css('visibility','visible');
		} else {
			$worklist_links.css('visibility', 'hidden');
		}
	});
	
	var $header_links = $worklist.find('> tbody > tr:first td:nth(1)');
	$header_links.children().each(function(e) {
		if($(this).is('a.minimal'))
			$(this).css('margin-right','5px').appendTo($worklist_links);
	});

	var $worklist_body = $('#view{$view->id}').find('TABLE.worklistBody');

	$worklist_body
		.attr('cellpadding', '1')
		.attr('cellspacing', '0')
		;
	
	// Hide button columns (if exist)
	
	var $th_buttons = $worklist_body.find('tr:first').find('th span.glyphicons-eye-open, th span.glyphicons-flag');
		$th_buttons.closest('th').hide();
	
	// Prepend header labels to column cells
	
	$worklist_body.find('td')
		.css('display', 'block')
		.css('min-width', '')
		.css('font-weight', 'normal')
		.removeAttr('align', '')
		.each(function(e) {
			var $td = $(this);

			if($td.is(':hidden'))
				return;
			
			if($td.find('button > div.badge-count').length > 0) {
				$td.hide();
				return;
			}
			
			if($td.find('a.subject, b.subject, input:checkbox:hidden').length > 0) {
				if($.trim($td.text()).length == 0)
					$td.hide();
				
				return;
			}
			
			var $ths = $td.closest('table.worklistBody').find('th:not(:hidden)');
			var $siblings = $td.closest('tr').find('td:not(:hidden)');
			var idx = $siblings.index($td);
			
			var $th = $ths.filter(':nth(' + (idx) + ')');
			
			// If the column header is hidden or contentless, hide this cell
			if($th.is(':hidden')) {
				$td.hide();
				return;
			}
			
			var txt = $th.text().trim();
			
			var $label = $('<span style="color:rgb(120,120,120);margin-left:15px;">' 
				+ txt 
				+ ': </span>');
			$label.prependTo($td);
		})
		;

	$worklist_body.find('a.subject, b.subject')
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
	
	var $sort_links = $('<div style="margin-bottom:5px;"></div>');
	
	$worklist_body.find('tr:first th:not(:hidden)')
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
	
	var $actions = $('#{$view->id}_actions');
	$actions.find('.action-always-show').hide();
}

on_refresh();

var $view = $('#view{$view_id}');
var $widget = $view.closest('div.dashboard-widget');

$widget.undelegate('DIV[id^=view]','view_refresh');
$widget.delegate('DIV[id^=view]','view_refresh', on_refresh);

});
</script>