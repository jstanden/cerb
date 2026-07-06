<div id="widget{$widget->id}Config" class="cerb-u-mt-3">
	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Display comments for this record:</div>
		</div>

		<div class="cerb-ui-form">
			<div class="cerb-ui-form--row">
				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label">Type</label>
					<select name="params[context]">
						<option value=""></option>
						{foreach from=$context_mfts item=context_mft}
						<option value="{$context_mft->id}" data-cerb-ui-icon="{$context_mft->params.icon|default:'collection'}" {if $widget->extension_params.context == $context_mft->id}selected="selected"{/if}>{$context_mft->name}</option>
						{/foreach}
					</select>
				</div>

				<div class="cerb-ui-form--field">
					<label class="cerb-ui-form--label"><a class="cerb-chooser cerb-u-cursor-pointer" data-context="{$widget->extension_params.context}" data-single="true">ID</a></label>
					<input type="text" name="params[context_id]" value="{$widget->extension_params.context_id}" class="placeholders" autocomplete="off" spellcheck="false">
				</div>
			</div>
		</div>
	</div>

	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">{'common.options'|devblocks_translate|capitalize}:</div>
		</div>

		<div class="cerb-ui-form">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Max. Height <span class="cerb-ui-form--hint">(pixels)</span></label>
				<input type="text" name="params[height]" value="{$widget->extension_params.height}" class="placeholders" autocomplete="off" spellcheck="false">
			</div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');
	var $select = $config.find("select[name='params[context]']");
	var $label_context_id = $config.find('a.cerb-chooser');
	var $input_context_id = $config.find('input[name="params[context_id]"]');

	// Record type — SelectMenu (type-to-filter + per-type icons). Keeps the native <select>,
	// so its change event still drives the ID chooser below.
	if(window.CerbUI && CerbUI.SelectMenu)
		$select.each(function() { new CerbUI.SelectMenu(this, { filter: true }); });

	$select.on('change', function(e) {
		var context = $(this).val();

		if(0 == context.length) {
			return;
		}

		$label_context_id.attr('data-context', context);
	});

	if(window.CerbUI && CerbUI.RecordChooser) CerbUI.RecordChooser.pickerLink($config.find('.cerb-chooser')[0], { input: $input_context_id });
});
</script>