<div style="float:right;">
	{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$view return_url=null reset=false}
</div>

<div style="float:left;">
	<h2>{$context_ext->manifest->name}</h2>
</div>

<div style="clear:both;"></div>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}

<script type="text/javascript">
$(function() {
	// Keyboard shortcuts
	$(document).keypress(function(event) {
		is_control_character = (event.which == 9 || event.which == 10 || event.which == 13 || event.which == 32);
		
		if($(event.target).is('button') && is_control_character)
			return;
			
		if($(event.target).is(':input') && !$(event.target).is('button'))
			return;
		
		// Allow these special keys
		switch(event.which) {
			case 42: // (*)
			case 126: // (~)
				break;
			default:
				if(event.altKey || event.ctrlKey || event.shiftKey || event.metaKey)
					return;
				break;
		}
		
		// [TODO] Intercept 91,93 ([] -- tabs prev/next)
		
		// Find the worklists on this tab
		$worklists = $('#pageSearch TABLE.worklistBody').closest('FORM');
		$worklist = $('');
		
		// Are we confident about the user's intentions with this keystroke?
		indirect = true; // by default, we're not
		
		// Try to find a selected row in the worklists
		$selected_row = $worklists.find('TABLE.worklistBody > TBODY > TR.selected').first();

		if($selected_row.length > 0) {
			$worklist = $selected_row.closest('form');
			
			// Since there's a selected row, we *are* confident.
			indirect = false;
			
		} else {
			// If nothing is selected, try to find a row being hovered over
			$selected_row = $worklists.find('TABLE.worklistBody > TBODY > TR.hover').first();
			
			if($selected_row.length > 0) {
				$worklist = $selected_row.closest('form');
				
			} else {
				// Otherwise, just focus the first worklist
				$worklist = $worklists.first();
			}
		}
		
		if($worklist.length > 0) {
			$worklist.each(function(e) {
				view_id = $(this).find('input:hidden[name=view_id]').val();
				$view = $('#viewForm' + view_id);
				
				// Intercept global worklist keys
				
				hotkey_activated = true;
				
				switch(event.which) {
					case 42: // (*) reset filters
						$('#viewCustomFilters' + view_id + ' TABLE TBODY.full TD:first FIELDSET SELECT[name=_preset]').val('reset').trigger('change');
						break;
						
					case 45: // (-) remove last filter
						$('#viewCustomFilters' + view_id + ' TABLE TBODY.summary UL.bubbles LI:last A.delete').click();
						break;
						
					case 96: // (`) focus first subtotal
						$('#view' + view_id + '_sidebar FIELDSET:first TABLE:first TD:first A:first').focus();
						break;
						
					case 97:  // (a) select all
						try {
							$('#view' + view_id + ' TABLE.worklist input:checkbox.select-all')
								.data('view_id',view_id)
								.each(function(e) {
									view_id = $(this).data('view_id');
									// Trigger event
									e = jQuery.Event('select_all');
									e.view_id = view_id;
									e.checked = !$(this).is(':checked');
									$('#view' + view_id).trigger(e);
								}
							);
						} catch(e) { }
						break;
						
					case 126: // (~) show subtotals
						$('#view' + view_id + '_sidebar FIELDSET UL.cerb-popupmenu').toggle().find('a:first').focus();
						break;
						
					default:
						hotkey_activated = false;
						break;
				}
				
				if(hotkey_activated) {
					event.preventDefault();
					return;
				}
				
				if($view.length > 0) {
					// Trigger event
					e = jQuery.Event('keyboard_shortcut');
					e.view_id = view_id;
					e.indirect = indirect;
					e.keypress_event = event;
					$view.trigger(e);
				}
			});
		}
	});
});
</script>
