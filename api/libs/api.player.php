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
     * Player JS lib implementation URL
     *
     * @var string
     */
    protected $playerLib = 'modules/jsc/playerjs/w2_playerjs.js';

    public function __construct($width = '', $autoPlay = false) {
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
     * Renders web player for some playlist
     * 
     * @param string $playlistPath - full playlist path
     * @param string $plStart - starting segment for playback
     * @param string $playerId - must be equal to channel name to access playlist in DOM
     * 
     * @return string
     */
    public function renderPlaylistPlayer($playlistPath, $plStart = '', $playerId = '') {
        $autoPlay = ($this->autoPlayFlag) ? 'true' : 'false';
        $playerId = ($playerId) ? $playerId : 'plplayer' . wf_InputId();

        $result = '';
        $result .= wf_tag('script', false, '', 'src="' . $this->playerLib . '"') . wf_tag('script', true);
        $result .= wf_tag('div', false, '', 'style="float:left; width:' . $this->width . '; margin:5px;"');
        $result .= wf_tag('div', false, '', 'id = "' . $playerId . '" style="width:90%;"') . wf_tag('div', true);
        $result .= wf_tag('script');
        $result .= 'var player = new Playerjs({id:"' . $playerId . '", file:"' . $playlistPath . '", autoplay:' . $autoPlay . ' ' . $plStart . '});';
        $result .= wf_tag('script', true);
        $result .= wf_tag('div', true);
        $result .= wf_CleanDiv();
        return($result);
    }

    /**
     * Renders web player for some single file
     * 
     * @param string $playlistPath - full playlist path
     * @param string $playerId - must be equal to channel name to access playlist in DOM
     * 
     * @return string
     */
    public function renderSinglePlayer($filePath, $playerId = '') {
        $autoPlay = ($this->autoPlayFlag) ? 'true' : 'false';
        $playerId = ($playerId) ? $playerId : 'singleplayer' . wf_InputId();

        $result = '';
        $result .= wf_tag('script', false, '', 'src="' . $this->playerLib . '"') . wf_tag('script', true);
        $result .= wf_tag('div', false, '', 'style="float:left; width:' . $this->width . '; margin:5px;"');
        $result .= wf_tag('div', false, '', 'id = "' . $playerId . '" style="width:90%;"') . wf_tag('div', true);
        $result .= wf_tag('script');
        $result .= 'var player = new Playerjs({id:"' . $playerId . '", file:"' . $filePath . '", autoplay:' . $autoPlay . '});';
        $result .= wf_tag('script', true);
        $result .= wf_tag('div', true);
        $result .= wf_CleanDiv();
        return($result);
    }

}
