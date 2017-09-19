<b>Name:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<textarea rows="1" cols="60" name="{$namePrefix}[name]" style="width:100%;white-space:pre;word-wrap:normal;" class="placeholders" spellcheck="false">{$params.name}</textarea>
</div>

<b>Value:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<textarea rows="1" cols="60" name="{$namePrefix}[value]" style="width:100%;white-space:pre;word-wrap:normal;" class="placeholders" spellcheck="false">{$params.value}</textarea>
</div>

<script type="text/javascript">
$(function() {
	var $action = $('fieldset#{$namePrefix}');
	$action.find('textarea').autosize();
});
</script>
