<?php

use Core\JwtAuth;
use Core\Session;
use Core\Utility;
use Core\Database;
use Core\Request;
use Core\Response;
use Core\Route;
use Core\Setting;
use Dotenv\Dotenv;
use Core\TableField;

if(file_exists(Utility::parentPath() . 'vendor/autoload.php'))
{
    require Utility::parentPath() . 'vendor/autoload.php';
}

require Utility::parentPath() . 'core/helpers/themes.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '//../');
$dotenv->safeLoad();

function app($key, $default = null)
{
    $config = config('app');
    return isset($config[$key]) ? $config[$key] : $default;
}

function env($key, $default = null)
{
    return isset($_ENV[$key]) ? $_ENV[$key] : (isset($_SERVER[$key]) ? $_SERVER[$key] : $default);
}

function config($key)
{
    $parent_path = Utility::parentPath();
    
    $file = $parent_path . 'config/' . $key .'.php';

    if(file_exists($file))
    {
        return require $file;
    }

    return [];
}

function conn(){
    $database = config('database');
    $type = $database['driver'];
    if($type=='PDO')
    {
        // Connect using UNIX sockets
        if($database['socket'])
        {
            $dsn = sprintf(
                'mysql:dbname=%s;unix_socket=%s',
                $database['dbname'],
                $database['socket']
            );
        }
        else
        {
            $dsn = sprintf(
                'mysql:dbname=%s;host=%s',
                $database['dbname'],
                $database['host']
            );
        }

        // Connect to the database.
        $conn = new PDO($dsn, $database['username'], $database['password']);

        return $conn;
    }
    else
    {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            return new mysqli(
                $database['host'],
                $database['user'],
                $database['password'],
                $database['name'],
                $database['port'],
                $database['socket']
            );
        } catch (\mysqli_sql_exception $e) {
            echo $e->getMessage();
            die();
        }
    }

}

function startWith($str, $compare)
{
    return substr($str, 0, strlen($compare)) === $compare;
}

function routeTo($path = false, $param = [])
{
    $path = $path == '/' ? '' : $path;
    $pretty = true;
    $base_url = base_url();
    if($param)
    {
        $param = http_build_query($param);
        $param = $pretty ? '?'.$param : '&'.$param;
    }
    else
    {
        $param = '';
    }
    if($pretty)
    {
        return $base_url.'/'.$path.$param;
    }
    return $base_url.'/index.php?r='.$path.$param;
}

function base_url()
{
    return url(); // config('base_url');
}

function url(){
    return app('url');
}

function auth()
{
    // mode jwt
    if(app('auth') == 'jwt' || Request::$isApiRoute)
        return JwtAuth::get();
    if(app('auth') == 'session')
        return Session::get('auth');
}

function jwtAuth()
{
    if(getBearerToken())
    {
        return JwtAuth::get();
    }

    return null;
}


function stringContains($string,$val){
    if (strpos($string, $val) !== false) {
        return true;
    }

    return false;
}

function arrStringContains($string,$arr){

    $result = [];

    for($i = 0; $i < count($arr);$i++){
       $result[] = stringContains($string,$arr[$i]);
    }

    return in_array(true,$result);
}

function startsWith( $haystack, $needle ) {
    $length = strlen( $needle );
    return substr( $haystack, 0, $length ) === $needle;
}

function endsWith( $haystack, $needle ) {
   $length = strlen( $needle );
   if( !$length ) {
       return true;
   }
   return substr( $haystack, -$length ) === $needle;
}

function set_flash_msg($data)
{
    $_SESSION['flash'] = $data;
}

function get_flash_msg($key)
{
    if(isset($_SESSION['flash'][$key]))
    {
        $message = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $message;
    }

    return false;
}

/**
 * Wrapper for easy cURLing
 *
 * @author Viliam Kopecký
 *
 * @param string HTTP method (GET|POST|PUT|DELETE)
 * @param string URI
 * @param mixed content for POST and PUT methods
 * @param array headers
 * @param array curl options
 * @return array of 'headers', 'content', 'error'
 */
function simple_curl($uri, $method='GET', $data=null, $curl_headers=array(), $curl_options=array()) {
	// defaults
	$default_curl_options = array(
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_HEADER => true,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT => 3,
	);
	$default_headers = array();

	// validate input
	$method = strtoupper(trim($method));
	$allowed_methods = array('GET', 'POST', 'PUT', 'DELETE');

	if(!in_array($method, $allowed_methods))
		throw new \Exception("'$method' is not valid cURL HTTP method.");

	// init
	$curl = curl_init($uri);

	// apply default options
	curl_setopt_array($curl, $default_curl_options);

	// apply method specific options
	switch($method) {
		case 'GET':
			break;
		case 'POST':
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
			break;
		case 'PUT':
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
			break;
		case 'DELETE':
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
			break;
	}

	// apply user options
	curl_setopt_array($curl, $curl_options);

	// add headers
	curl_setopt($curl, CURLOPT_HTTPHEADER, array_merge($default_headers, $curl_headers));

	// parse result
	$raw = rtrim(curl_exec($curl));
	$lines = explode("\r\n", $raw);
	$headers = array();
	$content = '';
	$write_content = false;
	if(count($lines) > 3) {
		foreach($lines as $h) {
			if($h == '')
				$write_content = true;
			else {
				if($write_content)
					$content .= $h."\n";
				else
					$headers[] = $h;
			}
		}
	}
	$error = curl_error($curl);

	curl_close($curl);

	// return
	return array(
		'raw' => $raw,
		'headers' => $headers,
		'content' => $content,
		'error' => $error
	);
}

function _ucwords($str)
{
    $str = str_replace('_',' ',$str);
    $str = str_replace('-',' ',$str);

    return ucwords($str);
}

function redirectBack($message = [])
{
    if($message)
    {
        set_flash_msg($message);
        $url = $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : base_url();
        header('location:'.$url);
        die();
    }
}

function asset($file)
{
    $file = str_replace('../', '', $file);
    return url() . '/' .$file;
}

/** 
 * Get header Authorization
 * */
function getAuthorizationHeader(){
    $headers = null;
    if (isset($_SERVER['Authorization'])) {
        $headers = trim($_SERVER["Authorization"]);
    }
    else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
        $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
    } elseif (function_exists('apache_request_headers')) {
        $requestHeaders = apache_request_headers();
        // Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
        $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
        //print_r($requestHeaders);
        if (isset($requestHeaders['Authorization'])) {
            $headers = trim($requestHeaders['Authorization']);
        }
    }
    return $headers;
}

/**
 * get access token from header
 * */
function getBearerToken() {
    $headers = getAuthorizationHeader();
    // HEADER: Get the access token from the header
    if (!empty($headers)) {
        if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
            return $matches[1];
        }
    }
    return null;
}

function view($file, $data = [])
{    
    return Response::view($file, $data);
}

function getModules()
{
    $modules = explode(',', env('APP_MODULES'));
    $modules = array_map(function($m){
        return "modules/$m";
    }, $modules);

    return $modules;
}

function __($string)
{
    // module.string -> module/lang/{localization}/{file}.php [string]
    $data = explode('.', $string);
    if(stringContains($string, '.') && is_array($data))
    {
        $module = $data[0];
        $langFile = $data[1];
        $localization = app('localization');
        $file = Utility::parentPath() . "modules/" . $module . "/config/lang/$localization/$langFile.php";
        if(file_exists($file))
        {
            $lang = require $file;
            return isset($lang[$data[2]]) ? $lang[$data[2]] : $string;
        }
    }

    return $string;
}

function tableFields($tbl = false, $activeModule = false)
{
    $tableFields = [];
    $modules = getModules();

    foreach($modules as $module)
    {
        $file = Utility::parentPath() . $module . "/config/table-fields.php";
        if(file_exists($file))
        {
            $module = $activeModule ? $activeModule : str_replace('modules/','',$module);
            $tables = require $file;
            foreach($tables as $table => $fields)
            {
                $tableFields = array_merge($tableFields, [$table => new TableField($table, $fields, $module)]);
            }
        }
    }

    if($table)
    {
        if(isset($tableFields[$tbl]))
        {
            return $tableFields[$tbl];
        }

        return [];
    }

    return $tableFields;
}

function csrf_field()
{
    return "<input type='hidden' name='_token' value='".$_SESSION['token']."'>";
}

function get_role($user_id)
{
    $db    = new Database();

    $query = "SELECT user_roles.*, roles.name FROM `user_roles` JOIN roles ON roles.id = user_roles.role_id WHERE user_id=$user_id";
    $db->query = $query;
    return $db->exec('single');
}

function get_roles($user_id)
{
    $db    = new Database();

    $query = "SELECT user_roles.*, roles.name FROM `user_roles` JOIN roles ON roles.id = user_roles.role_id WHERE user_id=$user_id";
    $db->query = $query;
    return $db->exec('all');
}

function get_allowed_routes($user_id)
{
    $allowed_routes = Route::allowed_routes($user_id);
    return $allowed_routes;
}

function is_allowed($path, $user_id)
{
    $ret = false;
    $allowed_routes = get_allowed_routes($user_id);
    foreach($allowed_routes as $route)
    {
        $route_path = $route->route_path;
        $opposite = false;
        if(startWith($route_path, "!"))
        {
            $opposite = true;
            $route_path = substr($route_path, 1);
        }

        if(endsWith($route_path, '*'))
        {
            $temp_route_path = str_replace('*','',$route_path);
            if(startWith($path, $temp_route_path))
            {
                $ret = $opposite ? false : true;
                break;
            }
        }
        elseif($path == $route_path)
        {
            $ret = $opposite ? false : true;
            break;
        }
        elseif(startWith($path, 'crud/')) // && isset($_GET['table']))
        {
            $url = "http://test.com/" . $path;
            $query_str = parse_url($url, PHP_URL_QUERY);
            if(!$query_str)
            {
                $url .= '?' . http_build_query($_GET);
                $query_str = parse_url($url, PHP_URL_QUERY);
            }
            
            parse_str($query_str, $query_params);
            if(isset($query_params['table']))
            {
                $fullpath = $path . '?table=' . $query_params['table'];
                $fullpath2 = $path . '?' . http_build_query($query_params);
                if(fnmatch($route_path, $fullpath) || $fullpath == $route_path || $fullpath2 == $route_path)
                {
                    $ret = $opposite ? false : true;
                    break;
                }
            }
        }
    }
    return $ret;
}

function is_item_allowed($items, $user_id)
{
    $ret = false;
    foreach($items as $item)
    {
        $path = parsePath($item['route']);
        if(is_allowed($path, $user_id))
        {
            $ret = true;
            break;
        }
    }
    return $ret;
}

function parsePath($url)
{
    // $url = strtok($url, '?');
    $app_url = app('url');
    return trim(str_replace($app_url, '', $url), '/');
}

$dir = Utility::parentPath() . 'modules';
$folders = scandir($dir);
$activeModules = env('APP_MODULES');
$activeModules = explode(',', $activeModules);

foreach($folders as $folder)
{
    if (!in_array($folder,array(".","..")) && in_array($folder, $activeModules))
    {
        $function_file = Utility::parentPath() . 'modules/'. $folder.'/libraries/functions.php';
        if(!file_exists($function_file)) continue;
        require $function_file;
    }
}

function isJson($string) {
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}


// $testingTraverse = [
//     'hello' => [
//         'world' => 'oke'
//     ],
//     'world' => 'oke'
// ];

// print_r(traverseArray($testingTraverse, 'hello.world', null));

function traverseArray($data, $key, $default)
{
    if(strpos($key, ".") !== false)
    {
        $keys = explode('.', $key);
        $mainKey = $keys[0];
        unset($keys[0]);
        $keys = implode('.', $keys);

        return isset($data[$mainKey]) ? traverseArray($data[$mainKey], $keys, $default) : $default;

    }

    return isset($data[$key]) ? $data[$key] : $default;
}

function getSetting($key = false)
{
    return Setting::get($key);
}

function penyebut($nilai) {
    $nilai = abs($nilai);
    $huruf = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
    $temp = "";
    if ($nilai < 12) {
        $temp = " ". $huruf[$nilai];
    } else if ($nilai <20) {
        $temp = penyebut($nilai - 10). " belas";
    } else if ($nilai < 100) {
        $temp = penyebut($nilai/10)." puluh". penyebut($nilai % 10);
    } else if ($nilai < 200) {
        $temp = " seratus" . penyebut($nilai - 100);
    } else if ($nilai < 1000) {
        $temp = penyebut($nilai/100) . " ratus" . penyebut($nilai % 100);
    } else if ($nilai < 2000) {
        $temp = " seribu" . penyebut($nilai - 1000);
    } else if ($nilai < 1000000) {
        $temp = penyebut($nilai/1000) . " ribu" . penyebut($nilai % 1000);
    } else if ($nilai < 1000000000) {
        $temp = penyebut($nilai/1000000) . " juta" . penyebut($nilai % 1000000);
    } else if ($nilai < 1000000000000) {
        $temp = penyebut($nilai/1000000000) . " milyar" . penyebut(fmod($nilai,1000000000));
    } else if ($nilai < 1000000000000000) {
        $temp = penyebut($nilai/1000000000000) . " trilyun" . penyebut(fmod($nilai,1000000000000));
    }     
    return $temp;
}

function terbilang($nilai) {
    if($nilai<0) {
        $hasil = "minus ". trim(penyebut($nilai));
    } else {
        $hasil = trim(penyebut($nilai));
    }     		
    return $hasil;
}

function getSidebarLogo()
{
    if(getSetting('application_sidebar_logo'))
    {
        return getSetting('application_sidebar_logo');
    }

    return env('APP_SIDEBAR_LOGO', asset('theme/assets/img/avatars/1.png"'));
}

function getFavicon()
{
    if(getSetting('application_favicon'))
    {
        return getSetting('application_favicon');
    }

    return env('APP_FAVICON', asset('theme/assets/images/favicon.ico'));
}

function getLogo()
{
    if(getSetting('application_logo'))
    {
        return getSetting('application_logo');
    }

    return env('APP_LOGO', asset('theme/assets/images/favicon.ico'));
}

function imageToBase64($path)
{
    $type = pathinfo($path, PATHINFO_EXTENSION);
    $data = file_get_contents($path);
    $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);

    return $base64;
}

function curl_get_contents($url)
{
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
  $data = curl_exec($ch);
  curl_close($ch);
  return $data;
}