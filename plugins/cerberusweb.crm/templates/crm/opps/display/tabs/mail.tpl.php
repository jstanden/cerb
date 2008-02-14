<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	<button id="btnQuickCompose" type="button" onclick="genericAjaxPanel('c=tickets&a=showComposePeek&view_id={$view->id}&to={$address->email}',null,false,'600px',{literal}function(o){ajax.cbEmailPeek(o);document.getElementById('formComposePeek').team_id.focus();}{/literal});"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/mail_write.gif{/devblocks_url}" align="top"> Quick Compose</button>
</form>

<div id="viewopp_tickets">{$view->render()}</div>
