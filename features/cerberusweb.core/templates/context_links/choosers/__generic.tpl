<div style="float:left;">
{include file="devblocks:cerberusweb.core::search/quick_search.tpl" view=$view}
</div>

<div style="clear:both;"></div>

{include file="devblocks:cerberusweb.core::internal/views/search_and_view.tpl" view=$view}

<form action="#" method="POST" id="chooser{$view->id}">
<b>Selected:</b>
<ul class="buffer bubbles"></ul>
<br>
<br>
<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')}</button>
<br>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFind('#chooser{$view->id}');
	
	$popup.find('UL.buffer').sortable({ placeholder: 'ui-state-highlight' });
	
	$popup.one('popup_open',function(event,ui) {
		event.stopPropagation();
		$(this).dialog('option','title','{$context->manifest->name} Chooser');
		
		$popup.find('select:first').focus();

		// Progressive de-enhancement
		
		var on_refresh = function() {
			$worklist = $('#view{$view->id}').find('TABLE.worklist');
			$worklist.css('background','none');
			$worklist.css('background-color','rgb(100,100,100)');
			
			$header = $worklist.find('> tbody > tr:first > td:first > span.title');
			$header.css('font-size', '14px');
			$header_links = $worklist.find('> tbody > tr:first td:nth(1)');
			$header_links.children().each(function(e) {
				if(!$(this).is('a.minimal, input:checkbox'))
					$(this).remove();
			});
			$header_links.find('a').css('font-size','11px');

			$worklist_body = $('#view{$view->id}').find('TABLE.worklistBody');
			$worklist_body.find('a.subject').each(function() {
				$txt = $('<b class="subject">' + $(this).text() + '</b>');
				$txt.insertBefore($(this));
				$(this).remove();
			});
			
			$actions = $('#{$view->id}_actions').find('> tbody > tr:first td');
			$actions.html('');
		}
		
		on_refresh();

		$(this).delegate('DIV[id^=view]','view_refresh', on_refresh);		
		
		$('#view{$view->id}').delegate(' TABLE.worklistBody input:checkbox', 'check', function(event) {
			checked = $(this).is(':checked');

			$view = $('#viewForm{$view->id}');
			$buffer = $('form#chooser{$view->id} UL.buffer');

			$tbody = $(this).closest('tbody');

			$label = $tbody.find('b.subject').text();
			$value = $(this).val();
		
			if(checked) {
				if($label.length > 0 && $value.length > 0) {
					if(0==$buffer.find('input:hidden[value="'+$value+'"]').length) {
						$li = $('<li>'+$label+'<input type="hidden" name="to_context_id[]" title="'+$label+'" value="'+$value+'"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>');
						$buffer.append($li);
					}
				}
				
			} else {
				$buffer.find('input:hidden[value="'+$value+'"]').closest('li').remove();
			}
			
		});
		
		$("form#chooser{$view->id} button.submit").click(function(event) {
			event.stopPropagation();
			$popup = genericAjaxPopupFind('form#chooser{$view->id}');
			$buffer = $($popup).find('UL.buffer input:hidden');
			$labels = [];
			$values = [];
			
			$buffer.each(function() {
				$labels.push($(this).attr('title')); 
				$values.push($(this).val()); 
			});
		
			// Trigger event
			event = jQuery.Event('chooser_save');
			event.labels = $labels;
			event.values = $values;
			$popup.trigger(event);
			
			genericAjaxPopupDestroy('{$layer}');
		});		
	});
	$popup.one('diagogclose', function(event) {
		event.stopPropagation();
		genericAjaxPopupDestroy('{$layer}');
	});
</script>