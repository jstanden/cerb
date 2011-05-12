<script type="text/javascript">
{if $pref_keyboard_shortcuts}

$(document).keypress(function(event) {
	// Don't trigger on forms
	if($(event.target).is(':input'))
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

	hotkey_activated = true;
	
	switch(event.which) {
		case 42: // (*) reset filters
			$('#viewCustomFilters{$view->id} TABLE TBODY.full TD:first FIELDSET SELECT[name=_preset]').val('reset').trigger('change');
			break;
		case 45: // (-) remove last filter
			$('#viewCustomFilters{$view->id} TABLE TBODY.summary UL.bubbles LI:last A.delete').click();
			break;
		case 96: // (`)
			$('#view{$view->id}_sidebar FIELDSET:first TABLE:first TD:first A:first').focus();
			break;
		case 97:  // (A) select all
			try {
				$('#view{$view->id} TABLE.worklist input:checkbox').each(function(e) {
					is_checked = !this.checked;
					checkAll('viewForm{$view->id}',is_checked);
					$rows=$('#viewForm{$view->id}').find('table.worklistBody').find('tbody > tr');
					if(is_checked) { 
						$rows.addClass('selected'); 
						$(this).attr('checked','checked');
					} else { 
						$rows.removeClass('selected');
						$(this).removeAttr('checked');
					}
				});
			} catch(e) { } 
			break;
		case 126: // (~)
			$('#view{$view->id}_sidebar FIELDSET UL.cerb-popupmenu').toggle().find('a:first').focus();
			break;
		default:
			hotkey_activated = false;
			break;
	}

	if(hotkey_activated)
		event.preventDefault();
});

{/if}
</script>