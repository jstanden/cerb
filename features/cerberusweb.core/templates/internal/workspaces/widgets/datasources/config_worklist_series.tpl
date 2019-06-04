{$series_ctx_id = $params.worklist_model.context}

{$series_ctx = null}
{$series_ctx_view = null}
{$series_ctx_fields = []}

{if !empty($series_ctx_id)}
	{$series_ctx = Extension_DevblocksContext::get($series_ctx_id)}
	{$series_ctx_view = $series_ctx->getChooserView()} 
	{$series_ctx_fields = $series_ctx_view->getParamsAvailable()}
{/if}

{$div_popup_worklist = uniqid()}

<b>Load </b>

<select name="params{$params_prefix}[context]" class="context">
	<option value=""> - {'common.choose'|devblocks_translate|lower} - </option>
	{foreach from=$context_mfts item=context_mft key=context_id}
	<option value="{$context_id}" {if $series_ctx_id==$context_id}selected="selected"{/if}>{$context_mft->name}</option>
	{/foreach}
</select>

 data using 

<div id="popup{$div_popup_worklist}" class="badge badge-lightgray" style="font-weight:bold;color:rgb(80,80,80);cursor:pointer;display:inline;"><span class="name">Worklist</span> &#x25be;</div>

<input type="hidden" name="params{$params_prefix}[worklist_model_json]" value="{$params.worklist_model|json_encode}" class="model">

<div style="margin-left:10px;">
	<label><input type="checkbox" name="params{$params_prefix}[search_mode]" value="quick_search" class="mode" {if $params.search_mode == "quick_search"}checked="checked"{/if}> Filter using quick search:</label>
	
	<div>
		<input type="text" name="params{$params_prefix}[quick_search]" value="{$params.quick_search}" class="quicksearch" style="width:95%;padding:5px;border-radius:5px;" autocomplete="off" spellcheck="false">
	</div>
</div>

<abbr title="horizontal axis" style="font-weight:bold;">X-axis</abbr> is 

{$xaxis_field = $params.xaxis_field}
{$xaxis_tick = $params.xaxis_tick}
{$xaxis_field_type = $series_ctx_fields.{$xaxis_field}->type}

<select name="params{$params_prefix}[xaxis_field]" class="xaxis_field">
	<option value="_id" {if $xaxis_field=='_id'}selected="selected"{/if}>(each record)</option>
	{if !empty($series_ctx_fields)}
	{foreach from=$series_ctx_fields item=field}
		{if !empty($field->db_label)}
			{if $field->type == Model_CustomField::TYPE_DATE || $field->type == Model_CustomField::TYPE_NUMBER}
			<option value="{$field->token}" class="{if $field->type == Model_CustomField::TYPE_DATE}date{elseif $field->type == Model_CustomField::TYPE_NUMBER}number{else}{/if}" {if $xaxis_field==$field->token}selected="selected"{/if}>{$field->db_label|lower}</option>
			{/if}
		{/if}
	{/foreach}
	{/if}
</select>

{$xaxis_ticks = [hour,day,week,month,year]}

<select name="params{$params_prefix}[xaxis_tick]" class="xaxis_tick" style="display:{if $xaxis_field_type=='E'}inline{else}none{/if};">
	{foreach from=$xaxis_ticks item=v}
	<option value="{$v}" {if $xaxis_tick==$v}selected="selected"{/if}>by {$v}</option>
	{/foreach}
</select>

 as 
 
<select name="params{$params_prefix}[xaxis_format]">
	<option value="" {if empty($params.xaxis_format)}selected="selected"{/if}></option>
	<option value="number" {if 'number'==$params.xaxis_format}selected="selected"{/if}>number</option>
	<option value="decimal" {if 'decimal'==$params.xaxis_format}selected="selected"{/if}>decimal</option>
	<option value="percent" {if 'percent'==$params.xaxis_format}selected="selected"{/if}>percentage</option>
	<option value="bytes" {if 'bytes'==$params.xaxis_format}selected="selected"{/if}>bytes</option>
	<option value="seconds" {if 'seconds'==$params.xaxis_format}selected="selected"{/if}>secs elapsed</option>
	<option value="minutes" {if 'minutes'==$params.xaxis_format}selected="selected"{/if}>mins elapsed</option>
</select>

<br>

<abbr title="vertical axis" style="font-weight:bold;">Y-axis</abbr> is 
 
{$yaxis_func = $params.yaxis_func}
{$yaxis_field = $params.yaxis_field}

<select name="params{$params_prefix}[yaxis_func]" class="yaxis_func">
	<option value="count" {if 'count'==$params.yaxis_func}selected="selected"{/if}>count</option>
	<option value="value" {if 'value'==$params.yaxis_func}selected="selected"{/if}>value</option>
	<option value="avg" class="number" {if 'avg'==$params.yaxis_func}selected="selected"{/if}>average</option>
	<option value="sum" class="number" {if 'sum'==$params.yaxis_func}selected="selected"{/if}>sum</option>
	<option value="min" class="number" {if 'min'==$params.yaxis_func}selected="selected"{/if}>min</option>
	<option value="max" class="number" {if 'max'==$params.yaxis_func}selected="selected"{/if}>max</option>
</select>

<select name="params{$params_prefix}[yaxis_field]" class="yaxis_field" style="display:{if empty($series_ctx_fields) || 'count'==$yaxis_func}none{else}inline{/if};">
	{if !empty($series_ctx_fields)}
	{foreach from=$series_ctx_fields item=field}
		{if !empty($field->db_label)}
			{if $field->type == Model_CustomField::TYPE_NUMBER}
			<option value="{$field->token}" {if $yaxis_field==$field->token}selected="selected"{/if}>{$field->db_label|lower}</option>
			{/if}
		{/if}
	{/foreach}
	{/if}
</select>

 as 
 
<select name="params{$params_prefix}[yaxis_format]">
	<option value="" {if empty($params.yaxis_format)}selected="selected"{/if}></option>
	<option value="number" {if 'number'==$params.yaxis_format}selected="selected"{/if}>number</option>
	<option value="decimal" {if 'decimal'==$params.yaxis_format}selected="selected"{/if}>decimal</option>
	<option value="percent" {if 'percent'==$params.yaxis_format}selected="selected"{/if}>percentage</option>
	<option value="bytes" {if 'bytes'==$params.yaxis_format}selected="selected"{/if}>bytes</option>
	<option value="seconds" {if 'seconds'==$params.yaxis_format}selected="selected"{/if}>secs elapsed</option>
	<option value="minutes" {if 'minutes'==$params.yaxis_format}selected="selected"{/if}>mins elapsed</option>
</select>

<script type="text/javascript">
$(function() {
	// [TODO] Limit to the series we're adding now
	var $fieldset = $('fieldset#widget{$widget->id}Datasource{$series_idx}');
	
	$fieldset.find('select.xaxis_field').change(function(e) {
		var $this = $(this);
		
		var $xaxis_tick = $this.siblings('select.xaxis_tick');
		
		if($(this).find('option:selected').is('.date')) {
			$xaxis_tick.show();
		} else {
			$xaxis_tick.hide();
		}
	});
	
	$fieldset.find('select.yaxis_func').change(function(e) {
		var val = $(this).val();
		
		var $select_yaxis = $(this).siblings('select.yaxis_field');
		
		if(val == 'count')
			$select_yaxis.hide();
		else
			$select_yaxis.show();
	});
	
	$fieldset.find('select.yaxis_field').change(function(e) {
		
	});
	
	$fieldset.find('select.context').change(function(e) {
		var ctx = $(this).val();
		
		// [TODO] Hide options until we know the context
		var $select = $(this);
		
		if(0 == ctx.length)
			return;
		
		genericAjaxGet('','c=ui&a=getContextFieldsJson&context=' + ctx, function(json) {
			if('object' == typeof(json) && json.length > 0) {
				var $select_xaxis = $select.siblings('select.xaxis_field').html('');
				var $select_yaxis = $select.siblings('select.yaxis_field').html('');
				
				var $option = $('<option value="_id">(each record)</option>');
				$select_xaxis.append($option);
				
				for(idx in json) {
					var field = json[idx];
					var field_type = (field.type=='E') ? 'date' : ((field.type=='N') ? 'number' : '');
					
					var $option = $('<option/>').attr('value',field.key).addClass(field_type).text(field.label);

					// X-Axis
					// Number or date
					if(field_type == 'number' || field_type == 'date')
						$select_xaxis.append($option.clone());
					
					// Y-Axis
					// Number
					if(field_type == 'number')
						$select_yaxis.append($option.clone());
					
					delete $option;
				}
			}
		});
	});
	
	$('#popup{$div_popup_worklist}').click(function(e) {
		var $select = $(this).siblings('select.context');
		var context = $select.val();
		var $mode = $fieldset.find('input.mode');
		var q = '';
		
		if(context.length == 0) {
			$select.effect('highlight','slow');
			return;
		}
		
		if($mode.is(':checked')) {
			q = $fieldset.find('input.quicksearch').val();
		}
		
		var $chooser = genericAjaxPopup("chooser{uniqid()}",'c=internal&a=chooserOpenParams&context='+context+'&view_id={"widget{$widget->id}_worklist{$series_idx}"}&q=' + encodeURIComponent(q),null,true,'750');
		
		$chooser.bind('chooser_save',function(event) {
			if(null != event.worklist_model) {
				$fieldset.find('input:hidden.model').val(event.worklist_model);
				$fieldset.find('input:text.quicksearch').val(event.worklist_quicksearch);
			}
		});
	});
});
</script>	