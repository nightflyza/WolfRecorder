<?php

class CoreForge extends ConfigForge {
    const URL_ME = '?module=settings';

    protected function isLocaleExists($locale) {
        $result = false;
        $locale = preg_replace('/\0/s', '', $locale);
        $locale = preg_replace("#[^a-z0-9A-Z]#Uis", '', $locale);
        if (file_exists(YALFCore::LANG_PATH . $locale)) {
            $result = true;
        }
        return ($result);
    }
}
