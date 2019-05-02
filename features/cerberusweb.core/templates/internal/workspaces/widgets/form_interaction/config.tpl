<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset class="peek">
		<legend>Enable these form interactions:</legend>
		
		<div>
			<textarea name="params[interactions_yaml]" class="cerb-code-editor" data-editor-mode="ace/mode/yaml" style="width:100%;">{$widget->params.interactions_yaml}</textarea>
		</div>
		
		<div style="margin-top:5px;">
			<button type="button" class="chooser-behavior" data-field-name="params[behavior_id]" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-single="true" data-query="" data-query-required="event:&quot;event.form.interaction.worker&quot;"><span class="glyphicons glyphicons-circle-plus"></span> {'common.add'|devblocks_translate|capitalize}</button>
		</div>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');
	var $editor = $config.find('.cerb-code-editor');
	
	$editor.cerbCodeEditor();
	
	$config.find('.chooser-behavior')
		.cerbChooserTrigger()
		.on('cerb-chooser-selected', function(e) {
			// Fetch dictionaries for the records
			genericAjaxGet(null, 'c=ui&a=dataQuery&q=' + encodeURIComponent('type:worklist.records of:behaviors query:(id:[' + e.values.join(',') + ']) format:dictionaries'), function(json) {
				if('object' != typeof json)
					return;
				
				if(null == json.data)
					return;
				
				for(id in json.data) {
					var evt = $.Event('cerb.insertAtCursor');
					evt.content = 
						"- id: " + (json.data[id].uri ? json.data[id].uri : json.data[id].id) + 
						" # " + json.data[id]._label + "\n" +
						"  label: >-\n" +
						"    " + json.data[id]._label + "\n"
						;
					
					if(null != json.data[id].variables) {
						evt.content += "  inputs:\n";
						
						for(i in json.data[id].variables) {
							if(false == json.data[id].variables[i].is_private)
								evt.content += "    " + json.data[id].variables[i].key + ": >-" +
								" #" + json.data[id].variables[i].type + 
								"\n" + 
								"      " + "~" + "\n";
						}
					}
					
					$editor.nextAll('pre.ace_editor').triggerHandler(evt);
				}
			});
		})
	;
});
</script>