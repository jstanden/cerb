{$menu_divid = "{uniqid()}"}

<b>{'search.operator'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">{'search.oper.in_list'|devblocks_translate}</option>
		<option value="{DevblocksSearchCriteria::OPER_IN_OR_NULL}">blank or in list</option>
		<option value="not in">{'search.oper.in_list.not'|devblocks_translate}</option>
		<option value="{DevblocksSearchCriteria::OPER_NIN_OR_NULL}">blank or not in list</option>
	</select>
</blockquote>

<b>{'common.owner'|devblocks_translate|capitalize}:</b><br>

<input type="text" size="32" class="input_search filter">

<ul class="cerb-popupmenu" id="{$menu_divid}" style="display:block;margin-bottom:5px;max-height:200px;overflow-x:hidden;overflow-y:auto;">
	<li context="{CerberusContexts::CONTEXT_APPLICATION}" context_id="0" label="Application (Global)">
		<div class="item">
			<a href="javascript:;">Application</a><br>
			<div style="margin-left:10px;">Global</div>
		</div>
	</li>

	{foreach from=$roles item=role name=roles}
	<li context="{CerberusContexts::CONTEXT_ROLE}" context_id="{$role->id}" label="{$role->name} (Role)">
		<div class="item">
			<a href="javascript:;">{$role->name}</a><br>
			<div style="margin-left:10px;">Role</div>
		</div>
	</li>
	{/foreach}
	
	{foreach from=$groups item=group name=groups}
	<li context="{CerberusContexts::CONTEXT_GROUP}" context_id="{$group->id}" label="{$group->name} (Group)">
		<div class="item">
			<a href="javascript:;">{$group->name}</a><br>
			<div style="margin-left:10px;">Group</div>
		</div>
	</li>
	{/foreach}

	{foreach from=$workers item=worker name=workers}
	<li context="{CerberusContexts::CONTEXT_WORKER}" context_id="{$worker->id}" label="{$worker->getName()} (Worker)">
		<div class="item">
			<a href="javascript:;">{$worker->getName()}</a><br>
			<div style="margin-left:10px;">Worker</div>
		</div>
	</li>
	{/foreach}
</ul>

<ul class="bubbles" style="display:block;">
{foreach from=$param->value item=context_data}
	{$context_pair = explode(':',$context_data)}
	{if is_array($context_pair) && 2 == count($context_pair)}
	<li>
		{$context = $context_pair.0}
		{$context_id = $context_pair.1}
		{$context_ext = Extension_DevblocksContext::get($context,true)}
		{$meta = $context_ext->getMeta($context_id)}
		<b>{$meta.name}</b> ({$context_ext->manifest->name})<!--
		--><input type="hidden" name="owner_context[]" value="{$context}:{$context_id}"><!--
		--><span class="ui-icon ui-icon-trash" style="display:inline-block;vertical-align:middle;" onclick="$(this).closest('li').remove();"></span>
	</li>
	{/if}
{/foreach}
</ul>

<br>

<script type="text/javascript">
$menu = $('#{$menu_divid}');
$input = $menu.prevAll('input.filter');
$input.focus();

$input.keypress(
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
	
$input.keyup(
	function(e) {
		term = $(this).val().toLowerCase();
		$menu = $(this).nextAll('ul.cerb-popupmenu');
		$menu.find('> li > div.item').each(function(e) {
			if(-1 != $(this).html().toLowerCase().indexOf(term)) {
				$(this).parent().show();
			} else {
				$(this).parent().hide();
			}
		});
	}
);

$menu.find('> li').click(function(e) {
	e.stopPropagation();
	if($(e.target).is('a'))
		return;

	$(this).find('a').trigger('click');
});

$menu.find('> li > div.item a').click(function() {
	$li = $(this).closest('li');
	$frm = $(this).closest('form');
	
	$ul = $li.closest('ul');
	$bubbles = $ul.nextAll('ul.bubbles');
	
	context = $li.attr('context');
	context_id = $li.attr('context_id');
	label = $li.attr('label');

	context_pair = context+':'+context_id;

	// Check for dupe context pair
	if($bubbles.find('li input:hidden[value="'+context_pair+'"]').length > 0)
		return;
	
	$bubble = $('<li></li>');
	$bubble.append($('<input type="hidden" name="owner_context[]" value="'+context_pair+'">'));
	$bubble.append(label);
	$bubble.append('<a href="javascript:;" onclick="$li=$(this).closest(\'li\');$li.remove();"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a>');
	
	$bubbles.append($bubble);
});	
</script>