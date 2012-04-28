<?php
/**
 * XML-RPC protocol support for NextGEN Gallery
 *
 * @package NextGEN Gallery
 * @author Alex Rabe
 * @copyright 2009-2012
 */
class nggXMLRPC{
	
	/**
	 * Init the methods for the XMLRPC hook
	 * 
	 */	
	function __construct() {
		
		add_filter('xmlrpc_methods', array(&$this, 'add_methods') );
	}
	
	function add_methods($methods) {
	    
		$methods['ngg.installed'] = array(&$this, 'nggInstalled');
        // Image methods
	    $methods['ngg.getImage'] = array(&$this, 'getImage');
	    $methods['ngg.getImages'] = array(&$this, 'getImages');
	    $methods['ngg.uploadImage'] = array(&$this, 'uploadImage');
        $methods['ngg.editImage'] = array(&$this, 'editImage');
        $methods['ngg.deleteImage'] = array(&$this, 'deleteImage');
        // Gallery methods
	    $methods['ngg.getGallery'] = array(&$this, 'getGallery');
	    $methods['ngg.getGalleries'] = array(&$this, 'getGalleries');
	    $methods['ngg.newGallery'] = array(&$this, 'newGallery');
        $methods['ngg.editGallery'] = array(&$this, 'editGallery');
        $methods['ngg.deleteGallery'] = array(&$this, 'deleteGallery');
        // Album methods
	    $methods['ngg.getAlbum'] = array(&$this, 'getAlbum');
   	    $methods['ngg.getAlbums'] = array(&$this, 'getAlbums');
        $methods['ngg.newAlbum'] = array(&$this, 'newAlbum');
	    $methods['ngg.editAlbum'] = array(&$this, 'editAlbum');
        $methods['ngg.deleteAlbum'] = array(&$this, 'deleteAlbum');

        //Added By xami
        $methods['ngg.addImages'] = array(&$this, 'addImages');
        $methods['ngg.findMeta'] = array(&$this, 'findMeta');
        $methods['ngg.getCategory'] = array(&$this, 'getCategory');
        $methods['ngg.getTag'] = array(&$this, 'getTag');
        $methods['ngg.newPost'] = array(&$this, 'newPost');

		return $methods;
	}

    // Added By xami
    function newPost($args){
        $this->escape($args);

        $blog_ID     = (int) $args[0]; // we will support this in the near future
        $username   = $args[1];
        $password   = $args[2];

        if ( !$user = $this->login($username, $password) )
            return $this->error;

        $post_mark  = $args[3];
        $categories = $args[4];
        $mt_keywords = $args[5];

        $content_struct = $args[6];		

        $post_id = $this->findMeta('lolita', $post_mark);
		if(intval($post_id)>0)
        	return intval($post_id);
		

        $cids=array();
        foreach($categories as $cat_name){
            $cids[] = $this->getCategory($cat_name, 0);
        }

        $tids=array();
        foreach($mt_keywords as $tag_name){
            $tids[] = $this->getTag($tag_name);
        }


        $publish=true;
        $page_template = '';
        if ( !empty( $content_struct['post_type'] ) ) {
            if ( $content_struct['post_type'] == 'page' ) {
                if ( $publish )
                    $cap  = 'publish_pages';
                elseif ('publish' == $content_struct['page_status'])
                    $cap  = 'publish_pages';
                else
                    $cap = 'edit_pages';
                $error_message = __( 'Sorry, you are not allowed to publish pages on this site.' );
                $post_type = 'page';
                if ( !empty( $content_struct['wp_page_template'] ) )
                    $page_template = $content_struct['wp_page_template'];
            } elseif ( $content_struct['post_type'] == 'post' ) {
                if ( $publish )
                    $cap  = 'publish_posts';
                elseif ('publish' == $content_struct['post_status'])
                    $cap  = 'publish_posts';
                else
                    $cap = 'edit_posts';
                $error_message = __( 'Sorry, you are not allowed to publish posts on this site.' );
                $post_type = 'post';
            } else {
                // No other post_type values are allowed here
                return new IXR_Error( 401, __( 'Invalid post type.' ) );
            }
        } else {
            if ( $publish )
                $cap  = 'publish_posts';
            elseif ('publish' == $content_struct['post_status'])
                $cap  = 'publish_posts';
            else
                $cap = 'edit_posts';
            $error_message = __( 'Sorry, you are not allowed to publish posts on this site.' );
            $post_type = 'post';
        }

        if ( !current_user_can( $cap ) )
            return new IXR_Error( 401, $error_message );

        // Check for a valid post format if one was given
        if ( isset( $content_struct['wp_post_format'] ) ) {
            $content_struct['wp_post_format'] = sanitize_key( $content_struct['wp_post_format'] );
            if ( !array_key_exists( $content_struct['wp_post_format'], get_post_format_strings() ) ) {
                return new IXR_Error( 404, __( 'Invalid post format' ) );
            }
        }

        // Let WordPress generate the post_name (slug) unless
        // one has been provided.
        $post_name = "";
        if ( isset($content_struct['wp_slug']) )
            $post_name = $content_struct['wp_slug'];

        // Only use a password if one was given.
        if ( isset($content_struct['wp_password']) )
            $post_password = $content_struct['wp_password'];

        // Only set a post parent if one was provided.
        if ( isset($content_struct['wp_page_parent_id']) )
            $post_parent = $content_struct['wp_page_parent_id'];

        // Only set the menu_order if it was provided.
        if ( isset($content_struct['wp_page_order']) )
            $menu_order = $content_struct['wp_page_order'];

        $post_author = $user->ID;

        // If an author id was provided then use it instead.
        if ( isset($content_struct['wp_author_id']) && ($user->ID != $content_struct['wp_author_id']) ) {
            switch ( $post_type ) {
                case "post":
                    if ( !current_user_can('edit_others_posts') )
                        return(new IXR_Error(401, __('You are not allowed to post as this user')));
                    break;
                case "page":
                    if ( !current_user_can('edit_others_pages') )
                        return(new IXR_Error(401, __('You are not allowed to create pages as this user')));
                    break;
                default:
                    return(new IXR_Error(401, __('Invalid post type.')));
                    break;
            }
            $post_author = $content_struct['wp_author_id'];
        }

        $post_title = isset( $content_struct['title'] ) ? $content_struct['title'] : null;
        $post_content = isset( $content_struct['description'] ) ? $content_struct['description'] : null;

        $post_status = $publish ? 'publish' : 'draft';

        if ( isset( $content_struct["{$post_type}_status"] ) ) {
            switch ( $content_struct["{$post_type}_status"] ) {
                case 'draft':
                case 'pending':
                case 'private':
                case 'publish':
                    $post_status = $content_struct["{$post_type}_status"];
                    break;
                default:
                    $post_status = $publish ? 'publish' : 'draft';
                    break;
            }
        }

        $post_excerpt = isset($content_struct['mt_excerpt']) ? $content_struct['mt_excerpt'] : null;
        $post_more = isset($content_struct['mt_text_more']) ? $content_struct['mt_text_more'] : null;

        $tags_input = isset($content_struct['mt_keywords']) ? $content_struct['mt_keywords'] : null;

        if ( isset($content_struct['mt_allow_comments']) ) {
            if ( !is_numeric($content_struct['mt_allow_comments']) ) {
                switch ( $content_struct['mt_allow_comments'] ) {
                    case 'closed':
                        $comment_status = 'closed';
                        break;
                    case 'open':
                        $comment_status = 'open';
                        break;
                    default:
                        $comment_status = get_option('default_comment_status');
                        break;
                }
            } else {
                switch ( (int) $content_struct['mt_allow_comments'] ) {
                    case 0:
                    case 2:
                        $comment_status = 'closed';
                        break;
                    case 1:
                        $comment_status = 'open';
                        break;
                    default:
                        $comment_status = get_option('default_comment_status');
                        break;
                }
            }
        } else {
            $comment_status = get_option('default_comment_status');
        }

        if ( isset($content_struct['mt_allow_pings']) ) {
            if ( !is_numeric($content_struct['mt_allow_pings']) ) {
                switch ( $content_struct['mt_allow_pings'] ) {
                    case 'closed':
                        $ping_status = 'closed';
                        break;
                    case 'open':
                        $ping_status = 'open';
                        break;
                    default:
                        $ping_status = get_option('default_ping_status');
                        break;
                }
            } else {
                switch ( (int) $content_struct['mt_allow_pings'] ) {
                    case 0:
                        $ping_status = 'closed';
                        break;
                    case 1:
                        $ping_status = 'open';
                        break;
                    default:
                        $ping_status = get_option('default_ping_status');
                        break;
                }
            }
        } else {
            $ping_status = get_option('default_ping_status');
        }

        if ( $post_more )
            $post_content = $post_content . '<!--more-->' . $post_more;

        $to_ping = null;
        if ( isset( $content_struct['mt_tb_ping_urls'] ) ) {
            $to_ping = $content_struct['mt_tb_ping_urls'];
            if ( is_array($to_ping) )
                $to_ping = implode(' ', $to_ping);
        }

        // Do some timestamp voodoo
        if ( !empty( $content_struct['date_created_gmt'] ) )
            $dateCreated = str_replace( 'Z', '', $content_struct['date_created_gmt']->getIso() ) . 'Z'; // We know this is supposed to be GMT, so we're going to slap that Z on there by force
        elseif ( !empty( $content_struct['dateCreated']) )
            $dateCreated = $content_struct['dateCreated']->getIso();

        if ( !empty( $dateCreated ) ) {
            $post_date = get_date_from_gmt(iso8601_to_datetime($dateCreated));
            $post_date_gmt = iso8601_to_datetime($dateCreated, 'GMT');
        } else {
            $post_date = current_time('mysql');
            $post_date_gmt = current_time('mysql', 1);
        }

        $post_category = array();
        if ( isset( $content_struct['categories'] ) ) {
            $catnames = $content_struct['categories'];
            logIO('O', 'Post cats: ' . var_export($catnames,true));

            if ( is_array($catnames) ) {
                foreach ($catnames as $cat) {
                    $post_category[] = get_cat_ID($cat);
                }
            }
        }

        $postdata = compact('post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_category', 'post_status', 'post_excerpt', 'comment_status', 'ping_status', 'to_ping', 'post_type', 'post_name', 'post_password', 'post_parent', 'menu_order', 'tags_input', 'page_template');
		
        $post_ID = $postdata['ID'] = get_default_post_to_edit( $post_type, true )->ID;

        // Only posts can be sticky
        if ( $post_type == 'post' && isset( $content_struct['sticky'] ) ) {
            if ( $content_struct['sticky'] == true )
                stick_post( $post_ID );
            elseif ( $content_struct['sticky'] == false )
                unstick_post( $post_ID );
        }

        if ( isset($content_struct['custom_fields']) )
            $this->set_custom_fields($post_ID, $content_struct['custom_fields']);

        // Handle post formats if assigned, value is validated earlier
        // in this function
        if ( isset( $content_struct['wp_post_format'] ) )
            wp_set_post_terms( $post_ID, array( 'post-format-' . $content_struct['wp_post_format'] ), 'post_format' );

        $post_ID = wp_insert_post( $postdata, true );
        if ( is_wp_error( $post_ID ) )
            return new IXR_Error(500, $post_ID->get_error_message());

        if ( !$post_ID )
            return new IXR_Error(500, __('Sorry, your entry could not be posted. Something wrong happened.'));

        logIO('O', "Posted ! ID: $post_ID");

        return strval($post_ID);
    }

    //Added By xami
    function set_custom_fields($post_id, $fields) {
        $post_id = (int) $post_id;

        foreach ( (array) $fields as $meta ) {
            if ( isset($meta['id']) ) {
                $meta['id'] = (int) $meta['id'];
                $pmeta = get_metadata_by_mid( 'post', $meta['id'] );
                $meta['value'] = stripslashes_deep( $meta['value'] );
                if ( isset($meta['key']) ) {
                    $meta['key'] = stripslashes( $meta['key'] );
                    if ( $meta['key'] != $pmeta->meta_key )
                        continue;
                    if ( current_user_can( 'edit_post_meta', $post_id, $meta['key'] ) )
                        update_metadata_by_mid( 'post', $meta['id'], $meta['value'] );
                } elseif ( current_user_can( 'delete_post_meta', $post_id, $pmeta->meta_key ) ) {
                    delete_metadata_by_mid( 'post', $meta['id'] );
                }
            } elseif ( current_user_can( 'add_post_meta', $post_id, stripslashes( $meta['key'] ) ) ) {
                add_post_meta( $post_id, $meta['key'], $meta['value'] );
            }
        }
    }

    // Added By xami
    function getCategory($cat_name, $parent=0){
        $cid=get_cat_ID( $cat_name );
        if(empty($cid)){
            $cid=wp_create_category($cat_name, $parent);
        }
        return $cid;
    }

    // Added By xami
    function getTag($tag_name){
        return wp_create_term($tag_name);
    }

    //Added By xami
    function findMeta($key, $val){

        global $wpdb;

        $query = "SELECT `post_id` FROM $wpdb->postmeta WHERE `meta_key` LIKE '" . $key . "' AND `meta_value` LIKE '" . $val . "' ORDER BY meta_id ASC LIMIT 1";
        $result = $wpdb->get_row($query);        
		return isset($result->post_id) ? $result->post_id : 0;  
    }

    //Added By xami
    function addImages($args) {

        global $ngg;

        require_once ( dirname ( dirname( __FILE__ ) ). '/admin/functions.php' );	// admin functions

        $this->escape($args);
        $blog_ID     = (int) $args[0];
        $username	 = $args[1];
        $password	 = $args[2];
        $gallery_id  = $args[3];
        $imageslist  = $args[4];
        $description = $args[4];
        $ids 		 = array();

        if ( !$user = $this->login($username, $password) )
            return $this->error;

        if( !current_user_can( 'NextGEN Manage gallery' ) )
            return new IXR_Error( 401, __( 'Sorry, you must be able to manage galleries' ) );

        if ( !empty( $imageslist ) )
            $ids = nggAdmin::add_Images($gallery_id, $imageslist, $description);

        nggAdmin::set_gallery_preview ( $gallery_id );

        return($ids);

    }

	/**
	 * Check if it's an csv string, then serialize it.
	 * 
     * @since 1.9.2
	 * @param string $data
	 * @return serialized string
	 */
	function is_serialized( $data ) {
	   
        // if it isn't a string, we don't serialize it.
        if ( ! is_string( $data ) )
            return false;
            
        if ($data && !strpos( $data , '{')) {
        	$items = explode(',', $data);
        	return serialize($items);
		}

		return $data;
	}

	/**
	 * Check if NextGEN Gallery is installed
	 * 
	 * @since 1.4
	 * 
	 * @param none
	 * @return string version number
	 */
	function nggInstalled($args) {
		global $ngg;
		return array( 'version' => $ngg->version );
	}
	
	/**
	 * Log user in.
	 *
	 * @since 2.8
	 *
	 * @param string $username User's username.
	 * @param string $password User's password.
	 * @return mixed WP_User object if authentication passed, false otherwise
	 */
	function login($username, $password) {
		if ( !get_option( 'enable_xmlrpc' ) ) {
			$this->error = new IXR_Error( 405, sprintf( __( 'XML-RPC services are disabled on this blog.  An admin user can enable them at %s'),  admin_url('options-writing.php') ) );
			return false;
		}

		$user = wp_authenticate($username, $password);

		if (is_wp_error($user)) {
			$this->error = new IXR_Error(403, __('Bad login/pass combination.'));
			return false;
		}

        wp_set_current_user( $user->ID );
		return $user;
	}

	/**
	 * Method "ngg.uploadImage"
	 * Uploads a image to a gallery
	 *
	 * @since 1.4
	 * 
	 * @copyright addapted from WP Core
	 * @param array $args Method parameters.
	 * 			- int blog_id
	 *	    	- string username
	 *	    	- string password
	 *	    	- struct data
	 *	          o string name
	 *            o string type (optional)
	 *	          o base64 bits 
	 *	          o bool overwrite (optional)
	 *			  o int gallery 
	 *			  o int image_id  (optional) 	 
	 * @return array with image meta data
	 */
	function uploadImage($args) {
		global $wpdb;
		
		require_once ( dirname ( dirname( __FILE__ ) ). '/admin/functions.php' );	// admin functions
		require_once ( 'meta.php' );			// meta data import

		$blog_ID	= (int) $args[0];
		$username	= $wpdb->escape($args[1]);
		$password	= $wpdb->escape($args[2]);
		$data		= $args[3];

		$name = $data['name'];
		$type = $data['type'];
		$bits = $data['bits'];
		
		// gallery & image id
		$gid  	= (int) $data['gallery'];  // required field
		$pid  	= (int) $data['image_id']; // optional but more foolproof of overwrite
		$image	= false; // container for the image object 

		logIO('O', '(NGG) Received '.strlen($bits).' bytes');

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		// Check if you have the correct capability for upload
		if ( !current_user_can('NextGEN Upload images') ) {
			logIO('O', '(NGG) User does not have upload_files capability');
			$this->error = new IXR_Error(401, __('You are not allowed to upload files to this site.'));
			return $this->error;
		}
		
		// Look for the gallery , could we find it ?
		if ( !$gallery = nggdb::find_gallery($gid) ) 
			return new IXR_Error(404, __('Could not find gallery ' . $gid ));
		
		// Now check if you have the correct capability for this gallery
		if ( !nggAdmin::can_manage_this_gallery($gallery->author) ) {
			logIO('O', '(NGG) User does not have upload_files capability');
			$this->error = new IXR_Error(401, __('You are not allowed to upload files to this gallery.'));
			return $this->error;
		}           
		                                                 
		//clean filename and extract extension
		$filepart = nggGallery::fileinfo( $name );
		$name = $filepart['basename'];
		
		// check for allowed extension and if it's an image file
		$ext = array('jpg', 'png', 'gif'); 
		if ( !in_array($filepart['extension'], $ext) ){ 
			logIO('O', '(NGG) Not allowed file type');
			$this->error = new IXR_Error(401, __('This is no valid image file.','nggallery'));
			return $this->error;
		}	

		// in the case you would overwrite the image, let's delete the old one first
		if(!empty($data["overwrite"]) && ($data["overwrite"] == true)) {
			
			// search for the image based on the filename, if it's not already provided
			if ($pid == 0)
				$pid = $wpdb->get_col(" SELECT pid FROM {$wpdb->nggpictures} WHERE filename = '{$name}' AND galleryid = '{$gid}' ");
			
			if ( !$image = nggdb::find_image( $pid ) )
				return new IXR_Error(404, __('Could not find image id ' . $pid ));			

			// sync the gallery<->image parameter, otherwise we may copy it to the wrong gallery
			$gallery = $image;
			
			// delete now the image
			if ( !@unlink( $image->imagePath ) ) {
				$errorString = sprintf(__('Failed to delete image %1$s ','nggallery'), $image->imagePath);
				logIO('O', '(NGG) ' . $errorString);
				return new IXR_Error(500, $errorString);
			}
		}

		// upload routine from wp core, load first the image to the upload folder, $upload['file'] contain the path
		$upload = wp_upload_bits($name, $type, $bits);
		if ( ! empty($upload['error']) ) {
			$errorString = sprintf(__('Could not write file %1$s (%2$s)'), $name, $upload['error']);
			logIO('O', '(NGG) ' . $errorString);
			return new IXR_Error(500, $errorString);
		}
		
		// this is the dir to the gallery		
		$path = WINABSPATH . $gallery->path;
		
		// check if the filename already exist, if not add a counter index
		$filename = wp_unique_filename( $path, $name );
		$destination = $path . '/'. $filename;

		// Move files to gallery folder
		if ( !@rename($upload['file'], $destination ) ) {
			$errorString = sprintf(__('Failed to move image %1$s to %2$s','nggallery'), '<strong>' . $upload['file'] . '</strong>', $destination);
			logIO('O', '(NGG) ' . $errorString);
			return new IXR_Error(500, $errorString);
		}
		
		//add to database if it's a new image
		if(empty($data["overwrite"]) || ($data["overwrite"] == false)) {
			$pid_array = nggAdmin::add_Images( $gallery->gid, array( $filename ) );
			// the first element is our new image id
			if (count($pid_array) == 1)
				$pid = $pid_array[0];
		}
		
		//get all information about the image, in the case it's a new one
		if (!$image)
			$image = nggdb::find_image( $pid );
		
		// create again the thumbnail, should return a '1'
		nggAdmin::create_thumbnail( $image );
		
		return apply_filters( 'ngg_upload_image', $image );

	}

	/**
	 * Method "ngg.deleteImage"
	 * Delete a Image from the database and gallery
	 * 
	 * @since 1.7.3
	 * 
	 * @param array $args Method parameters.
	 * 			- int blog_id
	 *	    	- string username
	 *	    	- string password
	 *	    	- int image_id 
	 * @return true
	 */
	function deleteImage($args) {
		
		global $nggdb, $ngg;
        
        require_once ( dirname ( dirname( __FILE__ ) ). '/admin/functions.php' );	// admin functions

        $this->escape($args);
		$blog_ID    = (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];
        $id    	    = (int) $args[3];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if ( !$image = nggdb::find_image($id) )
			return(new IXR_Error(404, __("Invalid image ID")));

		if ( !current_user_can( 'NextGEN Manage gallery' ) && !nggAdmin::can_manage_this_gallery($image->author) )
			return new IXR_Error( 401, __( 'Sorry, you must be able to edit this image' ) );

		if ($ngg->options['deleteImg']) {
            @unlink($image->imagePath);
            @unlink($image->thumbPath);	
            @unlink($image->imagePath . "_backup" );
        } 

        nggdb::delete_image ( $id );
		
		return true;
		
	}

	/**
	 * Method "ngg.editImage"
	 * Edit a existing Image
	 * 
	 * @since 1.7.3
	 * 
	 * @param array $args Method parameters.
	 * 			- int blog_id
	 *	    	- string username
	 *	    	- string password
	 *	    	- int Image ID
	 *	    	- string alt/title text
	 *	    	- string description
	 *	    	- int exclude from gallery (0 or 1)
	 * @return true if success
	 */
	function editImage($args) {
		
		global $ngg;

		require_once ( dirname ( dirname( __FILE__ ) ). '/admin/functions.php' );	// admin functions
        
        $this->escape($args);
		$blog_ID    = (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];
		$id      	= (int) $args[3];
        $alttext    = $args[4];
        $description= $args[5];
        $exclude    = (int) $args[6];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if ( !$image = nggdb::find_image($id)  )
			return(new IXR_Error(404, __( 'Invalid image ID' )));

        if ( !current_user_can( 'NextGEN Manage gallery' ) && !nggAdmin::can_manage_this_gallery($image->author) )
            return new IXR_Error( 401, __( 'Sorry, you must be able to edit this image' ) );

		if ( !empty( $id ) )
			$result = nggdb::update_image($id, false, false, $description, $alttext, $exclude);
		
		if ( !$result )
			return new IXR_Error(500, __('Sorry, could not update the image'));

		return true;
		
	}

	/**
	 * Method "ngg.newGallery"
	 * Create a new gallery
	 * 
	 * @since 1.4
	 * 
	 * @param array $args Method parameters.
	 * 			- int blog_id
	 *	    	- string username
	 *	    	- string password
	 *	    	- string new gallery name
	 * @return int with new gallery ID
	 */
	function newGallery($args) {
		
		global $ngg;

		require_once ( dirname ( dirname( __FILE__ ) ). '/admin/functions.php' );	// admin functions

        $this->escape($args);
		$blog_ID    = (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];
		$name   	= $args[3];
		$id 		= false;

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if( !current_user_can( 'NextGEN Manage gallery' ) )
			return new IXR_Error( 401, __( 'Sorry, you must be able to manage galleries' ) );

		if ( !empty( $name ) )
			$id = nggAdmin::create_gallery($name, $ngg->options['gallerypath'], false);

		if ( !$id )
			return new IXR_Error(500, __('Sorry, could not create the gallery'));

		return($id);
		
	}

	/**
	 * Method "ngg.editGallery"
	 * Edit a existing gallery
	 * 
	 * @since 1.7.0
	 * 
	 * @param array $args Method parameters.
	 * 			- int blog_id
	 *	    	- string username
	 *	    	- string password
	 *	    	- int gallery ID
	 *	    	- string gallery name
	 *	    	- string title
	 *	    	- string description 
     *          - int ID of the preview picture 
	 * @return true if success
	 */
	function editGallery($args) {
		
		global $ngg;

		require_once ( dirname ( dirname( __FILE__ ) ). '/admin/functions.php' );	// admin functions
        
        $this->escape($args);
		$blog_ID    = (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];
		$id      	= (int) $args[3];
		$name 		= $args[4];
        $title      = $args[5];
        $description= $args[6];
        $previewpic = (int) $args[7];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if ( !$gallery = nggdb::find_gallery($id)  )
			return(new IXR_Error(404, __("Invalid gallery ID")));

        if ( !current_user_can( 'NextGEN Manage gallery' ) && !nggAdmin::can_manage_this_gallery($gallery->author) )
            return new IXR_Error( 401, __( 'Sorry, you must be able to manage this gallery' ) );

		if ( !empty( $name ) )
			$result = nggdb::update_gallery($id, $name, false, $title, $description, false, $previewpic);
		
		if ( !$result )
			return new IXR_Error(500, __('Sorry, could not update the gallery'));

		return true;
		
	}

	/**
	 * Method "ngg.newAlbum"
	 * Create a new album
	 * 
	 * @since 1.7.0
	 * 
	 * @param array $args Method parameters.
	 * 			- int blog_id
	 *	    	- string username
	 *	    	- string password
	 *	    	- string new album name
     *          - int id of preview image
     *          - string description
     *          - string serialized array of galleries or a comma-separated string of gallery IDs
	 * @return int with new album ID
	 */
	function newAlbum($args) {
		
		global $ngg;

        $this->escape($args);
		$blog_ID    = (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];
		$name   	= $args[3];
		$preview   	= (int) $args[4];
        $description= $args[5];
        $galleries 	= $this->is_serialized($args[6]);
        $id 		= false;

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if( !current_user_can( 'NextGEN Edit album' ) || !nggGallery::current_user_can( 'NextGEN Add/Delete album' ) )
			return new IXR_Error( 401, __( 'Sorry, you must be able to manage albums' ) );

		if ( !empty( $name ) )
			$id = $result = nggdb::add_album( $name, $preview, $description, $galleries );
		
		if ( !$id )
			return new IXR_Error(500, __('Sorry, could not create the album'));

		return($id);
		
	}

	/**
	 * Method "ngg.editAlbum"
	 * Edit a existing Album
	 * 
	 * @since 1.7.0
	 * 
	 * @param array $args Method parameters.
	 * 			- int blog_id
	 *	    	- string username
	 *	    	- string password
	 *	    	- int album ID
	 *	    	- string album name
     *          - int id of preview image
     *          - string description
     *          - string serialized array of galleries or a comma-separated string of gallery IDs
	 * @return true if success
	 */
	function editAlbum($args) {
		
		global $ngg;

		require_once ( dirname ( dirname( __FILE__ ) ). '/admin/functions.php' );	// admin functions
        
        $this->escape($args);
		$blog_ID    = (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];
		$id      	= (int) $args[3];
		$name   	= $args[4];
		$preview   	= (int) $args[5];
        $description= $args[6];
        $galleries 	= $this->is_serialized($args[7]);

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if ( !$album = nggdb::find_album($id) )
			return(new IXR_Error(404, __("Invalid album ID")));

		if( !current_user_can( 'NextGEN Edit album' ) )
			return new IXR_Error( 401, __( 'Sorry, you must be able to manage albums' ) );

		if ( !empty( $name ) )
			$result = nggdb::update_album($id, $name, $preview, $description, $galleries);
		
		if ( !$result )
			return new IXR_Error(500, __('Sorry, could not update the album'));

		return true;
		
	}

	/**
	 * Method "ngg.deleteAlbum"
	 * Delete a album from the database
	 * 
	 * @since 1.7.0
	 * 
	 * @param array $args Method parameters.
	 * 			- int blog_id
	 *	    	- string username
	 *	    	- string password
	 *	    	- int album id 
	 * @return true
	 */
	function deleteAlbum($args) {
		
		global $nggdb;

        $this->escape($args);
		$blog_ID    = (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];
        $id    	    = (int) $args[3];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if ( !$album = nggdb::find_album($id) )
			return(new IXR_Error(404, __("Invalid album ID")));

		if( !current_user_can( 'NextGEN Edit album' ) && !nggGallery::current_user_can( 'NextGEN Add/Delete album' ) )
			return new IXR_Error( 401, __( 'Sorry, you must be able to manage albums' ) );
		
		$nggdb->delete_album($id);
		
		return true;
		
	}

	/**
	 * Method "ngg.deleteGallery"
	 * Delete a gallery from the database, including all images
	 * 
	 * @since 1.7.0
	 * 
	 * @param array $args Method parameters.
	 * 			- int blog_id
	 *	    	- string username
	 *	    	- string password
	 *	    	- int gallery_id 
	 * @return true
	 */
	function deleteGallery($args) {
		
		global $nggdb;

        require_once ( dirname ( dirname( __FILE__ ) ). '/admin/functions.php' );	// admin functions

        $this->escape($args);
		$blog_ID    = (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];
        $id    	    = (int) $args[3];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if ( !$gallery = nggdb::find_gallery($id) )
			return(new IXR_Error(404, __("Invalid gallery ID")));

		if ( !current_user_can( 'NextGEN Manage gallery' ) && !nggAdmin::can_manage_this_gallery($gallery->author) )
			return new IXR_Error( 401, __( 'Sorry, you must be able to manage galleries' ) );
		
		$nggdb->delete_gallery($id);
		
		return true;
		
	}

	/**
	 * Method "ngg.getAlbums"
	 * Return the list of all albums
	 * 
	 * @since 1.7.0
	 * 
	 * @param array $args Method parameters.
	 * 			- int blog_id
	 *	    	- string username
	 *	    	- string password
	 * @return array with all galleries
	 */
	function getAlbums($args) {
		
		global $nggdb;

        $this->escape($args);
		$blog_ID    = (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if( !current_user_can( 'NextGEN Edit album' ) )
			return new IXR_Error( 401, __( 'Sorry, you must be able to manage albums' ) );
		
		$album_list = $nggdb->find_all_album('id', 'ASC', 0, 0 );
		
		return($album_list);
		
	}

	/**
	 * Method "ngg.getAlbum"
	 * Return the specified album
	 *
	 * @since 1.9.2
	 *
	 * @param array $args Method parameters.
	 * 			- int blog_id
	 *	    	- string username
	 *	    	- string password
	 *          - int album_id
	 * @return array with the album object
	 */
	function getAlbum($args) {

		global $nggdb;

        $this->escape($args);
		$blog_ID    = (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];
		$id         = (int) $args[3];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if( !current_user_can( 'NextGEN Edit album' ) )
			return new IXR_Error( 401, __( 'Sorry, you must be able to manage albums' ) );

		$album = $nggdb->find_album( $id );

		return($album);

	}

	/**
	 * Method "ngg.getGalleries"
	 * Return the list of all galleries
	 * 
	 * @since 1.4
	 * 
	 * @param array $args Method parameters.
	 * 			- int blog_id
	 *	    	- string username
	 *	    	- string password
	 * @return array with all galleries
	 */
	function getGalleries($args) {
		
		global $nggdb;

        $this->escape($args);
		$blog_ID    = (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if( !current_user_can( 'NextGEN Manage gallery' ) )
			return new IXR_Error( 401, __( 'Sorry, you must be able to manage galleries' ) );
		
		$gallery_list = $nggdb->find_all_galleries('gid', 'asc', true, 0, 0, false);
		
		return($gallery_list);
		
	}

	/**
	 * Method "ngg.getGallery"
	 * Return the specified gallery
	 *
	 * @since 1.9.2
	 *
	 * @param array $args Method parameters.
	 * 			- int blog_id
	 *	    	- string username
	 *	    	- string password
	 *          - int gallery_id
	 * @return array with the gallery object
	 */
	function getGallery($args) {

		global $nggdb;

        $this->escape($args);
		$blog_ID    = (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];
		$gid		= (int) $args[3];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		if( !current_user_can( 'NextGEN Manage gallery' ) )
			return new IXR_Error( 401, __( 'Sorry, you must be able to manage galleries' ) );

		$gallery = $nggdb->find_gallery($gid);

		return($gallery);

	}

	/**
	 * Method "ngg.getImages"
	 * Return the list of all images inside a gallery
	 * 
	 * @since 1.4
	 * 
	 * @param array $args Method parameters.
	 * 			- int blog_id
	 *	    	- string username
	 *	    	- string password
	 *	    	- int gallery_id 
	 * @return array with all images
	 */
	function getImages($args) {
		
		global $nggdb;

		require_once ( dirname ( dirname( __FILE__ ) ). '/admin/functions.php' );	// admin functions
        
        $this->escape($args);
		$blog_ID    = (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];
		$gid    	= (int) $args[3];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		// Look for the gallery , could we find it ?
		if ( !$gallery = nggdb::find_gallery( $gid ) ) 
			return new IXR_Error(404, __('Could not find gallery ' . $gid ));

		// Now check if you have the correct capability for this gallery
		if ( !nggAdmin::can_manage_this_gallery($gallery->author) ) {
			logIO('O', '(NGG) User does not have upload_files capability');
			$this->error = new IXR_Error(401, __('You are not allowed to upload files to this gallery.'));
			return $this->error;
		}
		
		// get picture values
		$picture_list = $nggdb->get_gallery( $gid, 'pid', 'ASC', false );
		
		return($picture_list);
		
	}

	/**
	 * Method "ngg.getImage"
	 * Return a single image inside a gallery
	 *
	 * @since 1.9.2
	 *
	 * @param array $args Method parameters.
	 * 			- int blog_id
	 *	    	- string username
	 *	    	- string password
	 *          - int picture_id
	 * @return array with image properties
	 */
	function getImage($args) {

		global $nggdb;

		require_once ( dirname ( dirname( __FILE__ ) ). '/admin/functions.php' );	// admin functions

        $this->escape($args);
		$blog_ID    = (int) $args[0];
		$username	= $args[1];
		$password	= $args[2];
		$pid    	= (int) $args[3];

		if ( !$user = $this->login($username, $password) )
			return $this->error;

		// get picture
		$image = $nggdb->find_image( $pid );

		if ($image) {
			$gid = $image->galleryid;

			// Look for the gallery , could we find it ?
			if ( !$gallery = nggdb::find_gallery( $gid ) )
				return new IXR_Error(404, __('Could not find gallery ' . $gid ));

			// Now check if you have the correct capability for this gallery
			if ( !nggAdmin::can_manage_this_gallery($gallery->author) ) {
				logIO('O', '(NGG) User does not have upload_files capability');
				$this->error = new IXR_Error(401, __('You are not allowed to upload files to this gallery.'));
				return $this->error;
			}
		}

		return($image);

	}

	/**
	 * Sanitize string or array of strings for database.
	 *
	 * @since 1.7.0
     * @author WordPress Core
     * @filesource inludes/class-wp-xmlrpc-server.php
	 *
	 * @param string|array $array Sanitize single string or array of strings.
	 * @return string|array Type matches $array and sanitized for the database.
	 */
	function escape(&$array) {
		global $wpdb;

		if (!is_array($array)) {
			return($wpdb->escape($array));
		} else {
			foreach ( (array) $array as $k => $v ) {
				if ( is_array($v) ) {
					$this->escape($array[$k]);
				} else if ( is_object($v) ) {
					//skip
				} else {
					$array[$k] = $wpdb->escape($v);
				}
			}
		}
	}

	/**
	 * PHP5 style destructor and will run when database object is destroyed.
	 *
	 * @return bool Always true
	 */
	function __destruct() {
		
	}
}

$nggxmlrpc = new nggXMLRPC();