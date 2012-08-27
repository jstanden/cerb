{*include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl"*}

<div id="view{$view_id}">
{$view->render()}
</div>

<script type="text/javascript">
var on_refresh = function() {
	$worklist = $('#view{$view->id}').find('TABLE.worklist');
	$worklist.hide();
	
	$header = $worklist.find('> tbody > tr:first > td:first > span.title');
	$header.css('font-size', '14px');
	
	$header_links = $worklist.find('> tbody > tr:first td:nth(1)');
	$header_links.children().each(function(e) {
		if(!$(this).is('a.minimal'))
			$(this).remove();
	});
	$header_links.find('a').css('font-size','11px');

	$worklist_body = $('#view{$view->id}').find('TABLE.worklistBody');

	$worklist_body
		.attr('cellpadding', '1')
		.attr('cellspacing', '0')
		;
	
	// Hide watchers column (if exists)
	if($worklist_body.find('tr:first th:first:contains(Watchers)').length > 0) {
		$worklist_body.find('tr:first th:first').html('');
		$worklist_body.find('tbody').find('> tr > td:first > button').remove();
	}
	
	$worklist_body.find('tr:first th')
		.css('background', 'none')
		.css('border', '0')
		;

	$worklist_body.find('th, td')
		.css('display', 'block')
		.css('min-width', '')
		.removeAttr('align', '')
		.each(function(e) {
			if(0==$.trim($(this).text()).length)
				$(this).hide();
		})
		;
	
	$worklist_body.find('tbody')
		.each(function(e) {
			$cols = null;
			
			if($(this).find('tr').length > 1) {
				$cols = $(this).find('tr:gt(0) td');
				
			} else {
				$cols = $(this).find('td:gt(0)');
			}

			if(null != $cols) {
				$cols.css('padding-left', '15px');
			}
		})
		;
	
	$sort_links = $('<div style="margin-bottom:5px;"></div>');
	
	$worklist_body.find('tr:first th').each(function(e) {
		$(this).find('> a')
			.css('font-weight', 'bold')
			.css('text-decoration', 'underline')
			.css('color', 'rgb(51,92,142)')
		;

		$span = $('<span style="margin-right:10px;"></span>');
		$(this).children().appendTo($span);
		$span.appendTo($sort_links);
		$(this).remove();
	});
	
	$sort_links.insertBefore($worklist_body);
	
	$actions = $('#{$view->id}_actions');
	$actions.find('.action-always-show').hide();
}

on_refresh();

$view = $('#view{$view_id}');
$widget = $view.closest('div.dashboard-widget');

$widget.undelegate().delegate('DIV[id^=view]','view_refresh', on_refresh);
</script>