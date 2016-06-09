<input type="hidden" name="oper" value="fulltext">

<b>{'common.search'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<input type="text" name="value" value="{$param->value.0}" autofocus="autofocus" style="width:100%;"><br>
	
	<div id="fulltext_expert" style="display:none;padding-left:10px;padding-top:5px;">
		{'search.fulltext.examples'|devblocks_translate|nl2br nofilter}
	</div>
</blockquote>