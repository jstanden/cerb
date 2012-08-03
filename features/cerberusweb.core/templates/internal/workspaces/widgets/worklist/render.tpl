{*include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl"*}

<div id="view{$view_id}">
{$view->render()}
</div>

<script type="text/javascript">
// [TODO] Hide the title bar
// [TODO] Hide the actions
var on_refresh = function() {
	$worklist = $('#view{$view->id}').find('TABLE.worklist');
	//$worklist.css('background','none');
	//$worklist.css('background-color','rgb(100,100,100)');
	//$worklist.css('background-color','rgb(255,255,255)');
	$worklist.hide();
	
	$header = $worklist.find('> tbody > tr:first > td:first > span.title');
	$header.css('font-size', '14px');
	//$header.hide();
	
	$header_links = $worklist.find('> tbody > tr:first td:nth(1)');
	$header_links.children().each(function(e) {
		if(!$(this).is('a.minimal'))
			$(this).remove();
	});
	$header_links.find('a').css('font-size','11px');

	$worklist_body = $('#view{$view->id}').find('TABLE.worklistBody');
	
	//$worklist_body.find('tr:first th').css('background', 'rgb(245,245,245)');
	$worklist_body.find('tr:first th')
		.css('background', 'none')
		.css('border', '0')
		//.css('border-bottom', '1px solid rgb(230,230,230)')
		;

	$worklist_body.find('tr:first th > a')
		.css('text-decoration', 'underline')
		.css('color', 'rgb(80,80,80)')
		//.css('color', 'rgb(61,128,178)')
		;
	
	// Hide watchers column
	// [TODO] Config toggle
	if($worklist_body.find('tr:first th:first:contains(Watchers)').length > 0) {
		$worklist_body.find('tr:first th:first').hide();
		$worklist_body.find('tbody').find('> tr > td:first').hide();
	}
	
	$worklist_body.find('a.subject').each(function() {
		$txt = $('<b class="subject">' + $(this).text() + '</b>');
		$txt.css('font-weight', 'normal');
		$txt.insertBefore($(this));
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