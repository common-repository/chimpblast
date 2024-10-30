<?php

$wp_config = '../wp-config.php';
	
for($i = 0; $i < 5; $i++)
	{
	if(file_exists($wp_config) )
		{
		require($wp_config);
		break;
		}
	$wp_config = '../'.$wp_config;
	}

global $wp_filter;

$corefilters = array('convert_chars','wpautop','wptexturize','event_content');
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


$type = $_GET["load"];

global $wpdb;
global $wp_query;

if($postID = $_GET["loadpost"])
	{
	$querystring = "p=$postID&post_type=$type";
	query_posts($querystring);

have_posts();
the_post(); 

; ?>

<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>><h2 class="entry-title"><?php the_title(); ?></h2>
<div class="entry-content">
<?php the_content(); ?>
</div><!-- .entry-content --></div><!-- #post-## -->

<?php
	}
if($_GET["events"])
	{
$atts = array('');
echo rsvpmaker_upcoming($atts);
	
	}

; ?>