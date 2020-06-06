<b>{'common.keys.public'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<button type="button" class="chooser-abstract" data-field-name="{$namePrefix}[public_key_ids][]" data-context="{CerberusContexts::CONTEXT_GPG_PUBLIC_KEY}" data-query=""><span class="glyphicons glyphicons-search"></span></button>

	<ul class="bubbles chooser-container">
		{if $params.public_key_ids}
			{$public_keys = DAO_GpgPublicKey::getIds($params.public_key_ids)}
			{foreach from=$public_keys item=public_key}
				{if Context_GpgPublicKey::isReadableByActor($public_key, $trigger->getBot())}
					<li><input type="hidden" name="{$namePrefix}[public_key_ids][]" value="{$public_key->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_GPG_PUBLIC_KEY}" data-context-id="{$public_key->id}">{$public_key->name}</a></li>
				{/if}
			{/foreach}
		{/if}
	</ul>

	<div style="margin-top:10px;">
		<b>Include key IDs from this template:</b> (CSV)
		<br>
		<textarea name="{$namePrefix}[public_key_template]" data-editor-mode="ace/mode/twig" class="placeholders">{$params.public_key_template}</textarea>
	</div>
</div>

<b>{'common.message'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<textarea name="{$namePrefix}[message]" data-editor-mode="ace/mode/twig" class="placeholders">{$params.message}</textarea>
</div>

<b>Save result to a placeholder named:</b><br>
<div style="margin-left:10px;margin-bottom:10px;">
	&#123;&#123;<input type="text" name="{$namePrefix}[object_placeholder]" value="{$params.object_placeholder|default:"_results"}" required="required" spellcheck="false" size="32" placeholder="e.g. _results">&#125;&#125;
</div>

<script type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');

	$action.find('.chooser-abstract')
		.cerbChooserTrigger()
	;

	$action.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
	;
});
</script>
