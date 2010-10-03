<table cellspacing="0" cellpadding="0" border="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td valign="middle" align="right">
		<form action="{devblocks_url}{/devblocks_url}" method="post">
		<input type="hidden" name="c" value="kb.ajax">
		<input type="hidden" name="a" value="doArticleQuickSearch">
		<span><b>{$translate->_('common.search')|capitalize}:</b></span> <select name="type">
			<option value="articles_all">Articles (all words)</option>
			<option value="articles_phrase">Articles (phrase)</option>
		</select><input type="text" name="query" class="input_search" size="24"><button type="submit">go!</button>
		</form>
	</td>
</tr>
</table>

{include file="$core_tpl/internal/views/search_and_view.tpl" view=$view}