{include file="$tpl_path/portal/kb/header.tpl.php"}

<div style="margin:10px;">
<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="a" value="doSearch">
<table style="border: 1px solid rgb(0, 128, 255); width: 100%; padding: 5px; background-color: rgb(237, 241, 255);" border="0" cellpadding="0" cellspacing="0">
  <tbody>
  <tr>
  	<td colspan="2"><h2 style="margin:0px;">Search</h2></td>
  </tr>
  <tr>
  	<td width="0%" nowrap="nowrap"><b>Search for:</b>&nbsp; </td>
  	<td width="100%">
  		<input type="text" name="query" value="{$query|escape}" size="35" style="width:98%;">
  	</td>
  </tr>
  <tr>
  	<td width="0%" nowrap="nowrap" valign="top"><label><input type="checkbox" onclick="document.getElementById('kbSearchTips').style.display = (this.checked) ? 'block':'none';">Show Search Tips&nbsp; </td>
  	<td width="100%">
  		<div id="kbSearchTips" style="display:none;" valign="top">
  			<br>
  			<b>Booleans:</b><br>
  			<i>payment AND problem</i><br>
  			<i>payment OR receipt</i><br>
  			<i>+payment -receipt</i><br>
  			<br>
  			<b>Groups:</b><br>
  			<i>(payment AND receipt) OR (credit AND receipt)</i><br>
  			<i>(payment OR credit) AND receipt</i><br>
  			<br>
  			<b>Fields:</b><br>
  			<i>title:(payment OR credit)</i><br>
  			<i>title:"payment" AND address</i><br>
  			<br>
  			<b>Proximity:</b> (terms within 'n' words)<br>
  			<i>"payment address"~5</i><br>
  			<br>
  			<b>Boosting:</b> (more emphasis on some words)<br>
  			<i>payment^5 information</i><br>
  			<br>
  		</div>
  	</td>
  </tr>
  <tr>
  	<td colspan="2" align="right"><button type="submit"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/book_blue_view.gif{/devblocks_url}" alt="Search" align="top"> Search</button></td>
  </tr>
  </tbody>
</table>
</form>

<br>

<table style="border-top: 1px solid rgb(0, 128, 255); border-left: 1px solid rgb(0, 128, 255); text-align: left; width: 100%;" border="0" cellpadding="3" cellspacing="0">
  <tbody>
    <tr>
      <td style="background-color: rgb(237, 241, 255);"><span style="font-weight: bold;">Search Results</span></td>
      <td style="background-color: rgb(237, 241, 255); text-align: right;"><a href="{devblocks_url}c=rss&a=search&q={$query|escape:"url"}{/devblocks_url}"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/feed-icon-16x16.gif{/devblocks_url}" alt="Search" align="top" border="0"></a></td>
    </tr>
    <tr>
      <td>
      {if !empty($articles)}
      <ul>
      	{foreach from=$articles item=article name=articles key=article_id}
        <li><span style="font-size:80%;color:rgb(0,150,0);">{math equation="100*x" format="%d" x=$article->score}%</span> <a href="{devblocks_url}c=article&id={$article->id|string_format:"%06d"}{/devblocks_url}">{$article->title}</a><br>
          <span style="font-size: 90%;">
	          {$article->content|strip_tags|truncate:255:'...':false}<br>
	          <span style="color: rgb(120, 120, 120);">{devblocks_url full=true}c=article&id={$article->id|string_format:"%06d"}{/devblocks_url}</span><br>
	          <br>
          </span>
        </li>
        {/foreach}
      </ul>
      {/if}
      </td>
    </tr>
  </tbody>
</table>
</div>

{include file="$tpl_path/portal/kb/footer.tpl.php"}