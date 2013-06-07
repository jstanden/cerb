{$div_popup_worklist = uniqid()}
{$div_id = uniqid()}

{$ctx_id = $widget->params.worklist_model.context}

{$ctx = null}
{$ctx_view = null}
{$ctx_fields = []}

{if !empty($ctx_id)}
	{$ctx = Extension_DevblocksContext::get($ctx_id)}
	{$ctx_view = $ctx->getChooserView()} 
	{$ctx_fields = $ctx_view->getParamsAvailable()}
{/if}

<div id="{$div_id}">

<b>Load </b>

<select class="context">
	{foreach from=$context_mfts item=context_mft key=context_id}
	<option value="{$context_id}" {if $ctx_id==$context_id}selected="selected"{/if}>{$context_mft->name}</option>
	{/foreach}
</select>

<b> data using</b> 

<div id="popup{$div_popup_worklist}" class="badge badge-lightgray" style="font-weight:bold;color:rgb(80,80,80);cursor:pointer;display:inline;"><span class="name">Worklist</span> &#x25be;</div>

<input type="hidden" name="params[worklist_model_json]" value="{$widget->params.worklist_model|json_encode}" class="model">

<br>

<b>Value</b> is 
 
{$metric_func = $widget->params.metric_func}
{$metric_field = $widget->params.metric_field}

<select name="params[metric_func]" class="metric_func">
	<option value="count" {if 'count'==$widget->params.metric_func}selected="selected"{/if}>count</option>
	<option value="avg" class="number" {if 'avg'==$widget->params.metric_func}selected="selected"{/if}>average</option>
	<option value="sum" class="number" {if 'sum'==$widget->params.metric_func}selected="selected"{/if}>sum</option>
	<option value="min" class="number" {if 'min'==$widget->params.metric_func}selected="selected"{/if}>min</option>
	<option value="max" class="number" {if 'max'==$widget->params.metric_func}selected="selected"{/if}>max</option>
</select>

<select name="params[metric_field]" class="metric_field" style="display:{if empty($ctx_fields) || 'count'==$metric_func}none{else}inline{/if};">
	{if !empty($ctx_fields)}
	{foreach from=$ctx_fields item=field}
		{if !empty($field->db_label)}
			{if $field->type == Model_CustomField::TYPE_NUMBER}
			<option value="{$field->token}" {if $metric_field==$field->token}selected="selected"{/if}>{$field->db_label|lower}</option>
			{/if}
		{/if}
	{/foreach}
	{/if}
</select>
<br>

</div>

<script type="text/javascript">

$div = $('#{$div_id}');

$div.find('select.context').change(function(e) {
	ctx = $(this).val();
	
	// Hide options until we know the context
	var $select = $(this);
	
	if(0 == ctx.length)
		return;
	
	genericAjaxGet('','c=internal&a=handleSectionAction&section=dashboards&action=getContextFieldsJson&context=' + ctx, function(json) {
		if('object' == typeof(json) && json.length > 0) {
			$select_metric_field = $select.siblings('select.metric_field').html('');
			
			for(idx in json) {
				field = json[idx];
				field_type = (field.type=='E') ? 'date' : ((field.type=='N') ? 'number' : '');
				
				$option = $('<option value="'+field.key+'" class="'+field_type+'">'+field.label+'</option>');

				// Number
				if(field_type == 'number')
					$select_metric_field.append($option.clone());
				
				delete $option;
			}
		}
	});
});	

$div.find('select.metric_func').change(function(e) {
	val = $(this).val();
	
	var $select_metric_field = $(this).siblings('select.metric_field');
	
	if(val == 'count')
		$select_metric_field.hide();
	else
		$select_metric_field.show();
});

$('#popup{$div_popup_worklist}').click(function(e) {
	context = $(this).siblings('select.context').val();
	$chooser=genericAjaxPopup("chooser{uniqid()}",'c=internal&a=chooserOpenParams&context='+context+'&view_id={"widget{$widget->id}_worklist"}',null,true,'750');
	$chooser.bind('chooser_save',function(event) {
		if(null != event.worklist_model) {
			$('#popup{$div_popup_worklist}').parent().find('input:hidden.model').val(event.worklist_model);
		}
	});
});

</script>