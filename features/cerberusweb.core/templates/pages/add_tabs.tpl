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
		Depending on the plugins you have installed, a tab can be one of several <b>Types</b>.  The default is <i>Worklists</i>, which displays as many lists of specific information as you want.  The other tab types are specialized for specific purposes, such as informational dashboards and browsing the knowledgebase by category.
	</p>
	
	<p>
		After you've configured your tab below, click the <button type="button"><span class="glyphicons glyphicons-circle-plus"></span> {'common.add'|devblocks_translate|capitalize}</button> button.  You can then click on the tab's label above to display it.
	</p>
</div>
</form>
{/if}

{$uniq_id = uniqid()}
<form id="{$uniq_id}" action="{devblocks_url}{/devblocks_url}" method="post" onsubmit="return false;">
<input type="hidden" name="c" value="pages">
<input type="hidden" name="a" value="doAddCustomTabJson">
<input type="hidden" name="page_id" value="{$page->id}">
<input type="hidden" name="len" value="99">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset class="peek">
	<legend>Add a new tab:</legend>

	<label><input type="radio" name="mode" value="build" checked="checked"> Build</label>
	<label><input type="radio" name="mode" value="import"> Import</label>
	
	<table cellpadding="2" cellspacing="0" border="0" width="100%">
		<tbody class="build">
		<tr>
			<td valign="top" width="1%" nowrap="nowrap">
				<b>{'common.label'|devblocks_translate|capitalize}:</b>
			</td>
			<td width="99%">
				<input type="text" name="name" value="" size="32">
			</td>
		</tr>
		<tr>
			<td valign="top" width="1%" nowrap="nowrap">
				<b>{'common.type'|devblocks_translate|capitalize}:</b>
			</td>
			<td width="99%">
				<select name="extension_id">
					{if !empty($tab_extensions)}
						{foreach from=$tab_extensions item=tab_extension}
							<option value="{$tab_extension->id}">{$tab_extension->params.label|devblocks_translate|capitalize}</option>
						{/foreach}
					{/if}
				</select>
			</td>
		</tr>
		</tbody>

		<tbody class="import" style="display:none;">
		<tr>
			<td valign="top" width="1%" nowrap="nowrap">
				<b>{'common.import'|devblocks_translate|capitalize}:</b>
			</td>
			<td width="99%">
				<textarea name="import_json" style="width:100%;height:150px;white-space:pre;word-wrap:normal;" rows="10" cols="45" spellcheck="false" placeholder="Paste the contents of a workspace tab export here..."></textarea>
			</td>
		</tr>
		</tbody>
		
		<tr>
			<td valign="top" width="1%" nowrap="nowrap">
				<!-- blank -->
			</td>
			<td width="99%">
				<button type="button" class="add"><span class="glyphicons glyphicons-circle-plus"></span> {'common.add'|devblocks_translate|capitalize}</button>
			</td>
		</tr>
		
	</table>
</fieldset>

</form>

<script type="text/javascript">
var $frm = $('form#{$uniq_id}');
var $input = $frm.find('input:text[name=name]'); 
$input.focus();

$frm.find('input:radio[name=mode]').change(function() {
	var $radio = $(this);
	var $table = $radio.parent().siblings('table');

	if($radio.val() == 'import') {
		$table.find('tbody.build').hide();
		$table.find('tbody.import').fadeIn();
		
	} else {
		$table.find('tbody.import').hide();
		$table.find('tbody.build').fadeIn();
		
	}
});

$frm.find('button.add').click(function(e) {
	var $this = $(this);
	var $frm = $this.closest('form');
	var $tabs = $('#pageTabs{$page->id}');
	
	var len = $tabs.find('.ui-tabs-nav > li').length;
	
	$frm.find('> input:hidden[name=len]').val(len);
	
	// Get Ajax/JSON response
	genericAjaxPost($frm, '', '', function(json) {
		if(!json || !json.success)
			return;
		
		var $new_tab = $('<li class="drag" tab_id="' + json.tab_id + '"><a href="' + json.tab_url + '"><span>' + json.tab_name + '</span></a></li>');
		$new_tab.insertBefore($tabs.find('.ui-tabs-nav > li:last'));
		
		$tabs.tabs('refresh');
		
		$this.effect('transfer', { to:$new_tab, className:'effects-transfer' }, 500, function() { });
		
		$tabs.tabs('option', 'active', -2);
		
		$input.val('').focus();
		$frm.find('textarea[name=import]').val('');
	});
});
</script>