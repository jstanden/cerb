<fieldset class="peek">
	<legend><label><input type="checkbox" name="do_broadcast" id="chkMassReply"> Send Broadcast</label></legend>
	<input type="hidden" name="broadcast_format" value="{if $is_html}parsedown{else}{/if}">

	<blockquote id="bulkBroadcastContainer" style="display:none;margin:0px 10px 10px 10px;">
		{if !$is_reply}
		<b>{'message.header.from'|devblocks_translate|capitalize}:</b>

		<div style="margin:0px 0px 5px 10px;">
			<select name="broadcast_group_id">
				{foreach from=$groups item=group key=group_id}
					<option value="{$group_id}" {if $active_worker->isGroupMember($group_id)}member="true"{/if} {if $ticket->group_id == $group_id}selected="selected"{/if}>{$group->name}</option>
				{/foreach}
			</select>
			<select class="broadcast-bucket-options" style="display:none;">
				{foreach from=$buckets item=bucket key=bucket_id}
					<option value="{$bucket_id}" group_id="{$bucket->group_id}">{$bucket->name}</option>
				{/foreach}
			</select>
			<select name="broadcast_bucket_id">
				{$first_group_id = key($groups)}
				{foreach from=$buckets item=bucket key=bucket_id}
					{if $bucket->group_id == $first_group_id}
						<option value="{$bucket_id}">{$bucket->name}</option>
					{/if}
				{/foreach}
			</select>
		</div>
		{/if}
		
		{if $broadcast_recipient_fields}
		<b>{'message.header.to'|devblocks_translate|capitalize}:</b>
		
		<div style="margin:0px 0px 5px 10px;">
			{foreach from=$broadcast_recipient_fields item=recipient_label key=recipient_field}
			<div>
				<label><input type="checkbox" name="broadcast_to[]" value="{$recipient_field}"> {$recipient_label}</label>
			</div>
			{/foreach}
		</div>
		{/if}

		{if $is_reply}
			<b>{'common.reply'|devblocks_translate|capitalize}:</b>
			
		{else}
			<b>{'message.header.subject'|devblocks_translate|capitalize}:</b>
			
			<div style="margin:0px 0px 5px 10px;">
				<input type="text" name="broadcast_subject" value="" style="width:100%;">
			</div>
			
			<b>{'common.compose'|devblocks_translate|capitalize}:</b>
		{/if}
		
		<div style="margin:0px 0px 5px 10px;">

			{$types = $values._types}
			{function tree level=0}
				{foreach from=$keys item=data key=idx}
					{$type = $types.{$data->key}}
					{if is_array($data->children) && !empty($data->children)}
						{* A branch with a key is also insertable (selectableParents): click inserts its token, hover expands *}
						<li {if $data->key}data-token="{$data->key}{if $type == Model_CustomField::TYPE_DATE}|date{/if}" data-label="{$data->label}"{/if}>
							<span>{$data->l|default:$data->label|capitalize}</span>
							<ul>
								{tree keys=$data->children level=$level+1}
							</ul>
						</li>
					{elseif $data->key}
						<li data-token="{$data->key}{if $type == Model_CustomField::TYPE_DATE}|date{/if}" data-label="{$data->label}"><span>{$data->l|default:$data->label|capitalize}</span></li>
					{/if}
				{/foreach}
			{/function}

			{* Built-in formatting + markdown/plaintext toggle come from the editor; this host section (placeholders /
			   snippet / signature / preview) merges in after the formatting buttons. *}
			<ul class="cerb-ui-toolbar cerb-broadcast-toolbar" hidden>
				<li data-icon="placeholders" title="Insert placeholder">
					<ul>
					{tree keys=$placeholders}
					</ul>
				</li>
				<li></li>
				<li data-value="snippets" data-icon="clipboard" title="Insert snippet"></li>
				<li data-value="signature" data-icon="pen" title="Insert signature"></li>
				<li></li>
				<li data-value="preview" data-icon="eye-open" title="Preview message"></li>
			</ul>

			<textarea class="cerb-broadcast-editor" name="broadcast_message"></textarea>
		</div>
		
		<b>{'common.attachments'|devblocks_translate|capitalize}:</b>
		
		<div class="cerb-broadcast-attachments" style="margin:0px 0px 5px 10px;">
			<div class="cerb-ui-file-upload" data-name="broadcast_file_ids" data-multiple="1"></div>
		</div>
		
		{if !$is_reply}
		<b>{'common.status'|devblocks_translate|capitalize}:</b>
		<div style="margin:0px 0px 5px 10px;"> 
			<label><input type="radio" name="broadcast_status_id" value="{Model_Ticket::STATUS_OPEN}"> {'status.open'|devblocks_translate|capitalize}</label>
			<label><input type="radio" name="broadcast_status_id" value="{Model_Ticket::STATUS_WAITING}" checked="checked"> {'status.waiting'|devblocks_translate|capitalize}</label>
			<label><input type="radio" name="broadcast_status_id" value="{Model_Ticket::STATUS_CLOSED}"> {'status.closed'|devblocks_translate|capitalize}</label>
		</div>
		{/if}
		
		<b>{'common.options'|devblocks_translate|capitalize}:</b>
		
		<div style="margin:0px 0px 5px 10px;"> 
			<label><input type="radio" name="broadcast_is_queued" value="0" checked="checked"> Save as drafts</label>
			<label><input type="radio" name="broadcast_is_queued" value="1"> Send now</label>
		</div>
	</blockquote>
</fieldset>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $checkbox = $('#chkMassReply');

	// Checkbox toggle
	$checkbox.on('click', function(e) {
		$('#bulkBroadcastContainer').toggle();
		e.stopPropagation();
	});
});
</script>