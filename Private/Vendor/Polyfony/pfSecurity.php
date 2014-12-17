<?php
/**
 * PHP Version 5
 * @package Polyfony
 * @link https://github.com/SIB-FRANCE/Polyfony
 * @license http://www.gnu.org/licenses/lgpl.txt GNU General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */

class pfSecurity {
	
	// default is not granted
	protected static $_granted = false;
	protected static $_credentials = array();
	
	public static function secure($module=null,$level=null) {
		
		// if we have a security cookie we authenticate with it
		pfRequest::cookie(pfConfig::get('security','cookie')) ? self::authenticate();
		
		// if we have a post and posted a login, we log in with it
		pfRequest::post(pfConfig::get('security','login')) ? self::login();
		
		// if we have the module
		self::$_granted = $module and self::hasModule($module) ? true : false;
		
		// if we have the bypass level
		self::$_granted = !self::$_granted and $level and self::hasLevel($level) ? true : false;
		
		// and now we check if we have the proper rights
		!self::$_granted ? pfResponse::error403();
		
	}
	
	public static function authenticate() {
		
	}
	
	public static function login() {

	}
	
	public static function getPassword($string) {
		
		// get a signature using (the provided string + salt)
		return(hash(pfConfig::get('security','algo'),
			pfConfig::get('security','salt') . $string . pfConfig::get('security','salt')
			)
		);
		
	}
	
	public static function getSignature($string='') {
	
		// compute a hash with (the provided string + salt + user agent + remote ip)
		return(hash(pfConfig::get('security','algo'), 
			pfRequest::server('USER_AGENT') . pfRequest::server('REMOTE_ADDR').
			pfConfig::get('security','salt') . $string
		));
		
	}
	
	public static function hasLevel($level=null) {
	
		// if we have said level
		return(self::get('level',100) <= $level ? true : false);
		
	}
	
	public static function hasModule($module=null) {
		
		// if module is in our credentials
		return(in_array($module,self::get('modules',array())) ?: false);
		
	}
	
	public static function get($credential,$default=null) {
		
		// return said credential or default
		return(isset(self::$_credentials[$credential]) ? self::$_credentials[$credential] : $default);
		
	}
	
}	

?>