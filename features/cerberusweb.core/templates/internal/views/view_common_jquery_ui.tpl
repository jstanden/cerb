<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $view = $('div#view{$view->id}');
	let $view_form = $('form#viewForm{$view->id}');
	let $view_actions = $view_form.find('#{$view->id}_actions');
	let $last_row_clicked = null;

	// Row selection and hover effect
	$view_form.find('TABLE.worklistBody TBODY')
		.click(function(e) {
			if(window.getSelection && window.getSelection().toString().length > 0)
				return;

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

				var $rows = $chk.closest('tbody');
				
				// If we have a previously clicked row and are holding shift, interpolate
				if(e.shiftKey && $last_row_clicked instanceof jQuery) {
					
					if($chk.closest('tbody').index() < $last_row_clicked.index()) {
						$rows = $rows.add($last_row_clicked.prevUntil($chk.closest('tbody'),'tbody'));
						
					} else if($chk.closest('tbody').index() > $last_row_clicked.index()) {
						$rows = $rows.add($last_row_clicked.nextUntil($chk.closest('tbody'),'tbody'));
					}
				}
				
				$rows.each(function() {
					var $chk = $(this).find('input:checkbox');
					var $row = $(this);
					
					if(is_checked) {
						$chk.prop('checked', is_checked);
						$row.find('tr').addClass('selected').removeClass('hover');
						
					} else {
						$chk.prop('checked', is_checked);
						$row.find('tr').removeClass('selected');
					}
				});
				
				$last_row_clicked = $chk.closest('tbody');
		
				// Count how many selected rows we have left and adjust the toolbar actions
				var $frm = $this.closest('form');
				var $selected_rows = $frm.find('TR.selected').closest('tbody');
				var $view_actions = $frm.find('#{$view->id}_actions');
				
				if(0 === $selected_rows.length) {
					$view_actions.find('button,.action-on-select').not('.action-always-show').fadeOut('fast');
					$frm.find('TABLE.worklistBody TBODY').enableSelection();

				} else if(1 === $selected_rows.length) {
					$view_actions.find('button,.action-on-select').not('.action-always-show').fadeIn('fast');
					$frm.find('TABLE.worklistBody TBODY').disableSelection();
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
			let $target = $(e.target);
			if(!$target.is('th'))
				return;
			
			e.stopPropagation();
			$target.find('A').first().click();
		})
		;

	// Sort
	$view_form.find('table.worklistBody [data-cerb-worklist-sort]').click(function(e) {
		e.stopPropagation();
		let header = $(this).attr('data-cerb-worklist-sort');
		genericAjaxGet('view{$view->id}','c=internal&a=invoke&module=worklists&action=sort&id={$view->id}&sortBy=' + encodeURIComponent(header));
	});

	// Search
	$view.find('table.worklist [data-cerb-worklist-icon-search]').click(function(e) {
		e.stopPropagation();
		genericAjaxPopup('search','c=internal&a=invoke&module=worklists&action=showQuickSearchPopup&view_id={$view->id}',null,false,'50%');
	});

	// Customize
	$view.find('table.worklist [data-cerb-worklist-icon-customize]').click(function(e) {
		e.stopPropagation();
		genericAjaxGet('customize{$view->id}','c=internal&a=invoke&module=worklists&action=customize&id={$view->id}');
		toggleDiv('customize{$view->id}','block');
	});

	// Import
	$view.find('table.worklist [data-cerb-worklist-icon-import]').click(function(e) {
		e.stopPropagation();
		genericAjaxPopup('import','c=internal&a=invoke&module=worklists&action=renderImportPopup&context={$view_context}&view_id={$view->id}',null,false,'50%');
	});

	// Export
	$view.find('table.worklist [data-cerb-worklist-icon-export]').click(function(e) {
		e.stopPropagation();
		genericAjaxGet('{$view->id}_tips','c=internal&a=invoke&module=worklists&action=renderExport&id={$view->id}');
		toggleDiv('{$view->id}_tips','block');
	});

	// Copy
	$view.find('table.worklist [data-cerb-worklist-icon-copy]').click(function(e) {
		e.stopPropagation();
		genericAjaxGet('{$view->id}_tips','c=internal&a=invoke&module=worklists&action=renderCopy&view_id={$view->id}');
		toggleDiv('{$view->id}_tips','block');
	});

	// Subtotals
	$view.find('table.worklist [data-cerb-worklist-icon-subtotals]').click(function(e) {
		e.stopPropagation();
		genericAjaxGet('view{$view->id}_sidebar','c=internal&a=invoke&module=worklists&action=subtotal&view_id={$view->id}&toggle=1', function(html) {
			let $sidebar = $('#view{$view->id}_sidebar');

			if(0 === html.length) {
				$sidebar.hide();
			} else {
				$sidebar.show();
			}
		});
	});

	// Paging
	$view_form.find('[data-cerb-worklist-page-link]').click(function(e) {
		e.stopPropagation();
		let page = $(this).attr('data-cerb-worklist-page-link');
		genericAjaxGet('view{$view->id}','c=internal&a=invoke&module=worklists&action=page&id={$view->id}&page=' + encodeURIComponent(page));
	});

	// Jump
	$view.find('table.worklist [data-cerb-worklist-icon-actions]').click(function(e) {
		e.stopPropagation();
		$('#{$view->id}_actions button').first().focus();
	});

	// Refresh
	$view.find('table.worklist [data-cerb-worklist-icon-refresh]').click(function(e) {
		e.stopPropagation();
		genericAjaxGet('view{$view->id}','c=internal&a=invoke&module=worklists&action=refresh&id={$view->id}');
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
			$view_form.find('TABLE.worklistBody TBODY').disableSelection();
		} else {
			$checkbox.prop('checked', e.checked);
			$(this).prop('checked', e.checked);
			$rows.removeClass('selected');
			$view_actions.find('button,.action-on-select').not('.action-always-show').fadeOut('fast');
			$view_form.find('TABLE.worklistBody TBODY').enableSelection();
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
	
	let view_table_width = $view_thead.closest('TABLE').width();
	let view_table_width_left = 100;
	let view_table_width_cols = $view_thead.find('TH').length - 1;
	
	$view_thead.find('TH A').each(function(idx) {
		let $a = $(this);
		let $th = $a.closest('th');
		let width;

		// On the last column, take all the remaining width (no rounding errors)
		if(idx === view_table_width_cols) {
			width = view_table_width_left;
	
		// Figure out the proportional width for this column compared to the whole table
		} else {
			width = Math.round(100 * ($th.outerWidth() / view_table_width));
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
	
	// View toolbar

	$view.find('[data-cerb-worklist-toolbar]').cerbToolbar({
		caller: {
			name: 'cerb.toolbar.records.worklist',
			params: {
				worklist_id: '{$view->id}',
				worklist_record_type: '{$view->getRecordType()}'
			}
		},
		width: '75%',
		start: function(formData) {
			let $rows = $view_form.find('input:checkbox');
			$rows.toArray().forEach(function(checkbox) {
				let $checkbox = $(checkbox);
				formData.append('caller[params][visible_record_ids][]', $checkbox.val());
				if($checkbox.is(':checked')) {
					formData.append('caller[params][selected_record_ids][]', $checkbox.val());
				}
			});
		},
		done: function(e) {
			e.stopPropagation();

			var $target = e.trigger;

			if (!$target.is('.cerb-bot-trigger'))
				return;

			if (e.eventData.exit === 'error') {

			} else if(e.eventData.exit === 'return') {
				Devblocks.interactionWorkerPostActions(e.eventData);

				let done_params = new URLSearchParams($target.attr('data-interaction-done'));

				// Refresh the worklist?
				if(
					!done_params.has('refresh_worklist')
					|| '1' === done_params.get('refresh_worklist')
				) {
					genericAjaxGet('view{$view->id}', 'c=internal&a=invoke&module=worklists&action=refresh&id={$view->id}');
				}
			}
		}
	});
	
	// View actions
	$view_actions.find('button,.action-on-select').not('.action-always-show').hide();

	// Explore
	$view_actions.find('.action-explore').on('click', function(e) {
		e.stopPropagation();
		this.form.explore_from.value = $(this).closest('form').find('tbody input:checkbox:checked:first').val();
		this.form.action.value='viewExplore';
		this.form.submit();
	});

	// Bulk
	$view_actions.find('[data-cerb-worklist-action-bulk]').on('click', function(e) {
		e.stopPropagation();
		let module = $(this).attr('data-cerb-worklist-action-bulk');

		let field_key = $(this).attr('data-cerb-worklist-action-bulk-field-key');
		if(!field_key) field_key = 'row_id[]';

		genericAjaxPopup('peek','c=profiles&a=invoke&module=' + encodeURIComponent(module) + '&action=showBulkPopup&view_id={$view->id}&ids=' + Devblocks.getFormEnabledCheckboxValues('viewForm{$view->id}',field_key),null,false,'50%');
	});

	// Merge
	$view_actions.find('[data-cerb-worklist-action-merge]').on('click', function(e) {
		e.stopPropagation();

		let field_key = $(this).attr('data-cerb-worklist-action-merge');
		if(!field_key) field_key = 'row_id[]';

		genericAjaxPopup('peek','c=internal&a=invoke&module=records&action=renderMergePopup&view_id={$view->id}&context={$view_context}&ids=' + Devblocks.getFormEnabledCheckboxValues('viewForm{$view->id}',field_key),null,false,'50%');
	});

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
	<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
	{if $va_actions.jquery_scripts}
	{
		{foreach from=$va_actions.jquery_scripts item=jquery_script}
		try {
			{$jquery_script nofilter}
		} catch(e) { }
		{/foreach}

		let $view = $('div#view{$view->id}');
		let $va_actions = $('#view{$view->id}_va_actions');
		let $va_button = $('<a title="This worklist was modified by bots"><div style="background-color:var(--cerb-color-background-contrast-230);display:inline-block;margin-top:3px;border-radius:11px;padding:2px;"><img src="{devblocks_url}c=avatars&context=app&id=0{/devblocks_url}" style="width:14px;height:14px;margin:0;"></div></a>');
		$va_button.click(function(e) {
			e.stopPropagation();
			let $va_action_log = $('#view{$view->id}_va_actions');
			if($va_action_log.is(':hidden')) {
				$va_action_log.fadeIn();
			} else {
				$va_action_log.fadeOut();
			}
		});
		$va_button.insertAfter($view.find('TABLE.worklist SPAN.title'));

		$va_actions.find('button.cancel').on('click', function(e) {
			e.stopPropagation();
			$(this).closest('div.block').fadeOut();
		});

		$va_actions.insertAfter($view.find('TABLE.worklist'));
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
					<a class="cerb-peek-trigger" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$bot_behavior->id}" data-profile-url="{devblocks_url}c=profiles&a=behavior&id={$bot_behavior->id}{/devblocks_url}">{$bot_behavior->title}</a>
				</li>
			{/foreach}
			</ul>
		</div>

		<button type="button" class="cancel">{'common.ok'|devblocks_translate|upper}</button>
	</div>
{/if}