<?

/**
 * datasource for Peer Index
 */
class PIDataSource extends WTYDDataSource{


	private $api_key;
	
	private $username;
	
	private $wpdb;
	
	public function __construct($api_key, $username, $wpdb){
		$this->api_key = $api_key;
		$this->username = $username;
		$this->wpdb = $wpdb;
	}

	function getShortName(){
		return "Peer Index";
	}
	
	function getLongName(){
		return "Peer Index";
	}
	
	function getDescription(){
		return "My authority, audience, and reach according to PeerIndex.net";
	}
	
	function update($all=false){
		//
		// get the data, but only query the URL
		// if a fair bit of time has passed
		$wpdb = $this->wpdb;
		$filename = md5($this->api_key);
		
		$contents = $this->getCachedFile($filename);
		$age = $this->getCachedAge($filename);
		
		
		if($age > 60 || !$contents){
			// only update every hour at most

			$url = "http://api.peerindex.net/1/profile/show.json?id=" . urlencode($this->username) . "&api_key=" . urlencode($this->api_key);
			
			$contents = file_get_contents($url);
			$this->cacheFile($contents, $filename);
			
			
			$data = json_decode($contents);
			
			$today = date("Y-m-d");
			
	    	$sql = "SELECT COUNT(*) AS count FROM " . $wpdb->prefix . "wtyd_pi_profile WHERE date='" . $today . "'";
			$results = $wpdb->get_results($sql);
			
			if(($result = $results[0]) && !$data->error) {
				if($result->count > 0){
					// update
					$sql = "UPDATE " . $wpdb->prefix . "wtyd_pi_profile SET "
						 . "name      = '" . addslashes($data->name) . "',"
						 . "twitter   = '" . addslashes($data->twitter) . "',"
						 . "slug      = '" . addslashes($data->slug) . "',"
						 . "authority = '" . addslashes($data->authority) . "',"
						 . "activity  = '" . addslashes($data->activity) . "',"
						 . "audience  = '" . addslashes($data->audience) . "',"
						 . "realness  = '" . addslashes($data->realness) . "',"
						 . "peerindex = '" . addslashes($data->peerindex) . "',"
						 . "url       = '" . addslashes($data->url) . "',"
						 . "topics = '" . addslashes(json_encode($data->topics)) . "'"
						 . "WHERE date='" . $today . "'";
					$results = $wpdb->query($sql);
				}else{
					$sql = "INSERT INTO " . $wpdb->prefix . "wtyd_pi_profile "
						 . "(`date`,`name`,`twitter`,`slug`,`authority`,`activity`,`audience`,`realness`,`peerindex`,`url`,`topics`) "
						 . " VALUES "
						 . "('" . addslashes($today) . "','" . addslashes($data->name) . "','" . addslashes($data->twitter) . "','" . addslashes($data->slug) . "',"
						 . "'" . addslashes($data->authority) . "','" . addslashes($data->activity) . "','" . addslashes($data->audience) . "','" . addslashes($data->realness) . "',"
						 . "'" . addslashes($data->peerindex) . "','" . addslashes($data->url) . "','" . addslashes(json_encode($data->topics)) . "')";
					$results = $wpdb->query($sql);
					
	//				$wpdb->insert_id
				}
			}
		}else{
			echo "skipped.<br>";
		}
		
		return true;
	}

	function getDailyData($start=false, $end=false){
		return array();
	}

	function getWeeklyData($start=false, $end=false){
		$wpdb = $this->wpdb;
				
		$sql = "SELECT DATE_FORMAT(date, \"%Y-%U\") AS week, DATE_FORMAT(date,\"%Y-%m\") AS thedt, "
				. "AVG(authority) AS authority, "
				. "AVG(activity) AS activity, "
				. "AVG(audience) AS audience, "
				. "AVG(peerindex) AS peerindex "
				. "FROM  `" . $wpdb->prefix . "wtyd_pi_profile` "
				. "GROUP BY week ORDER BY week DESC";

		$pi_authority = array();
		$results = $wpdb->get_results($sql);
		foreach ($results as $result) {
			if(!count($pi_authority) && !$result->authority) continue;
			$pi_authority[$result->week] = $result->authority;
			$pi_activity[$result->week] = $result->activity;
			$pi_audience[$result->week] = $result->audience;
			$pi_peerindex[$result->week] = $result->peerindex;
		}
		
		$output = array();
		$output["pi_authority"]		= array("name" => "PI Authority",		"data" => $pi_authority,		"monthly" => false);
		$output["pi_activity"]		= array("name" => "PI Activity",		"data" => $pi_activity,			"monthly" => false);
		$output["pi_audience"]		= array("name" => "PI Audience",		"data" => $pi_audience,			"monthly" => false);
		$output["pi_peerindex"]		= array("name" => "PI PeerIndex",		"data" => $pi_peerindex,		"monthly" => false);
		return $output;
	}

	function getMonthlyData($start=false, $end=false){
		return array();
	}

}


?>
