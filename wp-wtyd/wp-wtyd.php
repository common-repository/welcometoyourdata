<?php 
/*
    Plugin Name: Welcome To Your Data
    Plugin URI: http://welcome.totheinter.net/welcome-to-your-data/
    Description: Download and analyze data from Google Analytics, Google Reader, Feedburner, PeerIndex, and WordPress in 1 plugin and 1 database.
    Version: 0.1.9
    Author: Adam Wulf
    Author URI: http://welcome.totheinter.net
*/


/**
 * recently popular posts/pages
 *
 * needs a better index. it's slow as hell
 
SELECT s.count, p.* FROM 
 (
   SELECT COUNT(s1.resource) AS count, s1.* FROM `wp_1_slim_stats` s1 
   WHERE s1.dt > (1233421845 - 3600)
   GROUP BY s1.resource
) s LEFT JOIN `wp_1_posts` p ON s.resource LIKE CONCAT("%/",p.post_name,"/")
 WHERE p.post_status="publish"
 ORDER BY count DESC


*/

require_once "lib.googleanalytics.php";
require_once "lib.feedburner.php";
require_once "lib/class.WTYDDataSource.php";
require_once "lib/class.GRDataSource.php";
require_once "lib/class.PIDataSource.php";
require_once "lib/class.TweetsDataSource.php";
require_once "lib/gapi-1.3/gapi.class.php";	


//
//
// http://www.google.com/reader/public/atom/user/06385121243970520143/state/com.google/starred?n=400
// https://www.google.com/analytics/reporting/export?fmt=1&id=7716510&pdr=20081214-20090113&cmp=average&rpt=VisitsReport
// https://www.google.com/analytics/reporting/export?fmt=1&id=7716510&pdr=20081214-20090113&cmp=average&rpt=TimeOnSiteReport
//
// Format the plugin page
if (is_plugin_page()) {
	if (isset($_POST['delete_history'])){
		$wtydPlugin->emptyDatabase();
		
        echo('<div class="updated"><p>Historical Data Deleted.</p></div>'); 
	}else if (isset($_POST['update_history'])){
		$thelast = date("Y") - 2004;
		$wtydPlugin->updateDatabase(true, $thelast);
		
        echo('<div class="updated"><p>Historical Data Loaded.</p></div>'); 
		
		
	}else if (isset($_POST['update_reader'])) { 
	
		$numdisp = (int) $_POST['numdisp'];
		if(!$numdisp) $numdisp = "10";
	
        update_option('WTYD_blog_ids', $_POST['blog_ids']);
        update_option('WTYD_pi_api_key', $_POST['pi_api_key']); 
        update_option('WTYD_pi_username', $_POST['pi_username']); 
        update_option('GoogleAnalyticsUser', $_POST['ga_user']); 
        update_option('GoogleAnalyticsPass', $_POST['ga_pass']); 
        update_option('GoogleAnalyticsSite', $_POST['ga_site']); 
        update_option('GoogleReader_FeedURL', $_POST['url']); 
        update_option('GoogleReader_NumDisplay', $numdisp); 
        update_option('GoogleReader_CSSClass', $_POST['cssclass']); 
        update_option('Feedburner_FeedURL', $_POST['fburl']); 
        
        echo('<div class="updated"><p>Options changes saved.</p></div>'); 
} 


if(!file_exists(WTYD_CACHE) || !is_writable(WTYD_CACHE)){
        echo('<div class="updated"><p>The directory ' . WTYD_CACHE . ' does not exist or is not write-able. From your server\'s terminal:<br>$ mkdir ' . WTYD_CACHE . '<br>
$ chmod 777 ' . WTYD_CACHE . '
</p></div>'); 
}


?> 
    <div class="wrap"> 
        <h2>Welcome To Your Data Account Settings</h2>
        <h3>Automating WTYD:</h3>
         <p>If you want WelcomeToYourData to update your stats automatically, then make sure that you setup a cron job on your server with the following command:</p>
         <pre>curl -L -s <?=get_site_url()?>?updateWTYD</pre>
         <p>This command should be run at least twice a day.</p>
         
        <form method="post">         
            <fieldset class="options"> 
                <h3>Google Analytics</h3>
                <p>Enter your Google Analytics username and password. This is probably your Gmail account info.</p>
                <label for="ga_user">Username:</label> 
                <input name="ga_user" type="text" id="ga_user" value="<?php echo get_option('GoogleAnalyticsUser'); ?>"/>
                <br>
                <label for="ga_pass">Password:</label> 
                <input name="ga_pass" type="password" id="ga_pass" value="<?php echo get_option('GoogleAnalyticsPass'); ?>"/>
                <br>
                <label for="ga_site">Site:</label>
                <select name="ga_site" id="ga_site">
                <?
                	$user = get_option('GoogleAnalyticsUser');
                	$pass = get_option('GoogleAnalyticsPass');
					$ga = new WTYDGoogleAnalytics();
					$ga->login($user, $pass);
					$sites = $ga->getSiteProfiles();
					foreach($sites as $site){
						$props = $site->getProperties();
						$s = (get_option('GoogleAnalyticsSite') == $props["profileId"]) ? "SELECTED" : "";
						echo "<option value='" . $props["profileId"] . "' $s>" . $props["accountName"] . ": " . $props["title"] . "</option>";
					}
                ?>
                </select>
                <br>
                <h3>Feedburner</h3>
                <p>Enter your Feedburner URL.</p>
                <label for="fburl">Feedburner URL: http://feeds.feedburner.com/</label> 
                <input name="fburl" type="text" id="fburl" value="<?php echo get_option('Feedburner_FeedURL'); ?>" size=50/>
                <br>
                <h3>Peer Index</h3>
                <label for="pi_api_key">API Key:</label> 
                <input name="pi_api_key" type="text" id="pi_api_key" value="<?php echo get_option('WTYD_pi_api_key'); ?>" size=100/>
                <br>
                <label for="pi_username">User name:</label> 
                <input name="pi_username" type="text" id="pi_username" value="<?php echo get_option('WTYD_pi_username'); ?>" size=100/>
                <br>
                <h3>Google Reader</h3>
                <p>Enter the RSS feed for your starred items in Google Reader. <a href="<?=get_bloginfo('wpurl').'/wp-content/plugins/wp-wtyd/help.html'?>" target="_new">How to find the URL</a>.</p>
                <label for="url">Feed URL:</label> 
                <input name="url" type="text" id="url" value="<?php echo get_option('GoogleReader_FeedURL'); ?>" size=100/>
                <br>
                <p>Enter the default number of bookmarks to display when using $wtydPlugin->readerShared().</p>
                <label for="numdisp">Number to Display:</label> 
                <input name="numdisp" type="text" id="numdisp" value="<?php echo get_option('GoogleReader_NumDisplay', 10); ?>" />
                <br>
                <p>Enter the CSS class name for the &lt;ul&gt; when using $wtydPlugin->readerShared().</p>
                <label for="cssclass">CSS Class:</label> 
                <input name="cssclass" type="text" id="cssclass" value="<?php echo get_option('GoogleReader_CSSClass'); ?>" /> 
                <h3>My Blogs</h3>
                <p>Enter a comma separated list of blog ids that you author. (Leave blank if you author all blogs.)</p>
                <label for="blog_ids">Blog Ids:</label> 
                <input name="blog_ids" type="text" id="blog_ids" value="<?php echo get_option('WTYD_blog_ids'); ?>" size=100/>
                <br>
            </fieldset> 

              <p><div class="submit"><input type="submit" name="update_reader" value="Save Settings" style="font-weight:bold;" /></div></p> 
        </form>        
		<br><br><br><br>
        <h2>Additional Commands</h2>
        <form method="post">         
			<div class="submit">
	            <fieldset class="options"> 
	            <p>Load in as much historical data as possible. (This will overwrite any data in your database, but will not delete any. It is purely additive.)</p>
	            <ul style='list-style:circle;margin-left:20px;'>
	            	<li>All of your Google Analytics data will be imported.</li>
	            	<li>All of your Feedburner data will be imported.</li>
	            	<li>Only the last 1000 starred items from Google Reader will be imported.</li>
	            	<li>Today's stats from PeerIndex will be imported.</li>
	            </ul>
	            </fieldset> 
		        <p><input type="submit" name="update_history" value="Load Historical Data" style="font-weight:bold;" /></p> 
			</div>
        </form>
        <form method="post" onsubmit="return confirm('Are you sure you want to delete all of your historical data?')">         
			<div class="submit">
	            <fieldset class="options"> 
		        Delete all data from the database. Not all data can necessarily be re-loaded,<br> so <strong>BACKUP your database</strong>!
	            </fieldset> 
		        <p><input type="submit" name="delete_history" value="Delete ALL Historical Data" style="font-weight:bold;" /></p> 
	        </div>
        </form>        
    </div> 
<?php 
} 

else { 

	if(!defined("WTYD_DATA_UPDATE")) define("WTYD_DATA_UPDATE", 8000* 60 * 24); // reresh every 24 hours
	if(!defined("WTYD_CACHE")) define("WTYD_CACHE", ABSPATH . "wp-content/cache_wtyd/" );

	class WTYD{
		
		function __construct(){
			
		}


		/**
		 * parse a number of the format #,###,###.###
		 * into an int or double as appropriate
		 */	
		function parseValue($val){
			$val = str_replace(",","", $val);
			if(strpos($val, ".")){
				return (double) $val;
			}else{
				return (int) $val;
			}
		}
	
	
		/**
		 * serialize and cache the contents at
		 * the input filename
		 */
		function cacheFile($contents, $filename){
			$cache = WTYD_CACHE;
			if(!file_exists($cache)){
				@mkdir($cache);
			}
			
			echo "wrote: " . $cache . $filename . "<br>";
			
			@file_put_contents( $cache . $filename, serialize($contents));
		}
	
		/**
		 * retrieve cached contents of the filename
		 * if any, false otherwise
		 */
		function getCachedFile($filename){
			$cache = WTYD_CACHE;
			
			if(!file_exists($cache)){
				@mkdir($cache);
				if(!file_exists($cache)){
					echo "could not create cache directory at " . $cache;
				}
			}else{
				if(file_exists($cache . $filename)){
					return unserialize(file_get_contents($cache . $filename));
				}
			}
			return false;
		}
	
		/**
		 * determine the age of the file cached at
		 * the input filename, or return false otherwise
		 */
		function getCachedAge($filename){
			$cache = WTYD_CACHE;
			if(!file_exists($cache)){
				@mkdir($cache);
			}else{
				if(file_exists($cache . $filename)){
					$time = filemtime($cache . $filename);
					$age = time() - $time;
					$age = $age / 60.0; // in minutes
					return $age;
				}
			}
			return false;
		}
		
		
		function emptyDatabase(){
			global $wpdb;
			$table_name = $wpdb->prefix . "wtyd_bookmarks";
			$sql = "DELETE FROM  `$table_name`";
			$results = $wpdb->query($sql);

			$table_name = $wpdb->prefix . "wtyd_feedstats";
			$sql = "DELETE FROM  `$table_name`";
			$results = $wpdb->query($sql);

			$table_name = $wpdb->prefix . "wtyd_ga_timeonsite";
			$sql = "DELETE FROM  `$table_name`";
			$results = $wpdb->query($sql);

			$table_name = $wpdb->prefix . "wtyd_ga_bouncerate";
			$sql = "DELETE FROM  `$table_name`";
			$results = $wpdb->query($sql);

			$table_name = $wpdb->prefix . "wtyd_ga_newusers";
			$sql = "DELETE FROM  `$table_name`";
			$results = $wpdb->query($sql);

			$table_name = $wpdb->prefix . "wtyd_ga_visits";
			$sql = "DELETE FROM  `$table_name`";
			$results = $wpdb->query($sql);

			$table_name = $wpdb->prefix . "wtyd_ga_eventpervisit";
			$sql = "DELETE FROM  `$table_name`";
			$results = $wpdb->query($sql);

			$table_name = $wpdb->prefix . "wtyd_pi_profile";
			$sql = "DELETE FROM  `$table_name`";
			$results = $wpdb->query($sql);

		}
		
		
		/**
		 * if the cache has expired, then poll the appropriate
		 * web service and update the database with the most
		 * recent data
		 */
		function updateDatabase($force_reload_all=false, $last = 1){
	    	global $wpdb;
	    	
			echo date("Y-m-d H:i:s") . "<br>";



//	    	error_reporting(0);
	    	//
	    	//
	    	// first, get peer index
	    	//
	        $pi_api_key = trim(get_option('WTYD_pi_api_key')); 
	        $pi_username = trim(get_option('WTYD_pi_username')); 
	        
	        if($pi_api_key && $pi_username){
	        	echo "Updating Peer Index" . "<br>";
		    	$pidatasource = new PIDataSource($pi_api_key, $pi_username, $wpdb);
		    	$pidatasource->update();
	        }

			echo date("Y-m-d H:i:s") . "<br>";


	    	//
	    	//
	    	// next, get tweets
	    	//
        	echo "Updating Tweets" . "<br>";
	    	$pidatasource = new TweetsDataSource($wpdb);
	    	$pidatasource->update();

			echo date("Y-m-d H:i:s") . "<br>";



	    	//
	    	//
	    	// next, get starred items
	    	//
	        $feedurl = trim(get_option('GoogleReader_FeedURL')); 
	        
	        if($feedurl){
	        	echo "Updating Google Reader" . "<br>";
		    	$grdatasource = new GRDataSource($feedurl, $wpdb);
		    	$grdatasource->update($force_reload_all);
	        }
			
			echo date("Y-m-d H:i:s") . "<br>";


	    	//
	    	//
	    	// next, get feedburner
	    	//
	    	$feed_set = trim(get_option('Feedburner_FeedURL'));
	        $feedurl = "http://feeds.feedburner.com/" . $feed_set;
	        
	        if($feed_set){
		        
		        echo "Updating Feedburner.<br>";
		        
				//
				// get the data, but only query the URL
				// if a fair bit of time has passed
				$filename = md5($feedurl);
				$contents = $this->getCachedFile($filename);
				$age = $this->getCachedAge($filename);
				//
				// new data, update ye 'ole db
				$ga = new WTYDFeedburner();
				if($force_reload_all){
		            $start = date('Y-m-d', strtotime("-" . $last . " year"));
		            $stop = date('Y-m-d', time());
					$feed = $ga->getFeedData($feedurl, $start, $stop);
				}else{
					$feed = $ga->getFeedData($feedurl);
				}
	//			$feed = simplexml_load_string($contents);
				$this->cacheFile($feed, $filename);
	
	
		        if(is_array($feed["records"])) foreach ($feed["records"] as $item) {
		        
		        	$dt = $item["Date"];
		        	$dt = substr($dt, 0, 4) . "-" . substr($dt, 4,2) . "-" . substr($dt, 6, 2);
		        	$circ = $item["Circulation"];
		        	$hits = $item["Hits"];
		        	        	
		        	$sql = "SELECT COUNT(*) AS count FROM " . $wpdb->prefix . "wtyd_feedstats WHERE date='" . addslashes($dt) . "'";
					$results = $wpdb->get_results($sql);
					if($result = $results[0]) {
						if($result->count > 0){
							$sql = "UPDATE " . $wpdb->prefix . "wtyd_feedstats "
								 . "SET `circulation`='" . addslashes($circ) . "', `hits`='" . addslashes($hits) . "' "
								 . "WHERE `date`='" . addslashes($dt) . "'";
							$results = $wpdb->query($sql);
						}else{
							$sql = "INSERT INTO " . $wpdb->prefix . "wtyd_feedstats "
								 . "(`date`,`circulation`,`hits`) "
								 . " VALUES "
								 . "('" . addslashes($dt) . "','" . addslashes($circ) . "','" . addslashes($hits) . "')";
							$results = $wpdb->query($sql);
						}
					}
		        }
	        }
				
			
			echo date("Y-m-d H:i:s") . "<br>";
			
			if(get_option('GoogleAnalyticsUser')){
				
				echo "Updating Google Analytics data.<br>";
				
				//
				//
				// next google analytics visitor data
				//
				//
				// get the data, but only query the URL
				// if a fair bit of time has passed
				$filename = md5("google_analytics_visits");
				$contents = $this->getCachedFile($filename);
				$age = $this->getCachedAge($filename);
				
				echo "updating...<br>";
				
	        	$user = get_option('GoogleAnalyticsUser');
	        	$pass = get_option('GoogleAnalyticsPass');
	        	
				$ga = new WTYDGoogleAnalytics();
				
				if(!$ga->isLoggedIn()){
					$ga->login($user, $pass);
				}
				$profile = get_option('GoogleAnalyticsSite');
				if($force_reload_all){
					$start = date("Y-m-d", strtotime("-" . $last . " year"));
					$end = date("Y-m-d", strtotime("today"));
		        }else{
					$start = date("Y-m-d", strtotime("-1 week"));
					$end = date("Y-m-d", strtotime("today"));
		        }
				$reportType = "visits";
				
				//
				// check if the date is before launch of analytics
				if($start < "2005-11-15"){
					$start = "2005-11-15";
				}
				if($start > $end){
					$end = $start;
				}
				
				
				echo "getting from: " . $start . " to " . $end . "<br>";
				
				$contents = $ga->getReportData($profile, $start, $end, $reportType);
				$this->cacheFile($contents, $filename);
				
				if(is_array($contents)){
					foreach($contents as $dt => $data){
					
					
						$visits			= $data["visits"];
						$timeonsite		= $data["timeonsite"];
						$bounce			= $data["bouncerate"];
						$newusers		= $data["newusers"];
						$eventspervisit	= $data["eventspervisit"];
						
			        	$sql = "SELECT COUNT(*) AS count FROM " . $wpdb->prefix . "wtyd_ga_visits WHERE date='" . addslashes($dt) . "'";
						$results = $wpdb->get_results($sql);
						if($result = $results[0]) {
							if($result->count > 0){
								$sql = "UPDATE " . $wpdb->prefix . "wtyd_ga_visits "
									 . "SET `visits`='" . addslashes($visits) . "' "
									 . "WHERE `date`='" . addslashes($dt) . "'";
								$results = $wpdb->query($sql);
							}else{
								$sql = "INSERT INTO " . $wpdb->prefix . "wtyd_ga_visits "
									 . "(`date`,`visits`) "
									 . " VALUES "
									 . "('" . addslashes($dt) . "','" . addslashes($visits) . "')";
								$results = $wpdb->query($sql);
							}
						}
						
						
						
						
			        	$sql = "SELECT COUNT(*) AS count FROM " . $wpdb->prefix . "wtyd_ga_timeonsite WHERE date='" . addslashes($dt) . "'";
						$results = $wpdb->get_results($sql);
						if($result = $results[0]) {
							if($result->count > 0){
								$sql = "UPDATE " . $wpdb->prefix . "wtyd_ga_timeonsite "
									 . "SET `seconds`='" . addslashes($timeonsite) . "' "
									 . "WHERE `date`='" . addslashes($dt) . "'";
								$results = $wpdb->query($sql);
							}else{
								$sql = "INSERT INTO " . $wpdb->prefix . "wtyd_ga_timeonsite "
									 . "(`date`,`seconds`) "
									 . " VALUES "
									 . "('" . addslashes($dt) . "','" . addslashes($timeonsite) . "')";
								$results = $wpdb->query($sql);
							}
						}
						
						
						
						
						
						
						$sql = "SELECT COUNT(*) AS count FROM " . $wpdb->prefix . "wtyd_ga_bouncerate WHERE date='" . addslashes($dt) . "'";
						$results = $wpdb->get_results($sql);
						if($result = $results[0]) {
							if($result->count > 0){
								$sql = "UPDATE " . $wpdb->prefix . "wtyd_ga_bouncerate "
									 . "SET `rate`='" . addslashes($bounce) . "' "
									 . "WHERE `date`='" . addslashes($dt) . "'";
								$results = $wpdb->query($sql);
							}else{
								$sql = "INSERT INTO " . $wpdb->prefix . "wtyd_ga_bouncerate "
									 . "(`date`,`rate`) "
									 . " VALUES "
									 . "('" . addslashes($dt) . "','" . addslashes($bounce) . "')";
								$results = $wpdb->query($sql);
							}
						}
						
						
						
						
						
						
						
						$sql = "SELECT COUNT(*) AS count FROM " . $wpdb->prefix . "wtyd_ga_newusers WHERE date='" . addslashes($dt) . "'";
						$results = $wpdb->get_results($sql);
						if($result = $results[0]) {
							if($result->count > 0){
								$sql = "UPDATE " . $wpdb->prefix . "wtyd_ga_newusers "
									 . "SET `visits`='" . addslashes($newusers) . "' "
									 . "WHERE `date`='" . addslashes($dt) . "'";
								$results = $wpdb->query($sql);
							}else{
								$sql = "INSERT INTO " . $wpdb->prefix . "wtyd_ga_newusers "
									 . "(`date`,`visits`) "
									 . " VALUES "
									 . "('" . addslashes($dt) . "','" . addslashes($newusers) . "')";
								$results = $wpdb->query($sql);
							}
						}
						
						
						
						
						
			        	$sql = "SELECT COUNT(*) AS count FROM " . $wpdb->prefix . "wtyd_ga_eventpervisit WHERE date='" . addslashes($dt) . "'";
						$results = $wpdb->get_results($sql);
						if($result = $results[0]) {
							if($result->count > 0){
								$sql = "UPDATE " . $wpdb->prefix . "wtyd_ga_eventpervisit "
									 . "SET `events`='" . addslashes($eventspervisit) . "' "
									 . "WHERE `date`='" . addslashes($dt) . "'";
								$results = $wpdb->query($sql);
							}else{
								$sql = "INSERT INTO " . $wpdb->prefix . "wtyd_ga_eventpervisit "
									 . "(`date`,`events`) "
									 . " VALUES "
									 . "('" . addslashes($dt) . "','" . addslashes($eventspervisit) . "')";
								$results = $wpdb->query($sql);
							}
						}
					}
				}else{
					echo "visitor data isn't an array<br>";
				}
		
			}
			
			
			echo date("Y-m-d H:i:s") . "<br>";


			echo "updating popular posts<br>";
			
			$this->update_popular_posts(7, 5);
			$this->update_popular_posts(7, 10);
			
			echo date("Y-m-d H:i:s") . "<br>";


			echo "done";
			
			exit;	
		}
		
		
		/**
		 * fetches an array of data from all
		 * available sources, and returns them
		 * in the following associated array:
		 
				$output["ga_sum_visits"]		= array("name" => "Total Visits",		"data" => $ga_sum_visits,			"monthly" => false);
				$output["fb_circ"]				= array("name" => "Subscribers",		"data" => $fb_circ,					"monthly" => false);
				$output["fb_hits"]				= array("name" => "Feed Hits",			"data" => $fb_hits,					"monthly" => false);
				$output["ga_avg_visits"]		= array("name" => "Avg Visits",			"data" => $ga_avg_visits,			"monthly" => false);
				$output["ga_avg_time"]			= array("name" => "Avg Time (sec)",		"data" => $ga_avg_time,				"monthly" => false);
				$output["ga_bounce_rate"]		= array("name" => "Bounce Rate",		"data" => $ga_bounce_rate,			"monthly" => false);
				$output["ga_new_rate"]			= array("name" => "% New Visitors",		"data" => $ga_new_rate,				"monthly" => false);
				$output["ga_events_per_visit"]	= array("name" => "Clicks per Visit",	"data" => $ga_events_per_visit,		"monthly" => false);
				$output["post_counts"]			= array("name" => "Posts",				"data" => $post_counts,				"monthly" => true);
				$output["comment_counts"]		= array("name" => "Comments",			"data" => $comment_counts,			"monthly" => false);
				$output["word_total"]			= array("name" => "Total Words",		"data" => $word_total,				"monthly" => true);
				$output["word_avg"]				= array("name" => "Avg Words",			"data" => $word_avg,				"monthly" => true);
				$output["bookmarks"]			= array("name" => "Bookmarks",			"data" => $bookmarks,				"monthly" => false);
		 
		 * the array returned contains an array
		 * of data sets.
		 * of those returned data sets, the
		 * following is returned:
		 *  name: the name of the dataset
		 *  data: the array data of the dataset
		 *  monthly: true if the data is returned
		 *			 as monthly data, or false
		 *			 if it is weekly data.
		 *
		 * NOTE: regardless of monthly data, or
		 * 		 weekly data, the timestamp
		 * 		 interval of the datapoints is
		 *		 always weekly.
		 */
		function getSimpleStats(){
			global $wpdb, $wpSlimStat;
			if (defined('POC_CACHE_4')) {
				$cache_key = 'simple-stats'.$args;
				$output = poc_cache_fetch($cache_key);
			}

			$output = array();
			$post_counts = array();
			$comment_counts = array();
			$word_total = array();
			$word_avg = array();
			$bookmarks = array();
			$fb_hits = array();
			$fb_circ = array();
			$ga_avg_visits = array();
			$ga_sum_visits = array();
			$ga_avg_time = array();
			$ga_bounce_rate = array();
			$ga_new_rate = array();
			$ga_events_per_visit = array();

			$post_counts_mo = array();
			$word_avg_mo = array();
			$word_total_mo = array();
			$post_counts_mo = array();
			
			$done_huh = array();

			
			$ids = get_option('WTYD_blog_ids');
			$ids = explode(",", $ids);
			$nids = "";
			foreach($ids as $id){
				$id = (int) $id;
				if($id != 0){
					if(strlen($nids)) $nids .= ",";
					$nids .= "'" . $id . "'";
				}
			};
			
			// get a list of blogs in order of most recent update. show only public and nonarchived/spam/mature/deleted
			$sql = "SELECT blog_id FROM $wpdb->blogs WHERE
				public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0' " .
				($how_long ? "AND last_updated >= DATE_SUB(CURRENT_DATE(), INTERVAL $how_long DAY) " : " ") .
				(strlen($nids) ? "AND blog_id IN ($nids) " : "") .
				"ORDER BY last_updated DESC";
			$blogs = $wpdb->get_col($sql);
			if(!$blogs) $blogs = array(false);
			
			if (!$output && $blogs) {
			
				foreach ($blogs as $blog) {
					if($blog !== false) switch_to_blog($blog);
					
					//
					// if $blog === false, then we're in normal
					// wordpress, otherwise we're in wordpress mu
					//
					// we need _posts and _options tables for this to work
					$blogOptionsTable = $wpdb->prefix . "options";
					$blogPostsTable = $wpdb->prefix . "posts";
					$blogCommentsTable = $wpdb->prefix . "comments";
					$blogPostMetaTable = $wpdb->prefix . "postmeta";
	
					$sql = "SELECT a.week, a.thedt, b.count FROM
								(SELECT DATE_FORMAT(post_date, \"%Y-%U\") AS week, DATE_FORMAT(post_date,\"%Y-%m\") AS thedt
								 FROM `$blogPostsTable`
								 WHERE post_status = 'publish'
								 GROUP BY week) a
							JOIN (SELECT DATE_FORMAT(post_date,\"%Y-%m\") AS thedt2, COUNT(*) AS count
								  FROM `$blogPostsTable`
								  WHERE post_status = 'publish'
								  GROUP BY thedt2) b
							ON b.thedt2 = a.thedt
								GROUP BY week ORDER BY week DESC";
								
								
								
								
								
					$results = $wpdb->get_results($sql);
					foreach ($results as $result) {
						$result->blogID = $blog;
						if(!count($post_counts_mo) && !$result->count) continue;
						if(!isset($post_counts_mo[$result->thedt])){
							$post_counts_mo[$result->thedt] = $result->count;
						}else if(!isset($done_huh["post_counts_" . $blog])){
							$post_counts_mo[$result->thedt] += $result->count;
						}
						$post_counts[$result->week] = $result->thedt;
						$done_huh["post_counts_" . $blog] = true;
					}
	
	
					$sql = "SELECT DATE_FORMAT(comment_date, \"%Y-%U\") AS week, DATE_FORMAT(comment_date,\"%Y-%m\") AS thedt, COUNT(*) AS count "
							. "FROM  `$blogCommentsTable` "
							. "WHERE comment_approved = '1' "
							. "GROUP BY week ORDER BY week DESC";
					$results = $wpdb->get_results($sql);
					foreach ($results as $result) {
						$result->blogID = $blog;
						if(!count($comment_counts) && !$result->count) continue;
						if(!isset($comment_counts[$result->week])){
							$comment_counts[$result->week] = $result->count;
						}else{
							$comment_counts[$result->week] += $result->count;
						}
					}
					
					
					/*
					$sql = "SELECT DATE_FORMAT(post_date, \"%Y-%U\") AS week, DATE_FORMAT(post_date,\"%Y-%m\") AS thedt, "
							. "FLOOR(AVG( LENGTH(`post_content`) - LENGTH(REPLACE(`post_content`, ' ', ''))+1)) AS wordcount, "
							. "FLOOR(AVG(LENGTH(post_content))) AS avglength "
							. "FROM `$blogPostsTable` "
							. "WHERE post_status = 'publish' "
							. "GROUP BY week ORDER BY week DESC";
					*/
					
					$sql = "SELECT a.week, a.thedt, b.wordcount FROM
								(SELECT DATE_FORMAT(post_date, \"%Y-%U\") AS week, DATE_FORMAT(post_date,\"%Y-%m\") AS thedt
								 FROM `$blogPostsTable`
								 WHERE post_status = 'publish'
								 GROUP BY week) a
							JOIN (SELECT DATE_FORMAT(post_date,\"%Y-%m\") AS thedt2, FLOOR(AVG( LENGTH(`post_content`) - LENGTH(REPLACE(`post_content`, ' ', ''))+1)) AS wordcount
								  FROM `$blogPostsTable`
								  WHERE post_status = 'publish'
								  GROUP BY thedt2) b
							ON b.thedt2 = a.thedt
								GROUP BY week ORDER BY week DESC";
					$results = $wpdb->get_results($sql);
					foreach ($results as $result) {
						$result->blogID = $blog;
						if(!count($word_avg_mo) && !$result->wordcount) continue;
						if(!isset($word_avg_mo[$result->week])){
							$word_avg_mo[$result->thedt] = $result->wordcount;
						}else if(!isset($done_huh["word_avg_" . $blog])){
							$word_avg_mo[$result->thedt] += $result->wordcount;
						}
						$word_avg[$result->week] = $result->thedt;
						$done_huh["word_avg_" . $blog] = true;
					}
					
					
					/*
					$sql = "SELECT DATE_FORMAT(post_date, \"%Y-%U\") AS week, DATE_FORMAT(post_date,\"%Y-%m\") AS thedt, "
							. "SUM( LENGTH(`post_content`) - LENGTH(REPLACE(`post_content`, ' ', ''))+1) AS wordcount, "
							. "SUM(LENGTH(post_content)) "
							. "FROM  `$blogPostsTable` "
							. "WHERE post_status = 'publish' "
							. "GROUP BY week ORDER BY week DESC";
					*/
					
					$sql = "SELECT a.week, a.thedt, b.wordcount FROM
								(SELECT DATE_FORMAT(post_date, \"%Y-%U\") AS week, DATE_FORMAT(post_date,\"%Y-%m\") AS thedt
								 FROM `$blogPostsTable`
								 WHERE post_status = 'publish'
								 GROUP BY week) a
							JOIN (SELECT DATE_FORMAT(post_date,\"%Y-%m\") AS thedt2, SUM( LENGTH(`post_content`) - LENGTH(REPLACE(`post_content`, ' ', ''))+1) AS wordcount
								  FROM `$blogPostsTable`
								  WHERE post_status = 'publish'
								  GROUP BY thedt2) b
							ON b.thedt2 = a.thedt
								GROUP BY week ORDER BY week DESC";
					$results = $wpdb->get_results($sql);
					foreach ($results as $result) {
						$result->blogID = $blog;
						if(!count($word_total_mo) && !$result->wordcount) continue;
						if(!isset($word_total_mo[$result->week])){
							$word_total_mo[$result->thedt] = $result->wordcount;
						}else if(!isset($done_huh["word_total_" . $blog])){
							$word_total_mo[$result->thedt] += $result->wordcount;
						}
						$word_total[$result->week] = $result->thedt;
						$done_huh["word_total_" . $blog] = true;
					}
					if($blog !== false) restore_current_blog();
				}
				
			
				$sql = "SELECT DATE_FORMAT(date, \"%Y-%U\") AS week, DATE_FORMAT(date,\"%Y-%m\") AS thedt, "
						. "COUNT(*) AS count "
						. "FROM  `" . $wpdb->prefix . "wtyd_bookmarks` "
						. "GROUP BY week ORDER BY week ASC";
				$results = $wpdb->get_results($sql);
				$keys = array();
				foreach ($results as $result) {
					if(!count($bookmarks) && !$result->count) continue;
					if(isset($keys[$result->thedt])){
						$bookmarks[$keys[$result->thedt]] += $result->count;
					}else{
						$bookmarks[$result->week] = $result->count;
						$keys[$result->thedt] = $result->week;
					}
				}
				
				$sql = "SELECT DATE_FORMAT(date, \"%Y-%U\") AS week, DATE_FORMAT(date,\"%Y-%m\") AS thedt, "
						. "FLOOR(AVG(hits)) AS hits, FLOOR(AVG(circulation)) AS circulation "
						. "FROM  `" . $wpdb->prefix . "wtyd_feedstats` "
						. "GROUP BY week ORDER BY week DESC";
	
				$results = $wpdb->get_results($sql);
				foreach ($results as $result) {
					$fb_hits[$result->week] = $result->hits;
					$fb_circ[$result->week] = $result->circulation;
				}
				
				
				
				$sql = "SELECT DATE_FORMAT(date, \"%Y-%U\") AS week, DATE_FORMAT(date,\"%Y-%m\") AS thedt, "
						. "AVG(visits) AS visits "
						. "FROM  `" . $wpdb->prefix . "wtyd_ga_visits` "
						. "GROUP BY week ORDER BY week DESC";
				$results = $wpdb->get_results($sql);
				foreach ($results as $result) {
					if(!count($ga_avg_visits) && !$result->visits) continue;
					$ga_avg_visits[$result->week] = $result->visits;
				}
				
				
				
				$sql = "SELECT DATE_FORMAT(date, \"%Y-%U\") AS week, DATE_FORMAT(date,\"%Y-%m\") AS thedt, "
						. "SUM(visits) AS visits "
						. "FROM  `" . $wpdb->prefix . "wtyd_ga_visits` "
						. "GROUP BY week ORDER BY week DESC";
	
				$results = $wpdb->get_results($sql);
				foreach ($results as $result) {
					if(!count($ga_sum_visits) && !$result->visits) continue;
					$ga_sum_visits[$result->week] = $result->visits;
				}
				
				
				
				$sql = "SELECT DATE_FORMAT(date, \"%Y-%U\") AS week, DATE_FORMAT(date,\"%Y-%m\") AS thedt, "
						. "FLOOR(AVG(seconds)) AS seconds "
						. "FROM  `" . $wpdb->prefix . "wtyd_ga_timeonsite` "
						. "GROUP BY week ORDER BY week DESC";
	
				$results = $wpdb->get_results($sql);
				foreach ($results as $result) {
					if(!count($ga_avg_time) && !$result->seconds) continue;
					$ga_avg_time[$result->week] = round($result->seconds, 1);
				}
				
				
				$sql = "SELECT DATE_FORMAT(date, \"%Y-%U\") AS week, DATE_FORMAT(date,\"%Y-%m\") AS thedt, "
						. "FLOOR(AVG(rate)) AS rate "
						. "FROM  `" . $wpdb->prefix . "wtyd_ga_bouncerate` "
						. "GROUP BY week ORDER BY week DESC";
	
				$results = $wpdb->get_results($sql);
				foreach ($results as $result) {
					if(!count($ga_bounce_rate) && !$result->rate) continue;
					$ga_bounce_rate[$result->week] = $result->rate;
				}
				
				
				
				$sql = "SELECT DATE_FORMAT(nu.date, \"%Y-%U\") AS week, DATE_FORMAT(nu.date,\"%Y-%m\") AS thedt, "
						. "FLOOR(AVG(nu.visits / v.visits * 100)) AS newrate "
						. "FROM  `" . $wpdb->prefix . "wtyd_ga_newusers` nu "
						. "LEFT JOIN `" . $wpdb->prefix . "wtyd_ga_visits` v "
						. "ON nu.date=v.date "
						. "GROUP BY week ORDER BY week DESC ";
	
	
				$results = $wpdb->get_results($sql);
				foreach ($results as $result) {
					if(!count($ga_new_rate) && !$result->newrate) continue;
					$ga_new_rate[$result->week] = $result->newrate;
				}
				
				
				
				$sql = "SELECT DATE_FORMAT(date, \"%Y-%U\") AS week, DATE_FORMAT(date,\"%Y-%m\") AS thedt, "
						. "AVG(events) AS events "
						. "FROM  `" . $wpdb->prefix . "wtyd_ga_eventpervisit` "
						. "GROUP BY week ORDER BY week DESC";
	
				$results = $wpdb->get_results($sql);
				foreach ($results as $result) {
					if(!count($ga_events_per_visit) && !$result->events) continue;
					$ga_events_per_visit[$result->week] = $result->events;
				}
				
				
				foreach($word_total as $var => $val){
					$word_total[$var] = $word_total_mo[$val];
				}
				foreach($word_avg as $var => $val){
					$word_avg[$var] = $word_avg_mo[$val];
				}
				foreach($post_counts as $var => $val){
					$post_counts[$var] = $post_counts_mo[$val];
				}
				
				
				
				
				if(isset($wpSlimStat) && $wpSlimStat){
	//				echo "stats installed";
				}
			}

			$output["ga_sum_visits"]		= array("name" => "Total Visits",		"data" => $ga_sum_visits,			"monthly" => false);
			$output["fb_circ"]				= array("name" => "Subscribers",		"data" => $fb_circ,					"monthly" => false);
			$output["fb_hits"]				= array("name" => "Feed Hits",			"data" => $fb_hits,					"monthly" => false);
			$output["ga_avg_visits"]		= array("name" => "Avg Visits",			"data" => $ga_avg_visits,			"monthly" => false);
			$output["ga_avg_time"]			= array("name" => "Avg Time (sec)",		"data" => $ga_avg_time,				"monthly" => false);
			$output["ga_bounce_rate"]		= array("name" => "Bounce Rate",		"data" => $ga_bounce_rate,			"monthly" => false);
			$output["ga_new_rate"]			= array("name" => "% New Visitors",		"data" => $ga_new_rate,				"monthly" => false);
			$output["ga_events_per_visit"]	= array("name" => "Clicks per Visit",	"data" => $ga_events_per_visit,		"monthly" => false);
			$output["post_counts"]			= array("name" => "Posts",				"data" => $post_counts,				"monthly" => true);
			$output["comment_counts"]		= array("name" => "Comments",			"data" => $comment_counts,			"monthly" => false);
			$output["word_total"]			= array("name" => "Total Words",		"data" => $word_total,				"monthly" => true);
			$output["word_avg"]				= array("name" => "Avg Words",			"data" => $word_avg,				"monthly" => true);
			$output["bookmarks"]			= array("name" => "Bookmarks",			"data" => $bookmarks,				"monthly" => true);
			
			
	        $pi_api_key = trim(get_option('WTYD_pi_api_key')); 
	        $pi_username = trim(get_option('WTYD_pi_username')); 
	        if($pi_api_key && $pi_username){
		    	$pidatasource = new PIDataSource($pi_api_key, $pi_username, $wpdb);
		    	$output = array_merge($output, $pidatasource->getWeeklyData());
	        }
	        
	        $tweetdatasource = new TweetsDataSource($wpdb);
	        if(count($tweetdatasource)){
		        $output = array_merge($output, $tweetdatasource->getWeeklyData());
	        }
			
					
			if (defined('POC_CACHE_4')) poc_cache_store($cache_key, $output); 
			
			return $output;
		}
	
		
		
		
		
		/**
		 * echos a JavaScript closure
		 * that will return the json data
		 * from getSimpleStats
		 *
		 * also, a global WTYD_MINTIME
		 * variable will be set with the
		 * minimum time datapoint
		 *
		 * data should be assumed to be
		 * between WTYD_MINTIME and now
		 */
		function echoScript(){
	
			$stats = $this->getSimpleStats();
	
			echo "(function(){ ";
			echo "all_data = [];\n";
		
			$color = 0;
			$mintime = time();
			
			foreach($stats as $var => $arr){
				$val_max = 0;
				echo "$var = [];\n";
				
				$name = $arr["name"];
				$data = $arr["data"];
				$monthly = $arr["monthly"];
				if(!is_array($data)) continue;
				ksort($data);
				foreach($data as $dt => $count){
					$year = substr($dt, 0, 4);
					$week = (int) substr($dt, 5);
					$time = strtotime($year . "-01-02");
	//				echo $year . " " . $week . "\n";
	//				echo $time . "+" . $week . " weeks" . "\n";
					$time = strtotime("+" . $week . " weeks", $time);
					if($time < $mintime) $mintime = $time;
	//				echo $time . "\n";
	//				$time = strtotime($dt . "-02");
					echo  "/*" . $year . "-" . $week . "*/\n";
		        	echo "$var.push([$time*1000, $count]);\n";
		        	if($count > $val_max) $val_max = $count;
			    }
			    
				echo "var " . $var . "_data = {\n";
				echo "	data: $var,\n";
				echo "	label: \"" . htmlentities($name) . "\",\n";
				echo "	monthly: " . ($monthly ? "true" : "false") . ",\n";
				$c = ($color < 2) ? "CHECKED" : "";
				echo "	dom : $(\"<div><input type='checkbox' id='wtyd_$color' $c><label for='wtyd_$color'>" . htmlentities($name) . "</label></div>\"),\n";
				if($val_max > 250){
					// kinda tall data, put it on yaxis2
					echo "	max : 0,\n";
					echo "	max2 : $val_max,\n";
					echo "	yaxis: 2,\n";
				}else{
					echo "	max : $val_max,\n";
				}
				echo "	color: $color\n";
				echo "};\n";
				echo "\n";
				echo "all_data.push(" . $var . "_data);\n";
				$color++;
			}
			$mintime = strtotime(gmdate("Y-m-02", $mintime));
			echo "WTYD_MINTIME = " . $mintime . " * 1000;";
			echo "return all_data;";
			echo "})();";
		}
		
		
		
		function getImageFrom($url, $content){
        	$blacklist = array("music_note.gif", "emoticons/", "smilies/", "scriptandstyle.com", "xarj.net");
        
			// Run preg_match_all to grab all the images and save the results in $aPics
			preg_match_all("/\< *[img][^\>]*[src] *= *[\"\']{0,1}([^\"\'\ >]*)/i", $content, $matches);
			// Check to see if we have at least 1 image
			if(count($matches[1])){
				if(strpos($matches[1][0], "http") !== false){
					$add = true;
					for($i=0;$i<count($blacklist);$i++){
						if(is_int(strpos($matches[1][0], $blacklist[$i]))){
							$add = false;
							break;
						}
					}
					if($add) return "<a href='" . $url . "' rel='nofollow'><img src=\"" . $matches[1][0] . "\" /></a>";
				}
			}
			return false;
		}
		
		
		
		function getBookmarkImages($num, $page){
			global $wpdb;
	    				
	    	$sql = "SELECT * FROM `" . $wpdb->prefix . "wtyd_bookmarks` ORDER BY `date` DESC LIMIT " . ($page*$num) . "," . $num;
			$results = $wpdb->get_results($sql);
	        $loopcount = 0;
	        $out = array();
	        foreach($results as $item){
	        	$image = $this->getImageFrom($item->book_url, $item->content);
	        	if($image && strlen($image)){
	        		$out[] = $image;
	        	}			}
			return $out;
		}
		
		
		
		/**
		 * prints out a <ul> list (with optional css class)
		 * with an <li> of $num bookmarks from Google Reader
		 */
	    function readerShared($num=false, $page=0, $excerpt=false, $date_format=false, $s="") {
	    	global $wpdb;
	    	
	        if(!is_int($num)){
		        $display = trim(get_option('GoogleReader_NumDisplay')); 
	        }else{
	        	$display = $num;
	        }
	        if(!$display) $display = 10;
	        
	        
	        $class = trim(get_option('GoogleReader_CSSClass')); 
	    
			$lastdt = "";
	        printf('<ul%s>', ($class != '') ? " class=\"$class\"" : '');
			
			if(strlen($s)){
				$like = "'%" . addslashes($s) . "%'";
				$where = "WHERE book_title LIKE $like OR  source_title LIKE $like OR content LIKE $like OR author LIKE $like";
			}else{
				$where = "";
			}
	    	$sql = "SELECT * FROM `" . $wpdb->prefix . "wtyd_bookmarks` $where ORDER BY `date` DESC LIMIT " . ($page*$display) . "," . $display;
	    	
			$results = $wpdb->get_results($sql);
	        $loopcount = 0;
			foreach($results as $item){
	            if ($loopcount < $display) {
	            	echo "<li>";
	            	if($date_format){
		            	$t = strtotime($item->date);
		            	$newdt = date($date_format, $t);
		            	if($newdt != $lastdt){
		            		echo "<span class='date'>" . $newdt . "</span>";
		            		$lastdt = $newdt;
		            	}
	            	}
	                printf('<a href="%s" rel="nofollow" class="link">%s</a><a href="%s" rel="nofollow" class="author">%s</a>',$item->book_url,$item->book_title,$item->source_url,$item->source_title);
	                if($excerpt && function_exists("sem_fancy_excerpt")){
	                	echo "<div>";
//	                	echo $item->content;
	                	echo sem_fancy_excerpt("", 200, $item->content);
	                	echo "</div>";
	                }
	                echo "</li>";
	                $loopcount++;
	            }
			}
			if(count($results) == 0){
                echo "<li>";
                echo "No results";
                echo "</li>";
			}
	        echo('</ul>'); 
	    }
	    
	    function countBookmarks($s=""){
	    	global $wpdb;
			if(strlen($s)){
				$like = "'%" . addslashes($s) . "%'";
				$where = "WHERE book_title LIKE $like OR  source_title LIKE $like OR content LIKE $like OR author LIKE $like";
			}else{
				$where = "";
			}
	    	$sql = "SELECT COUNT(*) AS count FROM `" . $wpdb->prefix . "wtyd_bookmarks` $where";
			$results = $wpdb->get_results($sql);
			foreach($results as $item){
				return $item->count;
			}
			return 0;
	    }
	    
	    function wp_wtyd_header(){
		    include(dirname(__FILE__).'/header.php');
	    }
	    
	    function echoGraphs(){
		    include(dirname(__FILE__).'/graphs.php');
	    }
	    
	    
	    
	    function popularCompare($a, $b){
	    	if($a->count < $b->count) return 1;
	    	return -1;
	    }
	    
	    
	    
	    function update_popular_posts($last, $num){
			global $wpdb, $post, $wp_query;
			//
			// caches the recently popular posts
			// 
			$filename = md5("recently_popular_" . $last . "_" . $num);
			$list = $this->getCachedFile($filename);
			$age = $this->getCachedAge($filename);



			$ids = get_option('WTYD_blog_ids');
			$ids = explode(",", $ids);
			$nids = "";
			foreach($ids as $id){
				$id = (int) $id;
				if($id != 0){
					if(strlen($nids)) $nids .= ",";
					$nids .= "'" . $id . "'";
				}
			};
			
			// get a list of blogs in order of most recent update. show only public and nonarchived/spam/mature/deleted
			$sql = "SELECT blog_id FROM $wpdb->blogs WHERE
				public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0' " .
				($how_long ? "AND last_updated >= DATE_SUB(CURRENT_DATE(), INTERVAL $how_long DAY) " : " ") .
				(strlen($nids) ? "AND blog_id IN ($nids) " : "") .
				"ORDER BY last_updated DESC";

			$blogs = $wpdb->get_col($sql);
			if(!$blogs) $blogs = array(false);
			
			$list = array();


			if ($blogs) {
				foreach ($blogs as $blog) {
					if($blog !== false) switch_to_blog($blog);
			    	$sql = "SELECT p.*, s.count, ss.count AS prev FROM "
							. "(
							   SELECT COUNT(s1.resource) AS count, s1.* FROM `" . $wpdb->prefix . "slim_stats` s1 
							   WHERE s1.dt > (" . (time() - 60*60*24*$last) . ") AND 
							   		 s1.platform != -1 AND
							   		 s1.browser IN ('0','1','2','3','4','5','6','7','8','9','10','23','24','25','26','27','28','29','39','42')
							   GROUP BY s1.resource
							) s "
							. "LEFT JOIN "
							. "(
							   SELECT COUNT(s2.resource) AS count, s2.* FROM `" . $wpdb->prefix . "slim_stats` s2 
							   WHERE s2.dt <= (" . (time() - 60*60*24*$last) . ") AND
							         s2.dt >  (" . (time() - 60*60*24*$last*2) . ") AND 
							   		 s2.platform != -1 AND
							   		 s2.browser IN ('0','1','2','3','4','5','6','7','8','9','10','23','24','25','26','27','28','29','39','42')
							   GROUP BY s2.resource
							) ss "
							. " ON s.resource = ss.resource "
							. "LEFT JOIN "
							. "`" . $wpdb->prefix . "posts` p ON s.resource LIKE CONCAT(\"%/\",p.post_name,\"/\")
							 WHERE p.post_status=\"publish\" AND p.post_title != \"Popular Content\" 
							 ORDER BY count DESC LIMIT " . $num;


					$results = $wpdb->get_results($sql);

					foreach($results as $item){
						$item->blogID = $blog;
						$list[] = $item;
					}

					if($blog !== false) restore_current_blog();
				}

				usort($list, array($this, "popularCompare"));

			}
		
			$this->cacheFile($list, $filename);
	    }
	    
	    
	    function popular_posts($last=1, $num=10, $echo=false, $excerpt=false, $crumbs=false){
			global $wpdb, $post, $wp_query;
			
			$crumbs = false;
			
			$orig_post = $post;
			//
			// caches the recently popular posts
			// 
			$filename = md5("recently_popular_" . $last . "_" . $num);
			$list = $this->getCachedFile($filename);
			$age = $this->getCachedAge($filename);
			
			
			
			
			if(!$list || count($list) == 0){
				// no cached data
				return array();
			}
			
			
			$is_home = is_home();
			$post_id = $wp_query->post->ID;
			$is_page = is_page();
			
			$old_post = $post;
			
			if($echo){
				echo "<ul>";
				$count = 0;
				$prev = false;
				for($i=0;$i<count($list);$i++){
					$item = $list[$i];
					
					
					$maxafter = 0;
					for($j = $i+1; $j<count($list);$j++){
						if($list[$j]->prev > $maxafter) $maxafter = $list[$j]->prev;
					}
					$minbefore = $item->prev;
					for($j = 0; $j<$i;$j++){
						if($list[$j]->prev < $minbefore) $minbefore = $list[$j]->prev;
					}
					if($item->blogID){
						switch_to_blog($item->blogID);
					}
					
					$up = false;
					$down = false;
					if($item->prev < $maxafter) $up = true;
					if($item->prev > $minbefore) $down = true;


					$class = "";
					if($up && !$down){
						$class = "class='up'";
					}else if(!$up && $down){
						$class = "class='down'";
					}else{
						$class = "";
					}
					
					
					echo "<li " . $class . ">";
					
					if($up && !$down){
						echo "<img src='" . get_bloginfo('wpurl') . "/wp-content/plugins/wp-wtyd/up.png' style='margin:0'>";
					}else if(!$up && $down){
						echo "<img src='" . get_bloginfo('wpurl') . "/wp-content/plugins/wp-wtyd/down.png' style='margin:0'>";
					}else{
						echo "<img src='" . get_bloginfo('wpurl') . "/wp-content/plugins/wp-wtyd/blank.png' style='margin:0'>";
					}
//					echo $item->count . " vs " . $item->prev;



					if($excerpt) echo "<h2>";
					if($crumbs){
						query_posts("p=" . $item->ID);
						if (have_posts()){
							if($item->blogID) switch_to_blog($item->blogID);
							the_post();
							bcn_display();
							if($item->blogID !== false) restore_current_blog();
						}else{
							query_posts("page_id=" . $item->ID);
							if (have_posts()){
								if($item->blogID) switch_to_blog($item->blogID);
								the_post();
								bcn_display();
								if($item->blogID !== false) restore_current_blog();
							}
						}
					}else{
						echo " <a href='" . get_permalink($item->ID) . "'>";
						echo htmlentities($item->post_title) . "</a>";
						echo "</a>";
					}
					if($excerpt) echo "</h2>";
					if($excerpt && function_exists("sem_fancy_excerpt")){
	                	echo "<div>";
//	                	echo $item->content;
	                	echo sem_fancy_excerpt("", 100, $item->post_content);
	                	echo "</div>";
	                }
					echo "</li>";
					if($item->blogID !== false) restore_current_blog();
					$count ++;
					if($count >= $num) break;
					
					$prev = $item;
				}
				echo "</ul>";
			}
			$post = $old_post;
			/*
			if($is_home){
				query_posts("");
			}else if($is_page){
				query_posts("page_id=" . $post_id);
				if (have_posts()){
					the_post();
				}
			}else{
				query_posts("p=" . $post_id);
				if (have_posts()){
					the_post();
				}
			}
			*/
			$post = $orig_post;
			return $list;
	    }
	    
	    
	    function wtyd_install () {
			global $wpdb;
			$table_name = $wpdb->prefix . "wtyd_bookmarks";
			if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
				$sql = "CREATE TABLE  `$table_name` (
						`id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
						`gid` VARCHAR( 256 ) NOT NULL ,
						`date` DATETIME NOT NULL ,
						`book_url` TEXT NOT NULL ,
						`book_title` TEXT NOT NULL ,
						`source_url` TEXT NOT NULL ,
						`source_title` TEXT NOT NULL ,
						`content` TEXT NOT NULL ,
						`author` TEXT NOT NULL ,
						INDEX (  `gid` ,  `date` )
						) ENGINE = MYISAM ;";
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
			}
			$table_name = $wpdb->prefix . "wtyd_feedstats";
			if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
				$sql = "CREATE TABLE  `$table_name` (
						`id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
						`date` DATE NOT NULL ,
						`circulation` INT NOT NULL ,
						`hits` INT NOT NULL ,
						INDEX (  `date` )
						) ENGINE = MYISAM ;";
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
			}
			$table_name = $wpdb->prefix . "wtyd_ga_timeonsite";
			if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
				$sql = "CREATE TABLE  `$table_name` (
						`id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
						`date` DATE NOT NULL ,
						`seconds` INT NOT NULL ,
						INDEX (  `date` ,  `seconds` )
						) ENGINE = MYISAM ;";
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
			}
			$table_name = $wpdb->prefix . "wtyd_ga_bouncerate";
			if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
				$sql = "CREATE TABLE  `$table_name` (
						`id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
						`date` DATE NOT NULL ,
						`rate` DOUBLE NOT NULL ,
						INDEX (  `date` ,  `rate` )
						) ENGINE = MYISAM ;";
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
			}
			$table_name = $wpdb->prefix . "wtyd_ga_newusers";
			if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
				$sql = "CREATE TABLE  `$table_name` (
						`id` BIGINT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
						`date` DATE NOT NULL ,
						`visits` INT NOT NULL ,
						INDEX (  `date` ,  `visits` )
						) ENGINE = MYISAM ;";
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
			}
			$table_name = $wpdb->prefix . "wtyd_ga_visits";
			if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
				$sql = "CREATE TABLE `$table_name` (
						  `id` bigint(20) NOT NULL auto_increment,
						  `date` date NOT NULL,
						  `visits` int(11) NOT NULL,
						  PRIMARY KEY  (`id`),
						  KEY `date` (`date`,`visits`)
						) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=430 ;";
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
			}
			$table_name = $wpdb->prefix . "wtyd_ga_eventpervisit";
			if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
				$sql = "CREATE TABLE `$table_name` (
						  `id` bigint(20) NOT NULL auto_increment,
						  `date` date NOT NULL,
						  `events` double NOT NULL,
						  PRIMARY KEY  (`id`),
						  KEY `date` (`date`,`events`)
						) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
			}
			$table_name = $wpdb->prefix . "wtyd_pi_profile";
			if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
				$sql = "CREATE TABLE `$table_name` (
						  `id` bigint(20) NOT NULL auto_increment,
						  `date` date NOT NULL,
						  `name` varchar(255) NOT NULL,
						  `twitter` varchar(255) NOT NULL,
						  `slug` varchar(255) NOT NULL,
						  `authority` int(11) NOT NULL,
						  `activity` int(11) NOT NULL,
						  `audience` int(11) NOT NULL,
						  `realness` int(11) NOT NULL,
						  `peerindex` int(11) NOT NULL,
						  `url` varchar(255) NOT NULL,
						  `topics` text NOT NULL,
						  PRIMARY KEY  (`id`)
						) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
				dbDelta($sql);
			}
		}

		// Add tab in the admin menu
		function wtyd_add_tab( $s ) {
			global $wpSlimStat;
			add_submenu_page( 'index.php', 'Your Data', 'Your Data', 1, __FILE__, array( &$this, 'echoGraphs' ) );
			return $s;
		}
		
		function wtyd_menu() { 
			add_options_page('Welcome To Your Data Account Settings', 'Welcome To Your Data', 9, "wp-wtyd/" . basename(__FILE__)); 
		}

	}
	
	
	/**
	 * Remove HTML tags, including invisible text such as style and
	 * script code, and embedded objects.  Add line breaks around
	 * block-level tags to prevent word joining after tag removal.
	 */
 	if(!function_exists("strip_html_tags")){
		function strip_html_tags( $text )
		{
		    $text = preg_replace(
		        array(
		          // Remove invisible content
		            '@<head[^>]*?>.*?</head>@siu',
		            '@<style[^>]*?>.*?</style>@siu',
		            '@<script[^>]*?.*?</script>@siu',
		            '@<object[^>]*?.*?</object>@siu',
		            '@<embed[^>]*?.*?</embed>@siu',
		            '@<applet[^>]*?.*?</applet>@siu',
		            '@<noframes[^>]*?.*?</noframes>@siu',
		            '@<noscript[^>]*?.*?</noscript>@siu',
		            '@<noembed[^>]*?.*?</noembed>@siu',
		          // Add line breaks before and after blocks
		            '@</?((address)|(blockquote)|(center)|(del))@iu',
		            '@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu',
		            '@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu',
		            '@</?((table)|(th)|(td)|(caption))@iu',
		            '@</?((form)|(button)|(fieldset)|(legend)|(input))@iu',
		            '@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu',
		            '@</?((frameset)|(frame)|(iframe))@iu',
		        ),
		        array(
		            ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
		            "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0",
		            "\n\$0", "\n\$0",
		        ),
		        $text );
		    return strip_tags( $text );
		}
	}
	
	$wtydPlugin = new WTYD();
	
	register_activation_hook(__FILE__ , array($wtydPlugin,"wtyd_install"));
	add_action('admin_menu', array($wtydPlugin,"wtyd_menu")); 
	add_action('admin_menu', array($wtydPlugin,"wtyd_add_tab") );
	if ( strpos( $_GET["page"], "wp-wtyd" ) !== false ) {
		add_action('admin_head', array($wtydPlugin,"wp_wtyd_header"));
	}



if(isset($_REQUEST["updateWTYD"])){	
	add_action('init',array($wtydPlugin, "updateDatabase"));
}

	
}
?>
