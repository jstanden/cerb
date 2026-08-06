<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Base URL</label>
			<input type="text" name="params[api_base_url]" value="{$params.api_base_url}" spellcheck="false">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">API Key Name</label>
			<input type="text" name="params[api_key_name]" value="{$params.api_key_name}" placeholder="e.g. apikey" spellcheck="false">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">API Key Location</label>
			<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-3">
				<label class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
					<input type="radio" name="params[api_key_location]" value="url" {if !$params.api_key_location || 'url' == $params.api_key_location}checked="checked"{/if}>
					URL Parameter
				</label>
				<label class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
					<input type="radio" name="params[api_key_location]" value="header" {if 'header' == $params.api_key_location}checked="checked"{/if}>
					HTTP Header
				</label>
			</div>
		</div>
	</div>
</div>
