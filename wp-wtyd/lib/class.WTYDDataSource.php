<?



abstract class WTYDDataSource{


	abstract function getShortName();
	
	abstract function getLongName();
	
	abstract function getDescription();
	

	/*
	 * @param all: true if we should
	 */
	abstract function update($all=false);

	/**
	 * return data in the format:
	 *  array ( [0] => array("dt" => "Y-m-d H:i:s", "data" => "###") )
	 * @param start the start date Y-m-d H:i:s to get the data
	 * @param end the end date Y-m-d H:i:s to get the data
	 */
	abstract function getDailyData($start=false, $end=false);

	abstract function getWeeklyData($start=false, $end=false);

	abstract function getMonthlyData($start=false, $end=false);








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
		
		


}



?>