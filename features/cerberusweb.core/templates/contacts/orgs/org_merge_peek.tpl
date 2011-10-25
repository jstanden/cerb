<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formOrgMerge" name="formOrgMerge" onsubmit="return false;">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="showOrgMergeContinuePeek">
<input type="hidden" name="view_id" value="{$view_id}">

<b>Select organizations to merge:</b><br>
<button type="button" class="chooser_orgs"><span class="cerb-sprite sprite-view"></span></button>
<ul class="chooser-container bubbles" style="display:block;">
</ul>
<br>

{if $active_worker->hasPriv('core.addybook.org.actions.update')}
	{*<button type="button" onclick="return;if($('#formOrgPeek').validate().form()) { genericAjaxPopupPostCloseReloadView(null,'formOrgPeek', '{$view_id}', false, 'org_save'); } "><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')|capitalize}</button>*}
	<button type="button" class="submit"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.continue')|capitalize}</button>
{/if}
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title', "Merge Organizations");
		$frm = $('#formOrgMerge');
		
		// Autocomplete
		$frm.find('button.chooser_orgs').each(function() {
			ajax.chooser(this,'cerberusweb.contexts.org','org_id', { autocomplete:true });
			$frm.find(':input:text:first').focus();
		});

		
		$('#formOrgMerge BUTTON.submit').click(function() {
			// Replace the current form
			genericAjaxPost('formOrgMerge','popuppeek','');
		});
	});
</script>