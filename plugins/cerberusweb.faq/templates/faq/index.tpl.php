<div id="pageFaq" style="background-color:rgb(255,255,255);">
{include file="file:$path/faq/menu.tpl.php"}

<table border="0" cellpadding="0" cellspacing="0" width="100%">
  <tbody>
    <tr>
      <td nowrap="nowrap" width="100%" valign="top">

		<div class="block">
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

		<div class="block">
		<h3>Search</h3>
		<form action="javascript:;" onsubmit="genericAjaxPanel('c=faq&a=showFaqSearchPanel&q='+this.q.value,this,true,'500px');">
			<input type="hidden" name="c" value="">
			<input type="hidden" name="a" value="">
			<input type="text" name="q" value="" style="font-size:18px;" size="45">
			<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.faq&f=images/find.gif{/devblocks_url}" align="top"> {$translate->_('common.search')|capitalize}</button>
		</form>
		</div>
		<br>
		 
		<div class="block">
      	<h3>Answers Needed</h3>
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
      	</div>
      	<br>

		<div class="block">
      	<h3>Most Popular Answers</h3>
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
      	</div>
      	<br>
      	
      </td>
    </tr>
  </tbody>
</table>
</div>