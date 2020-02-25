{$uniqid = uniqid()}
<form id="profileTabsConfig{$uniqid}" action="{devblocks_url}{/devblocks_url}" method="POST" onsubmit="return false;">
	<input type="hidden" name="c" value="profiles">
	<input type="hidden" name="a" value="configTabsSaveJson">
	<input type="hidden" name="context" value="{$context}">
	
	<div style="margin-bottom:10px;">
		<button type="button" class="cerb-add-tab-trigger" data-context="{CerberusContexts::CONTEXT_PROFILE_TAB}" data-context-id="0" data-edit="context:{$context}"><span class="glyphicons glyphicons-circle-plus"></span> {'common.add'|devblocks_translate|capitalize}</button>
	</div>
	
	<fieldset class="peek">
		<legend>Display these tabs on this record type for everyone:</legend>
		
		<div style="margin:5px 0 5px 0;">
			<div class="cerb-sortable" style="margin:5px 0px 0px 10px;">
				{foreach from=$profile_tabs_available item=profile_tab}
				<div class="cerb-sort-item">
					<span class="glyphicons glyphicons-menu-hamburger" style="cursor:move;vertical-align:top;color:rgb(175,175,175);line-height:1.4em;margin-right:2px;"></span><!--
					
					--><label style="margin:0 5px;"><input type="checkbox" name="profile_tabs[]" value="{$profile_tab->id}" {if in_array($profile_tab->id, $profile_tabs_enabled)}checked="checked"{/if}></label><!--
					
					--><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_PROFILE_TAB}" data-context-id="{$profile_tab->id}"><b>{$profile_tab->name}</b></a>
				</div>
				{/foreach}
			</div>
		</div>
	</fieldset>
	
	<div>
		<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	</div>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#profileTabsConfig{$uniqid}');
	var $sortable = $frm.find('.cerb-sortable');

	// Peeks

	$frm.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function(e) {
			var $item = $(this).closest('div.cerb-sort-item');
			$item.find('a > b').text(e.label);
		})
		.on('cerb-peek-deleted', function(e) {
			$(this).closest('div.cerb-sort-item').remove();
		})
		;
	
	$frm.find('.cerb-add-tab-trigger')
		.cerbPeekTrigger()
		.on('cerb-peek-saved', function(e) {
			var $sort_item = $('<div/>')
				.addClass('cerb-sort-item')
				;
			
			var $handle = $('<span class="glyphicons glyphicons-menu-hamburger" style="cursor:move;vertical-align:top;color:rgb(175,175,175);line-height:1.4em;margin-right:2px;"></span>')
				.appendTo($sort_item)
				;
			
			var $input = $('<input/>')
				.attr('type', 'checkbox')
				.attr('name', 'profile_tabs[]')
				.val(e.id)
				.attr('checked', 'checked')
				;
			
			var $label = $('<label/>')
				.css('margin', '0 5px')
				.prepend($input)
				.appendTo($sort_item)
				;
			
			var $b = $('<b/>')
				.text(e.label)
				;
			
			var $a = $('<a/>')
				.attr('href', 'javascript:;')
				.addClass('cerb-peek-trigger no-underline')
				.attr('data-context', '{CerberusContexts::CONTEXT_PROFILE_TAB}')
				.attr('data-context-id', e.id)
				.append($b)
				.appendTo($sort_item)
				;
			
			$sortable.append($sort_item);
		})
		;
	
	// Sortable
	
	$sortable
	.sortable({
		tolerance: 'pointer',
		helper: 'clone',
		handle: '.glyphicons-menu-hamburger',
		items: '.cerb-sort-item',
		opacity: 0.7
	})
	;
	
	// Submit
	
	$frm.find('button.submit').on('click', function(e) {
		genericAjaxPost($frm, '', null, function() {
			e.stopPropagation();
			document.location.reload();
		});
	});
});
</script>