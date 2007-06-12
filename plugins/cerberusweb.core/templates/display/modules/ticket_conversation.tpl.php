<div id="tourDisplayConversation"></div>
<!-- <h2>Ticket Conversation</h2> -->
{if !empty($ticket)}
{foreach from=$ticket->getMessages() item=message name=messages}
{if $smarty.foreach.messages.last}<a name="latest"></a>{/if}
<!-- class="displayConversationTable" -->
<div class="block" id="{$ticket->id}t">
<table style="text-align: left; width: 100%;" border="0" cellpadding="2" cellspacing="0">
  <tbody>
    <tr>
      <td>
      {if isset($message->headers.from)}<h3>From: {$message->headers.from|escape:"htmlall"|nl2br}</h3>{/if}
      {if isset($message->headers.to)}<b>To:</b> {$message->headers.to|escape:"htmlall"|nl2br}<br>{/if}
      {if isset($message->headers.subject)}<b>Subject:</b> {$message->headers.subject|escape:"htmlall"|nl2br}<br>{/if}
      {if isset($message->headers.date)}<b>Date:</b> {$message->headers.date|escape:"htmlall"|nl2br}<br>{/if}
      
      <a href="#{$ticket->id}b">skip to end</a><br>

      {* // [TODO] Move this to an Ajax packet for full headers 
      	{if is_array($message->headers)}
      	{foreach from=$message->headers item=headerValue key=headerKey}
      		<b>{$headerKey|capitalize}:</b> 
      		{if is_array($headerValue)}
      			{foreach from=$headerValue item=subHeader}
      				&nbsp;&nbsp;&nbsp;{$subHeader|escape:"htmlall"|nl2br}<br>
      			{/foreach}
      		{else}
      			{$headerValue|escape:"htmlall"|nl2br}<br>
      		{/if}
      	{/foreach}
      	{/if}
      	*}
      
      	<br>
      	{$message->getContent()|trim|escape:"htmlall"|nl2br}<br>
      	<br>
      	
      	<table width="100%" cellpadding="0" cellspacing="0" border="0">
      		<tr>
      			<td align="left">
			      	<button type="button" onclick="displayAjax.reply('{$message->id}');">Reply</button>
			      	<a href="javascript:;">more &raquo;</a>
      			</td>
      			<td align="right">
      				[ <a href="#{$ticket->id}t">top</a> ]
      			</td>
      		</tr>
      	</table>
      	
      	<!-- [ <a href="javascript:;" onclick="displayAjax.reply('{$message->id}');" style="color: rgb(0, 102, 255);font-weight:bold;">Reply</a> ]  -->
      	<!--  
      	[ <a href="javascript:;" onclick="displayAjax.forward('{$message->id}');" style="color: rgb(0, 102, 255);font-weight:bold;">Forward</a> ] 
      	[ <a href="javascript:;" onclick="displayAjax.comment('{$message->id}');" style="color: rgb(0, 102, 255);font-weight:bold;">Comment</a> ]
      	[ <a href="javascript:;" onclick="displayAjax.change('{$message->id}');" style="color: rgb(0, 102, 255);font-weight:bold;">Change</a> ]
      	 -->
      	<!-- [ <a href="#">More Options...</a> ] --> 
      	<br>
      	
      	{assign var=attachments value=$message->getAttachments()}
      	{if !empty($attachments)}
      	<b>Attachments:</b><br>
      	<ul style="margin-top:0px;margin-bottom:5px;">
      		{foreach from=$attachments item=attachment name=attachments}
				<li><a href="{devblocks_url}c=files&p={$attachment->filepath}&name={$attachment->display_name}{/devblocks_url}">{$attachment->display_name}</a></li>
				<!-- {if !$smarty.foreach.requesters.last}, {/if}-->
			{/foreach}<br>
		</ul>
		{/if}
      </td>
    </tr>
  </tbody>
</table>
</div>
<div id="{$ticket->id}b"></div>
<form id="reply{$message->id}" action="{devblocks_url}{/devblocks_url}" method="POST" enctype="multipart/form-data"></form>
{if !$smarty.foreach.messages.last}<br>{/if}
{/foreach}
{else}
  No messages on ticket.
  <br>
{/if}