<table cellspacing="0" cellpadding="0" border="0" width="100%" style="padding-bottom:5px;">
<tr>
	<td valign="middle" align="right">
		<form action="{devblocks_url}{/devblocks_url}" method="post">
		<input type="hidden" name="c" value="kb.ajax">
		<input type="hidden" name="a" value="doArticleQuickSearch">
		<span><b>{$translate->_('common.search')|capitalize}:</b></span> <select name="type">
			<option value="articles_all">Articles (all words)</option>
			<option value="articles_phrase">Articles (phrase)</option>
		</select><input type="text" name="query" size="24"><button type="submit">go!</button>
		</form>
	</td>
</tr>
</table>

<form action="#" method="POST" id="filter{$view->id}">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="">
<input type="hidden" name="id" value="{$view->id}">

<div id="viewCustomFilters{$view->id}" style="margin:10px;">
{include file="$core_tpl/internal/views/customize_view_criteria.tpl"}
</div>
</form>

<div id="view{$view->id}">{$view->render()}</div>

<script>
	$('#viewCustomFilters{$view->id}').bind('devblocks.refresh', function(event) {
		if(event.target == event.currentTarget)
			genericAjaxGet('view{$view->id}','c=internal&a=viewRefresh&id={$view->id|escape}');
	} );
</script>
