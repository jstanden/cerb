{$uniqid = "{uniqid()}"}
{$is_blank = empty($params.worklist_model.context)}

<div id="div{$uniqid}" class="datasource-params">
<b>Load</b>

<select class="context">
	<option value=""> - {'common.choose'|devblocks_translate|lower} - </option>
	{foreach from=$context_mfts item=context_mft}
	<option value="{$context_mft->id}" {if $params.worklist_model.context==$context_mft->id}selected="selected"{/if}>{$context_mft->name}</option>
	{/foreach}
</select>

 data using 

<div id="popup{$uniqid}" class="badge badge-lightgray" style="font-weight:bold;color:rgb(80,80,80);cursor:pointer;display:inline;"><span class="name">Worklist</span> &#x25be;</div>

<input type="hidden" name="params{$params_prefix}[worklist_model_json]" value="{$params.worklist_model|json_encode}" class="model">

<br>

<div>
<b>Label</b> with <input type="text" name="params{$params_prefix}[label]" value="{$params.label|default:'{{_label}}'}" size="64" class="placeholders-input">
</div>

<div style="display:none;" class="placeholders-toolbar">
<select class="placeholders">
	<option value="">- insert at cursor -</option>
	{if !empty($placeholders)}
	{foreach from=$placeholders key=k item=label}
	<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$label}</option>
	{/foreach}
	{/if}
</select>
</div>

<div>
<b>Start date</b> is 
	
<select name="params{$params_prefix}[field_start_date]" class="field_start_date">
	{if !empty($ctx_fields)}
	{foreach from=$ctx_fields item=field}
		{if !empty($field->db_label)}
			{if $field->type == Model_CustomField::TYPE_DATE}
			<option value="{$field->token}" class="{if $field->type == Model_CustomField::TYPE_DATE}date{else}{/if}" {if $params.field_start_date==$field->token}selected="selected"{/if}>{$field->db_label|lower}</option>
			{/if}
		{/if}
	{/foreach}
	{/if}
</select>
<input type="text" name="params{$params_prefix}[field_start_date_offset]" value="{$params.field_start_date_offset|default:''}" size="24" placeholder="e.g. +2 hours">
</div>

<div>
<b>End date</b> is 

<select name="params{$params_prefix}[field_end_date]" class="field_end_date">
	{if !empty($ctx_fields)}
	{foreach from=$ctx_fields item=field}
		{if !empty($field->db_label)}
			{if $field->type == Model_CustomField::TYPE_DATE}
			<option value="{$field->token}" class="{if $field->type == Model_CustomField::TYPE_DATE}date{else}{/if}" {if $params.field_end_date==$field->token}selected="selected"{/if}>{$field->db_label|lower}</option>
			{/if}
		{/if}
	{/foreach}
	{/if}
</select>
<input type="text" name="params{$params_prefix}[field_end_date_offset]" value="{$params.field_end_date_offset|default:''}" size="24" placeholder="e.g. +2 hours">
</div>

<div>
<b>Status</b> is 
<label><input type="radio" name="params{$params_prefix}[is_available]" value="1" {if !empty($params.is_available)}checked="checked"{/if}> available</label>
<label><input type="radio" name="params{$params_prefix}[is_available]" value="0" {if empty($params.is_available)}checked="checked"{/if}> busy</label>
</div>

<b>Color</b> it 
<input type="hidden" name="params{$params_prefix}[color]" value="{$params.color|default:'#A0D95B'}" style="width:100%;" class="color-picker">
</div>

<script type="text/javascript">
var $div = $('#div{$uniqid}');

$div.find('select.field_start_date').change(function(e) {
	var $this = $(this);
});

$div.find('input:hidden.color-picker').miniColors({
	color_favorites: ['#A0D95B','#FEAF03','#FCB3B3','#FF6666','#C5DCFA','#85BAFF','#E8F554','#F4A3FE','#C8C8C8']
});

$div.find('select.context').change(function(e) {
	var ctx = $(this).val();
	
	// Hide options until we know the context
	var $select = $(this);
	
	if(0 == ctx.length)
		return;
	
	genericAjaxGet('','c=internal&a=handleSectionAction&section=dashboards&action=getContextFieldsJson&context=' + ctx, function(json) {
			if('object' == typeof(json) && json.length > 0) {
				var $div = $('#div{$uniqid}');
				
				var $select_field_start_date = $div.find('select.field_start_date').html('');
				var $select_field_end_date = $div.find('select.field_end_date').html('');
				
				for(idx in json) {
					var field = json[idx];
					var field_type = (field.type=='E') ? 'date' : ((field.type=='N') ? 'number' : '');
					
					var $option = $('<option value="'+field.key+'" class="'+field_type+'">'+field.label+'</option>');
	
					// Field: Start Date
					if(field_type == 'date') {
						$select_field_start_date.append($option.clone());
						$select_field_end_date.append($option.clone());
					}
					
					delete $option;
				}
			}
		});
	
	genericAjaxGet('','c=internal&a=handleSectionAction&section=dashboards&action=getContextPlaceholdersJson&context=' + ctx, function(json) {
		if('object' == typeof(json) && json.length > 0) {
			var $div = $('#div{$uniqid}');
			var $placeholders = $div.find('select.placeholders');
			
			$placeholders.html('');
			
			var $option = $("<option value=''>- insert at cursor -</option>");
			$placeholders.append($option);
			
			for(i in json) {
				var field = json[i];
				
				if(field.label.length == 0 || field.key.length == 0)
					continue;
				
				var $option = $("<option value='{literal}{{{/literal}" + field.key + "{literal}}}{/literal}'>" + field.label + "</option>");
				$placeholders.append($option);
			}
			
			$placeholders.val('');
		}
	});
});

$div.find('input.placeholders-input')
	.focus(function() {
		var $this = $(this);
		var $div = $(this).closest('div.datasource-params');
		
		$div.find('div.placeholders-toolbar')
			.insertAfter($this)
			.css('margin-left', ($this.position().left-30) + 'px')
			.fadeIn();
	})
	;

$div.find('select.placeholders').change(function(e) {
	var $select = $(this);
	var $input = $select.parent().prev('input:text');
	var txt = $select.val();
	$input.insertAtCursor(txt);
	$select.val('');
});

$('#popup{$uniqid}').click(function(e) {
	var $select = $(this).siblings('select.context');
	var context = $select.val();
	
	if(context.length == 0) {
		$select.effect('highlight','slow');
		return;
	}
	
	$chooser=genericAjaxPopup("chooser{uniqid()}",'c=internal&a=chooserOpenParams&context='+context+'&view_id={"calendar{$calendar->id}_worklist{$series_idx}"}',null,true,'750');
	$chooser.bind('chooser_save',function(event) {
		if(null != event.worklist_model) {
			$('#popup{$uniqid}').parent().find('input:hidden.model').val(event.worklist_model);
		}
	});
});

</script>