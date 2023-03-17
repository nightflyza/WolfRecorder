<?php

/**
 * Primary Y.A.L.F core class that implements core functionality
 */
class YALFCore {

    /**
     * Contains raw YALF primary config as key=>value
     *
     * @var array
     */
    protected $config = array();

    /**
     * Contains names of libs to load as path=>layer
     *
     * @var array
     */
    protected $loadLibs = array();

    /**
     * Name of module which will be used as main route
     *
     * @var string
     */
    protected $indexModule = 'index';

    /**
     * Current skin name
     *
     * @var string
     */
    protected $skin = 'paper';

    /**
     * Default language name
     *
     * @var string
     */
    protected $language = 'english';

    /**
     * Application renderer type. Can be WEB/CLI at this moment
     *
     * @var string
     */
    protected $renderer = 'WEB';

    /**
     * Contains page title here
     *
     * @var string
     */
    protected $pageTitle = '';

    /**
     * Is global menu rendering enabled flag
     *
     * @var bool
     */
    protected $globalMenuEnabled = false;

    /**
     * Contains modules preloaded from general modules directory
     *
     * @var array
     */
    protected $modules = array();

    /**
     * Contains all rights injected with startup modules initialization
     *
     * @var array
     */
    protected $rights_database = array();

    /**
     * Is now some user logged in flag
     *
     * @var bool
     */
    protected $loggedIn = false;

    /**
     * Have current user root rights?
     *
     * @var bool
     */
    protected $root = false;

    /**
     * This array contain data from user's profile
     *
     * @var array
     */
    protected $user = array();

    /**
     * Contains current user rights
     *
     * @var array
     */
    protected $rights = array();

    /**
     * Some mystic output buffer. Used in i18n, users auth etc.
     *
     * @var array
     */
    protected $results = array();

    /**
     * Name of default auth cookie. May be configurable in future.
     *
     * @var string
     */
    protected $cookie_user = 'yalf_user';

    /**
     * Name of default user defined locale. May be configurable in future.
     *
     * @var string
     */
    protected $cookie_locale = 'yalf_lang';

    /**
     * System athorization enable flag
     *
     * @var bool
     */
    protected $authEnabled = false;

    /**
     * System logging engine
     *
     * @var string
     */
    protected $logType = 'fake';

    /**
     * Database logs table
     *
     * @var string
     */
    protected $logTable = 'weblogs';

    /**
     * Default system log file path. May be configurable in future.
     *
     * @var string
     */
    protected $logFilePath = 'content/logs/yalflog.log';

    /**
     * Is live-locale switching allowed flag
     *
     * @var bool
     */
    protected $langSwitchAllowed = false;

    /**
     * Contains names of pre-loaded modules as modulename=>title
     *
     * @var array
     */
    protected $loadableModules = array();

    /**
     * Contains list of modules which not require any authorization (public modules)
     *
     * @var array
     */
    protected $noAuthModules = array();

    /**
     * Some paths, routes etc
     */
    const YALF_CONF_PATH = 'config/yalf.ini';
    const YALF_MENU_PATH = 'config/globalmenu.ini';
    const LIBS_PATH = 'api/libs/';
    const LANG_PATH = 'languages/';
    const MODULE_CODE_NAME = 'index.php';
    const MODULE_DEFINITION = 'module.php';
    const ROUTE_MODULE_LOAD = 'module';
    const SKINS_PATH = 'skins/';
    const MENU_ICONS_PATH = 'skins/menuicons/';
    const DEFAULT_ICON = 'defaulticon.png';
    const SKIN_TEMPLATE_NAME = 'template.html';

    /**
     * Creates new system core instance
     */
    public function __construct() {
        $this->loadConfig();
        $this->performUserAuth();
        $this->initializeUser();
        $this->initializeModules();
        $this->setOptions();
        $this->switchIndexModule();
    }

    /**
     * Loads framework primary config into protected property for further usage
     * 
     * @return void
     */
    protected function loadConfig() {
        $this->config = parse_ini_file(self::YALF_CONF_PATH);
    }

    /**
     * Checks is module path valid and loadable?
     * 
     * @param string $moduleName
     * 
     * @return bool
     */
    protected function isModuleValid($moduleName) {
        $result = false;
        $moduleName = preg_replace('/\0/s', '', $moduleName);
        if (!empty($moduleName)) {
            //already preloaded from filesystem
            if (isset($this->loadableModules[$moduleName])) {
                //check for module dir
                if (file_exists(MODULES_PATH . $moduleName)) {
                    //check for module codepart
                    if (file_exists(MODULES_PATH . $moduleName . '/' . self::MODULE_CODE_NAME)) {
                        //check for module definition
                        if (file_exists(MODULES_PATH . $moduleName . '/' . self::MODULE_DEFINITION)) {
                            $result = true;
                        }
                    }
                }
            }
        }

        return($result);
    }

    /**
     * Preprocess some options an sets internal props for further usage
     * 
     * @return void
     */
    protected function setOptions() {
        //library layers preloading
        if (!empty($this->config)) {
            foreach ($this->config as $eachOption => $eachValue) {
                if (ispos($eachOption, 'LAYER_')) {
                    if (!empty($eachValue)) {
                        $requirements = explode(',', $eachValue);
                        if (!empty($requirements)) {
                            foreach ($requirements as $io => $eachLib) {
                                $libPath = self::LIBS_PATH . 'api.' . $eachLib . '.php';
                                if (!file_exists($libPath)) {
                                    die('Library ' . $libPath . ' required for loading of feature layer ' . $eachOption . ' is not exists!');
                                } else {
                                    $this->loadLibs[$libPath] = $eachOption;
                                }
                            }
                        }
                    }
                }
            }
        }

        //initial index module setup
        if (isset($this->config['INDEX_MODULE'])) {
            if (!empty($this->config['INDEX_MODULE'])) {
                if ($this->isModuleValid($this->config['INDEX_MODULE'])) {
                    $this->indexModule = $this->config['INDEX_MODULE'];
                } else {
                    die('Module code ' . MODULES_PATH . $this->config['INDEX_MODULE'] . '/' . self::MODULE_CODE_NAME . ' set in INDEX_MODULE is not exists!');
                }
            }
        }

        //template selection
        if (isset($this->config['YALF_SKIN'])) {
            if (!empty($this->config['YALF_SKIN'])) {
                $this->skin = $this->config['YALF_SKIN'];
                if (!file_exists(self::SKINS_PATH . $this->skin . '/' . self::SKIN_TEMPLATE_NAME)) {
                    die('Template code not found ' . self::SKINS_PATH . $this->skin . '/' . self::SKIN_TEMPLATE_NAME . ' set in YALF_SKIN');
                }
            }
        }

        //default locale selection
        if (isset($this->config['YALF_LANG'])) {
            //setting default locale
            if (!empty($this->config['YALF_LANG'])) {
                $this->language = $this->config['YALF_LANG'];
            }
        }

        //locale switching if allowed
        if (isset($this->config['YALF_LANG_SWITCHABLE'])) {
            if ($this->config['YALF_LANG_SWITCHABLE']) {
                //setup of flag
                $this->langSwitchAllowed = true;

                //setting new locale on GET request
                if (isset($_GET['yalfswitchlocale'])) {
                    $rawLocale = $_GET['yalfswitchlocale'];
                    $customLocale = preg_replace('/\0/s', '', $rawLocale);
                    $customLocale = preg_replace("#[^a-z0-9A-Z]#Uis", '', $customLocale);
                    if (!empty($customLocale)) {
                        if (file_exists(self::LANG_PATH . $customLocale)) {
                            $this->language = $customLocale;
                            setcookie($this->cookie_locale, $customLocale, time() + 2592000);
                            $currentUrlCallback = $_SERVER['REQUEST_URI'];
                            $currentUrlCallback = str_replace('&yalfswitchlocale=' . $rawLocale, '', $currentUrlCallback);
                            $currentUrlCallback = str_replace('?yalfswitchlocale=' . $rawLocale, '', $currentUrlCallback);
                            rcms_redirect($currentUrlCallback, true); //back to the same URL witchout switch param
                        }
                    }
                }

                //some custom locale already set
                if (@$_COOKIE[$this->cookie_locale]) {
                    $customLocale = preg_replace('/\0/s', '', $_COOKIE[$this->cookie_locale]);
                    $customLocale = preg_replace("#[^a-z0-9A-Z]#Uis", '', $customLocale);
                    if (!empty($customLocale)) {
                        if (file_exists(self::LANG_PATH . $customLocale)) {
                            $this->language = $customLocale;
                        }
                    }
                }
            }
        }

        //page title setup
        if (isset($this->config['YALF_TITLE'])) {
            if (!empty($this->config['YALF_TITLE'])) {
                $this->pageTitle = $this->config['YALF_TITLE'];
            }
        }

        //global menu rendering flag setup
        if (isset($this->config['YALF_MENU_ENABLED'])) {
            if ($this->config['YALF_MENU_ENABLED']) {
                $this->globalMenuEnabled = true;
            }
        }

        //system auth enabled
        if (isset($this->config['YALF_AUTH_ENABLED'])) {
            if ($this->config['YALF_AUTH_ENABLED']) {
                $this->authEnabled = true;
            }
        }

        //system logging settings
        if (isset($this->config['YALF_LOGGING_TYPE'])) {
            $this->logType = $this->config['YALF_LOGGING_TYPE'];
            if (isset($this->config['YALF_LOG_TABLE'])) {
                $this->logTable = $this->config['YALF_LOG_TABLE'];
            }
        }

        //no auth public modules
        if (isset($this->config['YALF_NO_AUTH_MODULES'])) {
            if (!empty($this->config['YALF_NO_AUTH_MODULES'])) {
                $this->noAuthModules = explode(',', $this->config['YALF_NO_AUTH_MODULES']);
                $this->noAuthModules = array_flip($this->noAuthModules); //use module name as index
            }
        }

        //renderer type detection
        if (isset($this->config['LAYER_CLIRENDER'])) {
            $this->renderer = 'CLI';
        }

        if (isset($this->config['LAYER_WEBRENDER'])) {
            $this->renderer = 'WEB';
        }
    }

    /**
     * Switches index(current) module if its required
     * 
     * @return void
     */
    protected function switchIndexModule() {
        $forceLoginForm = false; //show login form module instead any of called
//user is not authorized now and auth engine enabled
        if (!$this->loggedIn) {
            $forceLoginForm = true;
        }


        //is module public and excluded from forced auth?
        if ($forceLoginForm) {
            if (isset($_GET[self::ROUTE_MODULE_LOAD])) {
                $moduleName = $_GET[self::ROUTE_MODULE_LOAD];
                $moduleName = preg_replace('/\0/s', '', $moduleName);
                if (isset($this->noAuthModules[$moduleName])) {
                    $forceLoginForm = false;
                }
            } else {
                if (isset($this->noAuthModules[$this->indexModule])) {
                    $forceLoginForm = false;
                }
            }
        }

        if (!$forceLoginForm) {
            //switching module if set some required route
            if (isset($_GET[self::ROUTE_MODULE_LOAD])) {
                $moduleName = $_GET[self::ROUTE_MODULE_LOAD];
                $moduleName = preg_replace('/\0/s', '', $moduleName);
                if ($this->isModuleValid($moduleName)) {
                    $this->indexModule = $moduleName;
                } else {
                    die('No module ' . $moduleName . ' exists');
                }
            }
        } else {
            //force login form switch
            if ($this->isModuleValid('loginform')) {
                $this->indexModule = 'loginform';
            } else {
                die('No module loginform exists');
            }
        }
    }

    /**
     * Loads some module by its name. 
     * Not used at this moment, due fails on global objects like $ubillingConfig, $system, etc
     * 
     * @return void
     */
    public function loadCurrentModule() {
        require_once ($this->getIndexModulePath());
    }

    /**
     * Preloads all general modules from general modules directory
     * 
     * @return void
     */
    protected function initializeModules() {
        $disabledModules = array();
        //some modules may be disabled
        if (isset($this->config['YALF_DISABLED_MODULES'])) {
            if (!empty($this->config['YALF_DISABLED_MODULES'])) {
                $disabledModules = explode(',', $this->config['YALF_DISABLED_MODULES']);
                $disabledModules = array_flip($disabledModules);
            }
        }

        $allModules = scandir(MODULES_PATH);
        foreach ($allModules as $module) {
            if (!isset($disabledModules[$module])) {
                if (file_exists(MODULES_PATH . $module . '/' . self::MODULE_DEFINITION)) {
                    include_once(MODULES_PATH . $module . '/' . self::MODULE_DEFINITION);
                }
            }
        }

        // Register modules rights in main database
        foreach ($this->modules as $type => $modules) {
            foreach ($modules as $module => $moduledata) {
                //rights register
                foreach ($moduledata['rights'] as $right => $desc) {
                    $this->rights_database[$right] = $desc;
                }

                //registering module as loadable
                $this->loadableModules[$module] = $moduledata['title'];
            }
        }
    }

    /**
     * Registers module as preloaded
     * 
     * @param string $module
     * @param string $type
     * @param string $title
     * @param string $copyright
     * @param array $rights
     * 
     * @return void
     */
    protected function registerModule($module, $type, $title, $copyright = '', $rights = array()) {
        $this->modules[$type][$module]['title'] = $title;
        $this->modules[$type][$module]['copyright'] = $copyright;
        $this->modules[$type][$module]['rights'] = $rights;
    }

    /**
     * Returns array of libs required for loading layers
     * 
     * @return array
     */
    public function getLibs() {
        return($this->loadLibs);
    }

    /**
     * Returns state of flag that allows live locale switching by clients
     * 
     * @return bool
     */
    public function isLocaleSwitchable() {
        return($this->langSwitchAllowed);
    }

    /**
     * Returns full path of index module aka main route
     * 
     * @return string
     */
    public function getIndexModulePath() {
        return(MODULES_PATH . $this->indexModule . '/' . self::MODULE_CODE_NAME);
    }

    /**
     * Returns current module name
     * 
     * @return string
     */
    public function getCurrentModuleName() {
        return($this->indexModule);
    }

    /**
     * Returns current locale language full path
     * 
     * @return string
     */
    public function getLangPath() {
        return(self::LANG_PATH . $this->language . '/');
    }

    /**
     * Returns current locale ID as two-letters code
     * 
     * @return string
     */
    public function getCurLang() {
        return(substr($this->language, 0, '2'));
    }

    /**
     * Returns current locale name
     * 
     * @return string
     */
    public function getCurLangName() {
        return($this->language);
    }

    /**
     * Returns current skin path
     * 
     * @return string
     */
    public function getSkinPath() {
        return(self::SKINS_PATH . $this->skin . '/');
    }

    /**
     * Returns current application renderer type
     * 
     * @return string
     */
    public function getRenderer() {
        return($this->renderer);
    }

    /**
     * Returns current application page title
     * 
     * @return string
     */
    public function getPageTitle() {
        return($this->pageTitle);
    }

    /**
     * Sets current page title text
     * 
     * @param string $title
     * 
     * @return void
     */
    public function setPageTitle($title = '') {
        $this->pageTitle = $title;
    }

    /**
     * Returns ISP logo image code
     * 
     * @return string
     */
    public function renderLogo() {
        $result = '';
        if (isset($this->config['YALF_LOGO'])) {
            if ((!empty($this->config['YALF_APP'])) AND ( !empty($this->config['YALF_URL'])) AND ( (!empty($this->config['YALF_LOGO'])))) {
                $rawUrl = strtolower($this->config['YALF_URL']);
                if (stripos($rawUrl, 'http') === false) {
                    $rawUrl = 'http://' . $rawUrl;
                } else {
                    $rawUrl = $rawUrl;
                }
                $result = '<a href="' . $rawUrl . '" target="_BLANK"><img src="' . $this->config['YALF_LOGO'] . '" title="' . __($this->config['YALF_APP']) . '"></a>';
            }
        }
        return ($result);
    }

    /**
     * Renders application menu
     * 
     * @return string
     */
    public function renderMenu() {
        $result = '';
        if ($this->globalMenuEnabled) {
            if (file_exists(self::YALF_MENU_PATH)) {
                $rawData = parse_ini_file(self::YALF_MENU_PATH, true);
                if (!empty($rawData)) {
                    foreach ($rawData as $section => $each) {
                        $renderMenuEntry = true;
                        $icon = (!empty($each['ICON'])) ? $each['ICON'] : self::DEFAULT_ICON;
                        $icon = self::MENU_ICONS_PATH . $icon;
                        $name = __($each['NAME']);
                        $actClass = ($this->getCurrentModuleName() == $section) ? 'active' : '';
                        //right check
                        if (isset($each['NEED_RIGHT'])) {
                            //is auth engine enabled?
                            if ($this->authEnabled) {
                                if (!empty($each['NEED_RIGHT'])) {
                                    $renderMenuEntry = $this->checkForRight($each['NEED_RIGHT']);
                                }
                            }
                        }

                        //option check
                        if ($renderMenuEntry) {
                            //if not denied by rights now
                            if (isset($each['NEED_OPTION'])) {
                                if (!empty($each['NEED_OPTION'])) {
                                    if (isset($this->config[$each['NEED_OPTION']])) {
                                        if (empty($this->config[$each['NEED_OPTION']])) {
                                            $renderMenuEntry = false; //required option disabled
                                        }
                                    } else {
                                        $renderMenuEntry = false;
                                    }
                                }
                            }
                        }
                        if ($renderMenuEntry) {
                            $result .= wf_tag('li', false, $actClass) . wf_Link($each['URL'], wf_img($icon) . ' ' . $name, false) . wf_tag('li', true);
                        }
                    }
                }
            }
        }
        return($result);
    }

    /**
     * Returns global menu enable state flag
     * 
     * @return bool
     */
    public function getGlobalMenuFlag() {
        return($this->globalMenuEnabled);
    }

    /**
     * Returns some user data as array
     * 
     * @param string $username
     * 
     * @return array/bool
     */
    public function getUserData($username) {
        $result = @unserialize(@file_get_contents(USERS_PATH . basename($username)));
        if (empty($result)) {
            return (false);
        } else {
            return $result;
        }
    }

    /**
     * Inits user and sets some cookies if its ok
     * 
     * @param bool $skipcheck Use this parameter to skip userdata checks
     * 
     * @return bool
     */
    protected function initializeUser($skipcheck = false) {
        //Inits default guest user
        $this->user = array('nickname' => __('Guest'), 'username' => 'guest', 'admin' => '', 'tz' => (int) @$this->config['default_tz'], 'accesslevel' => 0);
        $this->initialiseAccess($this->user['admin']);

        if (@$this->config['YALF_AUTH_ENABLED']) {
            // If user cookie is not present we exiting without error
            if (empty($_COOKIE[$this->cookie_user])) {
                $this->loggedIn = false;
                return (true);
            }

            // So we have a cookie, let's extract data from it
            $cookie_data = explode(':', $_COOKIE[$this->cookie_user], 2);
            if (!$skipcheck) {

                // If this cookie is invalid - we exiting destroying cookie and exiting with error
                if (sizeof($cookie_data) != 2) {
                    setcookie($this->cookie_user, null, time() - 3600);
                    return(false);
                }
                // Now we must validate user's data
                if (!$this->checkUserData($cookie_data[0], $cookie_data[1], 'user_init', true, $this->user)) {
                    setcookie($this->cookie_user, null, time() - 3600);
                    $this->loggedIn = false;
                    return(false);
                }
            }

            $userdata = $this->getUserData($cookie_data[0]);
            //failed to load user profile
            if ($userdata == false) {
                setcookie($this->cookie_user, null, time() - 3600);
                $this->loggedIn = false;
                return (false);
            }

            $this->user = $userdata;
            $this->loggedIn = true;

            // Initialise access levels
            $this->initialiseAccess($this->user['admin']);

            // Secure the nickname
            $this->user['nickname'] = htmlspecialchars($this->user['nickname']);
        } else {
            //All users around is logged in and have root rights
            $this->loggedIn = true;
            $this->root = true;
        }
    }

    /**
     * Performs user ath/deauth if required
     * 
     * @return void
     */
    protected function performUserAuth() {
        if ($this->config['YALF_AUTH_ENABLED']) {
            if (!empty($_POST['login_form'])) {
                $this->logInUser(@$_POST['username'], @$_POST['password'], !empty($_POST['remember']) ? true : false);
            }
            //default POST logout
            if (!empty($_POST['logout_form'])) {
                $this->logOutUser();
                rcms_redirect('index.php', true);
            }
            //additional get-request user auto logout sub
            if (!empty($_GET['idleTimerAutoLogout'])) {
                $this->logOutUser();
                rcms_redirect('index.php', true);
            }

            //normal get-request user logout
            if (!empty($_GET['forceLogout'])) {
                $this->logOutUser();
                rcms_redirect('index.php', true);
            }
        }
    }

    /**
     * Parses some rights string into protected rights property
     * 
     * @param string $rights
     * 
     * @return bool
     */
    protected function initialiseAccess($rights) {
        if ($rights !== '*') {
            preg_match_all('/\|(.*?)\|/', $rights, $rights_r);
            foreach ($rights_r[1] as $right) {
                $this->rights[$right] = (empty($this->rights_database[$right])) ? ' ' : $this->rights_database[$right];
            }
        } else {
            $this->root = true;
        }
        return (true);
    }

    /**
     * Returns rights database registered due modules preload
     * 
     * @return array
     */
    public function getRightsDatabase() {
        return($this->rights_database);
    }

    /**
     * This function log out user from system and destroys his cookie.
     * 
     * @return bool
     */
    protected function logOutUser() {
        setcookie($this->cookie_user, '', time() - 3600);
        $_COOKIE[$this->cookie_user] = '';
        $this->initializeUser(false);
        return (true);
    }

    /**
     * This function check user's data and logs in him.
     * 
     * @param string $username
     * @param string $password
     * @param bool $remember
     * 
     * @return bool
     */
    protected function logInUser($username, $password, $remember) {
        $username = basename($username);
        if ($username == 'guest') {
            return false;
        }

        if (!$this->loggedIn AND $this->checkUserData($username, $password, 'user_login', false, $userdata)) {
            // OK... Let's allow user to log in :)
            setcookie($this->cookie_user, $username . ':' . $userdata['password'], ($remember) ? time() + 3600 * 24 * 365 : 0);
            $_COOKIE[$this->cookie_user] = $username . ':' . $userdata['password'];
            $this->initializeUser(true);
            return (true);
        } else {
            return (false);
        }
    }

    /**
     * Returns logged in state
     * 
     * @return bool
     */
    public function getLoggedInState() {
        return($this->loggedIn);
    }

    /**
     * Returns system athorization flag state
     * 
     * @return bool
     */
    public function getAuthEnabled() {
        return($this->authEnabled);
    }

    /**
     * This function check user's data and validate his profile file.
     * 
     * @param string $username
     * @param string $password
     * @param string $report_to
     * @param boolean $hash
     * @param link $userdata
     * 
     * @return bool
     */
    protected function checkUserData($username, $password, $report_to, $hash, &$userdata) {
        if (preg_replace("/[\d\w]+/i", "", $username) != "") {
            $this->results[$report_to] = __('Invalid username');
            return false;
        }
        // If login is not exists - we exiting with error
        if (!is_file(USERS_PATH . $username)) {
            $this->results[$report_to] = __('There are no user with this username');
            return false;
        }
        // So all is ok. Let's load userdata
        $result = $this->getUserData($username);
        // If userdata is invalid we must exit with error
        if (empty($result))
            return false;
        // If password is invalid - exit with error
        if ((!$hash && md5($password) !== $result['password']) || ($hash && $password !== $result['password'])) {
            $this->results[$report_to] = __('Invalid password');
            return false;
        }
        // If user is blocked - exit with error
        if (@$result['blocked']) {
            $this->results[$report_to] = __('This account has been blocked by administrator');
            return false;
        }
        $userdata = $result;
        return true;
    }

    /**
     * Public getter for currently logged in user login
     * 
     * @return string
     */
    public function getLoggedInUsername() {
        return($this->user['username']);
    }

    /**
     * Logs some data to system log
     * 
     * @param string $event Event text to log
     * 
     * @return void
     */
    public function logEvent($event) {
        $date = date("Y-m-d H:i:s");
        $myLogin = $this->getLoggedInUsername();
        $myIp = $_SERVER['REMOTE_ADDR'];

        switch ($this->logType) {
            case 'file':
                $logRecord = $date . ' ' . $myLogin . ' ' . $myIp . ': ' . $event . PHP_EOL;
                file_put_contents($this->logFilePath, $logRecord, FILE_APPEND);
                break;
            case 'mysql':
                $event = mysql_real_escape_string($event);
                $query = "INSERT INTO `" . $this->logTable . "` (`id`,`date`,`admin`,`ip`,`event`) VALUES";
                $query .= "(NULL,'" . $date . "','" . $myLogin . "','" . $myIp . "','" . $event . "');";
                nr_query($query);
                break;
            case 'fake':
                //just do nothing ^_^
                break;
            default :
                die('Wrong logging type');
                break;
        }
    }

    /**
     * TODO: This piece of shit must be reviewed and rewritten
     * to many existing code use $system->getRightsForUser and $system->checkForRight('ONLINE') or something like
     */

    /**
     * Check if user have specified right
     * 
     * @param string $right
     * @param string $username
     * 
     * @return bool
     */
    public function checkForRight($right = '-any-', $username = '') {
        if (empty($username)) {
            $rights = &$this->rights;
            $root = &$this->root;
        } else {
            if (!$this->getRightsForUser($username, $rights, $root)) {
                return false;
            }
        }

        return $root OR ( $right == '-any-' && !empty($rights)) OR ! empty($rights[$right]);
    }

    /**
     * 
     * @param string $username
     * @param pointer $rights
     * @param pointer $root
     * 
     * @return bool
     */
    protected function getRightsForUser($username, &$rights, &$root) {
        if (!($userdata = $this->getUserData($username))) {
            return false;
        }

        $rights = array();
        $root = false;
        if ($userdata['admin'] !== '*') {
            preg_match_all('/\|(.*?)\|/', $userdata['admin'], $rights_r);
            foreach ($rights_r[1] as $right) {
                $rights[$right] = (empty($this->rights_database[$right])) ? ' ' : $this->rights_database[$right];
            }
        } else {
            $root = true;
        }

        return true;
    }

    /**
     * Returns some yalfConf option value or false if its not exists
     * 
     * @param string $optionName
     * 
     * @return mixed/bool on error
     */
    public function getConfigOption($optionName) {
        $result = false;
        if (isset($this->config[$optionName])) {
            $result = $this->config[$optionName];
        }
        return($result);
    }

}
