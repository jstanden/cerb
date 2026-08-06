{$uniqid = uniqid()}
<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Access Key ID</label>
			<input type="text" name="params[access_key]" value="{$params.access_key}" spellcheck="false">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Secret Access Key</label>
			<input type="password" name="params[secret_key]" value="{$params.secret_key}" autocomplete="off" spellcheck="false">
		</div>

		<div class="cerb-ui-form--field">
			<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-2">
				<label class="cerb-ui-toggle">
					<input type="checkbox" name="params[allow_non_aws_hosts]" id="allowNonAwsHosts_{$uniqid}" value="1" {if $params.allow_non_aws_hosts}checked{/if}>
					<span class="cerb-ui-toggle--slider"></span>
				</label>
				<label for="allowNonAwsHosts_{$uniqid}">Allow requests to non-AWS endpoints (default is <code>*.amazonaws.com</code> only)</label>
			</div>
		</div>
	</div>
</div>
