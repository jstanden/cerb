<b>Relay to:</b><br>
<ul style="margin:0px 0px 10px 15px;padding:0;list-style:none;max-height:150px;overflow:auto;">
	{if in_array('owner',$show)}<li><label><input type="checkbox" name="{$namePrefix}[to_owner][]" value="owner" {if $params.to_owner}checked="checked"{/if}> {'common.owner'|devblocks_translate|capitalize}</label></li>{/if}
	{if in_array('watchers',$show)}<li><label><input type="checkbox" name="{$namePrefix}[to_watchers][]" value="watchers" {if $params.to_watchers}checked="checked"{/if}> {'common.watchers'|devblocks_translate|capitalize}</label></li>{/if}
	
	{if in_array('workers',$show)}
	{foreach from=$addresses item=address key=address_key}
	<li>
		<label>
		<input type="checkbox" name="{$namePrefix}[to][]" value="{$address_key}" {if in_array($address_key,$params.to)}checked="checked"{/if}>
		{$address->address} ({$workers.{$address->worker_id}->getName()})
		</label>
	</li>
	{/foreach}
	{/if}
</ul>

<b>{'message.header.subject'|devblocks_translate|capitalize}:</b> (use {literal}{{ticket_subject}}{/literal} for default)<br>
<input type="text" name="{$namePrefix}[subject]" value="{if empty($params.subject)}{literal}{{ticket_subject}}{/literal}{else}{$params.subject}{/if}" size="45" style="width:100%;"><br>
<br>

<b>{'common.content'|devblocks_translate|capitalize}:</b>
<div>
	<textarea name="{$namePrefix}[content]" rows="3" cols="45" style="width:100%;" class="placeholders">{if isset($params.content)}{$params.content}{else}{$default_content}{/if}</textarea>
</div>
<div>
Lines that begin with <code>##</code> will be ignored on reply.
</div>
<br>

<b>{'common.attachments'|devblocks_translate}:</b><br>
<label><input type="checkbox" name="{$namePrefix}[include_attachments]" value="1" {if $params.include_attachments}checked="checked"{/if}> Include attachments</label><br>

<script type="text/javascript">
$action = $('fieldset#{$namePrefix}');
$action.find('textarea').elastic();
</script>