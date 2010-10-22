<div id="history">
	
{if !empty($history_view)}
<fieldset>
	<legend>{$translate->_('portal.sc.public.history.my_conversations')}</legend>
	<div id="view{$history_view->id}">
	{$history_view->render()}
	</div>
</fieldset>
{/if}

</div><!--#history-->