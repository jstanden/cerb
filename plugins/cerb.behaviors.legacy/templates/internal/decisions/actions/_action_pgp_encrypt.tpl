<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.keys.public'|devblocks_translate|capitalize}</label>
	<div class="cerb-ui-record-chooser" id="{$namePrefix}_pubkeys_chooser">
		{if $public_keys}
			{foreach from=$public_keys item=public_key}
				{if CerberusContexts::isReadableByActor(CerberusContexts::CONTEXT_GPG_PUBLIC_KEY, $public_key, $trigger->getBot())}
					<li data-context-id="{$public_key->id}" data-label="{$public_key->name}"></li>
				{/if}
			{/foreach}
		{/if}
	</div>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Include key IDs from this template<span class="cerb-ui-form--hint">CSV</span></label>
	<textarea id="{$namePrefix}_pubkey_template_{$nonce}" name="{$namePrefix}[public_key_template]" data-editor-lines="6" spellcheck="false">{$params.public_key_template}</textarea>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.message'|devblocks_translate|capitalize}</label>
	<textarea id="{$namePrefix}_message_{$nonce}" name="{$namePrefix}[message]" data-editor-lines="20" spellcheck="false">{$params.message}</textarea>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Save result to a placeholder named</label>
	<div>
		&#123;&#123;<input type="text" name="{$namePrefix}[object_placeholder]" value="{$params.object_placeholder|default:"_results"}" required="required" spellcheck="false" size="32" placeholder="e.g. _results">&#125;&#125;
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');

	new CerbUI.ScriptingEditor($action.find('#{$namePrefix}_pubkey_template_{$nonce}')[0], { minLines: 2 });
	new CerbUI.ScriptingEditor($action.find('#{$namePrefix}_message_{$nonce}')[0], { minLines: 3 });

	if(window.CerbUI && CerbUI.RecordChooser)
		new CerbUI.RecordChooser($action.find('#{$namePrefix}_pubkeys_chooser')[0], { context: '{CerberusContexts::CONTEXT_GPG_PUBLIC_KEY}', name: '{$namePrefix}[public_key_ids]', multiple: true, emptyIcon: 'key' });

	$action.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
	;
});
</script>
