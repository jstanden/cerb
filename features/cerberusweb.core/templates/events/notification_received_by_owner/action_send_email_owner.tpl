<b>{'message.header.to'|devblocks_translate|capitalize}:</b><br>
<ul style="margin:0px 0px 10px 15px;padding:0;list-style:none;max-height:150px;overflow:auto;">
{foreach from=$addresses item=address key=address_key}
<li>
	<label>
	<input type="checkbox" name="{$namePrefix}[to][]" value="{$address_key}" {if in_array($address_key,$params.to)}checked="checked"{/if}>
	{$address->address} ({$workers.{$address->worker_id}->getName()})
	</label>
</li>
{/foreach}
</ul>

<b>{'message.header.subject'|devblocks_translate|capitalize}:</b>
<input type="text" name="{$namePrefix}[subject]" value="{$params.subject}" size="45" style="width:100%;"><br>
<br>

<b>{'common.content'|devblocks_translate|capitalize}:</b>
<div>
	<textarea name="{$namePrefix}[content]" rows="3" cols="45" style="width:100%;" class="placeholders">{$params.content}</textarea>
</div>
<br>

<script type="text/javascript">
$action = $('fieldset#{$namePrefix}');
$action.find('textarea').elastic();
</script>