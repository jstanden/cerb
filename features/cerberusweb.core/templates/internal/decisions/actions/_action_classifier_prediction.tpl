<b>{'common.classifier'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<select name="{$namePrefix}[classifier_id]">
		<option value=""></option>
		{foreach from=$classifiers item=classifier key=classifier_id}
			<option value="{$classifier_id}" {if $classifier_id==$params.classifier_id}selected="selected"{/if}>{$classifier->name}</option>
		{/foreach}
	</select>
</div>

<b>{'common.text'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<textarea rows="3" cols="60" name="{$namePrefix}[content]" style="width:100%;white-space:pre;word-wrap:normal;" class="placeholders" spellcheck="false">{$params.content}</textarea>
</div>

<b>Save prediction result to a placeholder named:</b><br>
<div style="margin-left:10px;margin-bottom:10px;">
	&#123;&#123;<input type="text" name="{$namePrefix}[object_placeholder]" value="{$params.object_placeholder|default:"_prediction"}" required="required" spellcheck="false" size="32" placeholder="e.g. _prediction">&#125;&#125;
</div>

{*
<script type="text/javascript">
var $action = $('fieldset#{$namePrefix}');
</script>
*}