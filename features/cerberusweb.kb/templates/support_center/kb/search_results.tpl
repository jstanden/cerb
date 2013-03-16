<div id="kb">

<div class="header"><h1>{$translate->_('portal.kb.public.search_results')}{if !empty($q)} {$translate->_('portal.kb.public.for')} '{$q}'{/if}</h1></div>

<div id="view{$view->id}">
{$view->render()}
</div>

</div>