<form action="{devblocks_url}{/devblocks_url}" method="POST" id="formBatchUpdate" onsubmit="return false;">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="task">
<input type="hidden" name="action" value="startBulkUpdateJson">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="ids" value="{$ids}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>{'common.bulk_update.with'|devblocks_translate|capitalize}</legend>
	<label><input type="radio" name="filter" value="" {if empty($ids)}checked{/if}> {'common.bulk_update.filter.all'|devblocks_translate}</label> 
	{if !empty($ids)}
		<label><input type="radio" name="filter" value="checks" {if !empty($ids)}checked{/if}> {'common.bulk_update.filter.checked'|devblocks_translate}</label> 
	{else}
		<label><input type="radio" name="filter" value="sample"> {'common.bulk_update.filter.random'|devblocks_translate} </label><input type="text" name="filter_sample_size" size="5" maxlength="4" value="100" class="input_number">
	{/if}
</fieldset>

<fieldset class="peek">
	<legend>Set Fields</legend>
	<table cellspacing="0" cellpadding="2" width="100%">
	
		<tr>
			<td width="0%" nowrap="nowrap" align="left" valign="middle">
				<label>
					<input type="checkbox" name="actions[]" value="due">
					{'task.due_date'|devblocks_translate|capitalize}:
				</label>
			</td>
			<td width="100%">
				<div style="display:none;">
					<input type="text" name="params[due]" size="35" value=""><button type="button" onclick="devblocksAjaxDateChooser(this.form.due,'#dateBulkTaskDue');"><span class="glyphicons glyphicons-calendar"></span></button>
					<div id="dateBulkTaskDue"></div>
				</div>
			</td>
		</tr>
		
		<tr>
			<td width="0%" nowrap="nowrap" align="left" valign="middle">
				<label>
					<input type="checkbox" name="actions[]" value="status">
					{'common.status'|devblocks_translate|capitalize}:
				</label>
			</td>
			<td width="100%">
				<div style="display:none;">
					<select name="params[status]">
						<option value="0">{'status.open'|devblocks_translate}</option>
						<option value="1">{'status.completed'|devblocks_translate}</option>
						{if $active_worker->hasPriv('contexts.cerberusweb.contexts.task.delete')}
						<option value="2">{'status.deleted'|devblocks_translate}</option>
						{/if}
					</select>
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
		
		{if 1}
		<tr>
			<td width="0%" nowrap="nowrap" align="left" valign="middle">
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

{include file="devblocks:cerberusweb.core::internal/custom_fieldsets/peek_custom_fieldsets.tpl" context=CerberusContexts::CONTEXT_TASK bulk=true}

{include file="devblocks:cerberusweb.core::internal/macros/behavior/bulk.tpl" macros=$macros}

<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
<br>
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind('#formBatchUpdate');
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"{'common.bulk_update'|devblocks_translate|capitalize|escape:'javascript' nofilter}");
		
		$popup.find('button.chooser-abstract').cerbChooserTrigger();
		
		$popup.find('button.submit').click(function() {
			genericAjaxPost('formBatchUpdate', '', null, function(json) {
				if(json.cursor) {
					// Pull the cursor
					var $tips = $('#{$view_id}_tips').html('');
					$('<span class="cerb-ajax-spinner"/>').appendTo($tips);

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
		
	});
});
</script>