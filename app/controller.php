<?php
class controller {

	//instantiate class
	function __construct() {
		$f3 = Base::instance();

		$main_path = dirname(__DIR__);
		$f3->set('main_path', $main_path);

		$f3->set('LOCALES', $main_path.'/dict/');
		$f3->set('FALLBACK', 'en');

		$f3->set('siteurl', $this->siteURL());

		$f3->set('DB', new DB\SQL('sqlite:'.$main_path.'/data/database.db'));

		//check if DB is empty and create structure
		$results = $f3->get('DB')->exec("SELECT name FROM sqlite_schema WHERE type=?", 'table');

		if (empty($results)) {
			$db = $f3->get('DB');
			$db->exec("CREATE TABLE link_list (
			    id       INTEGER PRIMARY KEY AUTOINCREMENT,
			    name     TEXT,
			    link    TEXT,
			    tags    TEXT,
			    list     TEXT,
			    status   TEXT,
			    user_ins TEXT,
			    time_ins TEXT,
			    user_upd TEXT,
			    time_upd TEXT
			)");

			$db->exec("CREATE TABLE user (
			    id       INTEGER PRIMARY KEY AUTOINCREMENT,
			    user_id  TEXT    UNIQUE,
			    bearer	 TEXT    NOT NULL,
			    password TEXT
			)");

			$db->exec("CREATE TABLE user_session (
			    id           INTEGER PRIMARY KEY AUTOINCREMENT,
			    user_id      TEXT    NOT NULL,
			    token        TEXT    NOT NULL,
			    token_expire         NOT NULL
			)");

			$db->exec("INSERT INTO user (user_id,bearer,password) VALUES(?,?)", array('superadmin', '56JgXXF77b6HlJKXplIjuK8KOYONTFPu', 'QVVOS09JU0oxSXcrR29QUmx4emVtUT09'));
		}

		$f3->set('formatDate', function ($date, $empty = '', $time = false, $second = false) {
			return $this->formatDate($date, $empty, $time, $second);
		});

		$f3->set('formatNumber', function ($value, $empty = '', $decimal = false, $money = false, $simple = false) {
			return $this->formatNumber($value, $empty, $decimal, $money, $simple);
		});
		
		$f3->set('generateRandom', function ($length, $type) {
			return $this->generateRandomString($length, $type);
		});
	}

	//custom error page
	function error($f3) {
		$log = new Log('error.log');
		$log->write($f3->get('ERROR.code').' - '.$f3->get('ERROR.text'));
		echo Template::instance()->render('service.html');
	}

	function siteURL() {
		return sprintf(
			"%s://%s",
			isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
			$_SERVER['SERVER_NAME']
		);
	}

	function encriptDecript($string, $action = 'e') {
		$secret_key = 'F)ajEx@:=;t;]v?ovg"o89.>p(:j]X(Zs$n8c0}:NHE3ba)d#He#]18Zh{HG#t&';
		$secret_iv = 'k%0uB+apnH=<1msH(!x`8@.=!7sw^:m;hXGx}uUdJ~*_wVSs1_2*~bNW_|_loD;';
		$output = false;

		$encrypt_method = "AES-256-CBC";
		$key = hash('sha256', $secret_key);

		$iv = substr(hash('sha256', $secret_iv), 0, 16);
		if ($action == 'e') {
			$output = base64_encode(openssl_encrypt($string, $encrypt_method, $key, 0, $iv));
		} else if ($action == 'd') {
			$output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
		}

		return $output;
	}

	function generateRandomString($length, $type) {
		if ($type == 'mix') {
			$characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		} elseif ($type == 'letter') {
			$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
		} else {
			$characters = '0123456789';
		}
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}

	function formatNumber($value, $empty = '', $decimal = false, $money = false, $simple = false) {
		if (!isset($value)) {
			return $empty;
		} else {
			if ($decimal) {
				if ($simple) {
					$number = number_format($value, 2, ',', '');
				} else {
					$number = number_format($value, 2, ',', '.');
				}
			} else {
				$number = number_format($value, 0, ',', '.');
			}
		}

		if ($money) {
			$number = $number.' €';
		}

		return $number;
	}

	function formatDate($date, $empty = '', $time = false, $second = false) {
		if ($time && !$second) {
			$format = 'd/m/Y H:i';
		} elseif ($time && $second) {
			$format = 'd/m/Y H:i:s';
		} else {
			$format = 'd/m/Y';
		}

		if (empty($date)) {
			return $empty;
		} else {
			return date($format, strtotime($date));
		}
	}
}