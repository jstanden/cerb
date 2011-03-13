<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmAddyOutgoingPeek">
<input type="hidden" name="c" value="groups">
<input type="hidden" name="a" value="saveBucketPeek">
<input type="hidden" name="group_id" value="{$group_id}">
<input type="hidden" name="bucket_id" value="{$bucket_id}">

<b>{'common.name'|devblocks_translate|capitalize}:</b><br>
{if '0' == $bucket_id}
{'common.inbox'|devblocks_translate|capitalize}
{else}
<input type="text" name="name" value="{$bucket->name}" style="width:100%;">
{/if}
<br>
<br>

{if !empty($group)}
{$group_replyto = $group->getReplyTo()}
{/if}
{if !empty($bucket)}
{$bucket_replyto = $bucket->getReplyTo()}
{/if}

{* Inbox *}
{if '0' == $bucket_id}
	{$replyto_default = $group_replyto}
	{$replyto = $group_replyto}
	{$object = $group}
{* Bucket *}
{else}
	{$replyto_default = $group_replyto}
	{$replyto = $bucket_replyto}
	{$object = $bucket} 
{/if}

<fieldset>
	<legend>Outgoing Mail</legend>
	
	<b>Send replies as e-mail:</b><br>
	<select name="reply_address_id">
		<option value="">- group default: - {$replyto_default->email} {if !empty($replyto_default->reply_personal)}({$replyto_default->reply_personal}){/if}</option>
		{foreach from=$replyto_addresses item=addy}
		<option value="{$addy->address_id}" {if $object->reply_address_id == $addy->address_id}selected="selected"{/if}>{$addy->email} {if !empty($addy->reply_personal)}({$addy->reply_personal}){/if}</option>
		{/foreach}
	</select>
	<br>
	<br>
	
	<b>Send replies as name:</b> (leave blank for default)<br>
	<input type="text" name="reply_personal" value="{$object->reply_personal}" size="65" style="width:100%;"><br>
	<br>
	
	<b>Bucket email signature:</b> (leave blank for default)<br>
	<textarea name="reply_signature" rows="10" cols="76" style="width:100%;" wrap="off">{$object->reply_signature}</textarea><br>
	<button type="button" onclick="genericAjaxPost('frmAddyOutgoingPeek','divSnippetBucketSigTester','c=internal&a=snippetTest&snippet_context=cerberusweb.contexts.worker&snippet_field=reply_signature');"><span class="cerb-sprite sprite-gear"></span> Test</button>
	<select name="sig_token" onchange="insertAtCursor(this.form.reply_signature,this.options[this.selectedIndex].value);this.selectedIndex=0;this.form.reply_signature.focus();">
		<option value="">-- insert at cursor --</option>
		{foreach from=$worker_token_labels key=k item=v}
		<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$v}</option>
		{/foreach}
	</select>
	
	<br>
	
	<div id="divSnippetBucketSigTester"></div>
</fieldset>

{if '0' != $bucket_id}
<fieldset>
	<legend>Workflow</legend>

	<label><input type="checkbox" name="is_hidden" value="1" {if !empty($bucket) && empty($bucket->is_assignable)}checked="checked"{/if}> This bucket is <b>hidden</b> in Mail Workflow.</label>
</fieldset>
{/if}

<br>

<button type="submit"><span class="cerb-sprite sprite-check"></span> {$translate->_('common.save_changes')}</button>
</form>
<br>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title', '{'common.bucket'|devblocks_translate|capitalize}');
	} );
</script>
