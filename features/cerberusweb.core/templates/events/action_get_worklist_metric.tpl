{$ctx_fields = []}
{if $params.context}
	{$ctx = Extension_DevblocksContext::get($params.context|default:'')}
	{if is_a($ctx, 'Extension_DevblocksContext')}
		{$ctx_view = $ctx->getChooserView()}
		{if $ctx_view}
			{$ctx_fields = $ctx_view->getParamsAvailable()}
		{/if}
	{/if}
{/if}

<b>{'common.type'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<select class="context" name="{$namePrefix}[context]">
		<option value=""></option>
		{foreach from=$contexts item=context key=context_id}
		<option value="{$context_id}" {if $params.context == $context_id}selected="selected"{/if}>{$context->name}</option>
		{/foreach}
	</select>
</div>

<b>Quick search query:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[query]" style="width:100%;" class="placeholders">{$params.query}</textarea>
</div>

<b>Metric</b> is 
<div style="margin-left:10px;margin-bottom:0.5em;">
	{$metric_func = $params.metric_func}
	{$metric_field = $params.metric_field}
	
	<select name="{$namePrefix}[metric_func]" class="metric_func">
		<option value="count" {if 'count'==$params.metric_func}selected="selected"{/if}>count</option>
		<option value="avg" class="number" {if 'avg'==$params.metric_func}selected="selected"{/if}>average</option>
		<option value="sum" class="number" {if 'sum'==$params.metric_func}selected="selected"{/if}>sum</option>
		<option value="min" class="number" {if 'min'==$params.metric_func}selected="selected"{/if}>min</option>
		<option value="max" class="number" {if 'max'==$params.metric_func}selected="selected"{/if}>max</option>
	</select>
	
	<select name="{$namePrefix}[metric_field]" class="metric_field" style="display:{if empty($ctx_fields) || in_array($metric_func,['','count'])}none{else}inline{/if};">
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
</div>

<b>Save value to a placeholder named:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	&#123;&#123;<input type="text" name="{$namePrefix}[var]" size="32" value="{if !empty($params.var)}{$params.var}{else}placeholder{/if}" required="required" spellcheck="false">&#125;&#125;
	<div style="margin-top:5px;">
		<i><small>The placeholder name must be lowercase, without spaces, and may only contain a-z, 0-9, and underscores (_)</small></i>
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
	var $select = $action.find('select.context');
	
	$select.on('change', function(e) {
		var ctx = $select.val();
		
		if(0 == ctx.length)
			return;
		
		genericAjaxGet('','c=ui&a=getContextFieldsJson&context=' + ctx, function(json) {
			if('object' == typeof(json) && json.length > 0) {
				var $select_metric_field = $action.find('select.metric_field').html('');
				
				for(idx in json) {
					var field = json[idx];
					var field_type = (field.type=='E') ? 'date' : ((field.type=='N') ? 'number' : '');
					
					var $option = $('<option/>').attr('value',field.key).addClass(field_type).text(field.label);
	
					// Number
					if(field_type == 'number')
						$select_metric_field.append($option.clone());
					
					delete $option;
				}
			}
		});
	});	
	
	$action.find('select.metric_func').on('change', function(e) {
		var val = $(this).val();
		
		var $select_metric_field = $action.find('select.metric_field');
		
		if(val == 'count') {
			$select_metric_field.val('');
			$select_metric_field.hide();
		} else {
			$select_metric_field.show();
		}
	});
});
</script>