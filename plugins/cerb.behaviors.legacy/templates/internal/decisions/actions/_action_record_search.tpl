<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.context'|devblocks_translate|capitalize}<span class="cerb-ui-form--hint">e.g. "ticket"</span> {include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/records/types/"}</label>
	<input type="text" name="{$namePrefix}[context]" class="placeholders" spellcheck="false" value="{$params.context}" placeholder="e.g. ticket">
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.query'|devblocks_translate}<span class="cerb-ui-form--hint">e.g. <tt>status:o</tt></span> {include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/search/"}</label>
	<textarea name="{$namePrefix}[query]" class="placeholders" spellcheck="false">{$params.query}</textarea>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Keys to expand<span class="cerb-ui-form--hint">one per line; e.g. <tt>custom_</tt>, <tt>owner_</tt></span></label>
	<textarea name="{$namePrefix}[expand]" class="placeholders" spellcheck="false">{$params.expand}</textarea>
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Save record dictionaries to a placeholder named</label>
	<div>
		&#123;&#123;<input type="text" name="{$namePrefix}[object_placeholder]" value="{$params.object_placeholder|default:"_records"}" required="required" spellcheck="false" size="32" placeholder="e.g. _records">&#125;&#125;
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
});
</script>
