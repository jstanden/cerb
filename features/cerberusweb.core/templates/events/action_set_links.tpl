{$random = uniqid()}
{$menu_button = "btn{$random}"}
{$param_name = 'context_objects'}
<div id="container_{$random}">

{if !empty($values_to_contexts)}
<b>On:</b>
<div style="margin-left:10px;">
<select name="{$namePrefix}[on]">
	{foreach from=$values_to_contexts item=context_data key=val_key}
	<option value="{$val_key}" context="{$context_data.context}" {if $params.on == $val_key}selected="selected"{/if}>{$context_data.label}</option>
	{/foreach}
</select>
</div>
{/if}

<b>Do:</b>
<div style="margin-left:10px;">
	<label><input type="radio" name="{$namePrefix}[is_remove]" value="0" {if !$params.is_remove}checked="checked"{/if}> {'common.add'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="{$namePrefix}[is_remove]" value="1" {if $params.is_remove}checked="checked"{/if}> {'common.remove'|devblocks_translate|capitalize}</label>
</div>

<b>These links:</b>
<div style="margin-left:10px;">
	<ul class="chooser-container bubbles" style="display:inline-block;">
	{foreach from=$params.context_objects item=context_data}
		{if is_string($context_data) && isset($values_to_contexts.$context_data)}
			<li>
				{$values_to_contexts.$context_data.label}<!--
				--><input type="hidden" name="{$namePrefix}[context_objects][]" value="{$context_data}"><!--
				--><span class="ui-icon ui-icon-trash" style="display:inline-block;vertical-align:middle;cursor:pointer;" onclick="$(this).closest('li').remove();"></span>
			</li>
		{else}
			{$context_pair = explode(':',$context_data)}
			{if is_array($context_pair) && 2 == count($context_pair)}
			<li>
				{$context = $context_pair.0}
				{$context_id = $context_pair.1}
				{$context_ext = Extension_DevblocksContext::get($context,true)}
				{$meta = $context_ext->getMeta($context_id)}
				{$meta.name} ({$context_ext->manifest->name})<!--
				--><input type="hidden" name="{$namePrefix}[context_objects][]" value="{$context}:{$context_id}"><!--
				--><span class="ui-icon ui-icon-trash" style="display:inline-block;vertical-align:middle;cursor:pointer;" onclick="$(this).closest('li').remove();"></span>
			</li>
			{/if}
		{/if}
	{/foreach}
	</ul>

	<div id="{$menu_button}" class="badge badge-lightgray" style="cursor:pointer;"><a href="javascript:;" style="text-decoration:none;color:rgb(50,50,50);">{'common.add'|devblocks_translate|capitalize} &#x25be;</a></div>
	
	<ul class="cerb-popupmenu" style="max-height:200px;overflow-y:auto;">
		<li class="filter"><input type="text" class="input_search" size="45"></li>
	
		<li><b>Placeholders</b></li>
	
		{foreach from=$values_to_contexts item=var_data key=var_key}
			{if !empty($var_data.context)}
			<li class="item" key="{$var_key}" style="padding-left:20px;">
				<a href="javascript:;">{$var_data.label}</a>
			</li>
			{/if}
		{/foreach}
		
		<li><b>Choosers</b></li>
		
		{foreach from=$contexts item=context key=context_id}
			{if isset($context->params['options'][0]['find'])}
				<li class="chooser" key="{$context_id}" style="padding-left:20px;">
					<a href="javascript:;">{$context->name}</a>
				</li>
			{/if}
		{/foreach}
	</ul>
</div>

</div>

<script type="text/javascript">
$('#container_{$random}').find('ul.cerb-popupmenu > li.chooser').click(function(e) {
	$this = $(this);
	$val = $this.attr('key');
	
	$container = $('#container_{$random}');
	$container.data('context', $val);
	$container.data('context_name', $this.find('a').text());
	
	if($val.length > 0) {
		$popup = genericAjaxPopup("chooser{uniqid()}",'c=internal&a=chooserOpen&context='+encodeURIComponent($val),null,true,'750');
		$popup.one('chooser_save',function(event) {
			event.stopPropagation();
			
			$container = $('#container_{$random}');
			$ul = $container.find('ul.chooser-container');
			$context = $container.data('context');
			$context_name = $container.data('context_name');
			
			for(i in event.labels) {
				// Look for dupes
				if(0 == $ul.find('input:hidden[value="' + $context + ':' + event.values[i] + '"]').length) {
					$li = $('<li>' + event.labels[i] + ' (' + $context_name + ')</li>');
					$li.append($('<input type="hidden" name="{$namePrefix}[context_objects][]" value="' + $context + ':' + event.values[i] + '">'));
					$li.append($('<span class="ui-icon ui-icon-trash" style="display:inline-block;vertical-align:middle;pointer:middle;" onclick="$(this).closest(\'li\').remove();"></span>'));
					
					$ul.append($li);
				}
			}
		});
	}
});

// Menu
$menu_trigger = $('#{$menu_button}');
$menu = $menu_trigger.nextAll('ul.cerb-popupmenu');
$menu_trigger.data('menu', $menu);

$menu_trigger
	.click(
		function(e) {
			$menu = $(this).data('menu');

			if($menu.is(':visible')) {
				$menu.hide();
				return;
			}

			$menu
				.show()
				.find('> li.filter > input.input_search')
				.focus()
				.select()
				;
		}
	)
;

$menu.find('> li.filter > input.input_search').keypress(
	function(e) {
		code = (e.keyCode ? e.keyCode : e.which);
		if(code == 13) {
			e.preventDefault();
			e.stopPropagation();
			$(this).select().focus();
			return false;
		}
	}
);
	
$menu.find('> li > input.input_search').keyup(
	function(e) {
		term = $(this).val().toLowerCase();
		$menu = $(this).closest('ul.cerb-popupmenu');
		$menu.find('> li.item, > li.chooser').each(function(e) {
			if(-1 != $(this).html().toLowerCase().indexOf(term)) {
				$(this).show();
			} else {
				$(this).hide();
			}
		});
	}
);

$menu.find('> li.item').click(function(e) {
	e.stopPropagation();
	if($(e.target).is('a'))
		return;

	$(this).find('a').trigger('click');
});

$menu.find('> li.item > a').click(function() {
	$li = $(this).closest('li');
	$menu = $(this).closest('ul.cerb-popupmenu')
	$bubbles = $menu.prevAll('ul.chooser-container.bubbles');
	
	$key = $li.attr('key');
	
	if($bubbles.find('li input:hidden[value="' + $key + '"]').length > 0)
		return;
	
	$bubble = $('<li></li>');
	$bubble.append($li.find('a').text());
	$bubble.append($('<input type="hidden" name="{$namePrefix}[{$param_name}][]" value="' + $key + '">'));
	$bubble.append($('<a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a>'));
	
	$bubbles.append($bubble);
});
</script>