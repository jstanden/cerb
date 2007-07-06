<div id="tourDisplayConversation"></div>
<!-- <h2>Ticket Conversation</h2> -->
{if !empty($ticket)}
{foreach from=$ticket->getMessages() item=message name=messages}
{assign var=headers value=$message->getHeaders()}
{if $smarty.foreach.messages.last}<a name="latest"></a>{/if}
<!-- class="displayConversationTable" -->
<div class="block" id="{$message->id}t">
<table style="text-align: left; width: 100%;" border="0" cellpadding="2" cellspacing="0">
  <tbody>
    <tr>
      <td>
      <table cellspacing="0" cellpadding="0" width="100%" border="0">
      	<tr>
      		<td>
      			{if isset($headers.from)}<h3>From: {$headers.from|escape:"htmlall"|nl2br}</h3>{/if}
      		</td>
      		<td align="right">
      		  <a href="javascript:;" onclick="toggleDiv('{$message->id}sh');toggleDiv('{$message->id}h');">toggle headers</a>
      		   | 
      		
		      {if !$smarty.foreach.messages.last}
				<a href="javascript:;" onclick="toggleDiv('{$message->id}c');">toggle message</a>
			  {else}
			  	<a href="#{$message->id}b">skip to ending</a>
		      {/if}
      		</td>
      	</tr>
      </table>
      
	  <div id="{$message->id}sh" style="display:block;">      
      {if isset($headers.to)}<b>To:</b> {$headers.to|escape:"htmlall"|nl2br}<br>{/if}
      {if isset($headers.date)}<b>Date:</b> {$headers.date|escape:"htmlall"|nl2br}<br>{/if}
      </div>

	  <div id="{$message->id}h" style="display:none;">      
      	{if is_array($headers)}
      	{foreach from=$headers item=headerValue key=headerKey}
      		<b>{$headerKey|capitalize}:</b>
      		{* 
      		{if is_array($headerValue)}
      			{foreach from=$headerValue item=subHeader}
      				&nbsp;&nbsp;&nbsp;{$subHeader|escape:"htmlall"|nl2br}<br>
      			{/foreach}
      		{else}
      		{/if}
      		*}
   			{$headerValue|escape:"htmlall"|nl2br}<br>
      	{/foreach}
      	{/if}
      </div>
      
      <div id="{$message->id}c" style="display:{if $smarty.foreach.messages.last}block{else}none{/if};">
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
      				<a href="#top">top</a>
      			</td>
      		</tr>
      	</table>
      	<br>
      	
      	{assign var=attachments value=$message->getAttachments()}
      	{if !empty($attachments)}
      	<b>Attachments:</b><br>
      	<ul style="margin-top:0px;margin-bottom:5px;">
      		{foreach from=$attachments item=attachment name=attachments}
				<li>
					<a href="{devblocks_url}c=files&p={$attachment->id}&name={$attachment->display_name}{/devblocks_url}">{$attachment->display_name}</a>
					{assign var=bytes value=$attachment->file_size}
					( 
					{if !empty($attachment->file_size)} 
						{if $bytes > 1024000}
							{math equation="round(x/1024000)" x=$attachment->file_size} MB
						{elseif $bytes > 1048}
							{math equation="round(x/1048)" x=$attachment->file_size} KB
						{else}
							{$attachment->file_size} bytes
						{/if}
						- 
					{/if}
					{if !empty($attachment->mime_type)}{$attachment->mime_type}{else}unknown format{/if}
					 )
				</li>
				<!-- {if !$smarty.foreach.requesters.last}, {/if}-->
			{/foreach}<br>
		</ul>
		{/if}
		
		</div> <!-- end visible -->
      </td>
    </tr>
  </tbody>
</table>
</div>
<div id="{$message->id}b"></div>
<form id="reply{$message->id}" action="{devblocks_url}{/devblocks_url}" method="POST" enctype="multipart/form-data"></form>
{if !$smarty.foreach.messages.last}<br>{/if}
{/foreach}
{else}
  No messages on ticket.
  <br>
{/if}