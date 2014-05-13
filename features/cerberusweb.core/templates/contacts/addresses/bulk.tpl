<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate" onsubmit="return false;">
<input type="hidden" name="c" value="contacts">
<input type="hidden" name="a" value="doAddressBatchUpdate">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="address_ids" value="{if is_array($address_ids)}{$address_ids|implode:','}{/if}">

<fieldset class="peek">
	<legend>{'common.bulk_update.with'|devblocks_translate|capitalize}</legend>
	<label><input type="radio" name="filter" value="" {if empty($address_ids)}checked{/if}> {'common.bulk_update.filter.all'|devblocks_translate}</label> 
	
 	{if !empty($address_ids)}
		<label><input type="radio" name="filter" value="checks" {if !empty($address_ids)}checked{/if}> {'common.bulk_update.filter.checked'|devblocks_translate}</label>
	{else}
		<label><input type="radio" name="filter" value="sample"> {'common.bulk_update.filter.random'|devblocks_translate} </label><input type="text" name="filter_sample_size" size="5" maxlength="4" value="100" class="input_number">
	{/if}

</fieldset>

<fieldset class="peek">
	<legend>Set Fields</legend>
	
	<table cellspacing="0" cellpadding="2" width="100%">
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'contact_org.name'|devblocks_translate|capitalize}:</td>
			<td width="100%">
				<input type="text" name="contact_org" id="orginput" value="" style="width:98%;">
			</td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'address.is_banned'|devblocks_translate|capitalize}:</td>
			<td width="100%"><select name="is_banned">
				<option value=""></option>
				<option value="0">{'common.no'|devblocks_translate|capitalize}</option>
				<option value="1">{'common.yes'|devblocks_translate|capitalize}</option>
			</select></td>
		</tr>
		<tr>
			<td width="0%" nowrap="nowrap" align="right">{'address.is_defunct'|devblocks_translate|capitalize}:</td>
			<td width="100%"><select name="is_defunct">
				<option value=""></option>
				<option value="0">{'common.no'|devblocks_translate|capitalize}</option>
				<option value="1">{'common.yes'|devblocks_translate|capitalize}</option>
			</select></td>
		</tr>
	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>Set Custom Fields</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=true}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_ADDRESS bulk=true}

{include file="devblocks:cerberusweb.core::internal/macros/behavior/bulk.tpl" macros=$macros}

{if $active_worker->hasPriv('core.addybook.addy.view.actions.broadcast')}
<fieldset class="peek">
	<legend>Send Broadcast</legend>
	<label><input type="checkbox" name="do_broadcast" id="chkMassReply" onclick="$('#bulkAddyBroadcast').toggle();"> {'common.enabled'|devblocks_translate|capitalize}</label>
	<input type="hidden" name="broadcast_format" value="">

	<blockquote id="bulkAddyBroadcast" style="display:none;margin:10px;">
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
		
		<b>Subject:</b>
		
		<div style="margin:0px 0px 5px 10px;">
			<input type="text" name="broadcast_subject" value="" style="width:100%;">
		</div>
		
		<b>Compose:</b> {*[<a href="#">syntax</a>]*}
		
		<div style="margin:0px 0px 5px 10px;">
			<textarea name="broadcast_message" style="width:100%;height:200px;"></textarea>
			
			<div>
				<button type="button" onclick="ajax.chooserSnippet('snippets',$('#bulkAddyBroadcast textarea[name=broadcast_message]'), { '{CerberusContexts::CONTEXT_ADDRESS}':'', '{CerberusContexts::CONTEXT_WORKER}':'{$active_worker->id}' });">{'common.snippets'|devblocks_translate|capitalize}</button>
				<select class="insert-placeholders">
					<option value="">-- insert at cursor --</option>
					{foreach from=$token_labels key=k item=v}
					<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$v}</option>
					{/foreach}
				</select>
			</div>
		</div>
		
		<b>{'common.attachments'|devblocks_translate|capitalize}:</b>
		
		<div style="margin:0px 0px 5px 10px;">
			<button type="button" class="chooser_file"><span class="cerb-sprite2 sprite-plus-circle"></span></button>
			<ul class="bubbles chooser-container">
		</div>
		
		<b>{'common.status'|devblocks_translate|capitalize}:</b>
		<div style="margin:0px 0px 5px 10px;"> 
			<label><input type="radio" name="broadcast_next_is_closed" value="0"> {'status.open'|devblocks_translate|capitalize}</label>
			<label><input type="radio" name="broadcast_next_is_closed" value="2" checked="checked"> {'status.waiting'|devblocks_translate|capitalize}</label>
			<label><input type="radio" name="broadcast_next_is_closed" value="1"> {'status.closed'|devblocks_translate|capitalize}</label>
		</div>
		
		<b>{'common.options'|devblocks_translate|capitalize}:</b>
		
		<div style="margin:0px 0px 5px 10px;"> 
			<label><input type="radio" name="broadcast_is_queued" value="0" checked="checked"> Save as drafts</label>
			<label><input type="radio" name="broadcast_is_queued" value="1"> Send now</label>
		</div>
	</blockquote>
</fieldset>
{/if}

{if $active_worker->hasPriv('core.addybook.addy.actions.update')}
	<button type="button" onclick="ajax.saveAddressBatchPanel('{$view_id}');"><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
{/if}
<br>
</form>

<script type="text/javascript">
	var $panel = genericAjaxPopupFind('#formBatchUpdate');
	$panel.one('popup_open',function(event,ui) {
		var $this = $(this);
		$panel.dialog('option','title',"{'common.bulk_update'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		
		ajax.orgAutoComplete('#orginput');
		
		var $content = $this.find('textarea[name=broadcast_message]');
		
		$this.find('select.insert-placeholders').change(function(e) {
			var $select = $(this);
			var $val = $select.val();
			
			if($val.length == 0)
				return;
			
			$content.insertAtCursor($val).focus();
			
			$select.val('');
		});
		
		$this.find('button.chooser_file').each(function() {
			ajax.chooserFile(this,'broadcast_file_ids');
		});
		
		// Text editor
		
		var markitupPlaintextSettings = $.extend(true, { }, markitupPlaintextDefaults);
		var markitupParsedownSettings = $.extend(true, { }, markitupParsedownDefaults);
		
		var markitupBroadcastFunctions = {
			switchToMarkdown: function(markItUp) { 
				$content.markItUpRemove().markItUp(markitupParsedownSettings);
				$content.closest('form').find('input:hidden[name=broadcast_format]').val('parsedown');
				
				// Template chooser
				
				var $ul = $content.closest('.markItUpContainer').find('.markItUpHeader UL');
				var $li = $('<li style="margin-left:10px;"></li>');
				
				var $select = $('<select name="broadcast_html_template_id"></select>');
				$select.append($('<option value="0"> - {'common.default'|devblocks_translate|lower|escape:'javascript'} -</option>'));
				
				{foreach from=$html_templates item=html_template}
				var $option = $('<option value="{$html_template->id}">{$html_template->name|escape:'javascript'}</option>');
				$select.append($option);
				{/foreach}
				
				$li.append($select);
				$ul.append($li);
			},
			
			switchToPlaintext: function(markItUp) {
				$content.markItUpRemove().markItUp(markitupPlaintextSettings);
				$content.closest('form').find('input:hidden[name=broadcast_format]').val('');
			}
		};
		
		markitupPlaintextSettings.markupSet.unshift(
			{ name:'Switch to Markdown', openWith: markitupBroadcastFunctions.switchToMarkdown, className:'parsedown' }
		);
		
		markitupPlaintextSettings.markupSet.push(
			{ separator:'---------------' },
			{ name:'Preview', key: 'P', call:'preview', className:"preview" }
		);
		
		var previewParser = function(content) {
			genericAjaxPost(
				'formBatchUpdate',
				'',
				'c=contacts&a=doAddressBulkUpdateBroadcastTest',
				function(o) {
					content = o;
				},
				{
					async: false
				}
			);
			
			return content;
		};
		
		markitupPlaintextSettings.previewParser = previewParser;
		markitupPlaintextSettings.previewAutoRefresh = false;
		
		markitupParsedownSettings.previewParser = previewParser;
		markitupParsedownSettings.previewAutoRefresh = false;
		delete markitupParsedownSettings.previewInWindow;
		
		markitupParsedownSettings.markupSet.unshift(
			{ name:'Switch to Plaintext', openWith: markitupBroadcastFunctions.switchToPlaintext, className:'plaintext' },
			{ separator:'---------------' }
		);
		
		markitupParsedownSettings.markupSet.splice(
			6,
			0,
			{ name:'Upload an Image', openWith: 
				function(markItUp) {
					$chooser=genericAjaxPopup('chooser','c=internal&a=chooserOpenFile&single=1',null,true,'750');
					
					$chooser.one('chooser_save', function(event) {
						if(!event.response || 0 == event.response)
							return;
						
						$content.insertAtCursor("![inline-image](" + event.response[0].url + ")");
					});
				},
				key: 'U',
				className:'image-inline'
			}
		);
		
		try {
			$content.markItUp(markitupPlaintextSettings);
			
		} catch(e) {
			if(window.console)
				console.log(e);
		}
		
	});
</script>
