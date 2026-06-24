{$uniqid = uniqid()}
<div class="cerb-ui-panel cerb-ui-panel--spaced cerb-ui-panel--note" id="{$uniqid}">
	<div class="cerb-ui-header cerb-ui-header--center">
		<div class="cerb-ui-callout">
			<span class="cerb-icons cerb-icon-circle-info cerb-ui-callout--icon"></span>
			<div>
				<div class="cerb-ui-header--title-sm">Configure a project board</div>
				<div class="cerb-ui-header--subtitle">
					Click the <button type="button"><span class="cerb-icons cerb-icon-gear"></span> </button> button in the top right and select <b>Edit Tab</b>.
				</div>
			</div>
		</div>
	</div>
</div>
<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $div = $('#{$uniqid}');
	var $frm = $('#frmWorkspacePage{$workspace_page->id}');
	
	$div.find('button').on('click', function() {
		var $menu = $frm.find('ul[data-cerb-config-menu]');
		$menu.find('li a.edit-tab').click();
	});
});
</script>