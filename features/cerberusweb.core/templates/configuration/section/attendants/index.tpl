<h2>Virtual Attendants</h2>

<form action="javascript:;" onsubmit="return false;">

<div>
	<b>{'common.owner'|devblocks_translate|capitalize}:</b>
	<input id="inputSetupVaOwner" type="text" size="32" class="input_search filter">
</div>

<ul class="cerb-popupmenu" id="menuSetupVaOwnerPicker" style="display:block;margin-bottom:5px;max-height:200px;overflow-x:hidden;overflow-y:auto;box-shadow:none;border:1px solid rgb(200,200,200);">
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

<script type="text/javascript">
$(function() {
	$tabs = $("#setupAttendantTabs");
	
	var tabs = $tabs.tabs({ 
		selected:0,
		select:function(e) {}
	});
	
	$tabs.find('> ul').sortable({
		items:'> li',
		distance: 20,
		forcePlaceholderWidth:true
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
	var $li = $(this).closest('li');
	var $frm = $(this).closest('form');
	
	var $ul = $li.closest('ul');
	var $menu = $('#menuSetupVaOwnerPicker');
	var $tabs = $("#setupAttendantTabs");
	
	var context = $li.attr('context');
	var context_id = $li.attr('context_id');
	var label = $li.attr('label');
	
	var url = "{devblocks_url full=true}ajax.php?c=internal&a=showAttendantTab&point={$point}{/devblocks_url}" + "&context=" + context + "&context_id=" + context_id;
	
	var $tab = $("<li><a href='"+url+"'>"+label+"</a></li>");
	
	$tabs.find('ul.ui-tabs-nav').append($tab);
	$tabs.tabs('refresh');
	
	$tabs.tabs('select', $tabs.tabs('length')-1);
	
	$li.remove();
	
	if($ul.find('> li').length == 0)
		$frm.remove();
});
</script>
