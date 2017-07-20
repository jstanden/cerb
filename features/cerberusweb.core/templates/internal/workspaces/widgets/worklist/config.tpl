<div id="widget{$widget->id}ConfigTabs">
	<ul>
		<li><a href="#widget{$widget->id}ConfigTabDatasource">Data Source</a></li>
	</ul>
	
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
			
			 data using 
			
			<div id="popup{$div_popup_worklist}" class="badge badge-lightgray" style="font-weight:bold;color:rgb(80,80,80);cursor:pointer;display:inline;"><span class="name">Worklist</span> &#x25be;</div>
			
			<input type="hidden" name="params[worklist_model_json]" value="{$widget->params.worklist_model|json_encode}" class="model">
			
			<br>
			
			<label><input type="checkbox" name="params[search_mode]" value="quick_search" class="mode" {if $widget->params.search_mode == "quick_search"}checked="checked"{/if}> Filter using quick search:</label>
			
			<div style="margin-left:20px;">
				<input type="text" name="params[quick_search]" value="{$widget->params.quick_search}" class="quicksearch" style="width:95%;padding:5px;border-radius:5px;" autocomplete="off" spellcheck="off">
			</div>
			
			<script type="text/javascript">
				var $fieldset = $('fieldset#widget{$widget->id}Datasource');
				
				$('#popup{$div_popup_worklist}').click(function(e) {
					var $select = $(this).siblings('select.context');
					var context = $select.val();
					var $mode = $fieldset.find('input.mode');
					var q = '';
					
					if($mode.is(':checked')) {
						q = $fieldset.find('input.quicksearch').val();
					}
					
					if(context.length == 0) {
						$select.effect('highlight','slow');
						return;
					}
					
					var $chooser = genericAjaxPopup("chooser{uniqid()}",'c=internal&a=chooserOpenParams&context='+context+'&view_id={"widget{$widget->id}_worklist_config"}&q=' + encodeURIComponent(q),null,true,'750');
					
					$chooser.on('chooser_save',function(event) {
						if(null != event.worklist_model) {
							$fieldset.find('input:hidden.model').val(event.worklist_model);
							$fieldset.find('input.quicksearch').val(event.worklist_quicksearch);
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