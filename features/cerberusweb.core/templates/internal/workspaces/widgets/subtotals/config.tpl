<div id="widget{$widget->id}ConfigTabs">
	<ul>
		<li><a href="#widget{$widget->id}ConfigTabDatasource">Data Source</a></li>
		<li><a href="#widget{$widget->id}ConfigTabStyle">Style</a></li>
	</ul>
	
    <div id="widget{$widget->id}ConfigTabDatasource">
    	
    	<fieldset id="widget{$widget->id}Datasource" class="peek">
    		{$div_popup_worklist = uniqid()}

			{$series_ctx_id = $widget->params.view_context}
			
			{$series_ctx = null}
			{$series_ctx_view = null}
			{$series_ctx_fields = []}
			
			{if !empty($series_ctx_id)}
				{$series_ctx = Extension_DevblocksContext::get($series_ctx_id)}
				{$series_ctx_view = $series_ctx->getChooserView()} 
				{$series_ctx_fields = $series_ctx_view->getParamsAvailable()}
			{/if}

			<b>Display </b>
			
			<select name="params[view_context]" class="context">
				<option value=""> - {'common.choose'|devblocks_translate|lower} - </option>
				{foreach from=$context_mfts item=context_mft key=context_id}
				<option value="{$context_id}" {if $series_ctx_id==$context_id}selected="selected"{/if}>{$context_mft->name}</option>
				{/foreach}
			</select>
			
			 subtotals using  
			
			<div id="popup{$div_popup_worklist}" class="badge badge-lightgray" style="font-weight:bold;color:rgb(80,80,80);cursor:pointer;display:inline;"><span class="name">Worklist</span> &#x25be;</div>
			
			<input type="hidden" name="params[view_id]" value="widget{$widget->id}_worklist">
			<input type="hidden" name="params[view_model]" value="{$widget->params.view_model}" class="model">
			
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
				
				$fieldset.find('select.context').change(function(e) {
					ctx = $(this).val();
					
					var $select = $(this);
					
					if(0 == ctx.length)
						return;
					
					genericAjaxGet('','c=internal&a=handleSectionAction&section=dashboards&action=getContextFieldsJson&context=' + ctx, function(json) {
						if('object' == typeof(json) && json.length > 0) {
							// ...
						}
					});
				});
				
				$('#popup{$div_popup_worklist}').click(function(e) {
					context = $(this).siblings('select.context').val();
					$chooser=genericAjaxPopup("chooser{uniqid()}",'c=internal&a=chooserOpenParams&context='+context+'&view_id={"widget{$widget->id}_worklist"}',null,true,'750');
					$chooser.bind('chooser_save',function(event) {
						if(null != event.view_model) {
							$('#popup{$div_popup_worklist}').parent().find('input:hidden.model').val(event.view_model);
						}
					});
				});
			</script>			
    	</fieldset>

	</div>
	
	<div id="widget{$widget->id}ConfigTabStyle">
		<label><input type="radio" name="params[style]" value="list" {if empty($widget->params.style) || $widget->params.style == 'list'}checked="checked"{/if}> List</label>
		<label><input type="radio" name="params[style]" value="pie" {if $widget->params.style == 'pie'}checked="checked"{/if}> Pie chart</label>
	</div>
    
</div>

<script type="text/javascript">
	$('#widget{$widget->id}ConfigTabs').tabs();
</script>