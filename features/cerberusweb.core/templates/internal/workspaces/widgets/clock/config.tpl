{$uid = uniqid()}
<div id="widget{$widget->id}ConfigTabDatasource" class="cerb-u-mt-3">
	<div class="cerb-ui-panel cerb-ui-panel--spaced" id="widget{$widget->id}Datasource">
		<div class="cerb-ui-header cerb-ui-header--tight">
			<div class="cerb-ui-header--title-sm">Data source</div>
		</div>

		<div class="cerb-ui-form">
			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Timezone</label>
				<select name="params[timezone]" data-cerb-clock-timezone>
					{foreach from=$timezones item=timezone}
					<option value="{$timezone}" {if $widget->params.timezone == $timezone}selected="selected"{/if}>{$timezone}</option>
					{/foreach}
				</select>
			</div>

			<div class="cerb-ui-form--field">
				<label class="cerb-ui-form--label">Format</label>
				<div>
					<input type="hidden" name="params[format]" id="clockFormat{$uid}" value="{if empty($widget->params.format)}0{else}1{/if}">
					<div class="cerb-ui-switcher" data-cerb-input="clockFormat{$uid}">
						<button type="button" data-value="0" {if empty($widget->params.format)}class="cerb-ui-switcher--active"{/if}>12-hour</button>
						<button type="button" data-value="1" {if !empty($widget->params.format)}class="cerb-ui-switcher--active"{/if}>24-hour</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const formatInput = document.getElementById('clockFormat{$uid}');
	const formatSwitcher = document.querySelector('.cerb-ui-switcher[data-cerb-input="clockFormat{$uid}"]');

	if(formatSwitcher && window.CerbUI && CerbUI.Switcher) {
		new CerbUI.Switcher(formatSwitcher, {
			value: formatInput.value,
			onSelect: v => formatInput.value = v
		});
	}

	if(window.CerbUI && CerbUI.SelectMenu)
		document.querySelectorAll('select[data-cerb-clock-timezone]').forEach(function(el) { new CerbUI.SelectMenu(el, { filter: true }); });
});
</script>