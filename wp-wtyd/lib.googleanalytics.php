<?php
/*
Copyright (C) 2008  Joe Tan

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA

$Revision: 109 $
$Date: 2008-07-15 10:51:01 -0400 (Tue, 15 Jul 2008) $
$Author: joetan54 $
$URL: http://wordpress-reports.googlecode.com/svn/trunk/tantan-reports/wordpress-reports/lib.googleanalytics.php $
*/

if (!$path_delimiter) {
    if (strpos($_SERVER['SERVER_SOFTWARE'], "Windows") !== false) {
        $path_delimiter = ";";
    } else {
        $path_delimiter = ":";
    }
    ini_set("include_path", substr(__FILE__, 0, strrpos(__FILE__, "/")) . "/PEAR" . $path_delimiter . ini_get("include_path") );
}

class WTYDGoogleAnalytics {


	var $ga;

    function __construct() {
    	$this->ga = null;
    }
    
    function isLoggedIn() {
        return $this->loggedin ? true : false;
    }
    function login($user, $passwd) {
        try{
			$this->ga = new gapi($user,$passwd);
			$this->loggedin = true;
		}catch(Exception $e){
			$this->loggedin = false;
		}
        return $this->loggedin;
    }
    
    function getSiteProfiles() {
        if (!$this->isLoggedIn()) {
            return array();
        }
		$this->ga->requestAccountData(1, 100);
		return $this->ga->getResults();
    }
	








    function getReport($profile, $start, $stop, $reportType='') {
        return $this->_parseCSV($this->getReportData($profile, $start, $stop, $reportType, 'CSV'));
        //return $this->_parseXML($this->getReportData($profile, $start, $stop, $reportType, 'XML'));
    }
    
    function getReportData($profile, $start, $stop, $reportType='', $mode='XML') {
        if (!$this->isLoggedIn()) {
            return '';
        }
        $lastStart = "";
        
		$out = array();
		
		$doneWithZeroes = false;

        while($start < $stop && $lastStart != $start){
	        $lastStart = $start;
			$data = $this->ga->requestReportData($profile,array('date'),array('ga:totalEvents','ga:newVisits','ga:bounces','ga:entrances','ga:timeOnSite','ga:visits'), "date", null, $start, $stop);
			foreach($data as $d){
				$dim = $d->getDimesions();
				$met = $d->getMetrics();
				$dt = $dim["date"];
	
				$visits = (int)$met["visits"];
				$timeOnSite = ((int)$met["visits"]) ? (int)($met["timeOnSite"] * 1.0 / $met["visits"]) : 0;
				$bounce = ((int)$met["entrances"]) ? ($met["bounces"] * 1.0 / $met["entrances"] * 100) : 0;
				$newusers = (int)$met["newVisits"];
				$eventspervisit = ((int)$met["visits"]) ? ($met["totalEvents"] * 1.0 / $met["visits"]) : 0;
				
				
				$dt = substr($dt, 0, 4) . "-" . substr($dt, 4, 2) . "-" . substr($dt, 6, 2);
				$start = $dt;
				
				if($visits != 0 || $timeOnSite != 0 || $bounce != 0 || $newusers != 0 || $eventspervisit != 0){
					$doneWithZeroes = true;
				}
				
				if($doneWithZeroes){
					$out[$dt] = array("visits" => $visits,
									  "timeonsite" => $timeOnSite,
									  "bouncerate" => $bounce,
									  "newusers" => $newusers,
									  "eventspervisit" => $eventspervisit);
				}
			}
        }
        
		return $out;
    }

}

?>