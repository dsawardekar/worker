<?php
/*************************************************************
 * 
 * stats.class.php
 * 
 * Get Site Stats
 * 
 * 
 * Copyright (c) 2011 Prelovac Media
 * www.prelovac.com
 **************************************************************/


class MMB_Stats extends MMB_Core
{
    function __construct()
    {
        parent::__construct();
    }
    
    /*************************************************************
     * FACADE functions
     * (functions to be called after a remote call from Master)
     **************************************************************/
    
	function get_core_update( $stats, $options = array() ){
		global $wp_version;
		
		if(isset($options['core']) && $options['core']){
			$core = $this->mmb_get_transient('update_core');
			if (isset($core->updates) && !empty($core->updates)) {
				$current_transient = $core->updates[0];
				if ($current_transient->response == "development" || version_compare($wp_version, $current_transient->current, '<')) {
					$current_transient->current_version = $wp_version;
					$stats['core_updates'] = $current_transient;
				} else
					$stats['core_updates'] = false;
			}else
				$stats['core_updates'] = false;
		}
		
		return $stats;
	}
	
	function get_hit_counter( $stats, $options = array() ){
		
		$mmb_user_hits = get_option('user_hit_count');
        if (is_array($mmb_user_hits)) {
            end($mmb_user_hits);
            $last_key_date = key($mmb_user_hits);
            $current_date  = date('Y-m-d');
            if ($last_key_date != $current_date)
                $this->set_hit_count(true);
        }
        $stats['hit_counter'] = get_option('user_hit_count');
		
		return $stats;
	}
	
	function get_comments( $stats, $options = array() ){
		
		$nposts = isset($options['numberposts']) ? (int) $options['numberposts'] : 20;
		$trimlen = isset($options['trimcontent']) ? (int) $options['trimcontent'] : 200;
		
		if( $nposts ){			
			$comments = get_comments('status=hold&number=' . $nposts);
			if(!empty($comments)){
				foreach ($comments as &$comment) {
					$commented_post = get_post($comment->comment_post_ID);
					$comment->post_title = $commented_post->post_title;
					$comment->comment_content = $this->trim_content($comment->comment_content, $trimlen);
					unset($comment->comment_author_url);
					unset($comment->comment_author_email);
					unset($comment->comment_author_IP);
					unset($comment->comment_date_gmt);
					unset($comment->comment_karma);
					unset($comment->comment_agent);
					unset($comment->comment_type);
					unset($comment->comment_parent);
					unset($comment->user_id);
				}
				$stats['comments']['pending'] = $comments;
			}
			
			$comments = get_comments('status=approve&number=' . $nposts);
			if(!empty($comments)){
				foreach ($comments as &$comment) {
					$commented_post = get_post($comment->comment_post_ID);
					$comment->post_title = $commented_post->post_title;
					$comment->comment_content = $this->trim_content($comment->comment_content, $trimlen);
					unset($comment->comment_author_url);
					unset($comment->comment_author_email);
					unset($comment->comment_author_IP);
					unset($comment->comment_date_gmt);
					unset($comment->comment_karma);
					unset($comment->comment_agent);
					unset($comment->comment_type);
					unset($comment->comment_parent);
					unset($comment->user_id);
				}
				$stats['comments']['approved'] = $comments;
			}
		}
		return $stats;
	}
	
	function get_posts( $stats, $options = array() ){
		
		$nposts = isset($options['numberposts']) ? (int) $options['numberposts'] : 20;
		
		if( $nposts ){			
			$posts = get_posts('post_status=publish&numberposts='.$nposts.'&orderby=post_date&order=desc');
			$recent_posts = array();
			if(!empty($posts)){
				foreach ($posts as $id => $recent_post) {
					$recent = new stdClass();
					$recent->post_permalink = get_permalink($recent_post->ID);
					$recent->ID = $recent_post->ID;
					$recent->post_date = $recent_post->post_date;
					$recent->post_title = $recent_post->post_title;
					$recent->comment_count = (int)$recent_post->comment_count;          
					$recent_posts[] = $recent;
				}
			}
			
			$posts = get_pages('post_status=publish&numberposts='.$nposts.'&orderby=post_date&order=desc');
			$recent_pages_published = array();
			if(!empty($posts)){
				foreach ((array)$posts as $id => $recent_page_published) {
					$recent = new stdClass();
					$recent->post_permalink = get_permalink($recent_page_published->ID);
					
					$recent->ID = $recent_page_published->ID;
					$recent->post_date = $recent_page_published->post_date;
					$recent->post_title = $recent_page_published->post_title;
				 
					$recent_posts[] = $recent;
				}
			}
			if(!empty($recent_posts)){
				usort($recent_posts, array($this, 'cmp_posts_worker'));
				$stats['posts'] = array_slice($recent_posts, 0, $nposts);
			}
		}
		return $stats;
	}
	
	function get_drafts( $stats, $options = array() ){
		
		$nposts = isset($options['numberposts']) ? (int) $options['numberposts'] : 20;
		
		if( $nposts ){
			$drafts = get_posts('post_status=draft&numberposts='.$nposts.'&orderby=post_date&order=desc');
			$recent_drafts = array();
			if(!empty($drafts)){
				foreach ($drafts as $id => $recent_draft) {
					$recent = new stdClass();
					$recent->post_permalink = get_permalink($recent_draft->ID);
					$recent->ID = $recent_draft->ID;
					$recent->post_date = $recent_draft->post_date;
					$recent->post_title = $recent_draft->post_title;
				 
					$recent_drafts[] = $recent;
				} 
			}
			$drafts = get_pages('post_status=draft&numberposts='.$nposts.'&orderby=post_date&order=desc');
			$recent_pages_drafts           = array();
			if(!empty($drafts)){
				foreach ((array)$drafts as $id => $recent_pages_draft) {
					$recent = new stdClass();
					$recent->post_permalink = get_permalink($recent_pages_draft->ID);
					$recent->ID = $recent_pages_draft->ID;
					$recent->post_date = $recent_pages_draft->post_date;
					$recent->post_title = $recent_pages_draft->post_title;
				 
					$recent_drafts[] = $recent;
				}
			}
			if(!empty($recent_drafts)){
				usort($recent_drafts, array($this, 'cmp_posts_worker'));
				$stats['drafts'] = array_slice($recent_drafts, 0, $nposts);
			}
		}
		return $stats;
	}
	
	function get_scheduled( $stats, $options = array() ){
		
		$nposts = isset($options['numberposts']) ? (int) $options['numberposts'] : 20;
		
		if( $nposts ){
			$scheduled = get_posts('post_status=future&numberposts='.$nposts.'&orderby=post_date&order=desc');
			$scheduled_posts = array();
			if(!empty($scheduled)){
				foreach ($scheduled as $id => $scheduled) {
					$recent = new stdClass();
					$recent->post_permalink = get_permalink($scheduled->ID);
					$recent->ID = $scheduled->ID;
					$recent->post_date = $scheduled->post_date;
					$recent->post_title = $scheduled->post_title;
					$scheduled_posts[] = $recent;
				}
			}
			$scheduled = get_pages('post_status=future&numberposts='.$nposts.'&orderby=post_date&order=desc');
			$recent_pages_drafts = array();
			if(!empty($scheduled)){
				foreach ((array)$scheduled as $id => $scheduled) {
					$recent = new stdClass();
					$recent->post_permalink = get_permalink($scheduled->ID);
					$recent->ID = $scheduled->ID;
					$recent->post_date = $scheduled->post_date;
					$recent->post_title = $scheduled->post_title;
				 
					$scheduled_posts[] = $recent;
				}
			}
			if(!empty($scheduled_posts)){
				usort($scheduled_posts, array($this, 'cmp_posts_worker'));
				$stats['scheduled'] = array_slice($scheduled_posts, 0, $nposts);
			}
		}
		return $stats;
	}
		
	function get_backups( $stats, $options = array() ){
		
		$stats['mwp_backups'] = $this->get_backup_instance()->get_backup_stats();
        $stats['mwp_next_backups'] = $this->get_backup_instance()->get_next_schedules();
        
		return $stats;
	}
	
	function get_backup_req( $stats = array(), $options = array() ){
		
		$stats['mwp_backups'] = $this->get_backup_instance()->get_backup_stats();
        $stats['mwp_next_backups'] = $this->get_backup_instance()->get_next_schedules();
		$stats['mwp_backup_req'] = $this->get_backup_instance()->check_backup_compat();
		
		return $stats;
	}
	
	function get_updates( $stats, $options = array() ){
		
		$upgrades = false;
		
		if(isset($options['premium']) && $options['premium']){
			$premium_updates = array();
			$upgrades = apply_filters('mwp_premium_update_notification', $premium_updates);
			if(!empty($upgrades)){
				$stats['premium_updates'] = $upgrades;
				$upgrades = false;
			}
		}
		if(isset($options['themes']) && $options['themes']){
			$this->get_installer_instance();
			$upgrades = $this->installer_instance->get_upgradable_themes();
			if(!empty($upgrades)){
				$stats['upgradable_themes'] = $upgrades;
				$upgrades = false;
			}
		}
		
		if(isset($options['plugins']) && $options['plugins']){
			$this->get_installer_instance();
			$upgrades = $this->installer_instance->get_upgradable_plugins();
			if(!empty($upgrades)){
				$stats['upgradable_plugins'] = $upgrades;
				$upgrades = false;
			}
		}
		
		return $stats;
	}
	
	function get_errors( $stats, $options = array() ){
		
		$period = isset($options['days']) ? (int) $options['days'] * 86400 : 86400;
		$errors = array();
		if(isset($options['get']) && $options['get'] == true){
			if( function_exists('ini_get') ){
				$logpath = ini_get('error_log');
				if(!empty($logpath) && file_exists($logpath)){
					$logfile = @fopen($logpath, 'r');
					if( $logfile ){
						$maxlines = 1;
						$linesize = -4096;
						$lines = array();
						$line = true;
						while( $line !== false ){
							fseek($logfile, ($maxlines * $linesize), SEEK_END);
							$maxlines++;
							if( $line ) {
								$line = fread($logfile, ($linesize * -1)).$line;
								
								foreach((array) preg_split("/(\r|\n|\r\n)/U", $line) as $l){
									preg_match('/\[(.*)\]/Ui', $l, $match);
									if(!empty($match)){
										$errors[strtotime($match[1])][] = str_replace($match[0], '', $l);
										if(strtotime($match[1]) < ((int) time() - $period)){
											$line = false;
											break;
										}
									}
								}
							}
						}
					}
						
				}
			}
		}
		if(!empty($errors))
			$stats['errors'] = $errors;
		
		return $stats;
	}
	
	function pre_init_stats( $params ){
		
		global $_mmb_item_filter;
		
		include_once(ABSPATH . 'wp-includes/update.php');
		include_once(ABSPATH . '/wp-admin/includes/update.php');
		
		$stats = $this->mmb_parse_action_params( 'pre_init_stats', $params, $this );
		$num = extract($params);
		
		if ($refresh == 'transient') {
			$current = $this->mmb_get_transient('update_core');
			if(isset($current->last_checked)){
				if(time() - $current->last_checked > 14400 ) {
					@wp_version_check();
					@wp_update_plugins();
					@wp_update_themes();
				}
			}			
		}
		
        global $wpdb, $mmb_wp_version, $mmb_plugin_dir, $wp_version, $wp_local_package;
		
		$stats['worker_version'] = MMB_WORKER_VERSION;
        $stats['wordpress_version'] = $wp_version;
		$stats['wordpress_locale_pckg'] = $wp_local_package;
        $stats['php_version'] = phpversion();
        $stats['mysql_version'] = $wpdb->db_version();
		$stats['wp_multisite'] 	 = $this->mmb_multisite;
		$stats['network_install'] = $this->network_admin_install;
		
        if (!function_exists('get_filesystem_method'))
         include_once(ABSPATH . 'wp-admin/includes/file.php');
         
        $stats['writable'] = $this->is_server_writable();
        
		return $stats;
	}
	
    function get( $params )
    {
    	
		global $wpdb, $mmb_wp_version, $mmb_plugin_dir, $_mmb_item_filter;
		
		include_once(ABSPATH . 'wp-includes/update.php');
		include_once(ABSPATH . '/wp-admin/includes/update.php');
		
		$stats = $this->mmb_parse_action_params( 'get', $params, $this );
		$update_check = array();
		$num = extract($params);
		if ($refresh == 'transient') {
			$update_check = apply_filters('mwp_premium_update_check', $update_check);
			if(!empty($update_check)){
				foreach($update_check as $update){
					if( is_array($update['callback']) ) {
						$update_result = call_user_func( array( $update['callback'][0], $update['callback'][1] ) );
					}
					else if ( is_string($update['callback']) ) {
						$update_result = call_user_func($update['callback']);
					}
				}
			}
		}		
		
		if( $this->mmb_multisite ){
			$stats = $this->get_multisite( $stats );
        }
		
        $stats = apply_filters('mmb_stats_filter', $stats);
        return $stats;
    }
    
	function get_multisite( $stats = array() ){
		global $current_user;
		
		if( $this->network_admin_install == '1' && current_user_can( 'update_core' )){
			$user_blogs = get_blogs_of_user($current_user->ID);
			if(!empty($user_blogs)){
				$blogs = array();
				foreach($user_blogs as $blog_id => $data){
					if($this->mmb_multisite == $blog_id)
						continue;
					
					if(isset($data->siteurl))
						$blogs[] = $data->siteurl;
				}
				if(!empty($blogs))
					$stats['network_blogs'] = $blogs;
			}
		}
		return $stats;
	}
	
    function get_comments_stats(){
    	$num_pending_comments  = 3;
        $num_approved_comments = 3;
        $pending_comments = get_comments('status=hold&number=' . $num_pending_comments);
        foreach ($pending_comments as &$comment) {
            $commented_post      = get_post($comment->comment_post_ID);
            $comment->post_title = $commented_post->post_title;
        }
        $stats['comments']['pending'] = $pending_comments;
        
        
        $approved_comments = get_comments('status=approve&number=' . $num_approved_comments);
        foreach ($approved_comments as &$comment) {
            $commented_post      = get_post($comment->comment_post_ID);
            $comment->post_title = $commented_post->post_title;
        }
        $stats['comments']['approved'] = $approved_comments;
        
        return $stats;
    }
    
    function get_initial_stats()
    {
        global $mmb_plugin_dir;
        
        $stats = array();
        
        $stats['email']          = get_option('admin_email');
        $stats['no_openssl']     = $this->get_random_signature();
        $stats['content_path']   = WP_CONTENT_DIR;
        $stats['worker_path']    = $mmb_plugin_dir;
        $stats['worker_version'] = MMB_WORKER_VERSION;
        $stats['site_title']     = get_bloginfo('name');
        $stats['site_tagline']   = get_bloginfo('description');
        $stats['site_home']      = get_option('home');
        $stats['admin_url']      = admin_url();
		$stats['wp_multisite'] 	 = $this->mmb_multisite;
		$stats['network_install'] = $this->network_admin_install;
		
		if( $this->mmb_multisite ){
			$details = get_blog_details($this->mmb_multisite);
			if(isset($details->site_id)){
				$details = get_blog_details($details->site_id);
				if(isset($details->siteurl))
					$stats['network_parent'] = $details->siteurl;
			}
		}
		if (!function_exists('get_filesystem_method'))
         include_once(ABSPATH . 'wp-admin/includes/file.php');
         
        $stats['writable'] = $this->is_server_writable();
       
        return $stats;
    }
    
    function set_hit_count($fix_count = false)
    {
        if ($fix_count || (!is_admin() && !MMB_Stats::is_bot())) {
            $date           = date('Y-m-d');
            $user_hit_count = (array) get_option('user_hit_count');
            if (!$user_hit_count) {
                $user_hit_count[$date] = 1;
                update_option('user_hit_count', $user_hit_count);
            } else {
                $dated_keys      = array_keys($user_hit_count);
                $last_visit_date = $dated_keys[count($dated_keys) - 1];
                
                $days = intval((strtotime($date) - strtotime($last_visit_date)) / 60 / 60 / 24);
                
                if ($days > 1) {
                    $date_to_add = date('Y-m-d', strtotime($last_visit_date));
                    
                    for ($i = 1; $i < $days; $i++) {
                        if (count($user_hit_count) > 14) {
                            $shifted = @array_shift($user_hit_count);
                        }
                        
                        $next_key = strtotime('+1 day', strtotime($date_to_add));
                        if ($next_key == $date) {
                            break;
                        } else {
                            $user_hit_count[$next_key] = 0;
                        }
                    }
                    
                }
                
                if (!isset($user_hit_count[$date])) {
                    $user_hit_count[$date] = 0;
                }
                if (!$fix_count)
                    $user_hit_count[$date] = ((int)$user_hit_count[$date] ) + 1;
                
                if (count($user_hit_count) > 14) {
                    $shifted = @array_shift($user_hit_count);
                }
                
                update_option('user_hit_count', $user_hit_count);
                
            }
        }
    }
    
    function get_hit_count()
    {
        // Check if there are no hits on last key date
        $mmb_user_hits = get_option('user_hit_count');
        if (is_array($mmb_user_hits)) {
            end($mmb_user_hits);
            $last_key_date = key($mmb_user_hits);
            $current_date  = date('Y-m-d');
            if ($last_key_date != $curent_date)
                $this->set_hit_count(true);
        }
        
        return get_option('user_hit_count');
    }
    
    function is_bot()
    {
        $agent = $_SERVER['HTTP_USER_AGENT'];
        
        if ($agent == '')
            return false;
        
        $bot_list = array(
            "Teoma",
            "alexa",
            "froogle",
            "Gigabot",
            "inktomi",
            "looksmart",
            "URL_Spider_SQL",
            "Firefly",
            "NationalDirectory",
            "Ask Jeeves",
            "TECNOSEEK",
            "InfoSeek",
            "WebFindBot",
            "girafabot",
            "crawler",
            "www.galaxy.com",
            "Googlebot",
            "Scooter",
            "Slurp",
            "msnbot",
            "appie",
            "FAST",
            "WebBug",
            "Spade",
            "ZyBorg",
            "rabaz",
            "Baiduspider",
            "Feedfetcher-Google",
            "TechnoratiSnoop",
            "Rankivabot",
            "Mediapartners-Google",
            "Sogou web spider",
            "WebAlta Crawler",
            "aolserver"
        );
                
        foreach ($bot_list as $bot)
             if (strpos($agent, $bot)!==false) 
                return true;                    
        
        return false;
    }
    
    
    function set_notifications($params)
    {
    	if(empty($params))
    		return false;
    	
    	extract($params);
    	
    	if(!isset($delete)){
    		$mwp_notifications = array(
			    		'plugins' => $plugins, 
			    		'themes' => $themes, 
			    		'wp' => $wp,
			    		'backups' => $backups,
			    		'url' => $url, 
			    		'notification_key' => $notification_key
		    		);
    		update_option('mwp_notifications',$mwp_notifications);
    	} else {
    		delete_option('mwp_notifications');
    	} 	
    	
    	return true;
    	
    }
    
    //Cron update check for notifications
    function check_notifications(){
    	global $wpdb, $mmb_wp_version, $mmb_plugin_dir, $wp_version, $wp_local_package;
    	
    	$mwp_notifications = get_option('mwp_notifications',true);
    	$updates = array();
    
    	if(is_array($mwp_notifications) && $mwp_notifications != false){
    		include_once(ABSPATH . 'wp-includes/update.php');
    		include_once(ABSPATH . '/wp-admin/includes/update.php');
    		extract($mwp_notifications);
    		
    		//Check wordpress core updates
    		if($wp){
					@wp_version_check();
    			if (function_exists('get_core_updates')) {
						$wp_updates = get_core_updates();
			       if (!empty($wp_updates)) {
							$current_transient = $wp_updates[0];
							if ($current_transient->response == "development" || version_compare($wp_version, $current_transient->current, '<')) {
									$current_transient->current_version = $wp_version;
									$updates['core_updates'] = $current_transient;
			           } else
			             $updates['core_updates'] = array();
			           } else
			                $updates['core_updates'] = array();
					}
				}
				
				//Check plugin updates
				if($plugins){
					@wp_update_plugins();
				$this->get_installer_instance();
				$updates['upgradable_plugins'] = $this->installer_instance->get_upgradable_plugins();
				}
				
				//Check theme updates
				if($themes){
					@wp_update_themes();
					$this->get_installer_instance();
					
					$updates['upgradable_themes']  = $this->installer_instance->get_upgradable_themes();
				}

				if($backups){
					$this->get_backup_instance();
					$backups = $this->backup_instance->get_backup_stats();
					$updates['backups'] = $backups;
					foreach($backups as $task_name => $backup_results){
						foreach($backup_results as $k => $backup){
							if(isset($backups[$task_name][$k]['server']['file_path'])){
							unset($backups[$task_name][$k]['server']['file_path']);
							}
						}
					}
					$updates['backups'] = $backups;
				}
				
				if( !class_exists( 'WP_Http' ) ){
    		include_once( ABSPATH . WPINC. '/class-http.php' );
    	}
    	
    		if(!empty($updates)){
					$args = array();
					$args['body'] = array('updates' => $updates, 'notification_key' => $notification_key);
					$result= wp_remote_post($url, $args);
				}
    		
    	}
    	
			
	}
	
	
	function cmp_posts_worker($a, $b)
	{
		return ($a->post_date < $b->post_date);
	}
	
	function trim_content($content = '', $length = 200){
		
		if( function_exists('mb_strlen') && function_exists('mb_substr') )
			$content = (mb_strlen($content) > ($length + 3)) ? mb_substr($content, 0, $length) . '...' : $content;
		else 
			$content = (strlen($content) > ($length + 3)) ? substr($content, 0, $length) . '...' : $content;
		
		return $content;
	}

}
?>