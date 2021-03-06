<?php

/*
	Copyright (c) 2009-2012 F3::Factory/Bong Cosca, All rights reserved.

	This file is part of the Fat-Free Framework (http://fatfree.sf.net).

	THE SOFTWARE AND DOCUMENTATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF
	ANY KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
	IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A PARTICULAR
	PURPOSE.

	Please see the license.txt file for more information.
*/

namespace Web;

//! Geo plugin
class Geo extends \Prefab {

	/**
		Return information about specified Unix time zone
		@return array
		@param $zone string
	**/
	function tzinfo($zone) {
		$ref=new \DateTimeZone($zone);
		$loc=$ref->getLocation();
		$trn=$ref->getTransitions($now=time(),$now);
		return array(
			'offset'=>$ref->
				getOffset(new \DateTime('now',new \DateTimeZone('GMT')))/3600,
			'country'=>$loc['country_code'],
			'latitude'=>$loc['latitude'],
			'longitude'=>$loc['longitude'],
			'dst'=>$trn[0]['isdst']
		);
	}

	/**
		Return geolocation data based on specified/auto-detected IP address
		@return array|FALSE
		@param $ip string
	**/
	function location($ip=NULL) {
		$web=\Web::instance();
		if (!$ip)
			$ip=\Base::instance()->get('IP');
		$public=filter_var($ip,FILTER_VALIDATE_IP,
			FILTER_FLAG_IPV4|FILTER_FLAG_IPV6|
			FILTER_FLAG_NO_RES_RANGE|FILTER_FLAG_NO_PRIV_RANGE);
		if (function_exists('geoip_db_avail') &&
			geoip_db_avail(GEOIP_CITY_EDITION_REV1) &&
			$out=@geoip_record_by_name($ip)) {
			$out['request']=$ip;
			$out['region_code']=$out['region'];
			$out['region_name']=geoip_region_name_by_code(
				$out['country_code'],$out['region']);
			unset($out['country_code3'],$out['region'],$out['postal_code']);
			return $out;
		}
		if (($req=$web->request('http://www.geoplugin.net/json.gp'.
			($public?('?ip='.$ip):''))) &&
			$data=@json_decode($req['body'],TRUE)) {
			$out=array();
			foreach ($data as $key=>$val)
				if (!strpos($key,'currency') && $key!=='geoplugin_status'
					&& $key!=='geoplugin_region')
					$out[strtolower(preg_replace('/[[:upper:]]/','_\0',
						substr($key, 10)))]=$val;
			return $out;
		}
		return FALSE;
	}

}
