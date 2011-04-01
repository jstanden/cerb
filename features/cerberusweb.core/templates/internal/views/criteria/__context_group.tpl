<input type="hidden" name="oper" value="in">

<b>{$translate->_('common.groups')|capitalize}:</b><br>

<div style="margin:0px 0px 10px 10px;">
<button type="button" class="chooser_group"><span class="cerb-sprite2 sprite-plus-circle-frame"></span></button>
</div>

<script type="text/javascript">
	$('DIV#addCriteria{$id} BUTTON.chooser_group').each(function() {
		ajax.chooser(this,'cerberusweb.contexts.group','group_id', { autocomplete:true });
	})
	.first().siblings('input:text').focus();
</script>
