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
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="saveContextLinkAddPeek">
<input type="hidden" name="from_context" value="{$from_context}">
<input type="hidden" name="from_context_id" value="{$from_context_id}">
<input type="hidden" name="to_context" value="{$to_context}">
<input type="hidden" name="return_uri" value="{$return_uri}">

<b>Linked:</b>
<div id="divContextLinkBuffer">
	{foreach from=$links item=link key=link_id}
		<div>
			<button type="button" onclick="genericAjaxGet('','c=internal&a=contextDeleteLink&context={$from_context|escape}&context_id={$from_context_id|escape}&dst_context={$to_context|escape}&dst_context_id={$link_id|escape}');$(this).parent().remove();"><span class="cerb-sprite sprite-forbidden"></span></button> {$link|escape}
			<input type="hidden" name="to_context_id[]" value="{$link_id}">
		</div>
	{/foreach}
</div>
<br>

<button type="button" onclick="this.form.submit();"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>

<br>
</form>

<script language="JavaScript1.2" type="text/javascript">
	function _bufferAddLink($label,$value) {
		var $html = $('<div>' + $label + '</div>');
		$html.prepend(' <button type="button" onclick="$(this).parent().remove();"><span class="cerb-sprite sprite-forbidden"></span></button> ');
		$html.append('<input type="hidden" name="to_context_id[]" value="' + $value + '">');
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
	
	var $popup = genericAjaxPopupFetch('chooser');
	$popup.one('dialogopen',function(event,ui) {
		$popup.dialog('option','title','Link {$context->manifest->name}');
		$('#frmContextLink :input:text:first').focus().select();
		ajax.emailAutoComplete('#frmContextLink :input:text:first');
		
		$('#viewCustomFilters{$view->id}').bind('devblocks.refresh', function(event) {
			if(event.target == event.currentTarget)
				genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id|escape}');
		} );
	} );
</script>