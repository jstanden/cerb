{assign var=headers value=$message->getHeaders()}
<div class="block">
<table style="text-align: left; width: 98%;table-layout: fixed;" border="0" cellpadding="2" cellspacing="0">
  <tbody>
    <tr>
      <td>
      <table cellspacing="0" cellpadding="0" width="100%" border="0">
      	<tr>
      		<td>
      			{assign var=sender_id value=$message->address_id}
      			{if isset($message_senders.$sender_id)}
      				{assign var=sender value=$message_senders.$sender_id}
      				{assign var=sender_org_id value=$sender->contact_org_id}
      				{assign var=sender_org value=$message_sender_orgs.$sender_org_id}
      				{assign var=is_outgoing value=$message->worker_id}
      				{if $expanded}
						<h3 style="display:inline;"><span style="{if !$is_outgoing}color:rgb(255,50,50);background-color:rgb(255,213,213);{else}color:rgb(50,120,50);background-color:rgb(219,255,190);{/if}">{if $is_outgoing}{$translate->_('mail.sent')|lower}{else}{$translate->_('mail.received')|lower}{/if}</span>
						{if $message->worker_id && isset($workers.{$message->worker_id})}
							{$msg_worker = $workers.{$message->worker_id}}
	      					<a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&email={$msg_worker->email|escape:'url'}', null, false, '500', function() { C4_ReloadMessageOnSave('{$message->id}', {if $expanded}true{else}false{/if}); } );" title="{$sender->email}">{if 0 != strlen($msg_worker->getName())}{$msg_worker->getName()}{else}&lt;{$msg_worker->email}&gt;{/if}</a>
						{else}
	      					<a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&address_id={$sender_id}', null, false, '500', function() { C4_ReloadMessageOnSave('{$message->id}', {if $expanded}true{else}false{/if}); } );" title="{$sender->email}">{if 0 != strlen($sender->getName())}{$sender->getName()}{else}&lt;{$sender->email}&gt;{/if}</a>
						{/if}
						</h3>
      				{else}
						<b><span style="{if !$is_outgoing}color:rgb(255,50,50);background-color:rgb(255,213,213);{else}color:rgb(50,120,50);background-color:rgb(219,255,190);{/if}">{if $is_outgoing}{$translate->_('mail.sent')|lower}{else}{$translate->_('mail.received')|lower}{/if}</span>
						{if $message->worker_id && isset($workers.{$message->worker_id})}
							{$msg_worker = $workers.{$message->worker_id}}
      						<a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&email={$msg_worker->email|escape:'url'}', null, false, '500', function() { C4_ReloadMessageOnSave('{$message->id}', {if $expanded}true{else}false{/if}); } );">{if 0 != strlen($msg_worker->getName())}{$msg_worker->getName()}{else}&lt;{$msg_worker->email}&gt;{/if}</a></b>
						{else}
      						<a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&address_id={$sender_id}', null, false, '500', function() { C4_ReloadMessageOnSave('{$message->id}', {if $expanded}true{else}false{/if}); } );">{if 0 != strlen($sender->getName())}{$sender->getName()}{else}&lt;{$sender->email}&gt;{/if}</a></b>
						{/if}
						</b>
      				{/if}
      				
      				&nbsp;
      				
      				{if $sender_org_id}
      					<a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showOrgPeek&id={$sender_org_id}', null, false, '500', function() { C4_ReloadMessageOnSave('{$message->id}',{if $expanded}true{else}false{/if}); } );"><small style="">{$sender_org->name}</small></a>
      				{else}{* No org *}
      					{if $active_worker->hasPriv('core.addybook.addy.actions.update')}<a href="javascript:;" onclick="genericAjaxPanel('c=contacts&a=showAddressPeek&address_id={$sender_id}', null, false, '500', function() { C4_ReloadMessageOnSave('{$message->id}', {if $expanded}true{else}false{/if}); } );"><small style="background-color:rgb(255,255,194);">{$translate->_('display.convo.set_org')|lower}</small></a>{/if}
      				{/if}
      				
      				<br>
      			{/if}
      		</td>
      		<td align="right">
      			<button id="btnMsgMax{$message->id}" style="display:none;visibility:hidden;" onclick="genericAjaxGet('{$message->id}t','c=display&a=getMessage&id={$message->id}');"></button>
      			<button id="btnMsgMin{$message->id}" style="display:none;visibility:hidden;" onclick="genericAjaxGet('{$message->id}t','c=display&a=getMessage&id={$message->id}&hide=1');"></button>
		      {if !$expanded}
				<a href="javascript:;" onclick="$('#btnMsgMax{$message->id}').click();">{$translate->_('common.maximize')|lower}</a>
			  {else}
			  	<a href="javascript:;" onclick="$('#btnMsgMin{$message->id}').click();">{$translate->_('common.minimize')|lower}</a>
      		  {/if}
      		</td>
      	</tr>
      </table>
      
	  <div id="{$message->id}sh" style="display:block;">      
      {if isset($headers.from)}<b>{$translate->_('message.header.from')|capitalize}:</b> {$headers.from|escape|nl2br}<br>{/if}
      {if isset($headers.to)}<b>{$translate->_('message.header.to')|capitalize}:</b> {$headers.to|escape|nl2br}<br>{/if}
      {if isset($headers.cc)}<b>{$translate->_('message.header.cc')|capitalize}:</b> {$headers.cc|escape|nl2br}<br>{/if}
      {if isset($headers.bcc)}<b>{$translate->_('message.header.bcc')|capitalize}:</b> {$headers.bcc|escape|nl2br}<br>{/if}      
      {if isset($headers.subject)}<b>{$translate->_('message.header.subject')|capitalize}:</b> {$headers.subject|escape|nl2br}<br>{/if}
      {if isset($headers.date)}<b>{$translate->_('message.header.date')|capitalize}:</b> {$headers.date|escape|nl2br}<br>{/if}
      </div>

	  <div id="{$message->id}h" style="display:none;">      
      	{if is_array($headers)}
      	{foreach from=$headers item=headerValue key=headerKey}
      		<b>{$headerKey|capitalize}:</b>
   			{$headerValue|escape|nl2br}<br>
      	{/foreach}
      	{/if}
      </div>
      
      {if $expanded}
      <div style="margin:2px;margin-left:10px;">
      	 <a href="javascript:;" class="brief" onclick="if($(this).hasClass('brief')) { $('#{$message->id}sh').hide();$('#{$message->id}h').show();$(this).html('{$translate->_('display.convo.brief_headers')|lower|escape}').removeClass('brief'); } else { $('#{$message->id}sh').show();$('#{$message->id}h').hide();$(this).html('{$translate->_('display.convo.full_headers')|lower|escape}').addClass('brief'); } ">{$translate->_('display.convo.full_headers')|lower|escape}</a>
      	 | <a href="#{$message->id}act">{$translate->_('display.convo.skip_to_bottom')|lower}</a>
      </div>
      {/if}
      
      <div style="display:block;">
      	{if $expanded}
    	  	<pre>{$message->getContent()|trim|escape|makehrefs}</pre>
    	  	<br>
	      	<table width="100%" cellpadding="0" cellspacing="0" border="0">
	      		<tr>
	      			<td align="left" id="{$message->id}act">
	      				{assign var=show_more value=0}
				      	{if $active_worker->hasPriv('core.display.actions.reply')}{if !empty($requesters)}{assign var=show_more value=1}<button {if $latest_message_id==$message->id}id="btnReplyFirst"{/if} type="button" onclick="displayReply('{$message->id}',0);"><span class="cerb-sprite sprite-export"></span> {$translate->_('display.ui.reply')|capitalize}</button>{/if}{/if}
				      	{if $active_worker->hasPriv('core.display.actions.forward')}{assign var=show_more value=1}<button type="button" onclick="displayReply('{$message->id}',1);"><span class="cerb-sprite sprite-document_out"></span> {$translate->_('display.ui.forward')|capitalize}</button>{/if}
				      	{if $active_worker->hasPriv('core.display.actions.note')}{assign var=show_more value=1}<button type="button" onclick="displayAddNote('{$message->id}');"><span class="cerb-sprite sprite-document_plain_yellow"></span> {$translate->_('display.ui.sticky_note')|capitalize}</button>{/if}
				      	
				      	{if $show_more} {* Only show more if we showed one of the built-in buttons first *}
				      	 &nbsp; 
			      		<button type="button" onclick="toggleDiv('{$message->id}options');">{$translate->_('common.more')|lower} &raquo;</button>
			      		{/if}
	      			</td>
	      		</tr>
	      	</table>
	      	
	      	<form id="{$message->id}options" style="padding-top:10px;display:{if $show_more}none{else}block{/if};" method="post" action="{devblocks_url}{/devblocks_url}">
	      		<input type="hidden" name="c" value="display">
	      		<input type="hidden" name="a" value="">
	      		<input type="hidden" name="id" value="{$message->id}">
	      		
	      		<button type="button" onclick="document.frmPrint.action='{devblocks_url}c=print&a=message&id={$message->id}{/devblocks_url}';document.frmPrint.submit();"><span class="cerb-sprite sprite-printer"></span> {$translate->_('common.print')|capitalize}</button>
	      		
	      		{if $ticket->first_message_id != $message->id && $active_worker->hasPriv('core.display.actions.split')} {* Don't allow splitting of a single message *}
	      		<button type="button" onclick="this.form.a.value='doSplitMessage';this.form.submit();" title="Split message into new ticket"><span class="cerb-sprite sprite-documents"></span> {$translate->_('display.button.split_ticket')|capitalize}</button>
	      		{/if}
	      		
				{* Plugin Toolbar *}
				{if !empty($message_toolbaritems)}
					{foreach from=$message_toolbaritems item=renderer}
						{if !empty($renderer)}{$renderer->render($message)}{/if}
					{/foreach}
				{/if}
	      	</form>
	      	
	      	{if $active_worker->hasPriv('core.display.actions.attachments.download')}
	      	{assign var=attachments value=$message->getAttachments()}
	      	{if !empty($attachments)}
	      	<br>
	      	<b>{$translate->_('display.convo.attachments_label')|capitalize}</b><br>
	      	<ul style="margin-top:0px;margin-bottom:5px;">
	      		{foreach from=$attachments item=attachment name=attachments}
					<li>
						<a href="{devblocks_url}c=files&p={$attachment->id}&name={$attachment->display_name|escape:'url'}{/devblocks_url}" target="_blank" style="font-weight:bold;color:rgb(50,120,50);">{$attachment->display_name}</a>
						(  
						{$attachment->storage_size|devblocks_prettybytes} 
						- 
						{if !empty($attachment->mime_type)}{$attachment->mime_type}{else}{$translate->_('display.convo.unknown_format')|capitalize}{/if}
						 )
					</li>
				{/foreach}<br>
			</ul>
			{/if}
			{/if}
      	{/if}
		
		</div> <!-- end visible -->
      </td>
    </tr>
  </tbody>
</table>
</div>
<div id="{$message->id}b"></div>
<div id="{$message->id}notes" style="background-color:rgb(255,255,255);">
	{include file="$core_tpl/display/modules/conversation/notes.tpl"}
</div>
<div id="reply{$message->id}"></div>
<br>

<script language="JavaScript1.2" type="text/javascript">
	function C4_ReloadMessageOnSave(msgid, expanded) {
		if(null==expanded)
			expanded=false;
		
		genericPanel.one('devblocks_dialogsaved',function(e) {
			if(expanded) 
				$('#btnMsgMax' + msgid).click();
			else 
				$('#btnMsgMin' + msgid).click();
		} );
	}
</script>
