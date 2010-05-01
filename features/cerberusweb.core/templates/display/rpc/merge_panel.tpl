<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmDisplayMerge" name="frmDisplayMerge">
<input type="hidden" name="c" value="display">
<input type="hidden" name="a" value="saveMergePanel">
<input type="hidden" name="src_ticket_id" value="{$ticket_id}">

<b>Merge with Ticket ID/Mask:</b><br>
<input type="text" name="dst_ticket_id" size="32" style="width:100%;" value=""><br>
<br>

<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')|capitalize}</button>
<br>
</form>

<script type="text/javascript" language="JavaScript1.2">
	genericPanel.one('dialogopen',function(event,ui) {
		genericPanel.dialog('option','title','{$translate->_('mail.merge')|escape}');
		
		//ajax.emailAutoComplete('#frmDisplayMerge textarea[name=req_adds]', { multiple: true } );
	});
</script>
