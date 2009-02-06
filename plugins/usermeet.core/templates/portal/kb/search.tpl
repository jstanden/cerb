{include file="$tpl_path/portal/kb/header.tpl"}

<div style="margin:10px;">
<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="a" value="doSearch">
<table style="border: 1px solid rgb(0, 128, 255); width: 100%; padding: 5px; background-color: rgb(237, 241, 255);" border="0" cellpadding="0" cellspacing="0">
  <tbody>
  <tr>
  	<td colspan="2"><h2 style="margin:0px;">{$translate->_('common.search')|capitalize}</h2></td>
  </tr>
  <tr>
  	<td width="1%" nowrap="nowrap"><b>{$translate->_('portal.kb.public.search_for')}</b>&nbsp; </td>
  	<td width="99%">
  		<input type="text" name="query" value="{$query|escape}" size="35" style="width:98%;">
  	</td>
  </tr>
  <tr>
  	<td width="1%" nowrap="nowrap" valign="top"><label><input type="checkbox" onclick="document.getElementById('kbSearchTips').style.display = (this.checked) ? 'block':'none';">{$translate->_('portal.kb.public.show_search_tips')}&nbsp;</label> </td>
  	<td width="99%">
  		<div id="kbSearchTips" style="display:none;" valign="top">
  			<br>
  			<b>{$translate->_('portal.kb.public.search_tips.any_word')}</b><br>
  			<i>{$translate->_('portal.kb.public.search_tips.any_word.example1')}</i><br>
  			<br>
  			<b>{$translate->_('portal.kb.public.search_tips.booleans')}</b><br>
  			<i>{$translate->_('portal.kb.public.search_tips.booleans.example1')}</i><br>
  			<i>{$translate->_('portal.kb.public.search_tips.booleans.example2')}</i><br>
  			<br>
  			<b>{$translate->_('portal.kb.public.search_tips.wildcards')}</b><br>
  			<i>{$translate->_('portal.kb.public.search_tips.wildcards.example1')}</i><br>
  			<br>
  			<b>{$translate->_('portal.kb.public.search_tips.exact_phrases')}</b><br>
  			<i>{$translate->_('portal.kb.public.search_tips.exact_phrases.example1')}</i><br>
  			<br>
  			<b>{$translate->_('portal.kb.public.search_tips.boosting')}</b><br>
  			<i>{$translate->_('portal.kb.public.search_tips.boosting.example1')}</i><br>
  			<i>{$translate->_('portal.kb.public.search_tips.boosting.example2')}</i><br>
  			<br>
  		</div>
  	</td>
  </tr>
  <tr>
  	<td colspan="2" align="right"><button type="submit"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/book_blue_view.gif{/devblocks_url}" alt="Search" align="top"> {$translate->_('common.search')|capitalize}</button></td>
  </tr>
  </tbody>
</table>
</form>

<br>

<table style="border-top: 1px solid rgb(0, 128, 255); border-left: 1px solid rgb(0, 128, 255); text-align: left; width: 100%;" border="0" cellpadding="3" cellspacing="0">
  <tbody>
    <tr>
      <td style="background-color: rgb(237, 241, 255);"><span style="font-weight: bold;">{$translate->_('common.search_results')}</span></td>
      <td style="background-color: rgb(237, 241, 255); text-align: right;"><a href="{devblocks_url}c=rss&a=search&q={$query|escape:"url"}{/devblocks_url}"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/feed-icon-16x16.gif{/devblocks_url}" alt="Search" align="top" border="0"></a></td>
    </tr>
    <tr>
      <td>
      {if !empty($articles)}
      <ul>
      	{foreach from=$articles item=article name=articles key=article_id}
        <li><span style="font-size:80%;color:rgb(0,150,0);">{*math equation="100*x" format="%d" x=$articlescore*}</span> <a href="{devblocks_url}c=article&id={$article.kb_id|string_format:"%06d"}{/devblocks_url}">{$article.kb_title}</a><br>
          <span style="font-size: 90%;">
	          {*$article.content|strip_tags|truncate:255:'...':false*}
	          <span style="color: rgb(120, 120, 120);">{devblocks_url full=true}c=article&id={$article.kb_id|string_format:"%06d"}{/devblocks_url}</span><br>
	          <br>
          </span>
        </li>
        {/foreach}
      </ul>
      {else}
      	{$translate->_('portal.public.no_results')}<br>
      {/if}
      </td>
    </tr>
  </tbody>
</table>
</div>

{include file="$tpl_path/portal/kb/footer.tpl"}
