<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="col-md-12">
    <?php foreach($articles as $category){ ?>
        <h4 class="bold mbot25 mtop25"><i class="fa fa-folder-o"></i> <?php echo $category['name']; ?></h4>
        <ul class="list-unstyled articles_list">
            <?php foreach($category['articles'] as $article) { ?>
                <li>
                   <h4 class="article-heading">
                        <a href="<?php echo site_url('knowledge-base/article/'.$article['slug']); ?>">
                            <?php echo $article['subject']; ?>
                        </a>
                   </h4>
                    <div class="text-muted mtop10"><?php echo strip_tags(mb_substr($article['description'],0,250)); ?>...</div>
                </li>
            <?php } ?>
        </ul>
    <?php } ?>
</div>
