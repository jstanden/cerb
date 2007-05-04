<table cellpadding="2" cellspacing="0" width="100%">
	<tr style="background-color:rgb(240,240,240);">
		<td style="border-bottom:1px solid rgb(200,200,200);" align="left"><b>Question</b></td>
	</tr>

	{foreach from=$fnr_faqs item=faq name=faqs key=faq_id}
	<tr>
		<td width="100%" valign="top" style="border-bottom:1px solid rgb(220,220,220);">
			<img src="{devblocks_url}images/help.gif{/devblocks_url}" align="absmiddle"> 
			<a href="javascript:;" onclick="genericAjaxPanel('c=faq&a=showFaqPanel&id={$faq_id}',this,false,'500px');" style="color:rgb(0, 102, 255);font-weight:normal;">{$faq.f_question}</a>
		</td>
	</tr>
	{/foreach}
</table>

<input type="text" name="q" value="">
<input type="submit" value="{$translate->_('common.search')}" onclick="genericAjaxPanel('c=faq&a=showFaqSearchPanel&q='+this.form.q.value,this,true,'500px');">

[ <a href="javascript:;" onclick="genericAjaxPanel('c=faq&a=showFaqPanel&id=0',this,true,'500px');">have a new question?</a> ]

