<?php defined('BASEPATH') or exit('No direct script access allowed');
if(count($articles) > 0){
	?>
	<div class="col-md-12 kb-search-results">
		<h2><?php echo $title; ?></h2>
		<hr />
	</div>
	<?php
	get_template_part('knowledge_base/category_articles_list', array('articles'=>$articles));
}
