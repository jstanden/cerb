<b>{'common.content'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;">
	<textarea name="{$namePrefix}[content]" rows="3" cols="45" style="width:100%;" class="placeholders">{$params.content}</textarea>
</div>

<b>{'common.notify_workers'|devblocks_translate|capitalize}:</b>  
<div style="margin-left:10px;">
	<button type="button" class="chooser_notify_workers unbound"><span class="cerb-sprite sprite-view"></span></button>
	<ul class="chooser-container bubbles" style="display:block;">
		<li><label><input type="checkbox" name="{$namePrefix}[notify_watchers]" value="1" {if $params.notify_watchers}checked="checked"{/if}> Watchers</label></li>
		{if isset($params.notify_worker_id)}
		{foreach from=$params.notify_worker_id item=worker_id}
			{$context_worker = $workers.$worker_id}
			{if !empty($context_worker)}
			<li>{$context_worker->getName()}<input type="hidden" name="{$namePrefix}[notify_worker_id][]" value="{$context_worker->id}"><a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a></li>
			{/if}
		{/foreach}
		{/if}
	</ul>
</div>

<script type="text/javascript">
$action = $('fieldset#{$namePrefix}');
$action.find('textarea').elastic();
</script>