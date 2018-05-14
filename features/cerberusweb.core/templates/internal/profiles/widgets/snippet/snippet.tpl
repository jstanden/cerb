{$snippet_content = $snippet->content|regex_replace:'#({{.*?}})#':'[ph]\1[/ph]'}
{$snippet_content = $snippet_content|escape:'htmlall'}
{$snippet_content = $snippet_content|regex_replace:'#(\[ph\](.*?)\[/ph\])#':'<div class="bubble">\2</div>'}

<div class="emailbody">{$snippet_content nofilter}</div>
