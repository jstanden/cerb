{$uniq_id = uniqid()}
<form id="{$uniq_id}" action="#" method="POST" onsubmit="return false;">
<fieldset>
	<legend>Add a new tab:</legend>
	
	<b>{'common.title'|devblocks_translate}:</b>
	<input type="text" name="add_name" value="" size="32">
	<button type="button" class="add"><span class="cerb-sprite2 sprite-plus-circle-frame"></span></button>
</fieldset>
</form>

<script type="text/javascript">
$('#{$uniq_id}').find('button.add').click(function(e) {
	$this = $(this);
	$frm = $this.closest('form');
	$tabs = $('#pageTabs');
	
	$input = $frm.find('input:text[name=add_name]'); 
	
	len = $tabs.tabs('length');
	title = $input.val();
	
	if(title.length == 0)
		return;
	
	// Second to last tab
	if(len > 0)
		len--;
	
	// Get Ajax/JSON response
	genericAjaxGet('', 'c=pages&a=doAddCustomTabJson&title=' + encodeURIComponent(title) + '&page_id={$page->id}' + '&index=' + encodeURIComponent(len), function(json) {
		if(!json || !json.success)
			return;
		
		$tabs.tabs('option', 'tabTemplate', '<li class="drag" tab_id="'+json.tab_id+'"><a href="#{literal}{href}{/literal}"><span>#{literal}{label}{/literal}</span></a></li>');
		$tabs.tabs('add', json.tab_url, title, len);
		
		$input.val('');
	});
});
</script>