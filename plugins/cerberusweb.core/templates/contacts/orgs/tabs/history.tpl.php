<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxPanel('c=tickets&a=showComposePeek&id=0&view_id={$view->id}',null,false,'600px',{literal}function(o){ajax.cbEmailPeek(o);document.getElementById('formComposePeek').team_id.focus();}{/literal});"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/mail_write.gif{/devblocks_url}" align="top"> Quick Compose</button>
</form>

<div id="viewcontact_history">{$contact_history->render()}</div>