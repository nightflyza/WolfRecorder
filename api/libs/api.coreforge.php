<?php

class CoreForge extends ConfigForge {
    const URL_ME = '?module=settings';
    protected $allowedExtensions = array('png', 'jpg', 'jpeg', 'gif');

    protected function isLocaleExists($locale) {
        $result = false;
        $locale = preg_replace('/\0/s', '', $locale);
        $locale = preg_replace("#[^a-z0-9A-Z]#Uis", '', $locale);
        if (file_exists(YALFCore::LANG_PATH . $locale)) {
            $result = true;
        }
        return ($result);
    }

    protected function isUrlValid($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    protected function isLogoUrlValid($url) {
        $result = false;
        
        if (empty($url)) {
            return $result;
        }

        if (preg_match('/^https?:\/\//i', $url)) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                return $result;
            }

            $urlLower = strtolower($url);
            foreach ($this->allowedExtensions as $ext) {
                if (substr($urlLower, -strlen($ext)) === $ext) {
                    $result = true;
                    break;
                }
            }
            return $result;
        }

        $fullPath = dirname(dirname(dirname(__FILE__))) . '/' . $url;
        if (file_exists($fullPath) and is_readable($fullPath)) {
            $fileExt = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            if (in_array($fileExt, $this->allowedExtensions)) {
                $result = true;
            }
        }
        
        return $result;
    }

    protected function isLogoAcceptable($url) {
        $result = false;
        
        if ($this->isLogoUrlValid($url)) {
            if (preg_match('/^https?:\/\//i', $url)) {
                $urlLower = strtolower($url);
                $fileExt = '';
                foreach ($this->allowedExtensions as $ext) {
                    if (substr($urlLower, -strlen($ext)) === $ext) {
                        $fileExt = $ext;
                        break;
                    }
                }

                $tmpFile = 'exports/tmplogo.' . $fileExt;
                $downloader = new OmaeUrl($url);
                $imageData = $downloader->response();
                
                if (!empty($imageData)) {
                    if (file_put_contents($tmpFile, $imageData)) {
                        $pixelCraft = new PixelCraft();
                        if ($pixelCraft->isImageValid($tmpFile)) {
                            $result = true;
                        }
                        if (file_exists($tmpFile)) {
                            unlink($tmpFile);
                        }
                    }
                }
            } else {
                $fullPath = dirname(dirname(dirname(__FILE__))) . '/' . $url;
                $pixelCraft = new PixelCraft();
                if ($pixelCraft->isImageValid($fullPath)) {
                    $result = true;
                }
            }
        }
        
        return ($result);
    }
}
