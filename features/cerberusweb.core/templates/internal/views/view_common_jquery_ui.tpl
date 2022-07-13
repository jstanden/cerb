<script type="text/javascript">
$(function() {
	var $view = $('div#view{$view->id}');
	var $view_form = $('form#viewForm{$view->id}');
	var $view_actions = $view_form.find('#{$view->id}_actions');
	
	// Row selection and hover effect
	$view_form.find('TABLE.worklistBody TBODY')
		.click(function(e) {
			var $target = $(e.target);
		
			// Are any of our parents an anchor tag?
			var $parents = $target.parents('a');
			if($parents.length > 0) {
				$target = $parents[$parents.length-1]; // 0-based
			}
			
			if (!($target instanceof jQuery)) {
				// Not a jQuery object
			} else if($target.is(':input,:button,a,img,div.badge-count,span.glyphicons,span.cerb-label')) {
				// Ignore form elements and links
				e.stopPropagation();
			} else {
				e.stopPropagation();
				
				var $this = $(this);
				
				e.preventDefault();
				
				var $chk = $this.find('input:checkbox:first');
				
				if(0 === $chk.length)
					return;
				
				var is_checked = !($chk.prop('checked') ? true : false);
				
				if(is_checked) {
					$chk.prop('checked', is_checked);
					$this.find('tr').addClass('selected').removeClass('hover');
					
				} else {
					$chk.prop('checked', is_checked);
					$this.find('tr').removeClass('selected');
				}
		
				// Count how many selected rows we have left and adjust the toolbar actions
				var $frm = $this.closest('form');
				var $selected_rows = $frm.find('TR.selected').closest('tbody');
				var $view_actions = $frm.find('#{$view->id}_actions');
				
				if(0 === $selected_rows.length) {
					$view_actions.find('button,.action-on-select').not('.action-always-show').fadeOut('fast');
					
				} else if(1 === $selected_rows.length) {
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
	$view_form.find('table.worklistBody thead th, table.worklistBody tbody th')
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
		genericAjaxGet('view{$view->id}_sidebar','c=internal&a=invoke&module=worklists&action=subtotal&view_id={$view->id}&toggle=1', function(html) {
			var $sidebar = $('#view{$view->id}_sidebar');
			
			if(0 == html.length) {
				$sidebar.hide();
			} else {
				$sidebar.show();
			}
		});
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
		var $view = $('div#view' + e.view_id);
		var $view_form = $view.find('#viewForm' + e.view_id);
		var $checkbox = $view.find('table.worklist input:checkbox.select-all');
		checkAll('viewForm' + e.view_id, e.checked);
		var $rows = $view_form.find('table.worklistBody').find('tbody > tr');
		var $view_actions = $('#' + e.view_id + '_actions');
		
		if(e.checked) {
			$checkbox.prop('checked', e.checked);
			$(this).prop('checked', e.checked);
			$rows.addClass('selected'); 
			$view_actions.find('button,.action-on-select').not('.action-always-show').fadeIn('fast');
		} else {
			$checkbox.prop('checked', e.checked);
			$(this).prop('checked', e.checked);
			$rows.removeClass('selected');
			$view_actions.find('button,.action-on-select').not('.action-always-show').fadeOut('fast');
		}
	});
	

	//Condense the TH headers
	
	var $view_thead = $view_form.find('TABLE.worklistBody THEAD');
	
	// Remove the heading labels to let the browser find the content-based widths
	$view_thead.find('TH').each(function() {
		var $th = $(this);
		var $a = $th.find('a');
		
		$th.find('span.glyphicons').prependTo($th);
		
		$a.attr('title', $a.text());
		$a.html('&nbsp;&nbsp;&nbsp;');
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
			width = Math.ceil(100 * ($th.outerWidth()) / view_table_width);
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
		$a.text($a.attr('title'));
	});
		
	// View actions
	$view_actions.find('button,.action-on-select').not('.action-always-show').hide();
	
	// Peeks
	$view.find('.cerb-peek-trigger').cerbPeekTrigger({ view_id: '{$view->id}' });

	// Searches
	$view.find('.cerb-search-trigger').cerbSearchTrigger({ view_id: '{$view->id}' });
});
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

		var $va_button = $('<a href="javascript:;" title="This worklist was modified by bots"><div style="background-color:var(--cerb-color-background-contrast-230);display:inline-block;margin-top:3px;border-radius:11px;padding:2px;"><img src="{devblocks_url}c=avatars&context=app&id=0{/devblocks_url}" style="width:14px;height:14px;margin:0;"></div></a>');
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
		<b>This worklist was modified by bots:</b>
		
		<div style="padding:10px;">
			<ul class="bubbles">
			{foreach from=$va_behaviors item=bot_behavior name=bot_behaviors}
				{$bot = $bot_behavior->getBot()}
				<li>
					<img src="{devblocks_url}c=avatars&context=bot&context_id={$bot->id}{/devblocks_url}?v={$bot->updated_at}" class="cerb-avatar">
					<a href="{devblocks_url}c=profiles&a=behavior&id={$bot_behavior->id}{/devblocks_url}" class="cerb-peek-trigger" onclick="return false;" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$bot_behavior->id}">{$bot_behavior->title}</a>
				</li>
			{/foreach}
			</ul>
		</div>
		
		<button type="button" onclick="$(this).closest('div.block').fadeOut();">{'common.ok'|devblocks_translate|upper}</button>
	</div>
{/if}