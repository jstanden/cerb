<div class="cerb-ui-form">
	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">URL</label>
		<label class="cerb-ui-form--control">
			<span class="cerb-ui-form--control-icon cerb-icons cerb-icon-link"></span>
			<input type="text" name="params[url]" value="{$params.url}" placeholder="https://">
		</label>
	</div>

	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">Format</label>
		<select name="params[url_format]">
			<option value="">(auto)</option>
			<option value="text/json" {if $params.url_format == 'text/json'}selected="selected"{/if}>JSON</option>
			<option value="text/xml" {if $params.url_format == 'text/xml'}selected="selected"{/if}>XML</option>
			<option value="text/csv" {if $params.url_format == 'text/csv'}selected="selected"{/if}>CSV</option>
			<option value="text/plain" {if $params.url_format == 'text/plain'}selected="selected"{/if}>Raw</option>
		</select>
	</div>

	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">Cache</label>
		<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1 cerb-u-flex-wrap">
			<span class="cerb-u-text-muted">results for</span>
			<input type="text" name="params[url_cache_mins]" value="{$params.url_cache_mins|number_format}" maxlength="3" style="width:5em;flex:0 0 auto;">
			<span class="cerb-u-text-muted">minute(s)</span>
		</div>
	</div>
</div>
