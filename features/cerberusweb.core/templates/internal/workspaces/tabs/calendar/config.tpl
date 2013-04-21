{$div_popup_worklist = "workspace_tab{$workspace_tab->id}_worklist"}
{$worklist_context_id = $workspace_tab->params.worklist_model.context}

<fieldset id="tabConfig{$workspace_tab->id}">
<legend>Calendar events</legend>

<b>Load</b>

<select class="context">
	<option value=""> - {'common.choose'|devblocks_translate|lower} - </option>
	{foreach from=$context_mfts item=context_mft}
	<option value="{$context_mft->id}" {if $worklist_context_id==$context_mft->id}selected="selected"{/if}>{$context_mft->name}</option>
	{/foreach}
</select>

 data using 

<div id="popup{$div_popup_worklist}" class="badge badge-lightgray" style="font-weight:bold;color:rgb(80,80,80);cursor:pointer;display:inline;"><span class="name">Worklist</span> &#x25be;</div>

<input type="hidden" name="params[worklist_model_json]" value="{$workspace_tab->params.worklist_model|json_encode}" class="model">

<br>

<b>Start date</b> is 
	
<select name="params[field_start_date]" class="field_start_date">
	{if !empty($ctx_fields)}
	{foreach from=$ctx_fields item=field}
		{if !empty($field->db_label)}
			{if $field->type == Model_CustomField::TYPE_DATE}
			<option value="{$field->token}" class="{if $field->type == Model_CustomField::TYPE_DATE}date{else}{/if}" {if $workspace_tab->params.field_start_date==$field->token}selected="selected"{/if}>{$field->db_label|lower}</option>
			{/if}
		{/if}
	{/foreach}
	{/if}
</select>

<br>

<b>Label</b> as <input type="text" name="params[label]" value="{$workspace_tab->params.label}" size="80">

<div style="margin-left:60px;">
	<select class="placeholders">
		<option value="">- insert at cursor -</option>
		{if !empty($placeholders)}
		{foreach from=$placeholders key=k item=label}
		<option value="{literal}{{{/literal}{$k}{literal}}}{/literal}">{$label}</option>
		{/foreach}
		{/if}
	</select>
</div>

<b>Color</b> it 

<input type="hidden" name="params[color]" value="{$workspace_tab->params.color|default:'#A0D95B'}" style="width:100%;" class="color-picker">

<br>

</fieldset>

<script type="text/javascript">
$fieldset = $('#tabConfig{$workspace_tab->id}');

$fieldset.find('select.field_start_date').change(function(e) {
	$this = $(this);
});

$fieldset.find('input:hidden.color-picker').miniColors({
	color_favorites: ['#A0D95B','#FEAF03','#FCB3B3','#FF6666','#C5DCFA','#85BAFF','#E8F554','#F4A3FE','#ADADAD']
});

$fieldset.find('select.context').change(function(e) {
	ctx = $(this).val();
	
	// Hide options until we know the context
	var $select = $(this);
	
	if(0 == ctx.length)
		return;
	
	genericAjaxGet('','c=internal&a=handleSectionAction&section=dashboards&action=getContextFieldsJson&context=' + ctx, function(json) {
		if('object' == typeof(json) && json.length > 0) {
			var $select_field_start_date = $select.siblings('select.field_start_date').html('');
			
			for(idx in json) {
				field = json[idx];
				field_type = (field.type=='E') ? 'date' : ((field.type=='N') ? 'number' : '');
				
				$option = $('<option value="'+field.key+'" class="'+field_type+'">'+field.label+'</option>');

				// Field: Start Date
				if(field_type == 'date')
					$select_field_start_date.append($option.clone());
				
				delete $option;
			}
		}
	});
	
	genericAjaxGet('','c=internal&a=handleSectionAction&section=dashboards&action=getContextPlaceholdersJson&context=' + ctx, function(json) {
		if('object' == typeof(json) && json.length > 0) {
			var $fieldset = $select.closest('fieldset');
			var $placeholders = $fieldset.find('select.placeholders');
			
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

$fieldset.find('select.placeholders').change(function(e) {
	var $select = $(this);
	var $input = $select.parent().prev('input:text');
	var txt = $select.val();
	$input.insertAtCursor(txt);
});

$('#popup{$div_popup_worklist}').click(function(e) {
	var $select = $(this).siblings('select.context');
	var context = $select.val();
	
	if(context.length == 0) {
		$select.effect('highlight','slow');
		return;
	}
	
	$chooser=genericAjaxPopup("chooser{uniqid()}",'c=internal&a=chooserOpenParams&context='+context+'&view_id={"workspace_tab{$workspace_tab->id}_worklist"}',null,true,'750');
	$chooser.bind('chooser_save',function(event) {
		if(null != event.worklist_model) {
			$('#popup{$div_popup_worklist}').parent().find('input:hidden.model').val(event.worklist_model);
		}
	});
});

</script>