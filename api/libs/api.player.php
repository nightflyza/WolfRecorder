<?php

class Player {

    /**
     * Current instance player width
     *
     * @var string
     */
    protected $width = '600px';

    /**
     * Current instance auto play flag
     *
     * @var bool
     */
    protected $autoPlayFlag = false;

    /**
     * Contains current locale in 2 letters format
     *
     * @var string
     */
    protected $lang = 'en';

    /**
     * Player JS lib implementation URL
     *
     * @var string
     */
    protected $playerLib = '';

    public function __construct($width = '', $autoPlay = false) {
        $this->setPlayerLib();
        $this->setLang();
        if ($width) {
            $this->setWidth($width);
        }
        $this->setAutoplay($autoPlay);
    }

    /**
     * Sets current instance width
     * 
     * @param string $width
     * 
     * @return void
     */
    protected function setWidth($width) {
        $this->width = $width;
    }

    /**
     * Sets current instance autoplay
     * 
     * @param bool $state
     * 
     * @return void
     */
    protected function setAutoplay($state) {
        $this->autoPlayFlag = $state;
    }

    /**
     * Sets current locale code in two letter format
     *
     * @return void
     */
    protected function setLang() {
        global $system;
        $currentLocale = $system->getCurLangName();
        $langCode = 'en';
        switch ($currentLocale) {
            case 'ukrainian':
                $langCode = 'uk';
                break;
            case 'english':
                $langCode = 'en';
                break;
            case 'portuguese':
                $langCode = 'pt';
                break;
            case 'spanish':
                $langCode = 'es';
                break;
            case 'russian':
                $langCode = 'ru';
                break;
        }
        $this->lang = $langCode;
    }

    /**
     * Sets player lib property
     * 
     * @param string $lib
     * 
     * @return void
     */
    public function setPlayerLib($lib = 'w7') {
        $this->playerLib = 'modules/jsc/playerjs/' . $lib . '.js';
    }

    /**
     * Renders web player for some playlist
     * 
     * @param string $playlistPath - full playlist path
     * @param string $plStart - starting segment for playback
     * @param string $playerId - must be equal to channel name to access playlist in DOM
     * @param string $poster - optional channel screenshot
     * 
     * @return string
     */
    public function renderPlaylistPlayer($playlistPath, $plStart = '', $playerId = '', $poster = '') {
        $autoPlay = ($this->autoPlayFlag) ? 'true' : 'false';
        $playerId = ($playerId) ? $playerId : 'plplayer' . wf_InputId();
        $poster = ($poster) ? ' poster:"' . $poster . '",' : '';
        $lang = 'lang: "' . $this->lang . '", ';
        $result = '';
        $result .= wf_tag('script', false, '', 'src="' . $this->playerLib . '"') . wf_tag('script', true);
        $result .= wf_tag('div', false, '', 'style="float:left; width:' . $this->width . '; margin:5px;"');
        $result .= wf_tag('div', false, 'archplayercontainer', 'id = "' . $playerId . '"') . wf_tag('div', true);
        $result .= wf_tag('script');
        $result .= 'var player = new Playerjs({id:"' . $playerId . '", ' . $lang . ' ' . $poster . ' file:"' . $playlistPath . '", autoplay:' . $autoPlay . ' ' . $plStart . '});';
        $result .= wf_tag('script', true);
        $result .= wf_tag('div', true);
        $result .= wf_CleanDiv();
        return ($result);
    }

    /**
     * Renders web player for some single file or stream
     * 
     * @param string $filePath - full file or stream path
     * @param string $playerId - must be equal to channel name to access playlist in DOM
     * @param string $poster - optional channel screenshot
     * @return string
     */
    public function renderLivePlayer($filePath, $playerId = '', $poster = '') {
        $autoPlay = ($this->autoPlayFlag) ? 'true' : 'false';
        $playerId = ($playerId) ? $playerId : 'singleplayer' . wf_InputId();
        $poster = ($poster) ? ' poster:"' . $poster . '",' : '';
        $lang = 'lang: "' . $this->lang . '", ';
        $result = '';
        $result .= wf_tag('script', false, '', 'src="' . $this->playerLib . '"') . wf_tag('script', true);
        $result .= wf_tag('div', false, '', 'style="float:left; width:' . $this->width . '; margin:5px;"');
        $result .= wf_tag('div', false, '', 'id = "' . $playerId . '" style="width:90%;"') . wf_tag('div', true);
        $result .= wf_tag('script');
        $result .= 'var player = new Playerjs({id:"' . $playerId . '", ' . $lang . ' ' . $poster . ' file:"' . $filePath . '", autoplay:' . $autoPlay . '});';
        $result .= wf_tag('script', true);
        $result .= wf_tag('div', true);
        $result .= wf_CleanDiv();
        return ($result);
    }
}
