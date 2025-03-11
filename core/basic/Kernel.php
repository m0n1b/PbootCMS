<?php
namespace core\basic;

class Kernel
{
    private static $_url_bindArray;
    public static function run()
    {
        self::_check_auth_sn();
        self::_check_cache();

        $_path_info = self::_get_path_info();
        $_path_info = self::_check_url_bind($_path_info);
        $_path_info = self::_check_route($_path_info);
        $_ctrl = self::_get_ctrl($_path_info);
        $_ctrl_name = self::_get_ctrl_name($_ctrl);
        self::_init_boot();

        self::exec($_ctrl_name);
    }
    private static function _get_path_info()
    {
        if (isset($_SERVER['PATH_INFO']) && !mb_check_encoding($_SERVER['PATH_INFO'], 'UTF-8')) {
            $_SERVER['PATH_INFO'] = mb_convert_encoding($_SERVER['PATH_INFO'], 'utf-8', 'GBK');
        }
        if (isset($_SERVER['REQUEST_URI']) && !mb_check_encoding($_SERVER['REQUEST_URI'], 'UTF-8')) {
            $_SERVER['REQUEST_URI'] = mb_convert_encoding($_SERVER['REQUEST_URI'], 'utf-8', 'GBK');
        }
        if (isset($_SERVER['ORIG_PATH_INFO']) && !mb_check_encoding($_SERVER['ORIG_PATH_INFO'], 'UTF-8')) {
            $_SERVER['ORIG_PATH_INFO'] = mb_convert_encoding($_SERVER['ORIG_PATH_INFO'], 'utf-8', 'GBK');
        }
        $_path_info = '';
        if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO']) {
            $_path_info = $_SERVER['PATH_INFO'];
        } elseif (isset($_SERVER["REDIRECT_URL"]) && $_SERVER["REDIRECT_URL"]) {
            $_path_info = str_replace('/' . basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['REDIRECT_URL']);
            $_path_info = str_replace(SITE_DIR, '', $_path_info);
            $_SERVER['PATH_INFO'] = $_path_info;
        }
        if (!$_path_info) {
            if (isset($_GET['p']) && $_GET['p']) {
                $_path_info = $_GET['p'];
            } elseif (isset($_GET['s']) && $_GET['s']) {
                $_path_info = $_GET['s'];
            }
        }
        if ($_path_info) {
            $pljjaui50a16ce4bc582c9d154bfde2f385deb7 = '{^\/?([\x{4e00}-\x{9fa5}\w\-\/\.' . Config::get('url_allow_char') . ']+?)?$}u';
            if (preg_match($pljjaui50a16ce4bc582c9d154bfde2f385deb7, $_path_info)) {
                $_path_info = preg_replace($pljjaui50a16ce4bc582c9d154bfde2f385deb7, '$1', $_path_info);
            } else {
                $vyae93130f40d6900ea3b3380dd21598fb1 = true;
            }
        }
        if (isset($vyae93130f40d6900ea3b3380dd21598fb1) && $vyae93130f40d6900ea3b3380dd21598fb1) {
            http_response_code(404);
            $vaoaiv872c6075636c3185446c60afe7927f6d = ROOT_PATH . '/defend.html';
            if (file_exists($vaoaiv872c6075636c3185446c60afe7927f6d)) {
                require $vaoaiv872c6075636c3185446c60afe7927f6d;
                exit();
            } else {
                error('您访问路径含有非法字符，防注入系统提醒您请勿尝试非法操作！');
            }
        }
        define('P', $_path_info);
        return $_path_info;
    }
    private static function _check_url_bind($_path_info)
    {
        $_url_bind = '';
        if (!!$_app_domain_bind = Config::get('app_domain_bind')) {
            $host = get_http_host();
            if (isset($_app_domain_bind[$host])) {
                $_url_bind = $_app_domain_bind[$host];
            }
        }
        if (defined('URL_BIND')) {
            if ($_url_bind && URL_BIND != $_url_bind) {
                error('系统配置的模块域名绑定与入口文件绑定冲突，请核对！');
            } else {
                $_url_bind = URL_BIND;
            }
        }
        return $_url_bind ? trim_slash($_url_bind) . '/' . $_path_info : $_path_info;
    }
    private static function _check_route($_path_info)
    {
        if (!!$_url_route = Config::get('url_route')) {
            if (!$_path_info && isset($_url_route['/'])) {
                return $_url_route['/'];
            }
            foreach ($_url_route as $_uri => $_ctrl) {
                $_uri = trim_slash($_uri);
                $_regx = "{" . $_uri . "}i";
                if (preg_match($_regx, $_path_info)) {
                    $_ctrl = trim_slash($_ctrl);
                    $_path_info = preg_replace($_regx, $_ctrl, $_path_info);
                    break;
                }
            }
        }
        return $_path_info;
    }
    private static function _get_ctrl($_path_info)
    {
        $_public_app = Config::get('public_app', true);
        if ($_path_info) {
            $_path_info = trim_slash($_path_info);
            $_url_bind_array = explode('/', $_path_info);
            self::$_url_bindArray = $_url_bind_array;
            $_url_bind_count = count($_url_bind_array);
            if ($_url_bind_count >= 3) {
                $_ctrl['m'] = $_url_bind_array[0];
                $_ctrl['c'] = $_url_bind_array[1];
                $_ctrl['f'] = $_url_bind_array[2];
            } elseif ($_url_bind_count == 2) {
                $_ctrl['m'] = $_url_bind_array[0];
                $_ctrl['c'] = $_url_bind_array[1];
            } elseif ($_url_bind_count == 1) {
                $_ctrl['m'] = $_url_bind_array[0];
            }
        }
        if (!isset($_ctrl['m'])) {
            $_ctrl['m'] = $_public_app[0];
        }
        if (!isset($_ctrl['c'])) {
            $_ctrl['c'] = 'Index';
        }
        if (!isset($_ctrl['f'])) {
            $_ctrl['f'] = 'index';
        }
        if (!in_array(strtolower($_ctrl['m']), $_public_app)) {
            error('您访问的模块' . $_ctrl['m'] . '未开放,请核对后重试！');
        }
        return $_ctrl;
    }
    private static function _get_ctrl_name($_ctrl)
    {
        define('M', strtolower($_ctrl['m']));
        define('APP_MODEL_PATH', APP_PATH . '/' . M . '/model');
        define('APP_CONTROLLER_PATH', APP_PATH . '/' . M . '/controller');
        if (($_tpl_dir = Config::get('tpl_dir')) && array_key_exists(M, $_tpl_dir)) {
            if (strpos($_tpl_dir[M], ROOT_PATH) === false) {
                define('APP_VIEW_PATH', ROOT_PATH . $_tpl_dir[M]);
            } else {
                define('APP_VIEW_PATH', $_tpl_dir[M]);
            }
        } else {
            define('APP_VIEW_PATH', APP_PATH . '/' . M . '/view');
        }
        if (strpos($_ctrl['c'], '.') > 0) {
            $_ctrl_name = str_replace('.', '/', $_ctrl['c']);
            $controller = ucfirst(basename($_ctrl_name));
            $_ctrl_name = dirname($_ctrl_name) . '/' . $controller;
        } else {
            $controller = ucfirst($_ctrl['c']);
            $_ctrl_name = $controller;
        }
        $_ctrl_file = APP_CONTROLLER_PATH . '/' . $_ctrl_name . 'Controller.php';
        $wltl_yiijuizzau8157f84e7d7d1c7b2105515e8681b822 = array('List', 'Content', 'About');
        $lvxrwj998a9adf1e19f1078357d314822985c3 = 0;
        if (M == 'home' && (!file_exists($_ctrl_file) || in_array($controller, $wltl_yiijuizzau8157f84e7d7d1c7b2105515e8681b822))) {
            $controller = 'Index';
            $_ctrl_name = 'Index';
            define('F', $_ctrl['c']);
            $lvxrwj998a9adf1e19f1078357d314822985c3 = -1;
        } elseif (M == 'home' && in_array($controller, Config::get('second_rvar'))) {
            define('F', 'index');
            define('RVAR', $_ctrl['f']);
        } else {
            define('F', $_ctrl['f']);
        }
        define('C', $controller);
        if (isset($_SERVER["REQUEST_URI"])) {
            define('URL', $_SERVER["REQUEST_URI"]);
        } else {
            define('URL', $_SERVER["ORIG_PATH_INFO"] . '?' . $_SERVER["QUERY_STRING"]);
        }
        $_url_bind_count = count(self::$_url_bindArray);
        for ($i = 3 + $lvxrwj998a9adf1e19f1078357d314822985c3; $i < $_url_bind_count; $i = $i + 2) {
            if (isset(self::$_url_bindArray[$i + 1])) {
                $_GET[self::$_url_bindArray[$i]] = self::$_url_bindArray[$i + 1];
            } else {
                $_GET[self::$_url_bindArray[$i]] = null;
            }
        }
        return $_ctrl_name;
    }
    private static function _init_boot()
    {
        Config::get('debug') ? Check::checkAppFile() : '';
        if (M == 'api') {
            if (!!$_request_sid = request('sid')) {
                session_id($_request_sid);
                session_start();
            }
            header("Access-Control-Allow-Origin: *");
        } else {
            Check::checkBs();
            Check::checkOs();
        }
        if (file_exists(APP_PATH . '/common/function.php')) {
            require APP_PATH . '/common/function.php';
        }
        $_cfg_file = APP_PATH . '/' . M . '/config/config.php';
        if (file_exists($_cfg_file)) {
            Config::assign($_cfg_file);
        }
        $_func_file = APP_PATH . '/' . M . '/function/function.php';
        if (file_exists($_func_file)) {
            require $_func_file;
        }
        if (file_exists(APP_PATH . '/common/' . ucfirst(M) . 'Controller.php')) {
            $_ctrl_class = '\\app\\common\\' . ucfirst(M) . 'Controller';
            $_ctrl_obj = new $_ctrl_class();
        }
    }
    private static function exec($controllerPath)
    {
        $_ctrl_file = $controllerPath . 'Controller.php';
        $_ctrl_file = APP_CONTROLLER_PATH . '/' . $_ctrl_file;
        $_ctrl_class = '\\app\\' . M . '\\controller\\' . str_replace('/', '\\', $controllerPath) . 'Controller';
        $_user_func = F;

        if (!file_exists($_ctrl_file)) {
            http_response_code(404);
            $_404 = ROOT_PATH . '/404.html';
            if (file_exists($_404)) {
                require $_404;
                exit();
            } else {
                error('对不起，您访问的页面类文件不存在，请核对后再试！');
            }
        }
        if (!class_exists($_ctrl_class)) {
            error('类文件中不存在访问的类名！' . $_ctrl_class);
        }
        $controller = new $_ctrl_class();

        if (method_exists($_ctrl_class, $_user_func)) {
            if (strtolower($_ctrl_class) != strtolower($_user_func)) {
                $resp = $controller->$_user_func();
            } else {
                $resp = $controller;
            }
        } else {
            if (method_exists($_ctrl_class, '_empty')) {
                $resp = $controller->_empty();

            } else {
                error('不存在您调用的类或方法' . $_user_func . '，可能正在开发中，请耐心等待！');
            }
        }
        if ($resp !== null) {
            print_r($resp);
            exit();
        }
    }
    private static function _check_cache()
    {
        if (!Config::get('tpl_html_cache') || URL_BIND == 'api' || get('nocache', 'int') == 1) {
            return;
        }
        $zb_ylyna328f80565fce50d4d921a0a9f362f0a0 = RUN_PATH . '/config/' . md5('area') . '.php';
        if (!file_exists($zb_ylyna328f80565fce50d4d921a0a9f362f0a0)) {
            return;
        } else {
            Config::assign($zb_ylyna328f80565fce50d4d921a0a9f362f0a0);
        }
        $_cfg_lgs = Config::get('lgs');
        if (count($_cfg_lgs) > 1) {
            $_host = get_http_host();
            foreach ($_cfg_lgs as $_ctrl) {
                if ($_ctrl['domain'] == $_host) {
                    cookie('lg', $_ctrl['acode']);
                }
            }
        }
        if (!isset($_COOKIE['lg'])) {
            $vaolrzj7ca22c6bc98d271b4c0c1d53799e8e8c = current(Config::get('lgs'));
            cookie('lg', $vaolrzj7ca22c6bc98d271b4c0c1d53799e8e8c['acode']);
        }
        $yiioyb_ylyna8256502dd6eb15e11781dfc48a4464e3 = RUN_PATH . '/config/' . md5('config') . '.php';
        if (!Config::assign($yiioyb_ylyna8256502dd6eb15e11781dfc48a4464e3)) {
            return;
        }
        if (Config::get('open_wap') && (is_mobile() || Config::get('wap_domain') == get_http_host())) {
            $wap_flag = 'wap';
        } else {
            $wap_flag = '';
        }
        $ylyna_oyza5cab4a25d6e10b15c6dfe4b60cfa365b = RUN_PATH . '/cache/' . md5(get_http_url() . $_SERVER["REQUEST_URI"] . cookie('lg') . $wap_flag) . '.html';
        if (file_exists($ylyna_oyza5cab4a25d6e10b15c6dfe4b60cfa365b) && time() - filemtime($ylyna_oyza5cab4a25d6e10b15c6dfe4b60cfa365b) < Config::get('tpl_html_cache_time')) {
            ob_start();
            include $ylyna_oyza5cab4a25d6e10b15c6dfe4b60cfa365b;
            $_cache_html = ob_get_contents();
            ob_end_clean();
            if (Config::get('gzip') && !headers_sent() && extension_loaded("zlib") && strstr($_SERVER["HTTP_ACCEPT_ENCODING"], "gzip")) {
                $_cache_html = gzencode($_cache_html, 6);
                header("Content-Encoding: gzip");
                header("Vary: Accept-Encoding");
                header("Content-Length: " . strlen($_cache_html));
            }
            echo $_cache_html;
            exit();
        }
    }
    private static function _check_auth_sn()
    {
        $_server_addr = isset($_SERVER['LOCAL_ADDR']) ? $_SERVER['LOCAL_ADDR'] : $_SERVER['SERVER_ADDR'];
        if ($_server_addr == '::1') {
            $_server_addr = '127.0.0.1';
        }
        $_license = 0;
        if (!!$_cfg_sn = Config::get('sn', true)) {
            $_cfg_sn_user = Config::get('sn_user');
            $_uri_user = strtoupper(substr(md5(substr(sha1($_cfg_sn_user), 0, 20)), 10, 10));
            $_license = $_license ?: (in_array($_uri_user, $_cfg_sn) ? 3 : 0);
            $_uri_host = strtoupper(substr(md5(substr(sha1($_server_addr), 0, 15)), 10, 10));
            $_license = $_license ?: (in_array($_uri_host, $_cfg_sn) ? 2 : 0);
            $_host = $_SERVER['HTTP_HOST'];
            $_uri_domain = strtoupper(substr(md5(substr(sha1($_host), 0, 10)), 10, 10));
            $_license = $_license ?: (in_array($_uri_domain, $_cfg_sn) ? 1 : 0);
        }
        define('LICENSE', $_license);
        if (!LICENSE && (filter_var(get_http_host(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || get_http_host() == 'localhost')) {
            return;
        }
        if (!$_license && (defined('URL_BIND') && URL_BIND != 'admin')) {
            $_sn_file = ROOT_PATH . '/sn.html';
            if (file_exists($_sn_file)) {
                require $_sn_file;
                exit();
            } else {
                error('未匹配到本域名(' . $_host . ')有效授权码，请到PbootCMS官网免费获取，并登录系统后台填写到"全局配置>>配置参数"中。');
            }
        }
    }
}
