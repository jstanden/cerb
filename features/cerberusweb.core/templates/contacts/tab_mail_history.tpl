<form action="{devblocks_url}{/devblocks_url}" style="margin-bottom:5px;">
	<button type="button" onclick="genericAjaxPopup('peek','c=mail&a=handleSectionAction&section=compose&action=showComposePeek&id=0&view_id={$view->id}',null,false,'600');"><span class="cerb-sprite sprite-export"></span> {'mail.send_mail'|devblocks_translate}</button>
</form>

<div id="view{$view->id}">{$view->render()}</div>