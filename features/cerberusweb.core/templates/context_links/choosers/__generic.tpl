<form action="#" method="POST" id="filter{$view->id}">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="">
<input type="hidden" name="id" value="{$view->id}">

<div id="viewCustomFilters{$view->id}" style="margin:10px;">
{include file="$core_tpl/internal/views/customize_view_criteria.tpl"}
</div>
</form>

<div id="view{$view->id}">
{$view->render()}
</div>

<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmContextLink" name="frmContextLink" onsubmit="return false;">
<b>Selected:</b>
<div id="divContextLinkBuffer">
</div>
<br>
<button type="button" type="button" onclick="_saveChooser();"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>
<br>
</form>

<script language="JavaScript1.2" type="text/javascript">
	var $popup = genericAjaxPopupFetch('chooser');
	
	function _saveChooser() {
		var $buffer = $('#divContextLinkBuffer input:hidden');
		var $labels = [];
		var $values = [];
		
		$buffer.each(function() {
			$labels.push($(this).attr('title')); 
			$values.push($(this).val()); 
		} );
	
		// Trigger event
		var event = jQuery.Event('chooser_save');
		event.labels = $labels;
		event.values = $values;
		$popup.trigger(event);
		
		genericAjaxPopupClose('chooser');
	}
	
	function _bufferAddLink($label, $value) {
		var $html = $('<div>' + $label + '</div>');
		$html.prepend(' <button type="button" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash"></span></button> ');
		$html.append('<input type="hidden" name="to_context_id[]" title="' + $label + '" value="' + $value + '">');
		$('#divContextLinkBuffer').append($html);
	}

	function bufferAddViewSelections() {
		var $view = $('#viewForm{$view->id}');
		var $buffer = $('#divContextLinkBuffer');
		
		$view.find('input:checkbox:checked').each(function(index) {
			$label = $(this).attr('title');
			$value = $(this).val();
			
			if($label.length > 0 && $value.length > 0)
				if(0==$buffer.find('input:hidden[value='+$value+']').length)
					_bufferAddLink($label, $value);
				
			$(this).removeAttr('checked');
		} );
	}
	
	$popup.one('popup_open',function(event,ui) {
		$popup.dialog('option','title','{$context->manifest->name} Chooser');
		
		$('#viewCustomFilters{$view->id}').bind('view_refresh', function(event) {
			if(event.target == event.currentTarget)
				genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id|escape}');
		} );
	} );
</script>