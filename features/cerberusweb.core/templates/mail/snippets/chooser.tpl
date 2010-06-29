<form style="margin-bottom:2px;" id="frmSnippetChooser" onsubmit="return false;">
	{$translate->_('common.filter')|capitalize}: <input type="text" name="term" value="" size="32" autocomplete="off">
</form>

<div id="view{$view->id}">
  	{$view->render()}
</div>

<script language="JavaScript1.2" type="text/javascript">
	var $popup = genericAjaxPopupFetch('peek');
	$popup.one('dialogopen',function(event,ui) {
		$popup.dialog('option','title',"Snippets");
		$('#view{$view->id}').data('context_id','{$context_id}');
		$('#view{$view->id}').data('text_element','{$text_element}');
		
		$('#frmSnippetChooser input:text:first').focus();
		
		var $snippetWaitTime = 0;
		var $snippetTimer = null;
		$('#frmSnippetChooser input:text[name=term]').bind('keyup', function(evt) {
			var $term = $(evt.target).val();
			
			$snippetWaitTime = new Date().getTime();
			clearTimeout($snippetTimer);
			
			$snippetTimer = setTimeout(function() { 
				if(new Date().getTime() - $snippetWaitTime >= 800) {
					genericAjaxGet('view{$view->id}','c=display&a=filterSnippetsChooser&view_id={$view->id}&term='+escape($term));
				}
			} , 810);
		} );
	} );
</script>
