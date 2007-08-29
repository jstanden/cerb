{include file="$tpl_path/portal/kb/header.tpl.php"}

<table style="text-align: left; width: 100%;" border="0" cellpadding="0" cellspacing="0">
	<tbody>
		<tr>
			<td style="padding: 5px; vertical-align: top;">
				<form action="{devblocks_url}{/devblocks_url}" method="post" name="articleForm" onsubmit="myEditor.saveHTML();">
				<input type="hidden" name="a" value="doArticleEdit">
				<input type="hidden" name="id" value="{if !empty($article)}{$article->id}{else}0{/if}">
				<h2>Article Editor</h2>
				
				<b>Title:</b><br>
				<input type="text" name="title" size="64" maxlength="128" value="{$article->title|escape}"><br>
				<br>
				
				<textarea name="content" id="article_content" rows="10" cols="80">{$article->content}</textarea><br>
				
				<b>Tags:</b> (comma-separated)<br>
				
				<div id="tagsautocomplete" style="width:98%" class="yui-ac">
				<input type="text" name="tags" id="tags" size="64" value="{if !empty($tags)}{foreach from=$tags item=tag name=tags}{$tag->name|escape}{if !$smarty.foreach.tags.last}, {/if}{/foreach}{/if}" maxlength="255" class="yui-ac-input"><br>
				<br>
				<div id="tagscontainer" class="yui-ac-container"></div>
				</div>			
				
				<button type="submit"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/check.gif{/devblocks_url}" alt="Save" align="top"> {$translate->_('common.save_changes')}</button>
				<button type="button" onclick="document.location='{if !empty($article)}{devblocks_url}c=article&id={$article->id}{/devblocks_url}{else}{devblocks_url}{/devblocks_url}{/if}';"><img src="{devblocks_url}c=resource&p=usermeet.core&f=images/delete.gif{/devblocks_url}" alt="Cancel" align="top"> {$translate->_('common.cancel')|capitalize}</button>
				</form>
			</td>
		</tr>
	</tbody>
</table>

<script>
{literal}
var myEditor = null;
YAHOO.util.Event.addListener(window,"load",function() {
	myEditor = new YAHOO.widget.Editor('article_content', {
	    height: '300px',
	    width: '650px',
	    dompath: false,
	    animate: false
		});	    
	myEditor.render();
	});


var myDataSource = new YAHOO.widget.DS_XHR({/literal}"{devblocks_url}ajax.php{/devblocks_url}"{literal}, ["\n", "\t"] );
myDataSource.scriptQueryAppend = "c=contacts&a=getTagAutoCompletions"; 
myDataSource.responseType = YAHOO.widget.DS_XHR.TYPE_FLAT;
myDataSource.maxCacheEntries = 60;
myDataSource.queryMatchSubset = true;
myDataSource.connTimeout = 3000;

var myInput = document.getElementById('tags'); 
var myContainer = document.getElementById('tagscontainer'); 
	
var myAutoComp = new YAHOO.widget.AutoComplete(myInput,myContainer, myDataSource);
myAutoComp.delimChar = ",";
myAutoComp.queryDelay = 1;
//myAutoComp.useIFrame = true; 
myAutoComp.typeAhead = false;
myAutoComp.useShadow = true;
myAutoComp.prehighlightClassName = "yui-ac-prehighlight"; 
myAutoComp.allowBrowserAutocomplete = false;

var contactOrgAutoCompSelected = function contactOrgAutoCompSelected(sType, args, me) {
			org_str = new String(args[2]);
			org_arr = org_str.split(',');
			document.articleForm.contact_orgid.value=org_arr[1];
		};

obj=new Object();
myAutoComp.itemSelectEvent.subscribe(contactOrgAutoCompSelected, obj);	
	
{/literal}
</script>

{include file="$tpl_path/portal/kb/footer.tpl.php"}