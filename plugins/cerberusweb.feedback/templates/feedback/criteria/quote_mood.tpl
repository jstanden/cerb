<b>{'common.operator'|devblocks_translate|capitalize}:</b><br>
<blockquote style="margin:5px;">
	<select name="oper">
		<option value="in">{'search.oper.in_list'|devblocks_translate}</option>
		<option value="not in">{'search.oper.in_list.not'|devblocks_translate}</option>
	</select>
</blockquote>

<label><input name="moods[]" type="checkbox" value="1"><span style="background-color:rgb(235, 255, 235);color:rgb(0, 180, 0);font-weight:bold;">{'feedback.mood.praise'|devblocks_translate|capitalize}</span></label><br>
<label><input name="moods[]" type="checkbox" value="0"><span style="font-weight:normal;">{'feedback.mood.neutral'|devblocks_translate|capitalize}</span></label><br>
<label><input name="moods[]" type="checkbox" value="2"><span style="background-color: rgb(255, 235, 235);color: rgb(180, 0, 0);font-weight:bold;">{'feedback.mood.criticism'|devblocks_translate|capitalize}</span></label><br>
