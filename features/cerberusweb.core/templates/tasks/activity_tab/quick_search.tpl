<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="tasks">
<input type="hidden" name="a" value="doQuickSearch">
<span><b>{$translate->_('common.quick_search')|capitalize}:</b></span><!--
--><input type="hidden" name="type" value="title">{* There's only one right now *}<!--
--><input type="text" name="query" class="input_search" size="24"><button type="submit">{$translate->_('common.search_go')|lower}</button>
</form>