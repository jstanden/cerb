<div id="widget{$widget->id}Config" class="cerb-u-mt-3">
	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Visualize this automation</div>
		</div>

		<div class="cerb-ui-form">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label"><a class="cerb-chooser cerb-u-cursor-pointer" data-context="{CerberusContexts::CONTEXT_AUTOMATION}" data-single="true">ID</a></label>
				<input type="text" name="params[automation_id]" value="{$widget->extension_params.automation_id}" class="placeholders" autocomplete="off" spellcheck="false">
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Height (px)</label>
				<input type="number" name="params[height]" value="{$widget->extension_params.height|default:500}" min="150" max="2000" step="10" autocomplete="off">
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label"><input type="checkbox" name="params[minimap]" value="1"{if $widget->extension_params.minimap} checked="checked"{/if}> Show minimap</label>
			</div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $config = $('#widget{$widget->id}Config');
	const $input = $config.find('input[name="params[automation_id]"]');

	if(window.CerbUI && CerbUI.RecordChooser) CerbUI.RecordChooser.pickerLink($config.find('.cerb-chooser')[0], { input: $input });
});
</script>
