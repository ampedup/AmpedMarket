	<div id="inbox" class="mainContent">
	<h2><?=$title;?></h2>
	<?php if(isset($returnMessage)) echo $returnMessage; ?><br />

	<?=anchor('messages','Return to inbox');?><br /><br />	
	<div class="clear"></div>
	</div>
