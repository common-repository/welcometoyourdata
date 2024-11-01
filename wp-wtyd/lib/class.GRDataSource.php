<?

/**
 * datasource for Google Reader
 */
class GRDataSource extends WTYDDataSource{


	private $feedurl;
	
	private $wpdb;
	
	public function __construct($feedurl, $wpdb){
		$this->feedurl = $feedurl;
		$this->wpdb = $wpdb;
	}



	function getShortName(){
		return "Bookmarks";
	}
	
	function getLongName(){
		return "Bookmarks";
	}
	
	function getDescription(){
		return "All of my bookmarked sites from around the web.";
	}
	
	function update($all=false){
		//
		// get the data, but only query the URL
		// if a fair bit of time has passed
		$wpdb = $this->wpdb;
		$feedurl = $this->feedurl;
		$filename = md5($feedurl);
		$contents = $this->getCachedFile($filename);
		$age = $this->getCachedAge($filename);
		
		if($all){
			echo "updating all.<br>";
			$contents = file_get_contents($feedurl . "?n=1000");
		}else{
			echo "updating recent.<br>";
			$contents = file_get_contents($feedurl);
		}
		$this->cacheFile($contents, $filename);
		
		//
		// new data, update ye 'ole db
		$feed = simplexml_load_string($contents);
		
        foreach ($feed->entry as $item) {
        
        	$gid = $item->id;
        	$dt = $item->published;
        	$book_url = $item->link[0]['href'];
        	$book_title = $item->title;
        	$source_url = $item->source->link[0]['href'];
        	$source_title = $item->source->title;
        	$content = $item->content;
        	$author = $item->author->name;
        	        	
        	$sql = "SELECT COUNT(*) AS count FROM " . $wpdb->prefix . "wtyd_bookmarks WHERE gid='" . addslashes($gid) . "'";
			$results = $wpdb->get_results($sql);
			if($result = $results[0]) {
				if($result->count > 0){
					// noop. it's in the db
				}else{
					$sql = "INSERT INTO " . $wpdb->prefix . "wtyd_bookmarks "
						 . "(`gid`,`date`,`book_url`,`book_title`,`source_url`,`source_title`,`content`,`author`) "
						 . " VALUES "
						 . "('" . addslashes($gid) . "','" . addslashes($dt) . "','" . addslashes($book_url) . "','" . addslashes($book_title) . "',"
						 . "'" . addslashes($source_url) . "','" . addslashes($source_title) . "','" . addslashes($content) . "','" . addslashes($author) . "')";
					$results = $wpdb->query($sql);
				}
			}
        }
		return true;
	}

	function getDailyData($start=false, $end=false){
		return array();
	}

	function getWeeklyData($start=false, $end=false){
		return array();
	}

	function getMonthlyData($start=false, $end=false){
		return array();
	}

}


?>