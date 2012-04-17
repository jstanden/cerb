<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmAddyOutgoingPeek">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="mail_from">
<input type="hidden" name="action" value="savePeek">
<input type="hidden" name="id" value="{$address->address_id}">

<b>Send replies as email:</b> {if empty($address->address_id)}(e.g. support@example.com){/if}
<br>
{if !empty($address->address_id)}
{$address->email}
{else}
<input type="text" name="reply_from" value="" style="width:100%;">
<br>
<span style="color:rgb(0,120,0);">(Make sure the above address delivers to the helpdesk or you won't receive replies!)</span>
{/if}
<br>
<br>

<b>Send replies as name:</b> (e.g. "Example, Inc.")
<br>
<input type="text" name="reply_personal" value="{$address->reply_personal}" style="width:100%;">
<br>
<button type="button" onclick="genericAjaxPost('frmAddyOutgoingPeek','divFromTester','c=internal&a=snippetTest&snippet_context=cerberusweb.contexts.worker&snippet_field=reply_personal');">{'common.test'|devblocks_translate|capitalize}</button>
<select name="sig_from_token" onchange="insertAtCursor(this.form.reply_personal,this.options[this.selectedIndex].value);this.selectedIndex=0;this.form.reply_personal.focus();">
	<option value="">-- insert at cursor --</option>
	{foreach from=$worker_token_labels key=k item=v}
	<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$v}</option>
	{/foreach}
</select>
<br>
<div id="divFromTester"></div>

<br>

<b>Default signature template:</b>
<br>
<textarea name="reply_signature" rows="10" cols="76" style="width:100%;">{$address->reply_signature}</textarea>
<br>
<button type="button" onclick="genericAjaxPost('frmAddyOutgoingPeek','divSigTester','c=internal&a=snippetTest&snippet_context=cerberusweb.contexts.worker&snippet_field=reply_signature');">{'common.test'|devblocks_translate|capitalize}</button>
<button type="button" onclick="genericAjaxGet('','c=mail&a=handleSectionAction&section=compose&action=getComposeSignature&raw=1&group_id=0',function(txt) { $('#frmAddyOutgoingPeek textarea').text(txt); } );">{'common.default'|devblocks_translate|capitalize}</button>
<select name="sig_token" onchange="insertAtCursor(this.form.reply_signature,this.options[this.selectedIndex].value);this.selectedIndex=0;this.form.reply_signature.focus();">
	<option value="">-- insert at cursor --</option>
	{foreach from=$worker_token_labels key=k item=v}
	<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$v}</option>
	{/foreach}
</select>
<br>
<div id="divSigTester"></div>

<br>

<label>
	<input type="checkbox" name="is_default" value="1" {if $address->is_default}checked="checked"{/if}> 
	<b>Make default.</b>  
	This will be used as the reply-to address for outgoing mail when no other preference exists.
</label>
<br>
<br>

<fieldset class="delete" style="display:none;">
	<legend>Are you sure you want to delete this reply-to address?</legend>
	<p>Any groups or buckets using this reply-to address will be reverted to defaults.</p>
	<button name="form_action" type="submit" value="delete" class="green"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {'common.yes'|devblocks_translate|capitalize}</button>
	<button name="form_action" type="button" class="red" onclick="$(this).closest('fieldset').nextAll('.toolbar').show();$(this).closest('fieldset').hide();"><span class="cerb-sprite2 sprite-cross-circle-frame"></span> {'common.no'|devblocks_translate|capitalize}</button>
</fieldset>

<div class="toolbar">
<button type="submit" value="submit"><span class="cerb-sprite2 sprite-tick-circle-frame"></span> {$translate->_('common.save_changes')}</button>
{if !$address->is_default}<button type="button" onclick="$(this).closest('.toolbar').hide();$(this).closest('form').find('fieldset.delete').show();"><span class="cerb-sprite2 sprite-cross-circle-frame"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>

</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title', 'Reply-To Address');
	} );
</script>
