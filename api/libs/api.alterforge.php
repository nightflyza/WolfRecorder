<?php

class AlterForge extends ConfigForge {
    const URL_ME = '?module=settings';
    protected $allowedExtensions = array('png');

    protected function isPwaIconAcceptable192($url) {
        $result = true;
        
        if (empty($url)) {
            return $result;
        }

        if (preg_match('/^https?:\/\//i', $url)) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                return false;
            }

            $urlLower = strtolower($url);
            foreach ($this->allowedExtensions as $ext) {
                if (substr($urlLower, -strlen($ext)) === $ext) {
                    $fileExt = $ext;
                    break;
                }
            }
            
            if (empty($fileExt)) {
                return false;
            }

            $tmpFile = 'exports/tmpicon192.' . $fileExt;
            $downloader = new OmaeUrl($url);
            $imageData = $downloader->response();
            
            if (!empty($imageData)) {
                if (file_put_contents($tmpFile, $imageData)) {
                    $pixelCraft = new PixelCraft();
                    if ($pixelCraft->loadImage($tmpFile)) {
                        if ($pixelCraft->getImageWidth() == 192 && $pixelCraft->getImageHeight() == 192) {
                            $result = true;
                        } else {
                            $result = false;
                        }
                    } else {
                        $result = false;
                    }
                    if (file_exists($tmpFile)) {
                        unlink($tmpFile);
                    }
                } else {
                    $result = false;
                }
            } else {
                $result = false;
            }
        } else {
            $fullPath = dirname(dirname(dirname(__FILE__))) . '/' . $url;
            if (file_exists($fullPath) and is_readable($fullPath)) {
                $fileExt = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                if (in_array($fileExt, $this->allowedExtensions)) {
                    $pixelCraft = new PixelCraft();
                    if ($pixelCraft->loadImage($fullPath)) {
                        if ($pixelCraft->getImageWidth() == 192 && $pixelCraft->getImageHeight() == 192) {
                            $result = true;
                        } else {
                            $result = false;
                        }
                    } else {
                        $result = false;
                    }
                } else {
                    $result = false;
                }
            } else {
                $result = false;
            }
        }
        
        return ($result);
    }

    protected function isPwaIconAcceptable512($url) {
        $result = true;
        
        if (empty($url)) {
            return $result;
        }

        if (preg_match('/^https?:\/\//i', $url)) {
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                return false;
            }

            $urlLower = strtolower($url);
            foreach ($this->allowedExtensions as $ext) {
                if (substr($urlLower, -strlen($ext)) === $ext) {
                    $fileExt = $ext;
                    break;
                }
            }
            
            if (empty($fileExt)) {
                return false;
            }

            $tmpFile = 'exports/tmpicon512.' . $fileExt;
            $downloader = new OmaeUrl($url);
            $imageData = $downloader->response();
            
            if (!empty($imageData)) {
                if (file_put_contents($tmpFile, $imageData)) {
                    $pixelCraft = new PixelCraft();
                    if ($pixelCraft->loadImage($tmpFile)) {
                        if ($pixelCraft->getImageWidth() == 512 && $pixelCraft->getImageHeight() == 512) {
                            $result = true;
                        } else {
                            $result = false;
                        }
                    } else {
                        $result = false;
                    }
                    if (file_exists($tmpFile)) {
                        unlink($tmpFile);
                    }
                } else {
                    $result = false;
                }
            } else {
                $result = false;
            }
        } else {
            $fullPath = dirname(dirname(dirname(__FILE__))) . '/' . $url;
            if (file_exists($fullPath) and is_readable($fullPath)) {
                $fileExt = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                if (in_array($fileExt, $this->allowedExtensions)) {
                    $pixelCraft = new PixelCraft();
                    if ($pixelCraft->loadImage($fullPath)) {
                        if ($pixelCraft->getImageWidth() == 512 && $pixelCraft->getImageHeight() == 512) {
                            $result = true;
                        } else {
                            $result = false;
                        }
                    } else {
                        $result = false;
                    }
                } else {
                    $result = false;
                }
            } else {
                $result = false;
            }
        }
        
        return ($result);
    }
}