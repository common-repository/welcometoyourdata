<?

/**
 * datasource for Peer Index
 */
class TweetsDataSource extends WTYDDataSource{


	private $wpdb;
	
	public function __construct($wpdb){
		$this->wpdb = $wpdb;
	}

	function getShortName(){
		return "Tweets";
	}
	
	function getLongName(){
		return "Tweets";
	}
	
	function getDescription(){
		return "All my tweets.";
	}
	
	function update($all=false){
		
		
		return true;
	}

	function getDailyData($start=false, $end=false){
		return array();
	}

	function getWeeklyData($start=false, $end=false){
		$wpdb = $this->wpdb;

		$table_name = $wpdb->prefix . "ak_twitter";
		if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			// twitter doesn't exist
			// show empty data
			
			return array();
			
		}else{
			// twitter does exist
			// group by week
			
			$sql = "SELECT DATE_FORMAT(tw_created_at, \"%Y-%U\") AS week, DATE_FORMAT(tw_created_at,\"%Y-%m\") AS thedt, "
					. "COUNT(id) AS tweets "
					. "FROM  `" . $table_name . "` "
					. "GROUP BY week ORDER BY week DESC";
					
					
//			echo $sql;
	
			$tweets = array();
			$results = $wpdb->get_results($sql);
			foreach ($results as $result) {
				$tweets[$result->week] = $result->tweets;
			}
			
			$output = array();
			$output["tweets"]		= array("name" => "Tweets",		"data" => $tweets,		"monthly" => false);
			return $output;
			
		}
	}

	function getMonthlyData($start=false, $end=false){
		return array();
	}

}


?>