<form action="{devblocks_url}{/devblocks_url}" method="POST" id="frmContextLink" name="frmContextLink" onsubmit="bufferContextLink();return false;">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="saveContextLinkAddPeek">
<input type="hidden" name="from_context" value="{$from_context}">
<input type="hidden" name="from_context_id" value="{$from_context_id}">
<input type="hidden" name="to_context" value="{$to_context}">
<input type="hidden" name="return_uri" value="{$return_uri}">

<b>{$translate->_('contact_org.name')|capitalize}:</b><br>
<table cellpadding="0" cellspacing="0" border="0">
	<tr>
		<td width="99%"><input type="text" name="_context_id" style="width:100%;"></td>
		<td width="1%" nowrap="nowrap"><button type="button" onclick="bufferContextLink();"><span class="cerb-sprite sprite-add"></span></button></td>
	</tr>
</table>
<br>

<div id="divContextLinkBuffer">
	{foreach from=$links item=link key=link_id}
		<div>
			<a href="javascript:;" onclick="genericAjaxGet('','c=internal&a=contextDeleteLink&context={$from_context|escape}&context_id={$from_context_id|escape}&dst_context={$to_context|escape}&dst_context_id={$link_id|escape}');$(this).parent().remove();">X</a> {$link->name|escape}
			<input type="hidden" name="to_context_id[]" value="{$link->context_id}">
		</div>
	{/foreach}
</div>
<br>

<button type="button" onclick="this.form.submit();"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>

<br>
</form>

<script language="JavaScript1.2" type="text/javascript">
	function bufferContextLink() {
		var $id = $('#frmContextLink input[name=_context_id]');
		if(0==$id.val().length)
			return;
		var $html = $('<div>' + $id.val() + '</div>');
		$html.prepend(' <a href="javascript:;" onclick="$(this).parent().remove();">X</a> ');
		$html.append('<input type="hidden" name="to_context_id[]" value="' + $id.val() + '">');
		$('#divContextLinkBuffer').append($html);
		$id.val(''); // clear
		$id.focus();
	}
	
	genericPanel.one('dialogopen',function(event,ui) {
		genericPanel.dialog('option','title','Link Organizations');
		$('#frmContextLink :input:text:first').focus().select();
		ajax.orgAutoComplete('#frmContextLink :input:text:first');
	} );
</script>