{*include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl"*}

<div id="view{$view_id}">
{$view->render()}
</div>

<script type="text/javascript">
// [TODO] Hide the title bar
// [TODO] Hide the actions
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
	
	$worklist_body.find('tr:first th')
		.css('background', 'none')
		.css('border', '0')
		;

	$worklist_body.find('td')
		.css('padding', '0px 3px 5px 0px')
		;
	
	$worklist_body.find('tr:first th > a')
		.css('text-decoration', 'underline')
		.css('color', 'rgb(51,92,142)')
		.closest('th')
			.css('padding-bottom', '5px')
		;
	
	// Hide watchers column
	// [TODO] Config toggle
	if($worklist_body.find('tr:first th:first:contains(Watchers)').length > 0) {
		$worklist_body.find('tr:first th:first').hide();
		$worklist_body.find('tbody').find('> tr > td:first').hide();
	}
	
	$worklist_body.find('a.subject').each(function() {
		$txt = $('<b class="subject">' + $(this).text() + '</b>');
		$txt.insertBefore($(this));
		$txt.closest('td').css('padding-bottom', '0');
		$(this).remove();
	});
	
	$actions = $('#{$view->id}_actions');
	$actions.find('.action-always-show').hide();
}

on_refresh();

$view = $('#view{$view_id}');
$widget = $view.closest('div.dashboard-widget');

$widget.undelegate().delegate('DIV[id^=view]','view_refresh', on_refresh);
</script>