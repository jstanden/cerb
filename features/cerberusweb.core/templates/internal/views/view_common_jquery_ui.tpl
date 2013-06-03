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

{* Run custom jQuery scripts from VA behavior *}
{$va_actions = []}
{$va_behaviors = []}
{Event_UiWorklistRenderByWorker::triggerForWorker($active_worker, $view_context, $view->id, $va_actions, $va_behaviors)}

{if !empty($va_behaviors)}
	<script type="text/javascript">
	{if $va_actions.jquery_scripts}
	{
		{foreach from=$va_actions.jquery_scripts item=jquery_script}
		try {
			{$jquery_script nofilter}
		} catch(e) { }
		{/foreach}

		var $va_button = $('<a href="javascript:;" title="This worklist was modified by Virtual Attendants"><span class="cerb-sprite2 sprite-robot" style="vertical-align:bottom;"></span></a>');
		$va_button.click(function() {
			var $va_action_log = $('#view{$view->id}_va_actions');
			if($va_action_log.is(':hidden')) {
				$va_action_log.fadeIn();
			} else {
				$va_action_log.fadeOut();
			}
		});
		$va_button.insertAfter($view.find('TABLE.worklist SPAN.title'));
		$('#view{$view->id}_va_actions').insertAfter($view.find('TABLE.worklist'));
	}
	{/if}
	</script>
	
	<div class="block" style="display:none;margin:5px;" id="view{$view->id}_va_actions">
		<b>This worklist was modified by Virtual Attendants:</b>
		<ul style="margin:0;">
			{foreach from=$va_behaviors item=va_behavior name=va_behaviors}
			<li>
				{$meta = $va_behavior->getOwnerMeta()}
				{$va_behavior->title} [{$meta.name}]
			</li>
			{/foreach}
		</ul>
		
		<button type="button" onclick="$(this).closest('div.block').fadeOut();">{'common.ok'|devblocks_translate|upper}</button>
	</div>
{/if}

<script type="text/javascript">
//Condense the TH headers
{
	var $view_thead = $view_frm.find('TABLE.worklistBody THEAD');
	
	// Remove the heading labels to let the browser find the content-based widths
	$view_thead.find('TH').each(function() {
		var $th = $(this);
		var $a = $th.find('a');
		
		$th.find('span.cerb-sprite').prependTo($th);
		
		$a.attr('title', $a.text());
		$a.html('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;');
	});

	var view_table_width = $view_thead.closest('TABLE').width();
	var view_table_width_left = 100;
	var view_table_width_cols = $view_thead.find('TH').length - 1;
	
	$view_thead.find('TH A').each(function(idx) {
		var $a = $(this);
		var $th = $a.closest('th');
		var width = 0;
		
		// On the last column, take all the remaining width (no rounding errors)
		if(idx == view_table_width_cols) {
			width = view_table_width_left;
	
		// Figure out the proportional width for this column compared to the whole table
		} else {
			width = Math.ceil(100 * $th.width() / view_table_width);
			view_table_width_left -= width;
		}
		
		// Set explicit proportional widths
		$th
			.css('white-space','nowrap')
			.css('overflow','hidden')
			.css('width', width + '%')
			;
		
	});
	
	// Reflow the table using our explicit widths (no auto layout)
	$view_thead.closest('table').css('table-layout','fixed');
	
	// Replace the truncated heading labels
	$view_thead.find('TH A').each(function(idx) {
		var $a = $(this);
		$a.html($a.attr('title'));
	});
}
</script>