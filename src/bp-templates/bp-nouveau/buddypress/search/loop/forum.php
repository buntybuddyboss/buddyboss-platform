<li class="bboss_search_item bboss_search_item_forum">
	<div class="list-wrap">
		<div class="item-avatar">
			<a href="<?php bbp_forum_permalink( get_the_ID() ); ?>">
				<img src="<?php echo bbp_get_forum_thumbnail_src( get_the_ID() ) ?>" alt=""/>
			</a>
		</div>

		<div class="item">
			<div class="item-title"><a href="<?php bbp_forum_permalink( get_the_ID()); ?>"><?php bbp_forum_title(get_the_ID()); ?></a></div>
			<div class="item-desc"><?php bbp_forum_content(get_the_ID());?></div>
		</div>
	</div>
</li>
