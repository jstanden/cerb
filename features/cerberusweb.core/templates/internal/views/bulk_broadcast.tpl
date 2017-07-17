<fieldset class="peek">
	<legend>Send Broadcast</legend>
	<label><input type="checkbox" name="do_broadcast" id="chkMassReply" onclick="$('#bulkBroadcastContainer').toggle();"> {'common.enabled'|devblocks_translate|capitalize}</label>
	<input type="hidden" name="broadcast_format" value="">

	<blockquote id="bulkBroadcastContainer" style="display:none;margin:10px;">
		<b>From:</b>
		
		<div style="margin:0px 0px 5px 10px;">
			<select name="broadcast_group_id">
				{foreach from=$groups item=group key=group_id}
				{if $active_worker_memberships.$group_id}
				<option value="{$group->id}">{$group->name}</option>
				{/if}
				{/foreach}
			</select>
		</div>
		
		{if $is_reply}
			<b>Reply:</b>
			
		{else}
			<b>Subject:</b>
			
			<div style="margin:0px 0px 5px 10px;">
				<input type="text" name="broadcast_subject" value="" style="width:100%;">
			</div>
			
			<b>Compose:</b>
		{/if}
		
		<div style="margin:0px 0px 5px 10px;">
			<textarea name="broadcast_message" style="width:100%;height:200px;"></textarea>
			
			<div>
				<button type="button" class="cerb-popupmenu-trigger" onclick="">Insert placeholder &#x25be;</button>
				
				<div class="cerb-snippet-insert" style="display:inline-block;">
					<button type="button" class="cerb-chooser-trigger" data-field-name="snippet_id" data-context="{CerberusContexts::CONTEXT_SNIPPET}" data-query="" data-query-required="type:[plaintext,worker,{$context}]" data-single="true">{'common.snippets'|devblocks_translate|capitalize}</button>
					<ul class="bubbles chooser-container"></ul>
				</div>
				
				{$types = $values._types}
				{function tree level=0}
					{foreach from=$keys item=data key=idx}
						{$type = $types.{$data->key}}
						{if is_array($data->children) && !empty($data->children)}
							<li {if $data->key}data-token="{$data->key}{if $type == Model_CustomField::TYPE_DATE}|date{/if}" data-label="{$data->label}"{/if}>
								{if $data->key}
									<div style="font-weight:bold;">{$data->l|capitalize}</div>
								{else}
									<div>{$idx|capitalize}</div>
								{/if}
								<ul>
									{tree keys=$data->children level=$level+1}
								</ul>
							</li>
						{elseif $data->key}
							<li data-token="{$data->key}{if $type == Model_CustomField::TYPE_DATE}|date{/if}" data-label="{$data->label}"><div style="font-weight:bold;">{$data->l|capitalize}</div></li>
						{/if}
					{/foreach}
				{/function}
				
				<ul class="menu" style="width:150px;">
				{tree keys=$placeholders}
				</ul>
			</div>
		</div>
		
		<b>{'common.attachments'|devblocks_translate|capitalize}:</b>
		
		<div style="margin:0px 0px 5px 10px;">
			<button type="button" class="chooser_file"><span class="glyphicons glyphicons-paperclip"></span></button>
			<ul class="bubbles chooser-container">
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