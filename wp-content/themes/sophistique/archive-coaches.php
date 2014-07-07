<?php get_header(); ?>

<?php while (have_posts()) : the_post(); ?>
	<div class="row">
	<div class="span3">
		<?php if(has_post_thumbnail()): ?>
			<?php the_post_thumbnail('thumbnail'); ?> 
		<?php endif; ?>
	</div>
	
	<div class="span9">
		<h1><?php the_title(); ?></h1>
		<?php the_excerpt(); ?>
		<a href="<?php the_permalink(); ?>" class="btn btn-small btn-success pull-right">Read More...</a>
	</div>
</div>
<hr>
<?php endwhile; ?>

<?php get_footer(); ?>