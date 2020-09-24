{$peek_context = CerberusContexts::CONTEXT_TICKET}
<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" name="formBatchUpdate" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="ticket">
<input type="hidden" name="action" value="startBulkUpdateJson">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="ids" value="{$ids}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>{'common.bulk_update.with'|devblocks_translate|capitalize}</legend>
	<label><input type="radio" name="filter" value="" {if empty($ids)}checked{/if}> {'common.bulk_update.filter.all'|devblocks_translate}</label> 
	{if !empty($ids)}
		<label><input type="radio" name="filter" value="checks" {if !empty($ids)}checked{/if}> {'common.bulk_update.filter.checked'|devblocks_translate}</label> 
	{/if}
	{if empty($ids)}
		<label><input type="radio" name="filter" value="sample"> {'common.bulk_update.filter.random'|devblocks_translate} </label><input type="text" name="filter_sample_size" size="5" maxlength="4" value="100" class="input_number">
	{/if}
</fieldset>

<fieldset class="peek">
	<legend>Set Fields</legend>
	<table cellspacing="0" cellpadding="2" width="100%">
		<tr>
			<td width="0%" nowrap="nowrap" align="left" valign="middle">
				<label>
					<input type="checkbox" name="actions[]" value="move">
					Move to:
				</label>
			</td>
			<td width="100%">
				<div style="display:none;">
					<select name="params[move][group_id]" class="cerb-moveto-group">
						<option></option>
						{foreach from=$groups item=group}
						<option value="{$group->id}">{$group->name}</option>
						{/foreach}
					</select>
					<select class="cerb-moveto-bucket-options" style="display:none;">
						{foreach from=$buckets item=bucket}
						<option value="{$bucket->id}" data-group-id="{$bucket->group_id}">{$bucket->name}</option>
						{/foreach}
					</select>
					<select name="params[move][bucket_id]" class="cerb-moveto-bucket" style="display:none;"></select>
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="0%" nowrap="nowrap" align="left" valign="top">
				<label>
					<input type="checkbox" name="actions[]" value="status">
					{'common.status'|devblocks_translate|capitalize}:
				</label>
			</td>
			<td width="100%" valign="top">
				<div style="display:none;">
					<select name="params[status][status_id]" onchange="$val=$(this).val();$waiting=$('#bulk{$view_id}_waiting');if($val=={Model_Ticket::STATUS_WAITING} || $val=={Model_Ticket::STATUS_CLOSED}){ $waiting.show(); } else { $waiting.hide(); }">
						<option value="{Model_Ticket::STATUS_OPEN}">{'status.open'|devblocks_translate|capitalize}</option>
						<option value="{Model_Ticket::STATUS_WAITING}">{'status.waiting.abbr'|devblocks_translate|capitalize}</option>
						{if $active_worker->hasPriv('core.ticket.actions.close')}
						<option value="{Model_Ticket::STATUS_CLOSED}">{'status.closed'|devblocks_translate|capitalize}</option>
						{/if}
						{if $active_worker->hasPriv("contexts.{$peek_context}.delete")}
						<option value="{Model_Ticket::STATUS_DELETED}">{'status.deleted'|devblocks_translate|capitalize}</option>
						{/if}
					</select>
					
					<div id="bulk{$view_id}_waiting" style="display:none;">
						<b>{'display.reply.next.resume'|devblocks_translate}</b>
						<br>
						<i>{'display.reply.next.resume_eg'|devblocks_translate}</i>
						<br> 
						<input type="text" name="params[status][reopen_at]" size="55" value="">
						<br>
						{'display.reply.next.resume_blank'|devblocks_translate}
						<br>
					</div>
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="0%" nowrap="nowrap" align="left" valign="middle">
				<label>
					<input type="checkbox" name="actions[]" value="importance">
					{'common.importance'|devblocks_translate|capitalize}:
				</label>
			</td>
			<td width="100%">
				<div style="display:none;">
					<div class="cerb-delta-slider-container">
					<input type="hidden" name="params[importance]" value="50">
						<div class="cerb-delta-slider cerb-slider-gray">
							<span class="cerb-delta-slider-midpoint"></span>
						</div>
					</div>
				</div>
			</td>
		</tr>
		
		{if $active_worker->hasPriv('core.ticket.actions.spam')}
		<tr>
			<td width="0%" nowrap="nowrap" align="left" valign="top">
				<label>
					<input type="checkbox" name="actions[]" value="spam">
					{'common.spam'|devblocks_translate|capitalize}:
				</label>
			</td>
			<td width="100%">
				<div style="display:none;">
					<select name="params[spam]">
						<option value="0">{'common.notspam'|devblocks_translate|capitalize}</option>
						<option value="1">{'common.spam'|devblocks_translate|capitalize}</option>
					</select>
				</div>
			</td>
		</tr>
		{/if}
		
		{if 1}
		<tr>
			<td width="0%" nowrap="nowrap" align="left" valign="top">
				<label>
					<input type="checkbox" name="actions[]" value="owner">
					{'common.owner'|devblocks_translate|capitalize}:
				</label>
			</td>
			<td width="100%">
				<div style="display:none;">
					<button type="button" class="chooser-abstract" data-field-name="params[owner]" data-context="{CerberusContexts::CONTEXT_WORKER}" data-single="true" data-query="" data-autocomplete="" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>
					<ul class="bubbles chooser-container"></ul>
				</div>
			</td>
		</tr>
		{/if}
		
		{if 1}
		<tr>
			<td width="0%" nowrap="nowrap" align="left" valign="top">
				<label>
					<input type="checkbox" name="actions[]" value="org">
					{'common.organization'|devblocks_translate|capitalize}:
				</label>
			</td>
			<td width="100%">
				<div style="display:none;">
					<button type="button" class="chooser-abstract" data-field-name="params[org]" data-context="{CerberusContexts::CONTEXT_ORG}" data-single="true" data-query="" data-autocomplete="" data-autocomplete-if-empty="true" data-create="if-null"><span class="glyphicons glyphicons-search"></span></button>
					<ul class="bubbles chooser-container"></ul>
				</div>
			</td>
		</tr>
		{/if}
		
		<tr>
			<td width="0%" nowrap="nowrap" align="left" valign="top">
				<label>
					<input type="checkbox" name="actions[]" value="watchers_add">
					Add watchers:
				</label>
			</td>
			<td width="100%">
				<div style="display:none;">
					<button type="button" class="chooser-abstract" data-field-name="params[watchers_add][]" data-context="{CerberusContexts::CONTEXT_WORKER}" data-query="isDisabled:n" data-autocomplete=""><span class="glyphicons glyphicons-search"></span></button>
					<ul class="bubbles chooser-container" style="display:block;"></ul>
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="0%" nowrap="nowrap" align="left" valign="top">
				<label>
					<input type="checkbox" name="actions[]" value="watchers_remove">
					Remove watchers:
				</label>
			</td>
			<td width="100%">
				<div style="display:none;">
					<button type="button" class="chooser-abstract" data-field-name="params[watchers_remove][]" data-context="{CerberusContexts::CONTEXT_WORKER}" data-query="isDisabled:n" data-autocomplete=""><span class="glyphicons glyphicons-search"></span></button>
					<ul class="bubbles chooser-container" style="display:block;"></ul>
				</div>
			</td>
		</tr>
	</table>
</fieldset>

{if !empty($custom_fields)}
<fieldset class="peek">
	<legend>Set Custom Fields</legend>
	{include file="devblocks:cerberusweb.core::internal/custom_fields/bulk/form.tpl" bulk=true}
</fieldset>
{/if}

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_TICKET bulk=true}

{include file="devblocks:cerberusweb.core::internal/macros/behavior/bulk.tpl" macros=$macros}

{if $active_worker->hasPriv('contexts.cerberusweb.contexts.ticket.broadcast')}
{include file="devblocks:cerberusweb.core::internal/views/bulk_broadcast.tpl" context=CerberusContexts::CONTEXT_TICKET is_reply=true}
{/if}

<fieldset class="peek">
	<legend>{'common.options'|devblocks_translate|capitalize}</legend>
	<label>
		<input type="checkbox" name="options[skip_updated]" value="1">
		Don't modify the updated timestamp
	</label>
</fieldset>

<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
<br>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#formBatchUpdate');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.bulk_update'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		$popup.css('overflow', 'inherit');
		
		$popup.find('button.chooser-abstract').cerbChooserTrigger();
		
		$popup.find('button.submit').click(function() {
			genericAjaxPost('formBatchUpdate', '', null, function(json) {
				if(json.cursor) {
					// Pull the cursor
					var $tips = $('#{$view_id}_tips').html('');
					Devblocks.getSpinner().appendTo($tips);

					var formData = new FormData();
					formData.set('c', 'internal');
					formData.set('a', 'invoke');
					formData.set('module', 'worklists');
					formData.set('action', 'viewBulkUpdateWithCursor');
					formData.set('view_id', '{$view_id}');
					formData.set('cursor', json.cursor);

					genericAjaxPost(formData, $tips, null);
				}
				
				genericAjaxPopupClose($popup);
			});
		});
		
		// Date helper
		$popup.find('input[name="params[status][reopen_at]"]')
			.cerbDateInputHelper()
			;
		
		// Checkboxes
		
		$popup.find('input:checkbox[name="actions[]"]').change(function() {
			$(this).closest('td').next('td').find('> div').toggle();
		});
		
		// Slider
		
		$popup.find('div.cerb-delta-slider').each(function() {
			var $this = $(this);
			var $input = $this.siblings('input:hidden');
			
			$this.slider({
				disabled: false,
				value: 50,
				min: 0,
				max: 100,
				step: 1,
				range: 'min',
				slide: function(event, ui) {
					$this.removeClass('cerb-slider-gray cerb-slider-red cerb-slider-green');
					
					if(ui.value < 50) {
						$this.addClass('cerb-slider-green');
						$this.slider('option', 'range', 'min');
					} else if(ui.value > 50) {
						$this.addClass('cerb-slider-red');
						$this.slider('option', 'range', 'max');
					} else {
						$this.addClass('cerb-slider-gray');
						$this.slider('option', 'range', false);
					}
				},
				stop: function(event, ui) {
					$input.val(ui.value);
				}
			});
		});
		
		// Move to
		
		var $select_moveto_group = $popup.find('select.cerb-moveto-group');
		var $select_moveto_bucket = $popup.find('select.cerb-moveto-bucket');
		var $select_moveto_bucket_options = $popup.find('select.cerb-moveto-bucket-options');
		
		$select_moveto_group.change(function() {
			var group_id = $(this).val();
			
			$select_moveto_bucket.find('> option').remove();
			
			if(0 == group_id.length) {
				$select_moveto_bucket.val('').hide();
				return;
			}
			
			$select_moveto_bucket_options.find('option').each(function(n) {
				var $opt = $(this);
				if($opt.attr('data-group-id') == group_id)
					$opt.clone().appendTo($select_moveto_bucket);
			});
			
			var bucket_id = $select_moveto_bucket.find('> option:first').val();
			
			$select_moveto_bucket.val(bucket_id).fadeIn();
		});
		
		{include file="devblocks:cerberusweb.core::internal/views/bulk_broadcast_jquery.tpl"}
	});
});
</script>