<b>{'common.context'|devblocks_translate|capitalize}:</b> <i>(e.g. "ticket")</i> {include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/records/types/"}
<div style="margin-left:10px;margin-bottom:10px;">
	<input type="text" name="{$namePrefix}[context]" class="placeholders" spellcheck="false" style="width:100%;" value="{$params.context}" placeholder="e.g. ticket">
</div>

<b>{'common.query'|devblocks_translate}:</b> <i>(e.g. <tt>status:o</tt>)</i> {include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/docs/search/"}
<div style="margin-left:10px;margin-bottom:10px;">
	<textarea name="{$namePrefix}[query]" class="placeholders" spellcheck="false" style="width:100%;">{$params.query}</textarea>
</div>

<b>Keys to expand:</b> <i>(one per line; e.g. <tt>custom_</tt>, <tt>owner_</tt>)</i>
<div style="margin-left:10px;margin-bottom:10px;">
	<textarea name="{$namePrefix}[expand]" class="placeholders" spellcheck="false" style="width:100%;">{$params.expand}</textarea>
</div>

<b>Save record dictionaries to a placeholder named:</b><br>
<div style="margin-left:10px;margin-bottom:10px;">
	&#123;&#123;<input type="text" name="{$namePrefix}[object_placeholder]" value="{$params.object_placeholder|default:"_records"}" required="required" spellcheck="false" size="32" placeholder="e.g. _records">&#125;&#125;
</div>

<script type="text/javascript">
$(function() {
	var $action = $('#{$namePrefix}_{$nonce}');
});
</script>
