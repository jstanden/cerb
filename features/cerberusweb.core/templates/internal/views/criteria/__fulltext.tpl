<input type="hidden" name="oper" value="fulltext">

<b>{$translate->_('common.text')|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<input type="text" name="value" value="{$param->value.0}" style="width:100%;"><br>
	
	<label><input type="radio" name="scope" value="all" {if $param && $param->value.1=='all'}checked="checked"{/if} onclick="$('#fulltext_expert').hide();"> all of these words</label><br>
	<label><input type="radio" name="scope" value="any" {if $param && $param->value.1=='any'}checked="checked"{/if} onclick="$('#fulltext_expert').hide();"> any of these words</label><br>
	<label><input type="radio" name="scope" value="phrase" {if $param && $param->value.1=='phrase'}checked="checked"{/if} onclick="$('#fulltext_expert').hide();"> exact phrase</label><br>
	<label><input type="radio" name="scope" value="expert" {if $param && $param->value.1=='expert'}checked="checked"{/if} onclick="$('#fulltext_expert').show();"> expert mode</label><br>
	
	<div id="fulltext_expert" style="display:none;padding-left:10px;padding-top:5px;">
		{$translate->_('search.fulltext.examples')|nl2br nofilter}
	</div>
</blockquote>

<script type="text/javascript">
	$('#fulltext_expert').closest('blockquote').find('input:text:first').focus();
</script>
