<h2>Virtual Attendants</h2>

<form action="javascript:;" onsubmit="return false;">

<div>
	<b>{'common.owner'|devblocks_translate|capitalize}:</b> 
	<input id="inputSetupVaOwner" type="text" size="32" class="input_search filter">
	<ul id="divSetupVaOwnerBubbles" class="bubbles"></ul>
</div>

<ul class="cerb-popupmenu" id="menuSetupVaOwnerPicker" style="display:block;margin-bottom:5px;max-height:200px;overflow-x:hidden;overflow-y:auto;">
	<li context="cerberusweb.contexts.app" context_id="0" label="Application (Global)">
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
</form>

<div id="setupAttendantTabs">
	<ul>
		{$tabs = []}
		{$point = 'setup.attendants.tab'}
	</ul>
</div> 
<br>

{$selected_tab_idx=0}
{foreach from=$tabs item=tab_label name=tabs}
	{if $tab_label==$selected_tab}{$selected_tab_idx = $smarty.foreach.tabs.index}{/if}
{/foreach}

<script type="text/javascript">
$(function() {
	$tabs = $("#setupAttendantTabs");
	var tabs = $tabs.tabs({ 
		selected:{$selected_tab_idx},
		select:function(e) {
			$menu = $('#menuSetupVaOwnerPicker');
			$menu.hide();
		},
		{literal}tabTemplate: "<li><a href='#{href}'>#{label}</a></li>"{/literal}
	});
});
	
// Owner selector
$menu = $('#menuSetupVaOwnerPicker');
$input = $('#inputSetupVaOwner');
{if empty($context)}$input.focus();{/if}

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

$input.focus(function(e) {
	$menu = $('#menuSetupVaOwnerPicker');
	$menu.show();
});

$input.keyup(
	function(e) {
		term = $(this).val().toLowerCase();
		$menu = $('#menuSetupVaOwnerPicker');
		$menu.find('> li > div.item').each(function(e) {
			if(-1 != $(this).html().toLowerCase().indexOf(term)) {
				$(this).parent().show();
			} else {
				$(this).parent().hide();
			}
		});
		$menu.show();
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
	$menu = $('#menuSetupVaOwnerPicker');
	$bubbles = $('#divSetupVaOwnerBubbles');
	$tabs = $("#setupAttendantTabs");
	
	context = $li.attr('context');
	context_id = $li.attr('context_id');
	label = $li.attr('label');
	
	context_pair = context+':'+context_id;

	// [TODO] Check for dupe context pair
	//if($bubbles.find('li input:hidden[value="'+context_pair+'"]').length > 0)
	//	return;
	
	url = "{devblocks_url}ajax.php?c=internal&a=showAttendantTab&point={$point}{/devblocks_url}";
	
	$tabs.tabs( "add", url + "&context=" + context + "&context_id=" + context_id, label );
});		
</script>
