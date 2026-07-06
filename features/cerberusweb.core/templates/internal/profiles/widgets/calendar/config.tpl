{$dv = $widget->extension_params.default_view|default:'month'}
<div id="widget{$widget->id}Config" class="cerb-u-mt-3">
	<div class="cerb-ui-panel cerb-ui-panel--spaced">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Display this calendar</div>
		</div>

		<div class="cerb-ui-form">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label"><a class="cerb-chooser cerb-u-cursor-pointer" data-context="{CerberusContexts::CONTEXT_CALENDAR}" data-single="true">ID</a></label>
				<input type="text" name="params[context_id]" value="{$widget->extension_params.context_id}" class="placeholders" autocomplete="off" spellcheck="false">
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Default view</label>
				<select name="params[default_view]">
					<option value="day"{if $dv == 'day'} selected="selected"{/if}>Day</option>
					<option value="week"{if $dv == 'week'} selected="selected"{/if}>Week</option>
					<option value="month"{if $dv == 'month'} selected="selected"{/if}>Month</option>
					<option value="year"{if $dv == 'year'} selected="selected"{/if}>Year</option>
				</select>
			</div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $config = $('#widget{$widget->id}Config');
	const $input = $config.find('input[name="params[context_id]"]');

	if(window.CerbUI && CerbUI.RecordChooser) CerbUI.RecordChooser.pickerLink($config.find('.cerb-chooser')[0], { input: $input });
	if(window.CerbUI && CerbUI.SelectMenu) $config.find('select[name="params[default_view]"]').each(function() { new CerbUI.SelectMenu(this); });
});
</script>