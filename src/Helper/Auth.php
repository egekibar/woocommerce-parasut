<?php

namespace Plugin\Helper;

use Plugin\Parasut\Authorization;

class Auth {
	public static $config;
	public static function login() {
		self::$config['redirect_uri'] = 'urn:ietf:wg:oauth:2.0:oob';
		return new Authorization(self::$config);
	}
}