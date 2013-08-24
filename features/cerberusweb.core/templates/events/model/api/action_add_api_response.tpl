<b>Generate response using this script:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	<textarea rows="3" cols="60" name="{$namePrefix}[value]" style="width:100%;white-space:pre;word-wrap:normal;" class="placeholders" spellcheck="false">{$params.value}</textarea>
</div>

<script type="text/javascript">
$action = $('fieldset#{$namePrefix}');
$action.find('textarea').elastic();
</script>
