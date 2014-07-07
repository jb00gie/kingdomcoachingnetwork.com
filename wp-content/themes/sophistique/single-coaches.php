<?php get_header(); ?>

<h1><?php the_title(); ?></h1>
<?php if(has_post_thumbnail()): ?>
	<?php the_post_thumbnail('medium'); ?> 
<?php endif; ?>

<h3>Bio:</h3>
<?php the_content(); ?>
<h3>Summary:</h3>
<?php the_excerpt(); ?>

<h3><?php echo get_field_object('coach_specialism')['label']; ?>:</h3>
<p><?php the_field('coach_specialism'); ?></p>

<h3><?php echo get_field_object('coach_experience')['label']; ?>:</h3>
<p><?php the_field('coach_experience'); ?></p>

<h3><?php echo get_field_object('coach_typical_clients')['label']; ?>:</h3>
<p><?php the_field('coach_typical_clients'); ?></p>

<h3><?php echo get_field_object('coach_accredited_training')['instructions']; ?></h3>
<p>
	<?php if(get_field('coach_accredited_training') == 1): ?>
		<strong>Yes</strong>
	<?php else: ?>
		<strong>No</strong>
	<?php endif; ?>
</p>

<h3><?php echo get_field_object('coach_training_body')['label']; ?>:</h3>
<p><?php the_field('coach_training_body'); ?></p>

<?php get_footer(); ?>