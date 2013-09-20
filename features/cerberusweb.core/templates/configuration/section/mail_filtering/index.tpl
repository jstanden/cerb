<h2>Mail Filtering</h2>
{$only_event_ids = 'event.mail.received.app'}

{$has_atleast_one = false}
{foreach from=$vas item=va key=va_id}
	{$has_atleast_one = true}
	<h3 style="font-size:150%;margin-bottom:5px;">{$va->name}</h3>
	<div style="margin-left:10px;">
	{include file="devblocks:cerberusweb.core::internal/decisions/assistant/tab.tpl" triggers_by_event=$va->behaviors}
	</div>
{/foreach}

{if !$has_atleast_one}
<form action="{devblocks_url}{/devblocks_url}">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="mail_filtering">
<input type="hidden" name="action" value="createDefaultVa">
<div class="help-box" style="padding:5px;border:0;">
	<h1 style="margin-bottom:5px;text-align:left;">Create a global Virtual Attendant</h1>
	
	<p>
		Only global Virtual Attendants are capable of filtering inbound mail.  You currently don't have one.
	</p>
	
	<p>
		<b>Would you like Cerb to automatically create your first global Virtual Attendant now?</b>
		
		<div style="margin-left:15px;">
			<button type="submit"><span class="cerb-sprite2 sprite-tick-circle"></span> Yes</button>
		</div>
	</p>
</div>
</form>
{/if}