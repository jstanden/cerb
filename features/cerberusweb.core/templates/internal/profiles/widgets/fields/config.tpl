<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset id="widget{$widget->id}Worklist" class="peek">
		<legend>Display this record:</legend>
		
		<b>Type:</b>
		
		<div style="margin-left:10px;">
			<select name="params[context]">
				<option value=""></option>
				{foreach from=$context_mfts item=context_mft}
				<option value="{$context_mft->id}" {if $widget->extension_params.context == $context_mft->id}selected="selected"{/if}>{$context_mft->name}</option>
				{/foreach}
			</select>
		</div>
		
		<b><a href="javascript:;" class="cerb-chooser" data-context="{$widget->extension_params.context}" data-single="true">ID</a>:</b>
		
		<div style="margin-left:10px;">
			<input type="text" name="params[context_id]" value="{$widget->extension_params.context_id}" class="placeholders" style="width:95%;padding:5px;border-radius:5px;" autocomplete="off" spellcheck="false">
		</div>
	</fieldset>
	
	<div class="cerb-context-tabs">
		{include file="devblocks:cerberusweb.core::internal/profiles/widgets/fields/fields_config_tabs.tpl"}
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');
	var $select = $config.find("select[name='params[context]']");
	var $label_context_id = $config.find('a.cerb-chooser');
	var $input_context_id = $config.find('input[name="params[context_id]"]');
	var $tab_fields = $config.find('#widget{$widget->id}TabFields');
	var $context_tabs = $config.find('div.cerb-context-tabs');
	
	$context_tabs.find('div.cerb-tabs').tabs();
	
	$select.on('change', function(e) {
		var context = $(this).val();
		
		if(0 == context.length) {
			$tabs.hide();
			return;
		}
		
		$label_context_id.attr('data-context', context);
		
		// When the context changes, redraw the tabs
		genericAjaxGet($context_tabs, 'c=profiles&a=invoke&module=profile_widget&action=getFieldsTabsByContext&context=' + encodeURIComponent(context), function() {
			var $tabs = $context_tabs.find('div.cerb-tabs');
			
			try {
				$tabs.tabs('destroy');
			} catch(e) {}
			
			$tabs.tabs().show();
		});
	});
	
	$config.find('.cerb-chooser').cerbChooserTrigger()
		.on('cerb-chooser-selected', function(e) {
			{literal}$input_context_id.val(e.values[0] + '{# ' + e.labels[0] + ' #}');{/literal}
		})
		;
});
</script>