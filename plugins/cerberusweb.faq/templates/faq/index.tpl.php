<div id="pageFaq" style="background-color:rgb(255,255,255);">
{include file="file:$path/faq/menu.tpl.php"}

<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tbody>
    <tr>
      <td nowrap="nowrap" width="100%" valign="top">

		<div style="border:1px solid rgb(120,120,120);padding:10px;background-color:rgb(245,245,245);">
		<a href="javascript:;" onclick="genericAjaxPanel('c=faq&a=showFaqPanel&id=0',this,true,'500px');" style="font-size:18px;font-weight:bold;color:rgb(0,180,0);">Have a new question?  Click here.</a>
		</div>
		<br>
		
		<!-- 
		<form action="{devblocks_url}{/devblocks_url}" method="post">
			<input type="hidden" name="c" value="faq">
			<input type="hidden" name="a" value="ask">
			<input type="text" name="question" value="" size="45">
			<input type="submit" value="Ask"><br>
			<br>
		</form>
		 -->

		<h2>Search</h2>
		<blockquote style="margin-top:0px;">
		<form action="javascript:;" onsubmit="genericAjaxPanel('c=faq&a=showFaqSearchPanel&q='+this.q.value,this,true,'500px');">
			<input type="hidden" name="c" value="">
			<input type="hidden" name="a" value="">
			<input type="text" name="q" value="" style="font-size:18px;">
			<input type="submit" value="{$translate->_('common.search')}">
		</form>
		</blockquote>
		 
      	<h2>Answers Needed</h2>
      	<ul style="margin-top:0px;">
      	{foreach from=$faqs item=faq name=faqs key=faq_id}
      		<li>
      		<a href="javascript:;" onclick="genericAjaxPanel('c=faq&a=showFaqPanel&id={$faq_id}',this,false,'500px');" style="color:rgb(0, 102, 255);font-weight:normal;">{$faq.f_question}</a>
      		{if $smarty.foreach.faqs.last}
      			<br><a href="#"><b>Read more...</b></a>
      		{/if}
      		</li>
      	{foreachelse}
      		No new questions have been asked.
      	{/foreach}
      	</ul>

      	<h2>Most Popular Answers</h2>
      	<ul style="margin-top:0px;">
      	{foreach from=$popular_faqs item=faq name=pfaqs key=faq_id}
      		<li>
      		<a href="javascript:;" onclick="genericAjaxPanel('c=faq&a=showFaqPanel&id={$faq_id}',this,false,'500px');" style="color:rgb(0, 102, 255);font-weight:normal;">{$faq.f_question}</a>
      		{if $smarty.foreach.pfaqs.last}
      			<br><a href="#"><b>Read more...</b></a>
      		{/if}
      		</li>
      	{foreachelse}
      		No answers have been submitted.
      	{/foreach}
      	</ul>
      	
      </td>
    </tr>
  </tbody>
</table>
</div>