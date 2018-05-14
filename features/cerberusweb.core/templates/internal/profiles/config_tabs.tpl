{$uniqid = uniqid()}
<form id="profileTabsConfig{$uniqid}" action="{devblocks_url}{/devblocks_url}" method="POST" onsubmit="return false;">
	<input type="hidden" name="c" value="profiles">
	<input type="hidden" name="a" value="configTabsSaveJson">
	<input type="hidden" name="context" value="{$context}">
	
	<b>Display these tabs on this record type:</b>
	
	<div style="margin:5px 0 10px 0;">
		<button type="button" class="chooser-profile-tabs" data-field-name="profile_tabs[]" data-context="{CerberusContexts::CONTEXT_PROFILE_TAB}" data-query="" data-query-required="record:&quot;{$context}&quot;"><span class="glyphicons glyphicons-search"></span></button>
		
		<ul class="bubbles chooser-container">
			{foreach from=$profile_tabs_enabled item=profile_tab_id}
				{$profile_tab = $profile_tabs[$profile_tab_id]}
				{if $profile_tab}
				<li><input type="hidden" name="profile_tabs[]" value="{$profile_tab->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_PROFILE_TAB}" data-context-id="{$profile_tab->id}">{$profile_tab->name}</a></li>
				{/if}
			{/foreach}
		</ul>
	</div>
	
	<div>
		<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	</div>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#profileTabsConfig{$uniqid}');

	// Peeks
	$frm.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		;
	
	// Profile tabs
	
	$frm.find('.chooser-profile-tabs')
		.cerbChooserTrigger()
		.on('cerb-chooser-saved', function(e) {
		})
		;
	
	// Sortable
	
	$frm.find('ul.bubbles')
		.sortable({
			'items': 'li',
			'helper': 'clone',
			'opacity': 0.5,
			'tolerance': 'pointer'
		})
		;
	
	// Submit
	$frm.find('button.submit').on('click', function(e) {
		genericAjaxPost($frm, '', null, function(json) {
			e.stopPropagation();
			document.location.reload();
		});
	});
});

</script>