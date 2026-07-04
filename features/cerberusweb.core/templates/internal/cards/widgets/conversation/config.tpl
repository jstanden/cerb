<div id="widget{$widget->id}Config" class="cerb-u-mt-3">
	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Display this record:</div>
		</div>

		<div class="cerb-ui-form">
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
				<label class="cerb-ui-form--label"><a class="cerb-chooser" data-context="{$widget->extension_params.context}" data-single="true">ID</a></label>
				<input type="text" name="params[context_id]" value="{$widget->extension_params.context_id}" class="placeholders" autocomplete="off" spellcheck="false">
			</div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $config = $('#widget{$widget->id}Config');
	const $link = $config.find('.cerb-chooser');
	const $input = $config.find('input[name="params[context_id]"]');
	const $type = $config.find('select[name="params[context]"]');

	// The record Type drives which context the ID chooser searches; keep the link in sync (pickerLink reads it live).
	$type.on('change', function() { $link.attr('data-context', this.value); });

	// Record type — SelectMenu (type-to-filter + per-type icons). Keeps the native <select>,
	// so its change event still drives the ID chooser below.
	if(window.CerbUI && CerbUI.SelectMenu)
		$type.each(function() { new CerbUI.SelectMenu(this, { filter: true }); });

	if(window.CerbUI && CerbUI.RecordChooser)
		CerbUI.RecordChooser.pickerLink($link[0], { input: $input });
});
</script>
