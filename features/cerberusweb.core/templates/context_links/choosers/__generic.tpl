<form action="#" method="POST" id="filters{$view->id}">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="">
<input type="hidden" name="id" value="{$view->id}">

<div id="viewCustomFilters{$view->id}" style="margin:10px;">
{include file="devblocks:cerberusweb.core::internal/views/customize_view_criteria.tpl"}
</div>
</form>

<div id="view{$view->id}">
{$view->render()}
</div>

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
		
		$('#viewCustomFilters{$view->id}').bind('view_refresh', function(event) {
			if(event.target == event.currentTarget)
				genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id}');
		});
		
		$('#view{$view->id}').delegate('button.devblocks-chooser-add-selected', 'click', function(event) {
			event.stopPropagation();
			$view = $('#viewForm{$view->id}');
			$buffer = $('form#chooser{$view->id} UL.buffer');
			
			$view.find('input:checkbox:checked').each(function(index) {
				$label = $(this).attr('title');
				$value = $(this).val();
				
				if($label.length > 0 && $value.length > 0) {
					if(0==$buffer.find('input:hidden[value='+$value+']').length) {
						$li = $('<li>'+$label+'<input type="hidden" name="to_context_id[]" title="'+$label+'" value="'+$value+'"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>');
						$buffer.append($li);
					}
				}
					
				$(this).removeAttr('checked');
			});
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