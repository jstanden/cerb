<script type="text/javascript">
$view = $('div#view{$view->id}');
$view_frm = $('form#viewForm{$view->id}');

// Row selection and hover effect
$view_frm.find('TABLE.worklistBody TBODY')
	.click(function(e) {
		$target = $(e.target);
	
		// Are any of our parents an anchor tag?	
		$parents = $target.parents('a');
		if($parents.length > 0) {
			$target = $parents[$parents.length-1]; // 0-based
		}
		
		if (false == $target instanceof jQuery) {
			// Not a jQuery object
		} else if($target.is(':input,:button,a,img,span.cerb-sprite,span.cerb-sprite2,span.cerb-label')) {
			// Ignore form elements and links
		} else {
			e.stopPropagation();
			
			$this = $(this);
			
			e.preventDefault();
			$this.disableSelection();
			
			$chk=$this.find('input:checkbox:first');
			if(!$chk)
				return;
			
			is_checked = !$chk.is(':checked');
			
			$chk.attr('checked', is_checked);
	
			if(is_checked) {
				$this.find('tr').addClass('selected').removeClass('hover');
			} else {
				$this.find('tr').removeClass('selected');
			}
		}
	})
	.hover(
		function() {
			$(this).find('tr')
				.addClass('hover')
				.find('BUTTON.peek').css('visibility','visible')
				;
		},
		function() {
			$(this).find('tr').
				removeClass('hover')
				.find('BUTTON.peek').css('visibility','hidden')
				;
		}
	)
	;

// Header clicks
$view_frm.find('table.worklistBody thead th, table.worklistBody tbody th')
	.click(function(e) {
		$target = $(e.target);
		if(!$target.is('th'))
			return;
		
		e.stopPropagation();
		$target.find('A').first().click();
	})
	;

// Subtotals
$view.find('table.worklist A.subtotals').click(function(event) {
	genericAjaxGet('view{$view->id}_sidebar','c=internal&a=viewSubtotal&view_id={$view->id}&toggle=1');
	
	$sidebar = $('#view{$view->id}_sidebar');
	if(0 == $sidebar.html().length) {
		$sidebar.css('padding-right','5px');
	} else {
		$sidebar.css('padding-right','0px');
	}
});
</script>