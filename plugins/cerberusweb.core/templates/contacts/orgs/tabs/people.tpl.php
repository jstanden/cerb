<input type="button" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&id=0&org_id={$contact->id}&view_id={$view->id}',this,false,'500px',ajax.cbAddressPeek);" value="Add Contact">
<br>
<div id="vieworg_contacts">{$view->render()}</div>