{if $page->isWriteableByWorker($active_worker)}
<form action="#" onsubmit="return false;">
<div class="help-box" style="padding:5px;border:0;">
	<h1 style="margin-bottom:5px;text-align:left;">Let's add some tabs to your page</h1>
	
	<p>
		Once you've created a new workspace page you can add tabs to organize your content.
	</p>

	<p>
		A <b>Label</b> gives your tab a short, descriptive name.
	</p>
	
	<p>
		Depending on the plugins you have installed, a tab can be one of several <b>Types</b>.  The default is <i>Custom Worklists</i>, which displays as many lists of specific information as you want.  The other tab types are specialized for specific purposes, such as browsing the knowledgebase by category.
	</p>
	
	<p>
		After you've configured your tab below, click the <button type="button"><span class="cerb-sprite2 sprite-plus-circle"></span> {'common.add'|devblocks_translate|capitalize}</button> button.  You can then click on the tab's label above to display it.
	</p>
</div>
</form>
{/if}

{$uniq_id = uniqid()}
<form id="{$uniq_id}" action="#" method="POST" onsubmit="return false;">
<fieldset class="peek">
	<legend>Add a new tab:</legend>

	<table cellpadding="2" cellspacing="0" border="0">
		<tr>
			<td>
				<b>{'common.label'|devblocks_translate|capitalize}:</b>
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
					<option value="">Custom Worklists (default)</option>
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
				<button type="button" class="add"><span class="cerb-sprite2 sprite-plus-circle"></span> {'common.add'|devblocks_translate|capitalize}</button>
			</td>
		</tr>
	</table>
</fieldset>

</form>

<script type="text/javascript">
$frm = $('form#{$uniq_id}');
$input = $frm.find('input:text[name=name]'); 
$input.focus();

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
		
		$this.effect('transfer', { to:$tabs.find('ul.ui-tabs-nav li:nth(' + len + ')'), className:'effects-transfer' }, 500, function() {
		});
		
		$input.val('').focus();
	});
});
</script>