<?php

require 'connection.class.php';

/**
 * @author Matt Bush mattbush@cs.stanford.edu
 *   for Code for America
 *   4/16/2011
 */
class FCCApi {
	
	private $_connection;
	
	public function __construct($connection = false) {
		$this->_connection = ($connection) ? $connection : new Connection('http://data.fcc.gov');
	}
			
	/**
	 * Method: findCensusBlock
   *
   * Fetches data from the FCC Consumer Broadband Test API given a
   *   latitude and longitude, as described at
   *   http://reboot.fcc.gov/developer/consumer-broadband-test-api
   *
   * Returns an associative array in the following format, describing
   *   the US county pertaining to the provided lat/long:
   * array ("wirelineMaxDownload" => 819184.9, 
   *        "wirelineMaxUpload" => 437664.8,
   *        "wirelineAvgDownload" => 246315.8,
   *        "wirelineAvgUpload" => 162184.2,
   *        "wirelessMaxDownload" => 98971.0,
   *        "wirelessMaxUpload" => 102734.0,
   *        "wirelessAvgDownload" => 35637.5,
   *        "wirelessAvgUpload" => 32652.3,
   *        "wirelineTests" => 140,
   *        "wirelessTests" => 1012)
   * )
   * Failure cases:
   *   Returns an empty array if no data is found for the parameters.
   *   Returns null if the parameters are improperly formatted, or the service is unavailable.
   */
	public function findSpeedTest($latitude, $longitude) {
	  $data = $this->_apiHelper('/api/speedtest/find',
	      array('latitude' => $latitude, 'longitude' => $longitude));
    if ($data == null) return null;
    
    $data = array_key_exists('SpeedTestCounty', $data) ? 
      $data['SpeedTestCounty'] : array();
    return $data;
	}
			
	/**
	 * Method: findCensusBlock
   *
   * Fetches data from the FCC Census Block Conversions API given a
   *   latitude and longitude, as described at
   *   http://reboot.fcc.gov/developer/census-block-conversions-api
   *
   * Returns an associative array in the following format:
   * array (
   *    "Block" => array("FIPS" => "060855116052015"),
   *    "County" => array("FIPS" => "06085", "name" => "Santa Clara"),
   *    "State" => array("FIPS" => "06", "code" => "CA", "name" => "California"),
   * )
   * Failure cases:
   *   Returns an empty array if no data is found for the parameters.
   *   Returns null if the parameters are improperly formatted, or the service is unavailable.
   */
	public function findCensusBlock($latitude, $longitude) {
	  $data = $this->_apiHelper('/api/block/find',
	      array('latitude' => $latitude, 'longitude' => $longitude));
    if ($data == null) return null;
    
    $data = array_intersect_key(
      $data,
      array_flip(array('Block', 'County', 'State')));
    return $data;
	}
	
	/**
	 * Method: getFRNList
   *
   * Fetches data from the FCC FRN Conversions API given a
   *   state, as described at
   *   http://reboot.fcc.gov/developer/frn-conversions-api
   *
   * Returns an associative array in the following format,
   * mapping frns to the companies/organizations they label:
   * array (
   * "0016639023" => array("frn" => "0016639023", 
   *       "companyName" => "WillitsOnline LLC", "holdingCompanyName" => "WillitsOnline LLC", 
   *       "regulationStatus" => "N", "modifyDate" => "2010.07.30 15:23:02"),
   * "0018357756" => array("frn" => "0018357756", 
   *       "companyName" => "WideVoice Communications,  Inc.", "holdingCompanyName" => "WideVoice Communications,  Inc.", 
   *       "regulationStatus" => "N", "modifyDate" => "2010.07.30 15:23:02"),
   * )
   * Failure cases:
   *   Returns an empty array if no data is found for the parameters.
   *   Returns null if the parameters are improperly formatted, or the service is unavailable.
   */
	public function getFRNList($stateCode, $multiStates = null) {
	  $params = array('stateCode' => $stateCode);
	  if ($multiStates !== null) $params['multiStates'] = $multiStates;
	  $data = $this->_apiHelper('/api/frn/getList', $params);
    if ($data == null) return null;
    
    if (is_array($data) && array_key_exists('Frns', $data)) {
      $data = array_key_exists('Frns', $data) ? 
        $data['Frns']['Frn'] : array();
      
      $results = array();
      foreach ($data as $item) {
        $results[$item['frn']] = $item;
      }
      return $results;
    } else {
      return array();
    }
	}
	
	/**
	 * Method: getFRNInfo
   *
   * Fetches data from the FCC FRN Conversions API given a
   *   state, as described at
   *   http://reboot.fcc.gov/developer/frn-conversions-api
   *
   * Returns an associative array in the following format:
   * array (
   *   "frn" => "0016639023", 
   *   "companyName" => "WillitsOnline LLC", "holdingCompanyName" => "WillitsOnline LLC", 
   *   "regulationStatus" => "N", "modifyDate" => "2010.07.30 15:23:02",
   *   "States" => array ("state" => "CA"), "TechnologyDescs" => null
   * )
   * Failure cases:
   *   Returns an empty array if no data is found for the parameters.
   *   Returns null if the parameters are improperly formatted, or the service is unavailable.
   */
	public function getFRNInfo($frn) {
	  $data = $this->_apiHelper('/api/frn/getInfo', array('frn' => $frn));
    if ($data == null) return null;
    
    if (is_array($data) && array_key_exists('Info', $data)) {
      return array_key_exists('Info', $data) ? $data['Info'] : array();
    }
    return array(); 
	}
	
	/**
	 * Method: getLicenses
   *
   * Fetches data from the FCC License View API's getLicenses method, as described at
   *   http://reboot.fcc.gov/developer/license-view-api
   *
   * Returns up to 1000 results as a linear (numeric) array, where each array value is
   * in the following format:
   * array (
   *   "licName" => "Willits Online LLC",
   *   "frn" => "0016639023",
   *   "callsign" => "WQHQ838",
   *   "categoryDesc" => "Fixed Wireless",
   *   "serviceDesc" => "Common Carrier Fixed Point to Point Microwave",
   *   "statusDesc" => "Active",
   *   "expiredDate" => "10/10/2017",
   *   "licenseID" => "2950933",
   *   "licDetailURL" => "http://wireless2.fcc.gov/UlsApp/UlsSearch/license.jsp?__newWindow=false&licKey=2950933"
   * )
   * Failure cases:
   *   Returns an empty array if no data is found for the parameters.
   *   Returns null if the parameters are improperly formatted, or the service is unavailable.
   */
	public function getLicenses($query) {
	  $data = $this->_apiHelper('/api/license-view/basicSearch/getLicenses', 
	      array('searchValue' => $query, 'pageSize' => 1000));
    if ($data == null) return null;
    
    return (array_key_exists('Licenses', $data)) ? $data['Licenses']['License'] : array();
	}
	
	/**
	 * Method: getLicenseCommonNames
   *
   * Fetches data from the FCC License View API's getCommonNames method, as described at
   *   http://reboot.fcc.gov/developer/license-view-api
   *
   * Returns a linear (numeric) array, where each array value is
   * in the following format:
   * array (
   *   "statDesc" => "Nextel License Holdings 4, Inc.",
   *   "statCount" => "11989"
   * )
   * Failure cases:
   *   Returns an empty array if no data is found for the parameters.
   *   Returns null if the parameters are improperly formatted, or the service is unavailable.
   */
	public function getLicenseCommonNames($commonName = null, $limit = null) {
	  $params = array();
	  if ($commonName !== null) $params['commonName'] = $commonName;
	  if ($limit !== null) $params['limit'] = $limit;
	  $data = $this->_apiHelper('/api/license-view/licenses/getCommonNames', $params);
    if ($data == null) return null;
    
    return (array_key_exists('Stats', $data)) ? $data['Stats']['Stat'] : array();
	}
	
	/**
	 * Method: getLicenseStatuses
   *
   * Fetches data from the FCC License View API's getStatuses method, as described at
   *   http://reboot.fcc.gov/developer/license-view-api
   *
   * Returns a linear (numeric) array, where each array value is
   * in the following format:
   * array (
   *   "statDesc" => "Active",
   *   "statCount" => "43980",
   *   "statPercent" => "76.0"
   * )
   * Failure cases:
   *   Returns an empty array if no data is found for the parameters.
   *   Returns null if the parameters are improperly formatted, or the service is unavailable.
   */
	public function getLicenseStatuses($commonName = null, $limit = null) {
	  $params = array();
	  if ($commonName !== null) $params['commonName'] = $commonName;
	  if ($limit !== null) $params['limit'] = $limit;
	  $data = $this->_apiHelper('/api/license-view/licenses/getStatuses', 
	      $params);
    if ($data == null) return null;
    
    return (array_key_exists('Stats', $data)) ? $data['Stats']['Stat'] : array();
	}
	
	/**
	 * Method: getLicenseCategories
   *
   * Fetches data from the FCC License View API's getCategories method, as described at
   *   http://reboot.fcc.gov/developer/license-view-api
   *
   * Returns a linear (numeric) array, where each array value is
   * in the following format:
   * array (
   *   "statDesc" => "Land Mobile Radio",
   *   "statCount" => "36739",
   *   "statPercent" => "83.5357"
   * )
   * Failure cases:
   *   Returns an empty array if no data is found for the parameters.
   *   Returns null if the parameters are improperly formatted, or the service is unavailable.
   */
	public function getLicenseCategories($commonName = null, $limit = null) {
	  $params = array();
	  if ($commonName !== null) $params['commonName'] = $commonName;
	  if ($limit !== null) $params['limit'] = $limit;
	  $data = $this->_apiHelper('/api/license-view/licenses/getCategories', 
	      $params);
    if ($data == null) return null;
    
    return (array_key_exists('Stats', $data)) ? $data['Stats']['Stat'] : array();
	}
	
	/**
	 * Method: getLicenseEntities
   *
   * Fetches data from the FCC License View API's getEntities method, as described at
   *   http://reboot.fcc.gov/developer/license-view-api
   *
   * Returns a linear (numeric) array, where each array value is
   * in the following format:
   * array (
   *   "statDesc" => "Individual",
   *   "statCount" => "1309117",
   *   "statPercent" => "64.6044"
   * )
   * Failure cases:
   *   Returns an empty array if no data is found for the parameters.
   *   Returns null if the parameters are improperly formatted, or the service is unavailable.
   */
	public function getLicenseEntities() {
	  $data = $this->_apiHelper('/api/license-view/licenses/getEntities', array());
    if ($data == null) return null;
    
    return (array_key_exists('Stats', $data)) ? $data['Stats']['Stat'] : array();
	}
	
	/**
	 * Method: getLicenseRenewals
   *
   * Fetches data from the FCC License View API's getRenewals method, as described at
   *   http://reboot.fcc.gov/developer/license-view-api
   *
   * Returns a linear (numeric) array, where each array value is
   * in the following format:
   * array (
   *   "statDesc" => "201104",
   *   "statCount" => "24",
   *   "statPercent" => "0.3051"
   * )
   * Failure cases:
   *   Returns an empty array if no data is found for the parameters.
   *   Returns null if the parameters are improperly formatted, or the service is unavailable.
   */
	public function getLicenseRenewals($commonName = null) {
    $params = array();
	  if ($commonName !== null) $params['commonName'] = $commonName;
	  $data = $this->_apiHelper('/api/license-view/licenses/getRenewals', $params);
    if ($data == null) return null;
    
    return (array_key_exists('Stats', $data)) ? $data['Stats']['Stat'] : array();
	}
	
	/**
	 * Method: getLicenseIssued
   *
   * Fetches data from the FCC License View API's getIssued method, as described at
   *   http://reboot.fcc.gov/developer/license-view-api
   *
   * Returns a linear (numeric) array, where each array value is
   * in the following format:
   * array (
   *   "statDesc" => "2001",
   *   "statCount" => "5298",
   *   "statPercent" => "12.1575"
   * )
   * Failure cases:
   *   Returns an empty array if no data is found for the parameters.
   *   Returns null if the parameters are improperly formatted, or the service is unavailable.
   */
	public function getLicenseIssued($commonName = null) {
	  $params = array();
	  if ($commonName !== null) $params['commonName'] = $commonName;
	  $data = $this->_apiHelper('/api/license-view/licenses/getIssued', $params);
    if ($data == null) return null;
    
    return (array_key_exists('Stats', $data)) ? $data['Stats']['Stat'] : array();
	}
	
	
	private function _apiHelper($path, $params) {
	  $params['format'] = 'json';
	  $json = $this->_connection->get($path, $params, array('Accept: application/json'));
    return $json == null ? null : json_decode($json, true);
	}
}
?>