<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.context'|devblocks_translate|capitalize}<span class="cerb-ui-form--hint">e.g. "ticket"</span> {include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/records/types/"}</label>
	<input type="text" name="{$namePrefix}[context]" class="placeholders" spellcheck="false" value="{$params.context}" placeholder="e.g. ticket">
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">{'common.id'|devblocks_translate}<span class="cerb-ui-form--hint">e.g. "123"</span></label>
	<input type="text" name="{$namePrefix}[id]" class="placeholders" spellcheck="false" value="{$params.id}" placeholder="e.g. 123">
</div>

<div class="cerb-ui-form--field">
	<label class="cerb-ui-form--label">Save record dictionary to a placeholder named</label>
	<div>
		&#123;&#123;<input type="text" name="{$namePrefix}[object_placeholder]" value="{$params.object_placeholder|default:"_record"}" required="required" spellcheck="false" size="32" placeholder="e.g. _record">&#125;&#125;
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
});
</script>
