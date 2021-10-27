<?php
/*
  Plugin Name: Easy Videos
  Plugin URI: 
  Description: Easy Videos fetch youtube videos from given Youtube Channel ID using Youtube Data API V3 and saved in "video" post type along with Video image as post featured image. If a video associated with Playlist which is added here as Video Category. Also, video iframe is added with Video description for front end views. 
  Version: 1.0
  Author: 
  Author URI: 
 */


 //Give a version of the Plugin
define('EASY_VIDEOS_VERSION', 10);


// Add action for Plugin Update -use it in future for plugin update data migration if required 
add_action('plugins_loaded', 'easy_videos_plugins_update');

function easy_videos_plugins_update() {
 
}

/* Uninstall and Activation handlers */
register_activation_hook(__FILE__, 'easy_videos_activate');
register_deactivation_hook(__FILE__, 'easy_videos_deactivate');
register_uninstall_hook(__FILE__, 'easy_videos_deactivate_uninstall');


// plugin activation hook to add plugin version
function easy_videos_activate() {
  add_option('EASY_VIDEOS_VERSION',EASY_VIDEOS_VERSION);
    
  
}

// plugin uninstall hook 
function easy_videos_deactivate_uninstall() {
  delete_option('EASY_VIDEOS_VERSION');
}


// plugin deactivation hook
function easy_videos_deactivate() {
  
}

/* Admin admin menu for Easy Video settings page. */
add_action('admin_menu', 'easy_videos_admin_menu');

// Admin menu action
function easy_videos_admin_menu()
{
  add_menu_page('Easy Videos - Settings', 'Easy Videos - Settings', 'administrator', 'easy-videos', 'easy_videos_adminsettings','dashicons-admin-generic',20);
}

// Easy Video - Settings page - to capture Youtube Channel ID and Youtube API Key
function easy_videos_adminsettings()
{
  require 'settings/easy_videos_admin_settings.php';
}



//Add ajax script for Fetch Youtube Videos action
add_action( 'admin_footer', 'easy_videos_admin_footer_enque' );
function easy_videos_admin_footer_enque($hook) {
    
        
	wp_enqueue_script( 'ajax-script-easy-videos', plugins_url( '/js/easyvideos.js', __FILE__ ), array('jquery') );

	// in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
	wp_localize_script( 'ajax-script-easy-videos', 'ajax_object',array( 'ajax_url' => admin_url( 'admin-ajax.php' )) );
}

// action handeler - called from /js/easyvideos.js for the action - "easy_videos_fetch_videos"
add_action( 'wp_ajax_easy_videos_fetch_videos', 'easy_videos_fetch_videos' );
add_action( 'wp_ajax_nopriv_easy_videos_fetch_videos', 'easy_videos_fetch_videos' );
function easy_videos_fetch_videos()
{

  //Get API KEY and Cahnnel ID
  $youtubeapikey= get_option('EASY_VIDEOS_YOUTUBE_API_KEY');
  $youtubechannelid= get_option('EASY_VIDEOS_YOUTUBE_CHANNEL_ID');

      $Max_Results = 2000; 
      

 //Validate if Youtube Data API key and ChannelID exist     
if($youtubeapikey!=''&& $youtubechannelid!=''){


                // Get all Playlists of Channel - add the playlists as Terms ( categories) for custom Taxonomy -asyyoutubevideos-category

                $playlists = @file_get_contents('https://www.googleapis.com/youtube/v3/playlists?part=snippet%2CcontentDetails&channelId='.$youtubechannelid.'&maxResults='.$Max_Results.'&key='.$youtubeapikey.''); 


                $playlistwpassociation=array();
                $i=0;
                if($playlists){ 
                  $playlistsdata = json_decode($playlists); 

                foreach($playlistsdata->items as $item){ 
                      $termid="";
                      $term_id=wp_insert_term($item->snippet->title,'video-category');
                      
                      if($term_id->error_data['term_exists'])
                      $termid=$term_id->error_data['term_exists'];
                      else
                      $termid=$term_id['term_id'];

                      $playlistwpassociation[$i]['playlistid']=$item->id;
                      $playlistwpassociation[$i++]['termid']=$termid;
                }

                }

                //Gather video ids associated with playlist

                $videoidstermsid=array();
                $i=0;

                if(!empty($playlistwpassociation))
                {
                  foreach($playlistwpassociation as $singleitem)
                  {
                      $playlists = @file_get_contents('https://www.googleapis.com/youtube/v3/playlistItems?part=snippet%2CcontentDetails&playlistId='.$singleitem['playlistid'].'&maxResults='.$Max_Results.'&key='.$youtubeapikey.''); 

                      if($playlists)
                      {
                          $playlistsdata = json_decode($playlists);

                          foreach($playlistsdata->items as $item){ 
                            
                      
                            $videoidstermsid[$i]['playlistid']=$item->snippet->playlistId;
                            $videoidstermsid[$i]['videoId']=$item->snippet->resourceId->videoId;
                            $videoidstermsid[$i++]['termid']=$singleitem['termid'];
                      }

                      }
                  }
                    
                }


                
                      
                      // Get videos from channel by YouTube Data API 
                      $apiData = @file_get_contents('https://www.googleapis.com/youtube/v3/search?order=date&part=snippet&channelId='.$youtubechannelid.'&maxResults='.$Max_Results.'&key='.$youtubeapikey.''); 
                      if($apiData){ 
                          $videoList = json_decode($apiData); 
                          $i=0;

                          foreach($videoList->items as $item){ 
                          

                            if(isset($item->id->videoId) && $item->id->videoId!='')
                            {

                              $api_url = 'https://www.googleapis.com/youtube/v3/videos?part=snippet%2CcontentDetails%2Cstatistics&id=' . $item->id->videoId.'&key=' . $youtubeapikey;
                        
                              $data = json_decode(file_get_contents($api_url));

                            //Prepare the Youtube iframe video player content.

                            $embedvideo="";

                            $embedvideo='<div class="youtube-channel-video-embed vid-' .$item->id->videoId . ' video-' . $i . '"><iframe width="500" height="300" src="https://www.youtube.com/embed/' . $item->id->videoId . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen>' . $item->snippet->title . '</iframe></div>';


                            // Create post object
                            $easy_video_post = array(
                              'post_title'    => wp_strip_all_tags($item->snippet->title),
                              'post_content'  => $embedvideo.'<br/>'.$data->items[0]->snippet->description,
                              'post_status'   => 'publish',
                              'post_author'  => get_current_user_id(),
                              'post_type' => 'video',
                            );
                            

                            //validate if the same video exist or not

                            if(!post_exists( wp_strip_all_tags($item->snippet->title),'','','video','publish')) {


                            // Insert the post into the database
                            $insertid=wp_insert_post($easy_video_post);

                            if(!empty($videoidstermsid))
                            {
                                foreach($videoidstermsid as $vterms)
                                {
                                    if($vterms['videoId']==$item->id->videoId)
                                    {
                                      wp_set_post_terms( $insertid, array($vterms['termid']), 'video-category' );
                                    }
                                }
                            }

                            
                            //add youtube video id in post meta for future use.
                              if($insertid)
                              {
                                update_post_meta($insertid,'youtubevideoid',$item->id->videoId);

                                $imagepathinfo = pathinfo($data->items[0]->snippet->thumbnails->maxres->url);
                              
                                // Add Featured Image to Post
                              $image_url        = $data->items[0]->snippet->thumbnails->maxres->url; // Define the image URL here
                                $image_name       = $imagepathinfo['filename'].'.'.$imagepathinfo['extension'];
                                $upload_dir       = wp_upload_dir(); // Set upload folder
                                $image_data       = file_get_contents($image_url); // Get image data
                                $unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); // Generate unique name
                                $filename         = basename( $unique_file_name ); // Create image file name

                                // Check folder permission and define file location
                                if( wp_mkdir_p( $upload_dir['path'] ) ) {
                                    $file = $upload_dir['path'] . '/' . $filename;
                                } else {
                                    $file = $upload_dir['basedir'] . '/' . $filename;
                                }

                                // Create the image  file on the server
                                file_put_contents( $file, $image_data );

                                // Check image file type
                                $wp_filetype = wp_check_filetype( $filename, null );

                                // Set attachment data
                                $attachment = array(
                                    'post_mime_type' => $wp_filetype['type'],
                                    'post_title'     => sanitize_file_name( $filename ),
                                    'post_content'   => '',
                                    'post_status'    => 'inherit'
                                );

                                // Create the attachment
                                $attach_id = wp_insert_attachment( $attachment, $file, $insertid );

                                // Include image.php
                                require_once(ABSPATH . 'wp-admin/includes/image.php');

                                // Define attachment metadata
                                $attach_data = wp_generate_attachment_metadata( $attach_id, $file );

                                // Assign metadata to attachment
                                wp_update_attachment_metadata( $attach_id, $attach_data );

                                // And finally assign featured image to post
                                set_post_thumbnail( $insertid, $attach_id );
                              
                              }

                            $i++;
                            }

                          }

                          }

                          echo "<strong>Video information added: ".$i."</strong>";

                      }else{ 
                        echo 0;
                      }

                    }// If API KEY and ChannelID present
                    else
                    echo 0; //Return error


     // print_r($videoList);
     wp_die();
}

//Register post type - video
add_action('init', 'create_easyvideo_cpt');

add_action('init', 'create_easyvideoscategory_tax');

// Register Custom Post Type Easy Video - video-category
function create_easyvideo_cpt() {

	$labels = array(
		'name' => _x( 'Easy Videos', 'Post Type General Name', 'textdomain' ),
		'singular_name' => _x( 'Easy Video', 'Post Type Singular Name', 'textdomain' ),
		'menu_name' => _x( 'Easy Videos', 'Admin Menu text', 'textdomain' ),
		'name_admin_bar' => _x( 'Easy Video', 'Add New on Toolbar', 'textdomain' ),
		'archives' => __( 'Easy Video Archives', 'textdomain' ),
		'attributes' => __( 'Easy Video Attributes', 'textdomain' ),
		'parent_item_colon' => __( 'Parent Easy Video:', 'textdomain' ),
		'all_items' => __( 'All Easy Videos', 'textdomain' ),
		'add_new_item' => __( 'Add New Easy Video', 'textdomain' ),
		'add_new' => __( 'Add New', 'textdomain' ),
		'new_item' => __( 'New Easy Video', 'textdomain' ),
		'edit_item' => __( 'Edit Easy Video', 'textdomain' ),
		'update_item' => __( 'Update Easy Video', 'textdomain' ),
		'view_item' => __( 'View Easy Video', 'textdomain' ),
		'view_items' => __( 'View Easy Videos', 'textdomain' ),
		'search_items' => __( 'Search Easy Video', 'textdomain' ),
		'not_found' => __( 'Not found', 'textdomain' ),
		'not_found_in_trash' => __( 'Not found in Trash', 'textdomain' ),
		'featured_image' => __( 'Featured Image', 'textdomain' ),
		'set_featured_image' => __( 'Set featured image', 'textdomain' ),
		'remove_featured_image' => __( 'Remove featured image', 'textdomain' ),
		'use_featured_image' => __( 'Use as featured image', 'textdomain' ),
		'insert_into_item' => __( 'Insert into Easy Video', 'textdomain' ),
		'uploaded_to_this_item' => __( 'Uploaded to this Easy Video', 'textdomain' ),
		'items_list' => __( 'Easy Videos list', 'textdomain' ),
		'items_list_navigation' => __( 'Easy Videos list navigation', 'textdomain' ),
		'filter_items_list' => __( 'Filter Easy Videos list', 'textdomain' ),
	);
	$args = array(
		'label' => __( 'Easy Video', 'textdomain' ),
		'description' => __( '', 'textdomain' ),
		'labels' => $labels,
		'menu_icon' => '',
		'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'author', 'comments', 'custom-fields'),
		'taxonomies' => array('video-category'),
		'public' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'menu_position' => 5,
		'show_in_admin_bar' => true,
		'show_in_nav_menus' => true,
		'can_export' => true,
		'has_archive' => true,
		'hierarchical' => false,
		'exclude_from_search' => false,
		'show_in_rest' => true,
		'publicly_queryable' => true,
		'capability_type' => 'post',
	);
	register_post_type( 'video', $args );

}



// Register Taxonomy Easy Videos Category - video-category
function create_easyvideoscategory_tax() {

	$labels = array(
		'name'              => _x( 'Easy Videos Categories', 'taxonomy general name', 'textdomain' ),
		'singular_name'     => _x( 'Easy Videos Category', 'taxonomy singular name', 'textdomain' ),
		'search_items'      => __( 'Search Easy Videos Categories', 'textdomain' ),
		'all_items'         => __( 'All Easy Videos Categories', 'textdomain' ),
		'parent_item'       => __( 'Parent Easy Videos Category', 'textdomain' ),
		'parent_item_colon' => __( 'Parent Easy Videos Category:', 'textdomain' ),
		'edit_item'         => __( 'Edit Easy Videos Category', 'textdomain' ),
		'update_item'       => __( 'Update Easy Videos Category', 'textdomain' ),
		'add_new_item'      => __( 'Add New Easy Videos Category', 'textdomain' ),
		'new_item_name'     => __( 'New Easy Videos Category Name', 'textdomain' ),
		'menu_name'         => __( 'Easy Videos Category', 'textdomain' ),
	);
	$args = array(
		'labels' => $labels,
		'description' => __( '', 'textdomain' ),
		'hierarchical' => false,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'show_tagcloud' => true,
		'show_in_quick_edit' => true,
		'show_admin_column' => false,
		'show_in_rest' => true,
	);
	register_taxonomy( 'video-category', array('video'), $args );

}