<b>Style:</b>
<label><input type="radio" name="params[style]" value="list" {if empty($widget->params.style) || $widget->params.style == 'list'}checked="checked"{/if}> List</label>
<label><input type="radio" name="params[style]" value="pie" {if $widget->params.style == 'pie'}checked="checked"{/if}> Pie chart</label>

<div id="widget{$widget->id}ConfigTabDatasource">
	<fieldset id="widget{$widget->id}Datasource" class="peek">
		{$div_popup_worklist = uniqid()}

		{$worklist_ctx_id = $widget->params.worklist_model.context}

		<b>Display </b>
		
		<select class="context">
			<option value=""> - {'common.choose'|devblocks_translate|lower} - </option>
			{foreach from=$context_mfts item=context_mft key=context_id}
			<option value="{$context_id}" {if $worklist_ctx_id==$context_id}selected="selected"{/if}>{$context_mft->name}</option>
			{/foreach}
		</select>
		
		 subtotals using 
		
		<div id="popup{$div_popup_worklist}" class="badge badge-lightgray" style="font-weight:bold;color:rgb(80,80,80);cursor:pointer;display:inline;"><span class="name">Worklist</span> &#x25be;</div>
	
	<input type="hidden" name="params[worklist_model_json]" value="{$widget->params.worklist_model|json_encode}" class="model">
	
	<br>
	
	<b>Limit </b> to the top 
	
	<select name="params[limit_to]">
		{$limit_to = $widget->params.limit_to|default:20}
		{section start=3 loop=21 step=1 name=increments}
		<option value="{$smarty.section.increments.index}" {if $smarty.section.increments.index==$limit_to}selected="selected"{/if}>{$smarty.section.increments.index}</option>
		{/section}
	</select>
	
	subtotals
	
	<br>

	<script type="text/javascript">
		$fieldset = $('fieldset#widget{$widget->id}Datasource');

		$('#popup{$div_popup_worklist}').click(function(e) {
			var $select =  $(this).siblings('select.context');
			var context = $select.val();
			
			if(context.length == 0) {
				$select.effect('highlight','slow');
				return;
			}
			
			$chooser=genericAjaxPopup("chooser{uniqid()}",'c=internal&a=chooserOpenParams&context='+context+'&view_id={"widget{$widget->id}_worklist"}',null,true,'750');
			$chooser.bind('chooser_save',function(event) {
				if(null != event.worklist_model) {
					$('#popup{$div_popup_worklist}').parent().find('input:hidden.model').val(event.worklist_model);
				}
			});
		});
	</script>
	</fieldset>

</div>