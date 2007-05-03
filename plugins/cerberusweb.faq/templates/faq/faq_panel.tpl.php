<div id="faqPanel">
<table cellpadding="0" cellspacing="0" border="0" width="98%">
	<tr>
		<td align="left" width="0%" nowrap="nowrap"><img src="{devblocks_url}images/help.gif{/devblocks_url}" align="absmiddle">&nbsp;</td>
		<td align="left" width="100%" nowrap="nowrap"><h1>FAQ</h1></td>
		<td align="right" width="0%" nowrap="nowrap"><form><input type="button" value=" X " onclick="genericPanel.hide();"></form></td>
	</tr>
</table>

{* View Mode *}
<div id="faqPanelView" style="display:{if $faq->id && $faq->is_answered}block{else}none{/if};">
<h2>Q: {$faq->question}</h2>
<div style="height:150px;overflow:auto;border:1px solid rgb(180,180,180);background-color:rgb(255,255,255);margin:2px;padding:3px;">
	{if $faq}{$faq->getAnswer()|markdown}{/if}
</div>
<a href="javascript:;" onclick="toggleDiv('faqPanelEdit','block');toggleDiv('faqPanelView','none');">edit answer</a>
</div>

{* Edit Mode *}
<div id="faqPanelEdit" style="display:{if $faq->id && $faq->is_answered}none{else}block{/if};">
<form action="{devblocks_url}{/devblocks_url}" id="formFaqAnswer">
<input type="hidden" name="c" value="faq">
<input type="hidden" name="a" value="answer">
<input type="hidden" name="id" value="{$faq->id}">
	<b>Topic?</b><br>
	<select name="topic">
		<option value="">Cerberus Helpdesk</option>
		<option value="">PortSensor</option>
		<option value="">WebGroup Media LLC.</option>
		<option value=""> -- None of these --</option>
	</select><br>
	<br>

	<b>Question:</b><br>
	<input type="text" name="question" size="45" style="width:98%;font-size:18px;" value="{$faq->question|escape:"htmlall"}"><br>
	<br>

	<b>Answer:</b> (optional) [ <a href="http://daringfireball.net/projects/markdown/dingus" target="_blank">formatting guide</a> ]<br>
	<textarea style="width:98%;" cols="45" rows="5" name="answer">{if !empty($faq)}{$faq->getAnswer()}{/if}</textarea><br>
	<input type="button" value="{$translate->_('common.save_changes')}" onclick="saveGenericAjaxPanel('formFaqAnswer',true);">
	{if $faq->id}<input type="button" value="Cancel" onclick="toggleDiv('faqPanelEdit','none');toggleDiv('faqPanelView','block');">{/if}
	{if $faq->id}<input type="hidden" name="delete" value="0"><input type="button" value="Delete" onclick="if(confirm('Are you sure?')){literal}{this.form.delete.value='1';saveGenericAjaxPanel('formFaqAnswer',true);}{/literal}">{/if}
	<br>
</form>
</div>

</div>