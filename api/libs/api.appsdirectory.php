<?php

/**
 * Remote WolfRecorder client apps catalog with caching
 */
class AppsDirectory {

    /**
     * System caching object placeholder
     *
     * @var object
     */
    protected $cache = '';

    /**
     * Apps list cache lifetime in seconds
     *
     * @var int
     */
    protected $cacheTimeout = 600;

    /**
     * System messages helper instance placeholder
     *
     * @var object
     */
    protected $messages = '';

    /**
     * Remote catalog fetch error code
     *
     * @var string
     */
    protected $remoteError = '';

    const REMOTE_ERR_COMM = 'comm';
    const REMOTE_ERR_LIST = 'list';

    const API_URL='https://wolfrecorder.com/software/';
    const API_FILE = 'api.php';
    const CACHE_KEY = 'APPS_DIRECTORY';

    public function __construct() {
        $this->initCache();
        $this->initMessages();
    }

    /**
     * Inits caching object instance for further usage
     *
     * @return void
     */
    protected function initCache() {
        $this->cache = new UbillingCache();
    }

    /**
     * Inits system messages helper
     *
     * @return void
     */
    protected function initMessages() {
        $this->messages = new UbillingMessageHelper();
    }

    /**
     * Renders downloadable apps list
     *
     * @return string
     */
    public function renderAppsList() {
        $result = '';
        $apps = $this->getAppsList();
        $this->notifyRemoteErrors();
        $allApps = array_merge($apps, $this->getPseudoApps());
        if (!empty($allApps)) {
            $rows = '';
            foreach ($allApps as $eachApp) {
                $rows .= $this->renderAppRow($eachApp);
            }
            $result .= wf_TableBody($rows, '100%', 0, 'resp-table');
        } else {
            if (empty($this->remoteError)) {
                $result .= $this->messages->getStyledMessage(__('Nothing to show'), 'warning');
            }
        }
        return ($result);
    }

    /**
     * Shows remote catalog errors via show_error()
     *
     * @return void
     */
    protected function notifyRemoteErrors() {
        if (!empty($this->remoteError)) {
            if ($this->remoteError == self::REMOTE_ERR_COMM) {
                show_error(__('Unable to communicate with apps server'));
            } else {
                if ($this->remoteError == self::REMOTE_ERR_LIST) {
                    show_error(__('Unable to get applications list from server'));
                }
            }
        }
    }

    /**
     * Returns built-in local pseudo apps appended after remote catalog
     *
     * @return array
     */
    protected function getPseudoApps() {
        global $ubillingConfig;
        $result = array();
        if (cfr('WALL')) {
        if ($ubillingConfig->getAlterParam(LiveCams::OPTION_WALL)) {
                $result[] = array(
                    'name' => __('Playlist'),
                    'description' => __('Playlist of your cameras').', '.__('M3U'),
                    'icon' => 'skins/list128.png',
                    'download' => LiveCams::URL_ME . '&' . LiveCams::ROUTE_DL_PLAYLIST . '=true',
                    'local' => true,
                );
         }
        }
        return ($result);
    }

    /**
     * Renders single app table row
     *
     * @param array $app
     *
     * @return string
     */
    protected function renderAppRow($app) {
        $result = '';
        $isLocal = (!empty($app['local']));
        if ($isLocal) {
            $iconUrl = $app['icon'];
            $downloadUrl = $app['download'];
        } else {
            $iconUrl = $this->buildRemoteUrl($app['icon']);
            $downloadUrl = $this->buildRemoteUrl($app['download']);
        }
        $name = $app['name'];
        $description = $app['description'];
        if (!$isLocal) {
            $description = __($description);
        }
        $linkTitle = wf_img($iconUrl, $name, 'vertical-align:middle; max-height:64px; margin-right:8px;');
        $linkTitle .= wf_tag('strong') . $name . wf_tag('strong', true);
        $linkOpts = 'title="' . $name . '"';
        if (!$isLocal) {
            $linkOpts .= ' target="_blank"';
        }
        $appLink = wf_Link($downloadUrl, $linkTitle, false, '', $linkOpts);
        $cells = wf_TableCell($appLink,'30%');
        $cells .= wf_TableCell($description);
        $result = wf_TableRow($cells, 'row5');
        return ($result);
    }

    /**
     * Returns apps list from cache or remote API
     *
     * @return array
     */
    protected function getAppsList() {
        $result = array();
        $this->remoteError = '';
        $cached = $this->cache->get(self::CACHE_KEY, $this->cacheTimeout);
        if (!empty($cached) and is_array($cached)) {
            $result = $cached;
        } else {
            $fetched = $this->fetchRemoteApps();
            if (!empty($fetched)) {
                $this->cache->set(self::CACHE_KEY, $fetched, $this->cacheTimeout);
                $result = $fetched;
            }
        }
        return ($result);
    }

    /**
     * Downloads and parses remote apps catalog
     *
     * @return array
     */
    protected function fetchRemoteApps() {
        $result = array();
        $this->remoteError = '';
        $apiUrl = $this->getApiEndpointUrl();
        try {
            $client = new OmaeUrl($apiUrl);
            $client->setUserAgent($this->getUserAgent());
            $client->setTimeout(5);
            $raw = $client->response();
            $error = $client->error();
            $httpCode = $client->httpCode();
            if (!empty($error)) {
                $this->remoteError = self::REMOTE_ERR_COMM;
            } else {
                if ($httpCode != 200) {
                    $this->remoteError = self::REMOTE_ERR_COMM;
                } else {
                    if (empty($raw)) {
                        $this->remoteError = self::REMOTE_ERR_COMM;
                    } else {
                        $parsed = $this->parseAppsPayload($raw);
                        if (!empty($parsed)) {
                            $result = $parsed;
                        } else {
                            $this->remoteError = self::REMOTE_ERR_LIST;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $this->remoteError = self::REMOTE_ERR_COMM;
        }
        return ($result);
    }

    /**
     * Parses JSON payload from apps API
     *
     * @param string $raw
     *
     * @return array
     */
    protected function parseAppsPayload($raw) {
        $result = array();
        $decoded = @json_decode($raw, true);
        if (is_array($decoded) and isset($decoded['apps']) and is_array($decoded['apps'])) {
            foreach ($decoded['apps'] as $each) {
                if ($this->isValidAppRecord($each)) {
                    $description = '';
                    if (isset($each['description'])) {
                        $description = trim($each['description']);
                    }
                    $result[] = array(
                        'name' => trim($each['name']),
                        'description' => $description,
                        'icon' => trim($each['icon']),
                        'download' => trim($each['download']),
                    );
                }
            }
        }
        return ($result);
    }

    /**
     * Checks is app record contains required fields
     *
     * @param mixed $app
     *
     * @return bool
     */
    protected function isValidAppRecord($app) {
        $result = false;
        if (is_array($app)) {
            if (!empty($app['name']) and !empty($app['icon']) and !empty($app['download'])) {
                $result = true;
            }
        }
        return ($result);
    }

    /**
     * Returns full apps API endpoint URL
     *
     * @return string
     */
    protected function getApiEndpointUrl() {
        $result = self::API_URL . self::API_FILE;
        return ($result);
    }

    /**
     * Builds absolute URL for remote asset path
     *
     * @param string $relativePath
     *
     * @return string
     */
    protected function buildRemoteUrl($relativePath) {
        $result = self::API_URL . ltrim($relativePath, '/');
        return ($result);
    }

    /**
     * Returns HTTP user agent for remote requests
     *
     * @return string
     */
    protected function getUserAgent() {
        $result = 'WolfRecorderApps';
        if (file_exists('RELEASE')) {
            $releaseInfo = file_get_contents('RELEASE');
            $releaseParts = explode(' ', trim($releaseInfo));
            if (!empty($releaseParts[0])) {
                $result .= '/' . trim($releaseParts[0]);
            }
        }
        return ($result);
    }

}
