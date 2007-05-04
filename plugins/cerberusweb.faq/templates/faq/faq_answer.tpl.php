<div style='margin:5px;margin-left:20px;padding:5px;border:1px dashed rgb(80,80,80);background-color:rgb(255,255,255);'>
{assign var=answer value=$faq->getAnswer()}
{$answer|markdown}
<b>Copy:</b><br>
<textarea id="faqAnswerText{$faq->id}" rows="5" cols="45" style="display:block;width:100%;">{$answer}</textarea>
</div>
<div align="right">
	[ <a href="javascript:;" onclick="clearDiv('faqSearchAnswer{$faq->id}');">close</a> ]
</div>
