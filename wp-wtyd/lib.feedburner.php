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
$URL: http://wordpress-reports.googlecode.com/svn/trunk/tantan-reports/wordpress-reports/lib.feedburner.php $

*/
if (!$path_delimiter) {
    if (strpos($_SERVER['SERVER_SOFTWARE'], "Windows") !== false) {
        $path_delimiter = ";";
    } else {
        $path_delimiter = ":";
    }
    ini_set("include_path", substr(__FILE__, 0, strrpos(__FILE__, "/")) . "/PEAR" . $path_delimiter . ini_get("include_path") );
}



class WTYDFeedburner {
    var $xmlParser;

    function __construct() {

        require_once(dirname(__FILE__).'/lib.xml.php');
        $this->xmlParser = new WTYDXML(false, true, true);
        $this->xmlParser->_replace = array();
        $this->xmlParser->_replaceWith = array();

        if (function_exists('curl_init')) {   
            if (!class_exists('TanTanHTTPRequestCurl')) require_once(dirname(__FILE__).'/lib/curl.php');     
            $this->req =& new TanTanHTTPRequestCurl();
        /*
        } elseif (file_exists(ABSPATH . 'wp-includes/class-snoopy.php')) {
			if (!class_exists('Snoopy')) require_once( ABSPATH . 'wp-includes/class-snoopy.php' );
	        if (!class_exists('TanTanHTTPRequestSnoopy')) require_once (dirname(__FILE__).'/../lib/snoopy.php');
	        $this->req =& new TanTanHTTPRequestSnoopy();
		*/
        } else {
            require_once("HTTP/Request.php");
            $this->req =& new HTTP_Request();
        }
    }
    
    function getFeedData($feed, $start=false, $stop=false) {
        $url = "https://feedburner.google.com/api/awareness/1.0/GetFeedData?uri=".urlencode($feed);
        if ($start && $stop) {
            $stop = date('Y-m-d', strtotime($stop));
            $start = date('Y-m-d', strtotime($start));
            $url .= "&dates=$start,$stop";
        }else{
            $stop = date('Y-m-d', time());
            $start = date('Y-m-d', strtotime("-1 week"));
            $url .= "&dates=$start,$stop";
        }

        $this->req->setMethod(HTTP_REQUEST_METHOD_GET);
        $this->req->setURL($url);
        $this->req->sendRequest();
        $response = $this->req->getResponseBody();
        if ($response) { // check ok
            $data = $this->_parseXML($response);
            
            //
            // trim leading zeroes
			foreach($data['records'] as $var => $val){
				if($val["Hits"] != 0 || $val["Circulation"] != 0) break;
				unset($data['records'][$var]);
			}
        }
        return $data;
    }
    
    function _parseXML($xml) {
        $return = array();
        $data = $this->xmlParser->parse($xml);
        if ($data['rsp']['_attributes']['stat'] == 'ok') {
            $return['uri'] = $data['rsp']['feed']['uri'];
            $return['url'] = 'http://feeds.feedburner.com/'.$data['rsp']['feed']['uri'];
            $return['records'] = array();
            if ($data['rsp']['_value']['feed']['_value']['entry'][0]) {
                foreach ($data['rsp']['_value']['feed']['_value']['entry'] as $rec) {
                    $return['records'][] = $this->_record($rec);
                }
            } else {
                $return['records'][] = $this->_record($data['rsp']['feed']['entry']);
            }
            foreach ($return['records'] as $rec) {
                if ($rec['Hits'] > $return['MaxHits']) {
                    $return['MaxHits'] = $rec['Hits'];
                }
                if ($rec['Circulation'] > $return['MaxCirculation']) {
                    $return['MaxCirculation'] = $rec['Circulation'];
                }
            }
        } elseif ($data['rsp']['stat'] == 'fail') {
            $return['error'] = $data['rsp']['err']['msg'];
        }
        return $return;
    }
    
    function _record($rec) {
        return array(
            'Date' => ereg_replace('-', '', $rec['_attributes']['date']), 
            'Date Label' => date('D n/j', strtotime($rec['_attributes']['date'])),
            'Circulation' => $rec['_attributes']['circulation'],
            'Hits' => $rec['_attributes']['hits'],
            );
    }

}
?>