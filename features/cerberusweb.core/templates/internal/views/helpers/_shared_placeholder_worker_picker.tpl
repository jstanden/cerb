{$menu_domid = "btn{uniqid()}"}
{if !isset($workers)}{$workers = DAO_Worker::getAllActive()}{/if}

{if $show_chooser}
{$chooser_button = "btnChooser{uniqid()}"}
<button type="button" class="chooser_worker" id="{$chooser_button}"><span class="cerb-sprite sprite-view"></span></button>
{/if}

<ul class="chooser-container bubbles">
{foreach from=$param->value item=v}
	<li>
		
		{if is_numeric($v) && isset($workers.$v)}
			{$workers.$v->getName()}
		{elseif isset($placeholders.{$v|replace:'{':''|replace:'}':''})}
			{$token = $v|replace:'{':''|replace:'}':''}
			{$placeholders.$token.label}
		{/if}
		
		<input type="hidden" name="{$param_name}[]" value="{$v}">
		<a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a>
	</li>
{/foreach}
</ul>

<ul id="{$menu_domid}" class="cerb-popupmenu" style="margin-top:5px;max-height:200px;overflow-y:auto;display:block;">
	<li class="filter">
		<input type="text" class="input_search" size="24">
	</li>

	<li><b>Placeholders</b></li>

	{foreach from=$placeholders item=var_data key=var_key}
		{if $var_data.type == Model_CustomField::TYPE_WORKER || $var_data.context == CerberusContexts::CONTEXT_WORKER}
		<li class="item" key="{literal}{{{/literal}{$var_key}{literal}}}{/literal}" style="padding-left:20px;">
			<a href="javascript:;">{$var_data.label}</a>
		</li>
		{/if}
	{/foreach}

	<li><b>Workers</b></li>
		
	{foreach from=$workers item=worker key=worker_id}
		<li class="item" key="{$worker_id}" style="padding-left:20px;">
			<a href="javascript:;">{$worker->getName()}</a>
		</li>
	{/foreach}
</ul>

<script type="text/javascript">
{if $show_chooser}
$('#{$chooser_button}').each(function(e) {
	ajax.chooser(this,'cerberusweb.contexts.worker','worker_id', { autocomplete:false });
});
{/if}

// Menu
$menu = $('#{$menu_domid}');

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
		$menu.find('> li.item').each(function(e) {
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
	$bubble.append($('<input type="hidden" name="{$param_name}[]" value="' + $key + '">'));
	$bubble.append($('<a href="javascript:;" onclick="$(this).parent().remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a>'));
	
	$bubbles.append($bubble);
});

</script>