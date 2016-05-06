{$addy_ids = DevblocksPlatform::extractArrayValues($addresses, 'address_id')}
{$object_addys = DAO_Address::getIds($addy_ids)}

<b>{'message.header.to'|devblocks_translate|capitalize}:</b><br>
<ul style="margin:0px 0px 10px 15px;padding:0;list-style:none;max-height:150px;overflow:auto;">
{foreach from=$addresses item=address key=address_key}
{$object_addy = $object_addys.{$address->address_id}}
{if $object_addy}
<li>
	<label>
	<input type="checkbox" name="{$namePrefix}[to][]" value="{$object_addy->email}" {if is_array($params.to) && in_array($object_addy->email,$params.to)}checked="checked"{/if}>
	<b>{$object_addy->email}</b> ({$workers.{$address->worker_id}->getName()})
	</label>
</li>
{/if}
{/foreach}
</ul>

<b>{'message.header.subject'|devblocks_translate|capitalize}:</b>
<input type="text" name="{$namePrefix}[subject]" value="{$params.subject}" size="45" style="width:100%;" class="placeholders"><br>
<br>

<b>{'common.content'|devblocks_translate|capitalize}:</b>
<div>
	<textarea name="{$namePrefix}[content]" rows="3" cols="45" style="width:100%;" class="placeholders">{$params.content}</textarea>
</div>
<br>

<script type="text/javascript">
$(function() {
	var $action = $('fieldset#{$namePrefix}');
	$action.find('textarea').autosize();
});
</script>