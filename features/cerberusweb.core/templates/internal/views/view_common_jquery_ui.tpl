<script type="text/javascript">
$view = $('div#view{$view->id}');
$view_frm = $('form#viewForm{$view->id}');
$view_actions = $view_frm.find('#{$view->id}_actions');

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

			// Count how many selected rows we have left and adjust the toolbar actions
			$frm = $this.closest('form');
			$selected_rows = $frm.find('TR.selected').closest('tbody');
			$view_actions = $frm.find('#{$view->id}_actions');
			
			if(0 == $selected_rows.length) {
				$view_actions.find('button,.action-on-select').not('.action-always-show').fadeOut('fast');
			} else if(1 == $selected_rows.length) {
				$view_actions.find('button,.action-on-select').not('.action-always-show').fadeIn('fast');
			}
			
			$chk.trigger('check');
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

// Select all

$view.find('table.worklist input:checkbox.select-all').click(function(e) {
	// Trigger event
	e = jQuery.Event('select_all');
	e.view_id = '{$view->id}';
	e.checked = $(this).is(':checked');
	$('div#view{$view->id}').trigger(e);
});

$view.bind('select_all', function(e) {
	$view = $('div#view' + e.view_id);
	$view_form = $view.find('#viewForm' + e.view_id);
	$checkbox = $view.find('table.worklist input:checkbox.select-all');
	checkAll('viewForm' + e.view_id, e.checked);
	$rows = $view_form.find('table.worklistBody').find('tbody > tr');
	$view_actions = $('#' + e.view_id + '_actions');
	
	if(e.checked) {
		$checkbox.attr('checked', 'checked');
		$rows.addClass('selected'); 
		$(this).attr('checked','checked');
		$view_actions.find('button,.action-on-select').not('.action-always-show').fadeIn('fast');	
	} else {
		$checkbox.removeAttr('checked');
		$rows.removeClass('selected');
		$(this).removeAttr('checked');
		$view_actions.find('button,.action-on-select').not('.action-always-show').fadeOut('fast');
	}
});

// View actions
$view_actions.find('button,.action-on-select').not('.action-always-show').hide();
</script>