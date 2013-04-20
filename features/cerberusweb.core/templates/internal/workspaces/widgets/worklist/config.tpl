<div id="widget{$widget->id}ConfigTabs">
	<ul>
		<li><a href="#widget{$widget->id}ConfigTabDatasource">Data Source</a></li>
	</ul>
	
	<div id="widget{$widget->id}ConfigTabDatasource">
	
		<fieldset id="widget{$widget->id}Datasource" class="peek">
			{$div_popup_worklist = uniqid()}

			{$series_ctx_id = $widget->params.view_context}
			{$worklist_ctx_id = $widget->params.worklist_model.context}
			
			{$series_ctx = null}
			{$series_ctx_view = null}
			{$series_ctx_fields = []}
			
			{if !empty($series_ctx_id)}
				{$series_ctx = Extension_DevblocksContext::get($series_ctx_id)}
				{if $series_ctx instanceof Extension_DevblocksContext}
					{$series_ctx_view = $series_ctx->getChooserView()} 
					{$series_ctx_fields = $series_ctx_view->getParamsAvailable()}
				{/if}
			{/if}

			<b>Display </b>
			
			<select class="context">
				<option value=""> - {'common.choose'|devblocks_translate|lower} - </option>
				{foreach from=$context_mfts item=context_mft key=context_id}
				<option value="{$context_id}" {if $worklist_ctx_id==$context_id}selected="selected"{/if}>{$context_mft->name}</option>
				{/foreach}
			</select>
			
			 data using 
			
			<div id="popup{$div_popup_worklist}" class="badge badge-lightgray" style="font-weight:bold;color:rgb(80,80,80);cursor:pointer;display:inline;"><span class="name">Worklist</span> &#x25be;</div>
			
			<input type="hidden" name="params[worklist_model_json]" value="{$widget->params.worklist_model|json_encode}" class="model">
			
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
					var $select = $(this).siblings('select.context');
					context = $select.val();
					
					if(context.length == 0) {
						$select.effect('highlight','slow');
						return;
					}
					
					$chooser=genericAjaxPopup("chooser{uniqid()}",'c=internal&a=chooserOpenParams&context='+context+'&view_id={"widget{$widget->id}_worklist_config"}',null,true,'750');
					$chooser.bind('chooser_save',function(event) {
						if(null != event.worklist_model) {
							$('#popup{$div_popup_worklist}').parent().find('input:hidden.model').val(event.worklist_model);
						}
					});
				});
			</script>
		</fieldset>

	</div>
	
</div>

<script type="text/javascript">
	$('#widget{$widget->id}ConfigTabs').tabs();
</script>