<div id="widget{$widget->id}Config" class="cerb-u-mt-3">
	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Display this knowledgebase article</div>
		</div>

		<div class="cerb-ui-form">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label"><a class="cerb-chooser cerb-u-cursor-pointer" data-context="{CerberusContexts::CONTEXT_KB_ARTICLE}" data-single="true">ID</a></label>
				<input type="text" name="params[context_id]" value="{$widget->extension_params.context_id}" class="placeholders" autocomplete="off" spellcheck="false">
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Max. height <small>(pixels)</small></label>
				<input type="text" name="params[height]" value="{$widget->extension_params.height}" class="placeholders" autocomplete="off" spellcheck="false">
			</div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $config = $('#widget{$widget->id}Config');
	const $input = $config.find('input[name="params[context_id]"]');

	if(window.CerbUI && CerbUI.RecordChooser) CerbUI.RecordChooser.pickerLink($config.find('.cerb-chooser')[0], { input: $input });
});
</script>
