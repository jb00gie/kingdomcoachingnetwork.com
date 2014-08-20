<?php get_header(); ?>

 <?php $coach_directory = new WP_Query("page_id=547"); while($coach_directory->have_posts()) : $coach_directory->the_post();?>
    <?php the_content(); ?>
 <?php endwhile; ?>

 <?php wp_reset_query(); ?>

<h1><?php the_title(); ?></h1>
<?php if(has_post_thumbnail()): ?>
	<?php the_post_thumbnail('medium'); ?> 
<?php endif; ?>

<h3>Bio:</h3>
<?php the_content(); ?>

<h3><?php echo get_field_object('coach_contact_info')['label']; ?>:</h3>
<?php the_field('coach_contact_info'); ?>

<h3><?php echo get_field_object('coach_specialism')['label']; ?>:</h3>
<p><?php the_field('coach_specialism'); ?></p>

<h3><?php echo get_field_object('coach_experience')['label']; ?>:</h3>
<p><?php the_field('coach_experience'); ?></p>

<h3><?php echo get_field_object('coach_type_of_experience')['label']; ?>:</h3>
<p><?php the_field('coach_type_of_experience'); ?></p>

<h3><?php echo get_field_object('coach_typical_clients')['label']; ?>:</h3>
<p><?php the_field('coach_typical_clients'); ?></p>

<h3><?php echo get_field_object('coach_status')['label']; ?>:</h3>
<p><?php the_field('coach_status'); ?></p>

<h3><?php echo get_field_object('coach_training_body')['label']; ?>:</h3>
<p><?php the_field('coach_training_body'); ?></p>

<h3><?php echo get_field_object('coach_location')['label']; ?>:</h3>
<p><?php the_field('coach_location'); ?></p>

<h3><?php echo get_field_object('coach_countries_served')['label']; ?>:</h3>
<p><?php the_field('coach_countries_served'); ?></p>

<?php get_footer(); ?>



