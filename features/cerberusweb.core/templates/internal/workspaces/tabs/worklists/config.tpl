{$uniqid = uniqid()}
<fieldset id="fieldset{$uniqid}" class="peek">
	<legend>Worklists</legend>
	
	<b>{'common.add'|devblocks_translate|capitalize}:</b> 
	<select name="add_context">
		<option value="">- {'common.choose'|devblocks_translate|lower} -</option>
		{foreach from=$contexts item=mft key=mft_id}
		{if $mft->hasOption('workspace')}
		<option value="{$mft_id}">{$mft->name}</option>
		{/if}
		{/foreach}
	</select>

	<div data-cerb-worklists-container>
		{foreach from=$worklists item=worklist name=worklists key=worklist_id}
		<div class="column">
			<span class="cerb-icons cerb-icon-move" style="cursor:move;margin-right:0.5em;" title="Drag to rearrange"></span><!--
			--><a data-cerb-link-worklist-delete><span class="cerb-icons cerb-icon-trash"></span></a><!--
			--><input type="hidden" name="ids[]" value="{$worklist->id}"><!--
			--><input type="text" name="names[]" value="{$worklist->name}" size="45"><!--
			--><span>{if isset($contexts.{$worklist->context})}{$contexts.{$worklist->context}->name}{/if}</span>
		</div>
		{/foreach}
	</div>
</fieldset>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $fieldset = $('fieldset#fieldset{$uniqid}');
	
	$fieldset.sortable({ items: 'DIV.column', placeholder:'ui-state-highlight' });

	$fieldset.find('select[name=add_context]').change(function() {
		let $select = $(this);
		
		if($select.val() == '')
			return;
		
		let $columns = $fieldset.find('[data-cerb-worklists-container]');
		let $new_column = $('<div class="column"></div>');
		
		$('<span class="cerb-icons cerb-icon-move" style="cursor:move;margin-right:0.5em;" title="Drag to rearrange"></span>').appendTo($new_column);
		$('<a data-cerb-link-worklist-delete><span class="cerb-icons cerb-icon-trash"></span></a>').appendTo($new_column);
		$('<input type="hidden" name="ids[]">').attr('value',$select.val()).appendTo($new_column);
		$('<input type="text" name="names[]" size="45">').attr('value',$select.find(':selected').text()).appendTo($new_column);
		$('<span/>').text($select.find(':selected').text()).appendTo($new_column);
		
		$select.val('');
	
		$new_column.appendTo($columns);
		
		$new_column.find('input:text:first').select().focus();
	});

	$fieldset.find('[data-cerb-worklists-container').on('click', function(e) {
		e.stopPropagation();

		let $target = $(e.target);

		if($target.is('.cerb-icons'))
			$target = $target.closest('a');

		if($target.is('[data-cerb-link-worklist-delete]')) {
			confirmPopup(
				'Delete worklist',
				'Are you sure you want to permanently delete this worklist?',
				function () {
					$(this).closest('div').remove();
				}.bind($target.get(0))
			);
		}
	});
});
</script>