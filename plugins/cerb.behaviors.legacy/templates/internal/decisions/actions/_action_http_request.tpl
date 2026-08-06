<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.url'|devblocks_translate|upper}</label>
	{$verbs = [get,post,put,patch,head,options,delete]}
	<select name="{$namePrefix}[http_verb]" class="cerb-httprequest-verb">
		{foreach from=$verbs item=verb}
		<option value="{$verb}" {if $params.http_verb == $verb}selected="selected"{/if}>{$verb|upper}</option>
		{/foreach}
	</select>

	<textarea name="{$namePrefix}[http_url]" class="placeholders" spellcheck="false" rows="5" placeholder="e.g. http://example.com/api/request.json">{$params.http_url|default:""}</textarea>
</div>

<div class="cerb-httprequest-headers">
	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">Request headers</label>
		<textarea rows="3" name="{$namePrefix}[http_headers]" style="white-space:pre;word-wrap:normal;" class="placeholders" spellcheck="false">{$params.http_headers}</textarea>
	</div>
</div>

<div class="cerb-httprequest-body" style="{if !in_array($params.http_verb,[post,put,patch])}display:none;{/if}">
	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">Request body</label>
		<textarea rows="3" name="{$namePrefix}[http_body]" style="white-space:pre;word-wrap:normal;" class="placeholders" spellcheck="false">{$params.http_body}</textarea>
	</div>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Authentication</label>
	<div>
		<label><input type="radio" name="{$namePrefix}[auth]" value="" {if !$params.auth}checked="checked"{/if}> {'common.none'|devblocks_translate|capitalize}</label>
		<label><input type="radio" name="{$namePrefix}[auth]" value="connected_account" {if 'connected_account' == $params.auth}checked="checked"{/if}> {'common.connected_account'|devblocks_translate|capitalize}</label>
		<label><input type="radio" name="{$namePrefix}[auth]" value="placeholder" {if 'placeholder' == $params.auth}checked="checked"{/if}> {'common.placeholder'|devblocks_translate|capitalize}</label>
	</div>
</div>

<div class="cerb-httprequest-connected-account" style="{if 'connected_account' != $params.auth}display:none;{/if}">
	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label">{'common.connected_account'|devblocks_translate|capitalize}</label>
		<div class="cerb-ui-record-chooser cerb-record-chooser-account">
			{if $params.auth_connected_account_id}
				{$account = DAO_ConnectedAccount::get($params.auth_connected_account_id)}
				{if $account && CerberusContexts::isWriteableByActor(CerberusContexts::CONTEXT_CONNECTED_ACCOUNT, $account, $trigger->getBot())}
				<li data-context="{CerberusContexts::CONTEXT_CONNECTED_ACCOUNT}" data-context-id="{$account->id}" data-label="{$account->name}"></li>
				{/if}
			{/if}
		</div>
	</div>
</div>

<div class="cerb-httprequest-placeholder" style="{if 'placeholder' != $params.auth}display:none;{/if}">
	<div class="cerb-ui-form--field">
		<label class="cerb-ui-form--label"><a class="chooser-account" data-field-name="{$namePrefix}[auth_placeholder]" data-context="{CerberusContexts::CONTEXT_CONNECTED_ACCOUNT}" data-single="true" data-query="">{'common.connected_account'|devblocks_translate|capitalize} {'common.id'|devblocks_translate}</a></label>
		<textarea name="{$namePrefix}[auth_placeholder]" class="placeholders" spellcheck="false" rows="5" placeholder="e.g. {literal}{{connected_account_id}}{/literal}">{$params.auth_placeholder}</textarea>
	</div>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.options'|devblocks_translate|capitalize}</label>
	<div>
		<label><input type="checkbox" name="{$namePrefix}[options][ignore_ssl_validation]" value="1" {if $params.options.ignore_ssl_validation}checked="checked"{/if}> Ignore SSL certificate validation (e.g. self-signed)</label>
		<br>
		<label><input type="checkbox" name="{$namePrefix}[options][raw_response_body]" value="1" {if $params.options.raw_response_body}checked="checked"{/if}> Don't attempt to auto-convert the response body (e.g. JSON decode)</label>
	</div>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Also execute HTTP request in simulator mode</label>
	<div>
		<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="1" {if $params.run_in_simulator}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}</label>
		<label><input type="radio" name="{$namePrefix}[run_in_simulator]" value="0" {if !$params.run_in_simulator}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}</label>
	</div>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Save response to a placeholder named</label>
	<div class="cerb-u-flex cerb-u-items-center cerb-u-gap-1">
		&#123;&#123;<input type="text" name="{$namePrefix}[response_placeholder]" value="{$params.response_placeholder|default:"_http_response"}" required="required" spellcheck="false" size="32" placeholder="e.g. _http_response">&#125;&#125;
	</div>
	<div class="cerb-ui-form--help">(with properties: <tt>.content_type</tt> &nbsp; <tt>.body</tt> &nbsp; <tt>.info.http_code</tt> &nbsp; <tt>.error</tt>)</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');

	var $div_account = $action.find('div.cerb-httprequest-connected-account');
	var $div_placeholder = $action.find('div.cerb-httprequest-placeholder');

	$action.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;

	if(window.CerbUI && CerbUI.RecordChooser) {
		$action.find('.cerb-record-chooser-account').each(function() {
			new CerbUI.RecordChooser(this, {
				context: '{CerberusContexts::CONTEXT_CONNECTED_ACCOUNT}',
				name: '{$namePrefix}[auth_connected_account_id]',
				emptyIcon: 'key'
			});
		});

		// "Connected Account ID:" link picks an account and inserts its id into the placeholder-capable textarea
		$action.find('.chooser-account').each(function() {
			CerbUI.RecordChooser.pickerLink(this, { input: $div_placeholder.find('textarea[name="{$namePrefix}[auth_placeholder]"]')[0] });
		});
	}

	$action.find('select.cerb-httprequest-verb').change(function() {
		var $div_httpbody = $action.find('div.cerb-httprequest-body');
		var val = $(this).val();

		if(val == 'post' || val == 'put' || val == 'patch')
			$div_httpbody.show();
		else
			$div_httpbody.fadeOut();
	});

	$action.find('input[name="{$namePrefix}[auth]"]').change(function() {
		var val = $(this).val();

		if(val == 'connected_account') {
			$div_account.show();
			$div_placeholder.hide();
		} else if(val == 'placeholder') {
			$div_account.hide();
			$div_placeholder.show();
		} else {
			$div_account.fadeOut();
			$div_placeholder.fadeOut();
		}
	});
});
</script>
