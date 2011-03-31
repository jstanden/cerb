<script type="text/javascript">
$('FORM#viewForm{$view->id} TABLE.worklistBody TBODY')
	.click(function(e) {
		$target = $(e.target);
	
		// Are any of our parents an anchor tag?	
		$parents = $target.parents('a');
		if($parents.length > 0) {
			$target = $parents[$parents.length-1]; // 0-based
		}
		
		if (false == $target instanceof jQuery) {
			// Not a jQuery object
		} else if($target.is(':input,:button,a,img')) {
			// Ignore form elements and links
		} else {
			e.preventDefault();
			$chk=$(this).find('input:checkbox:first');
			if(!$chk)
				return;
	
			is_checked = !$chk.is(':checked');
			
			$chk.attr('checked', is_checked);
	
			if(is_checked) {
				$(this).find('tr').addClass('selected').removeClass('hover');
			} else {
				$(this).find('tr').removeClass('selected');
			}
				
		}
		e.stopPropagation();
	})
	.hover(
		function() {
			$(this).find('tr').addClass('hover');
		},
		function() {
			$(this).find('tr').removeClass('hover');
		}
	)
	;
</script>