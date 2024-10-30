<?php
/**
 * The template for displaying eblast previews.
 *
 */

if ( have_posts() ) : the_post();

$custom_fields = get_post_custom($post->ID); 
$t_array = get_option('chimp_template');
$t_index = isset($custom_fields["_email_template"][0]) ? $custom_fields["_email_template"][0] : 0;
$template = $t_array[$t_index]["html"];
//get rid of comments
$template = preg_replace('!/\*.*?\*/!s', '', $template);
$template = preg_replace('/\n\s*\n/', "\n", $template);

global $wp_filter;
$corefilters = array('convert_chars','wpautop','wptexturize');
foreach($wp_filter["the_content"] as $priority => $filters)
	foreach($filters as $name => $details)
		{
		//keep only core text processing or shortcode
		if(!in_array($name,$corefilters) && !strpos($name,'hortcode'))
			{
			$r = remove_filter( 'the_content', $name, $priority );
			/*
			if($r)
				echo "removed $name $priority<br />";
			else
				echo "error $name $priority<br />";
			*/
			}
		}

ob_start();
global $rsvp_options;
; ?>
<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
<?php if($custom_fields["_email_headline"][0]) { ; ?>
<h1 class="entry-title"><?php the_title(); ?></h1>
<?php } ; ?>
<div class="entry-content">
<?php the_content(); ?>
</div><!-- .entry-content -->
</div><!-- #post-## -->
<?php endif;
$content = ob_get_clean();
$template = do_shortcode($template);
$message = str_replace('{content}',$content,$template);
$text = trim(strip_tags($content)).'

==============================================
*|LIST:DESCRIPTION|*

Forward to a friend:
*|FORWARD|*

*|FORWARD|*        
Unsubscribe *|EMAIL|* from this list:
*|UNSUB|*

Update your profile:
*|UPDATE_PROFILE|*

Our mailing address is:
*|LIST:ADDRESS|*
Copyright (C) *|CURRENT_YEAR|* *|LIST:COMPANY|* All rights reserved.';

echo str_replace('{content}',chimpblast_ui($message,$text).$content,$template);

; ?>