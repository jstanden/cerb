<form id="frmDecisionNodeReorder" onsubmit="return false;" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="behavior">
<input type="hidden" name="action" value="saveDecisionReorderPopup">
{if isset($node)}<input type="hidden" name="id" value="{$node->id}">{/if}
{if isset($trigger)}<input type="hidden" name="trigger_id" value="{$trigger->id}">{/if}
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset>
	<legend>{if !empty($node)}{$node->title}{elseif !empty($trigger)}{$trigger->title}{/if}</legend>
	
	{* [TODO] Show a tree excluding the current branch *}
	<div class="container">
		{foreach from=$children item=child}
		<div class="item" style="margin:2px;">
			<span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;"></span>
			
			{if $child->node_type == 'subroutine'}
				<div class="badge badge-lightgray">
					<input type="hidden" name="child_id[]" value="{$child->id}">
					<span style="text-decoration:none;font-weight:bold;color:var(--cerb-color-background-contrast-50);" node_id="{$child->id}" trigger_id="{$trigger_id}">
						<span style="font-weight:normal;"></span> {$child->title}{if $is_writeable} &#x25be;{/if}
					</span>
				</div>
				
			{elseif $child->node_type == 'switch'}
				<div class="badge badge-lightgray">
					<input type="hidden" name="child_id[]" value="{$child->id}">
					<span style="text-decoration:none;font-weight:bold;color:rgb(68,154,220);" node_id="{$child->id}" trigger_id="{$trigger_id}">
						{*<span style="font-weight:normal;font-size:1.2em;">&#x22d4;</span> *}{$child->title}{if $is_writeable} &#x25be;{/if}
					</span>
				</div>
				
			{elseif $child->node_type == 'loop'}
				<div class="badge badge-lightgray">
					<input type="hidden" name="child_id[]" value="{$child->id}">
					<span style="text-decoration:none;font-weight:bold;color:var(--cerb-color-background-contrast-100);" node_id="{$child->id}" trigger_id="{$trigger_id}">
						<span style="font-weight:normal;">&#x27f3;</span> {$child->title}{if $is_writeable} &#x25be;{/if}
					</span>
				</div>
			
			{elseif $child->node_type == 'outcome'}
				<div class="badge badge-lightgray">
					<input type="hidden" name="child_id[]" value="{$child->id}">
					<span style="text-decoration:none;font-weight:bold;{if preg_match('#^yes($|,| )#i',$child->title)}color:rgb(0,150,0);{elseif preg_match('#^no($|,| )#i',$child->title)}color:rgb(150,0,0);{/if}" node_id="{$child->id}" trigger_id="{$trigger_id}">
						{$child->title}{if $is_writeable} &#x25be;{/if}
					</span>
				</div>
			
			{elseif $child->node_type == 'action'}
				<div class="badge badge-lightgray">
					<input type="hidden" name="child_id[]" value="{$child->id}">
					<span style="text-decoration:none;font-weight:normal;font-style:italic;" node_id="{$child->id}" trigger_id="{$trigger_id}">
						{$child->title}{if $is_writeable} &#x25be;{/if}
					</span>
				</div>
				
			{/if}
		</div>
		{/foreach}
	</div>
	
</fieldset>
<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#frmDecisionNodeReorder');
	var $popup = genericAjaxPopupFind($frm);
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"Reorder");

		$frm.find('DIV.container').sortable({ items:'DIV.item', placeholder:'ui-state-highlight' });
		
		$frm.find('button.submit').click(function() {
			genericAjaxPost($frm,'','',function() {
				{if !empty($node)}{$trigger_id = $node->trigger_id}{elseif !empty($trigger)}{$trigger_id = $trigger->id}{/if}
				genericAjaxGet('decisionTree{$trigger_id}','c=profiles&a=invoke&module=behavior&action=renderDecisionTree&id={$trigger_id}');
				genericAjaxPopupDestroy($popup);
			});
		})
	});
});
</script>