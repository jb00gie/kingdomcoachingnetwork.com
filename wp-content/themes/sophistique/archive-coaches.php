<?php get_header(); ?>

 <?php $coach_directory = new WP_Query("page_id=547"); while($coach_directory->have_posts()) : $coach_directory->the_post();?>
    <?php the_content(); ?>
 <?php endwhile; ?>
<?php wp_reset_query(); ?>

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
		
		<h3><?php echo get_field_object('coach_specialism')['label']; ?>:</h3>
		<p><?php the_field('coach_specialism'); ?></p>

		<h3><?php echo get_field_object('coach_experience')['label']; ?>:</h3>
		<p><?php the_field('coach_experience'); ?></p>

		<h3><?php echo get_field_object('coach_status')['label']; ?>:</h3>
		<p><?php the_field('coach_status'); ?></p>

		<a href="<?php the_permalink(); ?>" class="btn btn-small btn-success pull-right">Read More...</a>
	</div>
</div>
<hr>

<?php endwhile; ?>

<?php get_footer(); ?>