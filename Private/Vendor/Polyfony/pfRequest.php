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
 
class pfRequest {
	
	private static $_url;
	private static $_get;
	private static $_post;
	private static $_argv;
	private static $_server;
	private static $_headers;
	private static $_context;
	private static $_method;
	private static $_signature;
	
	public static function init() {
		
		// set proper context
		// depending if we are in command line
		self::$_context = isset($_ARGV) ? 'CLI' : 'HTTP';
		
		// set current URL
		// depending on the context
		self::$_url = self::$_context == 'CLI' ? $_ARGV[2] : $_SERVER['REQUEST_URI'];
		
		// set the request method
		// depending if superglobal post exists
		self::$_method = isset($_POST) ? 'get' : 'post';
		
		// set the request signature
		// with post, if any
		self::$_signature = self::isPost() ? sha1(self::$_url.json_encode($_POST)) : sha1(self::$_url);
		
		// set the headers
		// if using a FPM fix
		function_exists('getallheaders') ? self::$_headers = getallheaders() : self::getAllHeaders();
		
		// set globals
		self::$_get		= isset($_GET)?: array();
		self::$_post	= isset($_POST)?: array();
		self::$_server	= isset($_SERVER)?: array();
		self::$_argv	= isset($_ARGV)?: array();
		unset($_GET, $_POST, $_SERVER);

	}
	
	public static function getContext() {
		
		// return current context
		return(self::$_context);	
		
	}
	
	public static function getUrl() {
		
		// get current url
		return(self::$_url ?: '/');
		
	}
	
	public static function getSignature() {
		
		// return current signature
		return(self::$_signature);
		
	}
	
	private static function getAllHeaders() {
	
		// for each $_server key
		foreach(self::$_server as $name => $value) { 
			// if it's a header
			if(substr($name, 0, 5) == 'HTTP_') { 
				// clean it
				self::$_headers[str_replace(' ','-',ucwords(strtolower(str_replace('_',' ',substr($name,5)))))] = $value; 
			} 
		} 
		
	}
	
	public static function getHeaders($key=null,$default=null) {
		
	}

	
	/**
	 * Get a single GET variable.
	 *
	 * @access public
	 * @param  string $variable The variable we wish to return.
	 * @param  mixed  $default  If the variable is not found, this is returned.
	 * @return mixed
	 * @static
	 */
	public static function get($variable, $default = null) {
		return isset(self::$_get[$variable])
			? self::$_get[$variable]
			: $default;
	}

	/**
	 * Get a single POST variable.
	 *
	 * @access public
	 * @param  string $variable The variable we wish to return.
	 * @param  mixed  $default  If the variable is not found, this is returned.
	 * @return mixed
	 * @static
	 */
	public static function post($variable, $default = null) {
		return isset(self::$_post[$variable])
			? self::$_post[$variable]
			: $default;
	}

	/**
	 * Get a single SERVER variable.
	 *
	 * @access public
	 * @param  string $variable The variable we wish to return.
	 * @param  mixed  $default  If the variable is not found, this is returned.
	 * @return mixed
	 * @static
	 */
	public static function server($variable, $default = null) {
		return isset(self::$_server[$variable])
			? self::$_server[$variable]
			: $default;
	}

	/**
	 * Check whether the users request was a standard request, or via Ajax.
	 *
	 * @access public
	 * @return boolean
	 * @static
	 */
	public static function isAjax() {
		return isset(self::$_server['HTTP_X_REQUESTED_WITH'])
			&& strtolower(self::$_server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
	}

	/**
	 * Check if the request is a POST.
	 *
	 * @access public
	 * @return boolean
	 * @static
	 */
	public static function isPost() {
		
		// if method is post return true
		return(self::$_method == 'post' ? true : false);
		
	}	

	
}

?>