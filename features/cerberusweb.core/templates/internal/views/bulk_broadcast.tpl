<fieldset class="peek">
	<legend><label><input type="checkbox" name="do_broadcast" id="chkMassReply" onclick="$('#bulkBroadcastContainer').toggle();"> Send Broadcast</label></legend>
	<input type="hidden" name="broadcast_format" value="">

	<blockquote id="bulkBroadcastContainer" style="display:none;margin:0px 10px 10px 10px;">
		{if !$is_reply}
		<b>{'message.header.from'|devblocks_translate|capitalize}:</b>

		<div style="margin:0px 0px 5px 10px;">
			<button type="button" class="chooser-broadcast-group" data-field-name="broadcast_group_id" data-context="{CerberusContexts::CONTEXT_GROUP}" data-single="true" data-query="" data-query-required="member:(id:{$active_worker->id})" data-autocomplete="member:(id:{$active_worker->id})" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
			
			<ul class="bubbles chooser-container">
			</ul>
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

			<div class="cerb-code-editor-toolbar cerb-code-editor-toolbar--broadcast">
				<button type="button" title="Insert placeholder" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--placeholders"><span class="glyphicons glyphicons-sampler"></span></button>
				<div class="cerb-code-editor-toolbar-divider"></div>
				<button type="button" title="Toggle formatting" class="cerb-code-editor-toolbar-button cerb-reply-editor-toolbar-button--formatting" data-format="{if $is_html}html{else}plaintext{/if}">{if $is_html}Formatting on{else}Formatting off{/if}</button>
				<div class="cerb-code-editor-toolbar-divider"></div>

				<div class="cerb-code-editor-subtoolbar-format-html" style="display:inline-block;{if !$is_html}display:none;{/if}">
					<button type="button" title="Bold" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--bold"><span class="glyphicons glyphicons-bold"></span></button>
					<button type="button" title="Italics" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--italic"><span class="glyphicons glyphicons-italic"></span></button>
					<button type="button" title="Link" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--link"><span class="glyphicons glyphicons-link"></span></button>
					<button type="button" title="Image" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--image"><span class="glyphicons glyphicons-picture"></span></button>
					<button type="button" title="List" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--list"><span class="glyphicons glyphicons-list"></span></button>
					<button type="button" title="Quote" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--quote"><span class="glyphicons glyphicons-quote"></span></button>
					<button type="button" title="Code" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--code"><span class="glyphicons glyphicons-embed"></span></button>
					<button type="button" title="Table" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--table"><span class="glyphicons glyphicons-table"></span></button>
					<div class="cerb-code-editor-toolbar-divider"></div>
				</div>

				<button type="button" title="Insert snippet" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--snippets"><span class="glyphicons glyphicons-notes-2"></span></button>
				<button type="button" title="Insert signature" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--signature"><span class="glyphicons glyphicons-pen"></span></button>
				<div class="cerb-code-editor-toolbar-divider"></div>

				<button type="button" title="Preview message" class="cerb-code-editor-toolbar-button cerb-markdown-editor-toolbar-button--preview"><span class="glyphicons glyphicons-eye-open"></span></button>
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

			<ul class="menu cerb-float" style="width:250px;">
				{tree keys=$placeholders}
			</ul>

			<textarea name="broadcast_message"></textarea>
		</div>
		
		<b>{'common.attachments'|devblocks_translate|capitalize}:</b>
		
		<div class="cerb-broadcast-attachments" style="margin:0px 0px 5px 10px;">
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