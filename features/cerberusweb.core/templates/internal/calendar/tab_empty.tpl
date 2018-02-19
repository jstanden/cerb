{$uniqid = uniqid()}
<div class="help-box" id="{$uniqid}">
<h1>Configure a calendar</h1>
<p>
	Click the <button type="button"><span class="glyphicons glyphicons-cogwheel"></span> </button> button in the top right and select <b>Edit Tab</b>.
</p>
</div>
<script type="text/javascript">
$(function() {
	var $div = $('#{$uniqid}');
	var $frm = $('#frmWorkspacePage{$workspace_page->id}');
	
	$div.find('button').on('click', function() {
		var $menu = $frm.find('ul.cerb-popupmenu');
		$menu.find('li a.edit-tab').click();
	});
});
</script>