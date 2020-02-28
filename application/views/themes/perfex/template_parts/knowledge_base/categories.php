<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php foreach($articles as $category){ ?>
<div class="col-md-12">
    <div class="article_group_wrapper">
        <h4 class="bold"><i class="fa fa-folder-o"></i> <a href="<?php echo site_url('knowledge-base/category/'.$category['group_slug']); ?>"><?php echo $category['name']; ?></a>
            <small><?php echo count($category['articles']); ?></small>
        </h4>
        <p><?php echo $category['description']; ?></p>
    </div>
</div>
<?php } ?>
