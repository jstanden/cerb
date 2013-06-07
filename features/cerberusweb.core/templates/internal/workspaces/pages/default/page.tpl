<div id="pageTabs">
	<ul>
		{$tabs = []}
		
		{foreach from=$page_tabs item=tab}
			{$tabs[] = 'w_'|cat:$tab->id}
			<li class="drag" tab_id="{$tab->id}"><a href="{devblocks_url}ajax.php?c=pages&a=showWorkspaceTab&point={$point}&id={$tab->id}&request={$response_uri|escape:'url'}{/devblocks_url}">{$tab->name}</a></li>
		{/foreach}

		{if $page->isWriteableByWorker($active_worker)}
			<li><a href="{devblocks_url}ajax.php?c=pages&a=showAddTabs&page_id={$page->id}{/devblocks_url}">+</a></li>
		{/if}
	</ul>
</div>
	
<div style="margin-top:10px;">
	{include file="devblocks:cerberusweb.core::internal/whos_online.tpl"}
</div>

{$tab_selected_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$selected_tab}{$tab_selected_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
	$(function() {
		var $tabs = $("#pageTabs");
		
		var tabs = $tabs.tabs( { 
			selected: {$tab_selected_idx}
		});
		
		$tabs.find('> ul').sortable({
			items:'> li.drag',
			distance: 20,
			forcePlaceholderWidth:true,
			update:function(e) {
				$tabs = $("#pageTabs");
				$page_tabs = $tabs.find('ul.ui-tabs-nav > li.drag[tab_id=*]');
				page_tab_ids = $page_tabs.map(function(e) {
					return $(this).attr('tab_id');
				}).get().join(',');
	
				genericAjaxGet('', 'c=pages&a=setTabOrder&page_id={$page->id}&tabs=' + page_tab_ids);
			}
		});
		
		$tabs = $("#pageTabs").on('tabsactivate', function(e, ui) {
			var $tab_text = $.trim($(ui.newTab).find('a').text());
			
			var $frm = $('#frmWorkspacePage{$page->id}');
			var $menu = $frm.find('ul.cerb-popupmenu');
			
			if($tab_text == '+') {
				$menu.find('a.edit-tab, a.export-tab').parent().hide();
			} else {
				$menu.find('a.edit-tab, a.export-tab').parent().show();
			}
		});
		
		// Keyboard shortcuts
		
		$(document).keypress(function(event) {
			//console.log(event.which);
			
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
			
			// How many tabs are we showing?
			num_tabs = $tabs.tabs('length');
			
			if(0 == num_tabs)
				return;
			
			// Which tab is selected?
			tab_id = $tabs.tabs('option','selected') + 1;
			
			// Find the worklists on this tab
			$worklists = $('#ui-tabs-' + tab_id).find('TABLE.worklistBody').closest('FORM');
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
					// [TODO] alternate
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
								$('#view' + view_id + ' TABLE.worklist input:checkbox').data('view_id',view_id).each(function(e) {
									view_id = $(this).data('view_id');
									// Trigger event
									e = jQuery.Event('select_all');
									e.view_id = view_id;
									e.checked = !$(this).is(':checked')
									$('#view' + view_id).trigger(e);
								});
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