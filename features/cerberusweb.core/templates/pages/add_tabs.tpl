{$uniq_id = uniqid()}
<form id="{$uniq_id}" action="#" method="POST" onsubmit="return false;">
<fieldset class="peek">
	<legend>Add a new tab:</legend>

	<table cellpadding="2" cellspacing="0" border="0">
		<tr>
			<td>
				<b>{'common.title'|devblocks_translate|capitalize}:</b>
			</td>
			<td>
				<input type="text" name="name" value="" size="32">
			</td>
		</tr>
		
		<tr>
			<td>
				<b>{'common.type'|devblocks_translate|capitalize}:</b>
			</td>
			<td>
				<select name="extension_id">
					<option value="">Custom Worklists</option>
					{if !empty($tab_extensions)}
						{foreach from=$tab_extensions item=tab_extension}
							<option value="{$tab_extension->id}">{$tab_extension->params.label|devblocks_translate|capitalize}</option>
						{/foreach}
					{/if}
				</select>
			</td>
		</tr>
		
		<tr>
			<td>
				<!-- blank -->
			</td>
			<td>
				<button type="button" class="add"><span class="cerb-sprite2 sprite-plus-circle-frame"></span> {'common.add'|devblocks_translate|capitalize}</button>
			</td>
		</tr>
	</table>
</fieldset>

</form>

<script type="text/javascript">
$('#{$uniq_id}').find('button.add').click(function(e) {
	$this = $(this);
	$frm = $this.closest('form');
	$tabs = $('#pageTabs');
	
	$input = $frm.find('input:text[name=name]'); 
	$type = $frm.find('select[name=extension_id]'); 
	
	len = $tabs.tabs('length');
	title = $input.val();
	type = $type.val();
	
	if(title.length == 0)
		return;
	
	// Second to last tab
	if(len > 0)
		len--;
	
	// Get Ajax/JSON response
	genericAjaxGet('', 'c=pages&a=doAddCustomTabJson&title=' + encodeURIComponent(title) + '&type=' + encodeURIComponent(type) + '&page_id={$page->id}' + '&index=' + encodeURIComponent(len), function(json) {
		if(!json || !json.success)
			return;
		
		$tabs.tabs('option', 'tabTemplate', '<li class="drag" tab_id="'+json.tab_id+'"><a href="#{literal}{href}{/literal}"><span>#{literal}{label}{/literal}</span></a></li>');
		$tabs.tabs('add', json.tab_url, title, len);
		
		$input.val('');
	});
});
</script>