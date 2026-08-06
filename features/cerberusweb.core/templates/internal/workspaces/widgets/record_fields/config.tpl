<div id="widget{$widget->id}Config" class="cerb-u-my-3">
	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Display this record:</div>
		</div>

		<div class="cerb-ui-form">
			<div class="cerb-ui-form--row">
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">{'common.type'|devblocks_translate|capitalize}</label>
					<select name="params[context]">
						<option value=""></option>
						{foreach from=$context_mfts item=context_mft}
						<option value="{$context_mft->id}" data-cerb-ui-icon="{$context_mft->params.icon|default:'collection'}" {if CerberusContexts::isSameContext($widget->params.context, $context_mft->id)}selected="selected"{/if}>{$context_mft->name}</option>
						{/foreach}
					</select>
				</div>

				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label"><a class="cerb-chooser cerb-u-cursor-pointer" data-context="{$widget->params.context}" data-single="true">ID</a></label>
					<input type="text" name="params[context_id]" value="{$widget->params.context_id}" class="placeholders" autocomplete="off" spellcheck="false">
				</div>
			</div>
		</div>
	</div>

	<div class="cerb-context-tabs">
		{include file="devblocks:cerberusweb.core::internal/workspaces/widgets/record_fields/fields_config_tabs.tpl"}
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');
	var $select = $config.find("select[name='params[context]']");
	var $label_context_id = $config.find('a.cerb-chooser');
	var $input_context_id = $config.find('input[name="params[context_id]"]');
	var $tab_fields = $config.find('#widget{$widget->id}TabFields');
	var $context_tabs = $config.find('div.cerb-context-tabs');

	// Record type — SelectMenu (type-to-filter + per-type icons). Keeps the native <select>, so its
	// change event still drives the field tabs below.
	if(window.CerbUI && CerbUI.SelectMenu)
		$select.each(function() { new CerbUI.SelectMenu(this, { filter: true }); });

	$context_tabs.find('div.cerb-tabs > ul').each(function() { if(window.CerbUI && CerbUI.Tabs) new CerbUI.Tabs(this); });

	$select.on('change', function(e) {
		var context = $(this).val();

		if(0 == context.length) {
			$context_tabs.hide();
			return;
		}
		
		$label_context_id.attr('data-context', context);
		
		// When the context changes, redraw the tabs
		genericAjaxGet($context_tabs, 'c=profiles&a=invoke&module=workspace_widget&action=getFieldsTabsByContext&context=' + encodeURIComponent(context), function() {
			var $tabs = $context_tabs.find('div.cerb-tabs');
			
			$tabs.find('> ul').each(function() { if(window.CerbUI && CerbUI.Tabs) new CerbUI.Tabs(this); });
			$tabs.show();
		});
	});
	
	if(window.CerbUI && CerbUI.RecordChooser) CerbUI.RecordChooser.pickerLink($config.find('.cerb-chooser')[0], { input: $input_context_id });
});
</script>