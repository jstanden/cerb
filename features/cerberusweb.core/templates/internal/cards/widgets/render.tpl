{$width_units = $widget->width_units|default:1}
{$widget_extension = $widget->getExtension()}
{$widget_is_hidden = $widget->isHidden($card_popup_dict)}
<div class="cerb-card-widget {if $widget_is_hidden}cerb-card-widget--hidden{/if}" data-widget-id="{$widget->id}" data-widget-name="{$widget->name}" style="flex:{$width_units} {$width_units} {$width_units * 0.25 * 100}%;">
	<div>
		<div class="cerb-card-widget--header {if $active_worker->is_superuser}cerb-draggable{/if}">
			<b>
				<a class="cerb-card-widget--link no-underline">
					{if $widget_is_hidden}<span class="glyphicons glyphicons-eye-close"></span> {/if}{$widget->name}<!--
                    --><span class="glyphicons glyphicons-chevron-down"></span>
                </a>
				{if $active_worker->is_superuser}
				<span class="glyphicons glyphicons-menu-hamburger" style="vertical-align:baseline;color:rgb(200,200,200);float:right;display:none;"></span>
				{/if}
			</b>
		</div>
		<div>
			<ul class="cerb-card-widget--menu cerb-popupmenu cerb-float" style="display:none;margin-top:-12px;">
				{if $active_worker->is_superuser}
				<li class="cerb-card-widget-menu--edit" data-context="{CerberusContexts::CONTEXT_CARD_WIDGET}" data-context-id="{$widget->id}" data-edit="true" data-width="75%">
					<div>
						<a>{'common.edit'|devblocks_translate|capitalize}</a>
					</div>
				</li>
				<li class="cerb-card-widget-menu--export-widget">
					<div>
						<a>{'common.export.widget'|devblocks_translate|capitalize}</a>
					</div>
				</li>
				{/if}
				<li class="cerb-card-widget-menu--refresh">
					<div>
						<a>{'common.refresh'|devblocks_translate|capitalize}</a>
					</div>
				</li>
			</ul>
		</div>
		<div id="cardWidget{if $context_id}{$widget->getUniqueId($context_id)}{else}{$widget->getUniqueId($dict->id)}{/if}" class="cerb-card-widget--content">
			{* We only have full content on create/edit *}
			{if isset($full) && $full}
				{$widget_extension->render($widget, $context, $context_id, [])}
			{/if}
		</div>
	</div>
</div>