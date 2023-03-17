<?php

/**
 * Just a basic YALF language switcher class that illustrates how your application
 * live-locale switching can work.
 */
class YalfLangSwitch {

    /**
     * Yep!
     */
    public function __construct() {
        //nothing to see here!
    }

    /**
     * Preloads available locale list and 
     * 
     * @global object $system
     * 
     * @return void
     */
    public static function render() {
        global $system;
        $result = '';
        $allLocales = rcms_scandir($system::LANG_PATH);
        if (!empty($allLocales)) {
            foreach ($allLocales as $io => $each) {
                $result .= wf_Link('?yalfswitchlocale=' . $each, $each) . ' ';
            }
        }
        return($result);
    }

}
