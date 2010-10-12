<div id="history">
	
{if !empty($open_view)}
<fieldset>
	<legend>{$translate->_('portal.sc.public.history.my_open_conversations')}</legend>
	<div id="view{$open_view->id}">
	{$open_view->render()}
	</div>
</fieldset>
{/if}

{if !empty($closed_view)}
<fieldset>
	<legend>{$translate->_('portal.sc.public.history.my_closed_conversations')}</legend>
	<div id="view{$closed_view->id}">
	{$closed_view->render()}
	</div>
</fieldset>
{/if}

</div><!--#history-->