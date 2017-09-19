{$properties = $context_ext->getDefaultProperties()}
{CerberusContexts::getContext($card->_context, null, $labels, $values, '', true)}
{foreach from=$properties item=property}
<div>{include file="devblocks:cerb.project_boards::boards/board/card_property.tpl" dict=$card k=$property labels=$labels types=$values._types}</div>
{/foreach}
