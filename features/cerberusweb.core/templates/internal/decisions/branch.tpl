{$node = $nodes[$node_id]}

{* Label *}
<div class="node {$node->node_type}">
	<input type="hidden" name="node_id" value="{$node_id}">

	{if $node->node_type == 'switch'}
		<div class="badge badge-lightgray" style="margin:2px;">
			<a href="javascript:;" style="text-decoration:none;font-weight:bold;color:rgb(68,154,220);" onclick="decisionNodeMenu(this);" node_id="{$node->id}" trigger_id="{$trigger_id}">
				{$node->title} &#x25be;
			</a>
		</div>
	
	{elseif $node->node_type == 'outcome'}
		<div class="badge badge-lightgray">
			<a href="javascript:;" style="text-decoration:none;font-weight:bold;{if preg_match('#^yes($|,| )#i',$node->title)}color:rgb(0,150,0);{elseif preg_match('#^no($|,| )#i',$node->title)}color:rgb(150,0,0);{/if}" onclick="decisionNodeMenu(this);" node_id="{$node->id}" trigger_id="{$trigger_id}">
				{$node->title} &#x25be;
			</a>
		</div>
	
	{elseif $node->node_type == 'action'}
		<div class="badge badge-lightgray" style="margin:2px;">
			<a href="javascript:;" style="text-decoration:none;font-weight:normal;font-style:italic;" onclick="decisionNodeMenu(this);" node_id="{$node->id}" trigger_id="{$trigger_id}">
				{$node->title} &#x25be;
			</a>
		</div>
		
	{/if}
	
	{* Recurse Children *}
	<div class="branch {$node->node_type}" style="padding-bottom:2px;margin-left:10px;padding-left:10px;{if $node->node_type == 'outcome'}border-left:1px solid rgb(200,200,200);{/if}">
	{if is_array($tree[$node_id]) && !empty($tree[$node_id])}
		{foreach from=$tree[$node_id] item=child_id}
			{include file="devblocks:cerberusweb.core::internal/decisions/branch.tpl" node_id=$child_id trigger_id=$trigger_id nodes=$nodes tree=$tree depths=$depths}
		{/foreach}
	{/if}
	</div>
</div>


