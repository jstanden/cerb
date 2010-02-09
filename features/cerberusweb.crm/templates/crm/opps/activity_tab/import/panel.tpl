<form action="{devblocks_url}{/devblocks_url}" method="POST" enctype="multipart/form-data" id="frmOppImport">
<input type="hidden" name="c" value="crm">
<input type="hidden" name="a" value="parseUpload">

<b>{'crm.opp.import.upload_csv'|devblocks_translate}:</b> {'crm.opp.import.upload_csv.tip'|devblocks_translate}<br>
<input type="file" name="csv_file" size="45"><br>
<br>

{if $active_worker->hasPriv('crm.opp.actions.import')}<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.upload')|capitalize}</button>{/if}
<button type="button" onclick="document.location.href='{devblocks_url}c=activity&a=opps{/devblocks_url}';"><span class="cerb-sprite sprite-delete"></span> {$translate->_('common.cancel')|capitalize}</button>
</form>

<script type="text/javascript" language="JavaScript1.2">
	genericPanel.one('dialogopen', function(event,ui) {
		genericPanel.dialog('option','title',"{'common.import'|devblocks_translate|capitalize|escape:'quotes'}");
	} );
</script>
