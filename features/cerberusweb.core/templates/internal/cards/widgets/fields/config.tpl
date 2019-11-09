{$config_uniqid = uniqid('widgetConfig_')}
<div id="{$config_uniqid}" style="margin-top:10px;">
	<fieldset class="peek">
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
		{include file="devblocks:cerberusweb.core::internal/cards/widgets/fields/fields_config_tabs.tpl"}
	</div>
</div>

<script type="text/javascript">
$(function() {
	var $config = $('#{$config_uniqid}');
	var $select = $config.find("select[name='params[context]']");
	var $label_context_id = $config.find('a.cerb-chooser');
	var $input_context_id = $config.find('input[name="params[context_id]"]');
	var $context_tabs = $config.find('div.cerb-context-tabs');
	
	$context_tabs.find('div.cerb-tabs').tabs();
	
	$select.on('change', function(e) {
		var context = $(this).val();
		
		if(0 == context.length) {
			$context_tabs.hide();
			return;
		}
		
		$label_context_id.attr('data-context', context);
		
		// When the context changes, redraw the tabs
		genericAjaxGet($context_tabs, 'c=profiles&a=handleSectionAction&section=card_widget&action=getFieldsTabsByContext&context=' + encodeURIComponent(context), function() {
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