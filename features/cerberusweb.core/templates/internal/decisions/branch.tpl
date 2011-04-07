{$node = $nodes[$node_id]}

{* Label *}
<div class="node {$node->node_type}">
	{*<span class="ui-icon ui-icon-arrowthick-2-n-s handle" style="display:inline-block;vertical-align:middle;"></span>*}

	{if $node->node_type == 'switch'}
		<div class="badge badge-lightgray" style="margin:2px;">
			<a href="javascript:;" style="text-decoration:none;font-weight:bold;color:rgb(68,154,220);" onclick="decisionNodeMenu(this,'{$node->id}','{$trigger_id}');">
				{$node->title} &#x25be;
			</a>
		</div>
	
	{elseif $node->node_type == 'outcome'}
		<div class="badge badge-lightgray">
			<a href="javascript:;" style="text-decoration:none;font-weight:bold;{if 0==strcasecmp($node->title,'yes')}color:rgb(0,150,0);{elseif 0==strcasecmp($node->title,'no')}color:rgb(150,0,0);{/if}" onclick="decisionNodeMenu(this,'{$node->id}','{$trigger_id}');">
				{$node->title} &#x25be;
			</a>
		</div>
	
	{elseif $node->node_type == 'action'}
		<div class="badge badge-lightgray" style="margin:2px;">
			<a href="javascript:;" style="text-decoration:none;font-weight:normal;font-style:italic;" onclick="decisionNodeMenu(this,'{$node->id}','{$trigger_id}');">
				{$node->title} &#x25be;
			</a>
		</div>
		
	{/if}
	
	{* Recurse Children *}
	{if is_array($tree[$node_id]) && !empty($tree[$node_id])}
		<div class="branch {$node->node_type}" style="padding-bottom:2px;margin-left:10px;padding-left:10px;{if $node->node_type == 'outcome'}border-left:1px solid rgb(200,200,200);{/if}">
		{foreach from=$tree[$node_id] item=child_id}
			{include file="devblocks:cerberusweb.core::internal/decisions/branch.tpl" node_id=$child_id trigger_id=$trigger_id data=$data nodes=$nodes tree=$tree depths=$depths}
		{/foreach}
		</div>
	{/if}
</div>


