<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset id="widget{$widget->id}Worklist" class="peek">
		<legend>Display this knowledgebase article</legend>
		
		<div>
			<b><a href="javascript:;" class="cerb-chooser" data-context="{CerberusContexts::CONTEXT_KB_ARTICLE}" data-single="true">ID</a>:</b>
			
			<div style="margin-left:10px;">
				<input type="text" name="params[context_id]" value="{$widget->extension_params.context_id}" class="placeholders" style="width:95%;padding:5px;border-radius:5px;" autocomplete="off" spellcheck="off">
			</div>
		</div>
		
		<div>
			<b>Max. Height: <small>(pixels)</small></b>
			
			<div style="margin-left:10px;">
				<input type="text" name="params[height]" value="{$widget->extension_params.height}" class="placeholders" style="width:95%;padding:5px;border-radius:5px;" autocomplete="off" spellcheck="off">
			</div>
		</div>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');
	var $input = $config.find('input[name="params[context_id]"]');
	
	$config.find('.cerb-chooser').cerbChooserTrigger()
		.on('cerb-chooser-selected', function(e) {
			{literal}$input.val(e.values[0] + '{# ' + e.labels[0] + ' #}');{/literal}
		})
		;

});
</script>