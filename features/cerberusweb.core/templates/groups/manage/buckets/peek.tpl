<form action="{devblocks_url}{/devblocks_url}" method="post" id="frmAddyOutgoingPeek">
<input type="hidden" name="c" value="groups">
<input type="hidden" name="a" value="saveBucketPeek">
<input type="hidden" name="group_id" value="{$group_id}">
<input type="hidden" name="bucket_id" value="{$bucket_id}">

<b>{'common.name'|devblocks_translate|capitalize}:</b><br>
{if '0' == $bucket_id}
{'common.inbox'|devblocks_translate|capitalize}
{else}
<input type="text" name="name" value="{$bucket->name}" maxlength="64" style="width:100%;">
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
		<option value="">default: {$replyto_default->email} {if !empty($replyto_default->reply_personal)}({$replyto_default->getReplyPersonal($active_worker)}){/if}</option>
		{foreach from=$replyto_addresses item=addy}
		<optgroup label="{$personal = $addy->getReplyPersonal($active_worker)}{if !empty($personal)}{$personal}{else}(blank){/if}">
			<option value="{$addy->address_id}" {if $object->reply_address_id == $addy->address_id}selected="selected"{/if}>{$addy->email}</option>
		</optgroup>
		{/foreach}
	</select>
	<br>
	<br>
	
	<b>Send replies as name:</b> (leave blank for default)<br>
	<input type="text" name="reply_personal" value="{$object->reply_personal}" size="65" style="width:100%;"><br>
	<button type="button" onclick="genericAjaxPost('frmAddyOutgoingPeek','divSnippetBucketFromTester','c=internal&a=snippetTest&snippet_context=cerberusweb.contexts.worker&snippet_field=reply_personal');">{'common.test'|devblocks_translate|capitalize}</button>
	<select name="personal_token">
		<option value="">-- insert at cursor --</option>
		{foreach from=$worker_token_labels key=k item=v}
		<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$v}</option>
		{/foreach}
	</select>
	<br>
	<div id="divSnippetBucketFromTester"></div>
	
	<br>
	
	<b>Bucket email signature:</b> (leave blank for default)<br>
	<textarea name="reply_signature" rows="10" cols="76" style="width:100%;" wrap="off">{$object->reply_signature}</textarea><br>
	<button type="button" onclick="genericAjaxPost('frmAddyOutgoingPeek','divSnippetBucketSigTester','c=internal&a=snippetTest&snippet_context=cerberusweb.contexts.worker&snippet_field=reply_signature');">{'common.test'|devblocks_translate|capitalize}</button>
	<button type="button" onclick="genericAjaxGet('','c=tickets&a=getComposeSignature&raw=1&group_id={$group_id}&bucket_id={$bucket_id}',function(txt) { $('#frmAddyOutgoingPeek textarea').text(txt); } );">{'common.default'|devblocks_translate|capitalize}</button>
	<select name="sig_token">
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

{if !empty($bucket_id)}
<fieldset style="display:none;" class="delete">
	<legend>{'common.delete'|devblocks_translate|capitalize}</legend>
	
	Where should the tickets in this bucket be moved to?<br>
	<select name="delete_moveto">
		<option value="0">{'common.inbox'|devblocks_translate|capitalize}</option>
		{foreach from=$buckets item=move_bucket key=move_bucket_id}
		{if $move_bucket_id == $bucket_id}
		{else}
		<option value="{$move_bucket_id}">{$move_bucket->name}</option>
		{/if}
		{/foreach}
	</select>
	<br>
	<button type="submit" name="form_submit" value="delete"><span class="cerb-sprite2 sprite-tick-circle"></span> Confirm</button>
	<button type="button" onclick="$(this).closest('form').find('div.buttons').fadeIn();$(this).closest('fieldset.delete').fadeOut();"><span class="cerb-sprite2 sprite-minus-circle"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
</fieldset>
{/if}

<div class="buttons">
	<button type="submit" name="form_submit" value="save"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	{if !empty($bucket_id)}<button type="button" onclick="$(this).parent().siblings('fieldset.delete').fadeIn();$(this).closest('div').fadeOut();"><span class="cerb-sprite2 sprite-cross-circle"></span> {'common.delete'|devblocks_translate|capitalize}</button>{/if}
</div>
</form>

<script type="text/javascript">
	$popup = genericAjaxPopupFetch('peek');
	$popup.one('popup_open',function(event,ui) {
		var $this = $(this);
		
		$this.dialog('option','title', '{'common.bucket'|devblocks_translate|capitalize}');
		
		$this.find('select[name=personal_token]').change(function(e) {
			var $select = $(this);
			var $val = $select.val();
			
			if($val.length == 0)
				return;
			
			var $textarea = $select.siblings('input[name=reply_personal]');
			
			$textarea.insertAtCursor($val).focus();
			
			$select.val('');
		});
		
		$this.find('select[name=sig_token]').change(function(e) {
			var $select = $(this);
			var $val = $select.val();
			
			if($val.length == 0)
				return;
			
			var $textarea = $select.siblings('textarea[name=reply_signature]');
			
			$textarea.insertAtCursor($val).focus();
			
			$select.val('');
		});
	});
</script>
