<?php

/**
 * Some legacy workaround here
 */
if (!function_exists('__')) {

    /**
     * Dummy i18n function
     * 
     * @param string $str
     * @return string
     */
    function __($str) {
        global $lang;
        if (isset($lang['def'][$str])) {
            if (!empty($lang['def'][$str])) {
                $str = $lang['def'][$str];
            }
        }
        return($str);
    }

}


if (!function_exists('log_register')) {

    /**
     * Dummy function wrapper around logEvent system logging
     * 
     * @param string $data
     * 
     * @return void
     */
    function log_register($data) {
        global $system;
        $system->logEvent($data);
    }

}

if (!function_exists('cfr')) {

    /**
     * Checks is some right available for current user
     * 
     * @global object $system
     * @param string $right
     * 
     * @return bool
     */
    function cfr($right) {
        global $system;
        return($system->checkForRight($right));
    }

}

if (!function_exists('whoami')) {

    /**
     * Returns current user login
     * 
     * @global object $system
     * 
     * @return string
     */
    function whoami() {
        global $system;
        return($system->getLoggedInUsername());
    }

}

/**
 * Dummy rcms localisation function
 * 
 * @param string $str
 * 
 * @return string
 */
function rcms_date_localise($str) {
    global $lang;
    if (isset($lang['datetime'][$str])) {
        $str = $lang['datetime'][$str];
    }
    return($str);
}

/**
 * Returns current locale as two-letters code extracted form YalfCore
 * 
 * @return string
 */
function curlang() {
    global $system;
    $locale = $system->getCurLang();
    return($locale);
}

if (!function_exists('curdatetime')) {

    /**
     * Returns current date and time in mysql DATETIME view
     * 
     * @return string
     */
    function curdatetime() {
        $currenttime = date("Y-m-d H:i:s");
        return($currenttime);
    }

}

if (!function_exists('rcms_redirect')) {

    /**
     * Shows redirection javascript. 
     * 
     * @param string $url
     * @param bool $header
     */
    function rcms_redirect($url, $header = false) {
        if ($header) {
            @header('Location: ' . $url);
        } else {
            echo '<script language="javascript">document.location.href="' . $url . '";</script>';
        }
    }

}


if (!function_exists('ispos')) {

    /**
     * Checks for substring in string
     * 
     * @param string $string
     * @param string $search
     * @return bool
     */
    function ispos($string, $search) {
        if (strpos($string, $search) === false) {
            return(false);
        } else {
            return(true);
        }
    }

}

if (!function_exists('zb_convertSize')) {

    /**
     * Converts bytes into human-readable values like Kb, Mb, Gb...
     * 
     * @param int $fs
     * @param string $traffsize
     * 
     * @return string
     */
    function zb_convertSize($fs, $traffsize = 'float') {
        if ($traffsize == 'float') {
            if ($fs >= (1073741824 * 1024))
                $fs = round($fs / (1073741824 * 1024) * 100) / 100 . ' ' . __('Tb');
            elseif ($fs >= 1073741824)
                $fs = round($fs / 1073741824 * 100) / 100 . ' ' . __('Gb');
            elseif ($fs >= 1048576)
                $fs = round($fs / 1048576 * 100) / 100 . ' ' . __('Mb');
            elseif ($fs >= 1024)
                $fs = round($fs / 1024 * 100) / 100 . ' ' . __('Kb');
            else
                $fs = $fs . ' ' . __('b');
            return ($fs);
        }

        if ($traffsize == 'b') {
            return ($fs);
        }

        if ($traffsize == 'Kb') {
            $fs = round($fs / 1024 * 100) / 100 . ' ' . __('Kb');
            return ($fs);
        }

        if ($traffsize == 'Mb') {
            $fs = round($fs / 1048576 * 100) / 100 . ' ' . __('Mb');
            return ($fs);
        }
        if ($traffsize == 'Gb') {
            $fs = round($fs / 1073741824 * 100) / 100 . ' ' . __('Gb');
            return ($fs);
        }

        if ($traffsize == 'Tb') {
            $fs = round($fs / (1073741824 * 1024) * 100) / 100 . ' ' . __('Tb');
            return ($fs);
        }
    }

}

if (!function_exists('zb_TraffToGb')) {

    /**
     * Convert bytes to human-readable Gb values. Much faster than stg_convert_size()/zb_convertSize()
     * 
     * @param int $fs
     * 
     * @return string
     */
    function zb_TraffToGb($fs) {
        $fs = round($fs / 1073741824, 2) . ' Gb';
        return ($fs);
    }

}

/**
 * Advanced php5 scandir analog wit some filters
 * 
 * @param string $directory Directory to scan
 * @param string $exp  Filter expression - like *.ini or *.dat
 * @param string $type Filter type - all or dir
 * @param bool $do_not_filter
 * 
 * @return array
 */
function rcms_scandir($directory, $exp = '', $type = 'all', $do_not_filter = false) {
    $dir = $ndir = array();
    if (!empty($exp)) {
        $exp = '/^' . str_replace('*', '(.*)', str_replace('.', '\\.', $exp)) . '$/';
    }
    if (!empty($type) && $type !== 'all') {
        $func = 'is_' . $type;
    }
    if (is_dir($directory)) {
        $fh = opendir($directory);
        while (false !== ($filename = readdir($fh))) {
            if (substr($filename, 0, 1) != '.' || $do_not_filter) {
                if ((empty($type) || $type == 'all' || $func($directory . '/' . $filename)) && (empty($exp) || preg_match($exp, $filename))) {
                    $dir[] = $filename;
                }
            }
        }
        closedir($fh);
        natsort($dir);
    }
    return $dir;
}

/**
 * Parses standard INI-file structure and returns this as key=>value array
 * 
 * @param string $filename Existing file name
 * @param bool $blocks Section parsing flag
 * 
 * @return array
 */
function rcms_parse_ini_file($filename, $blocks = false) {
    $array1 = file($filename);
    $section = '';
    foreach ($array1 as $filedata) {
        $dataline = trim($filedata);
        $firstchar = substr($dataline, 0, 1);
        if ($firstchar != ';' && !empty($dataline)) {
            if ($blocks && $firstchar == '[' && substr($dataline, -1, 1) == ']') {
                $section = strtolower(substr($dataline, 1, -1));
            } else {
                $delimiter = strpos($dataline, '=');
                if ($delimiter > 0) {
                    preg_match("/^[\s]*(.*?)[\s]*[=][\s]*(\"|)(.*?)(\"|)[\s]*$/", $dataline, $matches);
                    $key = $matches[1];
                    $value = $matches[3];

                    if ($blocks) {
                        if (!empty($section)) {
                            $array2[$section][$key] = stripcslashes($value);
                        }
                    } else {
                        $array2[$key] = stripcslashes($value);
                    }
                } else {
                    if ($blocks) {
                        if (!empty($section)) {
                            $array2[$section][trim($dataline)] = '';
                        }
                    } else {
                        $array2[trim($dataline)] = '';
                    }
                }
            }
        }
    }
    return (!empty($array2)) ? $array2 : false;
}

if (!function_exists('vf')) {

    /**
     * Returns cutted down data entry 
     *  Available modes:
     *  1 - digits, letters
     *  2 - only letters
     *  3 - only digits
     *  4 - digits, letters, "-", "_", "."
     *  5 - current lang alphabet + digits + punctuation
     *  default - filter only blacklist chars
     *
     * @param string $data
     * @param int $mode
     * 
     * @return string
     */
    function vf($data, $mode = 0) {
        switch ($mode) {
            case 1:
                return preg_replace("#[^a-z0-9A-Z]#Uis", '', $data); // digits, letters
                break;
            case 2:
                return preg_replace("#[^a-zA-Z]#Uis", '', $data); // letters
                break;
            case 3:
                return preg_replace("#[^0-9]#Uis", '', $data); // digits
                break;
            case 4:
                return preg_replace("#[^a-z0-9A-Z\-_\.]#Uis", '', $data); // digits, letters, "-", "_", "."
                break;
            case 5:
                return preg_replace("#[^ [:punct:]" . ('a-zA-Z') . "0-9]#Uis", '', $data); // current lang alphabet + digits + punctuation
                break;
            default:
                return preg_replace("#[~@\+\?\%\/\;=\*\>\<\"\'\-]#Uis", '', $data); // black list anyway
                break;
        }
    }

}

/**
 * Fast debug text data output
 * 
 * @param string $data
 */
function deb($data) {
    show_window('DEBUG', $data);
}

/**
 * Fast debug output of array
 * 
 * @param string $data
 */
function debarr($data) {
    $result = print_r($data, true);
    $result = '<pre>' . $result . '</pre>';
    show_window('DEBUG', $result);
}

/**
 * Returns current date and time in mysql DATETIME view
 * 
 * @return string
 */
function curdatetime() {
    $currenttime = date("Y-m-d H:i:s");
    return($currenttime);
}

/**
 * returns current time in mysql DATETIME view
 * 
 * @return string
 */
function curtime() {
    $currenttime = date("H:i:s");
    return($currenttime);
}

/**
 * Returns current date in mysql DATETIME view
 * 
 * @return string
 */
function curdate() {
    $currentdate = date("Y-m-d");
    return($currentdate);
}

/**
 * Returns current year-month in mysql DATETIME view
 * 
 * @return string
 */
function curmonth() {
    $currentmonth = date("Y-m");
    return($currentmonth);
}

/**
 * Returns previous year-month in mysql DATETIME view
 * 
 * @return string
 */
function prevmonth() {
    $result = date("Y-m", strtotime("-1 months"));
    return ($result);
}

/**
 * Returns current year as just Y
 * 
 * @return string
 */
function curyear() {
    $currentyear = date("Y");
    return($currentyear);
}

/**
 * Returns all months with names in two digit notation
 * 
 * @param string $number
 * @return array/string
 */
function months_array($number = null) {
    $months = array(
        '01' => 'January',
        '02' => 'February',
        '03' => 'March',
        '04' => 'April',
        '05' => 'May',
        '06' => 'June',
        '07' => 'July',
        '08' => 'August',
        '09' => 'September',
        '10' => 'October',
        '11' => 'November',
        '12' => 'December'
    );
    if (empty($number)) {
        return $months;
    } else {
        return $months[$number];
    }
}

/**
 * Retuns all months with names without begin zeros
 * 
 * @return array
 */
function months_array_wz() {
    $months = array(
        '1' => 'January',
        '2' => 'February',
        '3' => 'March',
        '4' => 'April',
        '5' => 'May',
        '6' => 'June',
        '7' => 'July',
        '8' => 'August',
        '9' => 'September',
        '10' => 'October',
        '11' => 'November',
        '12' => 'December');
    return($months);
}

/**
 * Returns visual bar with count/total proportional size
 * 
 * @param float $count
 * @param float $total
 * @return string
 */
function web_bar($count, $total) {
    $barurl = 'skins/bar.png';
    if ($total != 0) {
        $width = ($count / $total) * 100;
    } else {
        $width = 0;
    }

    $code = wf_img_sized($barurl, '', $width . '%', '14');
    return($code);
}

/**
 * Calculates percent value
 * 
 * @param float $sum
 * @param float $percent
 * 
 * @return float
 */
function zb_Percent($sum, $percent) {
    $result = $percent / 100 * $sum;
    return ($result);
}

/**
 * Counts percentage between two values
 * 
 * @param float $valueTotal
 * @param float $value
 * 
 * @return float
 */
function zb_PercentValue($valueTotal, $value) {
    $result = 0;
    if ($valueTotal != 0) {
        $result = round((($value * 100) / $valueTotal), 2);
    }
    return ($result);
}

/**
 * UTF8-safe translit function
 * 
 * @param $string  string to be transliterated
 * @param $bool Save case state
 * 
 * @return string
 */
function zb_TranslitString($string, $caseSensetive = false) {

    if ($caseSensetive) {
        $replace = array(
            "'" => "",
            "`" => "",
            "а" => "a", "А" => "A",
            "б" => "b", "Б" => "B",
            "в" => "v", "В" => "V",
            "г" => "g", "Г" => "G",
            "д" => "d", "Д" => "D",
            "е" => "e", "Е" => "E",
            "ё" => "e", "Ё" => "E",
            "ж" => "zh", "Ж" => "Zh",
            "з" => "z", "З" => "Z",
            "и" => "i", "И" => "I",
            "й" => "y", "Й" => "Y",
            "к" => "k", "К" => "K",
            "л" => "l", "Л" => "L",
            "м" => "m", "М" => "M",
            "н" => "n", "Н" => "N",
            "о" => "o", "О" => "O",
            "п" => "p", "П" => "P",
            "р" => "r", "Р" => "R",
            "с" => "s", "С" => "S",
            "т" => "t", "Т" => "T",
            "у" => "u", "У" => "U",
            "ф" => "f", "Ф" => "F",
            "х" => "h", "Х" => "H",
            "ц" => "c", "Ц" => "C",
            "ч" => "ch", "Ч" => "Ch",
            "ш" => "sh", "Ш" => "Sh",
            "щ" => "sch", "Щ" => "Sch",
            "ъ" => "", "Ъ" => "",
            "ы" => "y", "Ы" => "Y",
            "ь" => "", "Ь" => "",
            "э" => "e", "Э" => "E",
            "ю" => "yu", "Ю" => "Yu",
            "я" => "ya", "Я" => "Ya",
            "і" => "i", "І" => "I",
            "ї" => "yi", "Ї" => "Yi",
            "є" => "e", "Є" => "E",
            "ґ" => "g", "Ґ" => "G"
        );
    } else {
        $replace = array(
            "'" => "",
            "`" => "",
            "а" => "a", "А" => "a",
            "б" => "b", "Б" => "b",
            "в" => "v", "В" => "v",
            "г" => "g", "Г" => "g",
            "д" => "d", "Д" => "d",
            "е" => "e", "Е" => "e",
            "ё" => "e", "Ё" => "e",
            "ж" => "zh", "Ж" => "zh",
            "з" => "z", "З" => "z",
            "и" => "i", "И" => "i",
            "й" => "y", "Й" => "y",
            "к" => "k", "К" => "k",
            "л" => "l", "Л" => "l",
            "м" => "m", "М" => "m",
            "н" => "n", "Н" => "n",
            "о" => "o", "О" => "o",
            "п" => "p", "П" => "p",
            "р" => "r", "Р" => "r",
            "с" => "s", "С" => "s",
            "т" => "t", "Т" => "t",
            "у" => "u", "У" => "u",
            "ф" => "f", "Ф" => "f",
            "х" => "h", "Х" => "h",
            "ц" => "c", "Ц" => "c",
            "ч" => "ch", "Ч" => "ch",
            "ш" => "sh", "Ш" => "sh",
            "щ" => "sch", "Щ" => "sch",
            "ъ" => "", "Ъ" => "",
            "ы" => "y", "Ы" => "y",
            "ь" => "", "Ь" => "",
            "э" => "e", "Э" => "e",
            "ю" => "yu", "Ю" => "yu",
            "я" => "ya", "Я" => "ya",
            "і" => "i", "І" => "i",
            "ї" => "yi", "Ї" => "yi",
            "є" => "e", "Є" => "e",
            "ґ" => "g", "Ґ" => "g"
        );
    }
    return $str = iconv("UTF-8", "UTF-8//IGNORE", strtr($string, $replace));
}

/**
 * Returns random alpha-numeric string of some lenght
 * 
 * @param int $size
 * @return string
 */
function zb_rand_string($size = 4) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
    $string = "";
    for ($p = 0; $p < $size; $p++) {
        $string .= $characters[mt_rand(0, (strlen($characters) - 1))];
    }

    return ($string);
}

/**
 * Converts CIDR mask into decimal like 24 => 255.255.255.0
 * 
 * @param int $mask_bits
 * 
 * @return string 
 */
function multinet_cidr2mask($mask_bits) {
    if ($mask_bits > 31 || $mask_bits < 0)
        return("0.0.0.0");
    $host_bits = 32 - $mask_bits;
    $num_hosts = pow(2, $host_bits) - 1;
    $netmask = ip2int("255.255.255.255") - $num_hosts;
    return int2ip($netmask);
}

/**
 * Converts IP to integer value
 * 
 * @param string $src
 * 
 * @return int
 */
function ip2int($src) {
    $t = explode('.', $src);
    return count($t) != 4 ? 0 : 256 * (256 * ((float) $t[0] * 256 + (float) $t[1]) + (float) $t[2]) + (float) $t[3];
}

/**
 * Converts integer into IP
 * 
 * @param int $src
 * 
 * @return string
 */
function int2ip($src) {
    $s1 = (int) ($src / 256);
    $i1 = $src - 256 * $s1;
    $src = (int) ($s1 / 256);
    $i2 = $s1 - 256 * $src;
    $s1 = (int) ($src / 256);
    return sprintf('%d.%d.%d.%d', $s1, $src - 256 * $s1, $i2, $i1);
}

/**
 * Returns exploded array of some multi-lined strings
 * 
 * @param string $data
 * 
 * @return array
 */
function explodeRows($data) {
    $result = explode("\n", $data);
    return ($result);
}

/**
 * Initializes file download procedure
 * 
 * @param string $filePath
 * @param string $contentType
 * @throws Exception
 */
function zb_DownloadFile($filePath, $contentType = '') {
    if (!empty($filePath)) {
        if (file_exists($filePath)) {
            log_register("DOWNLOAD FILE `" . $filePath . "`");

            if (($contentType == '') OR ( $contentType == 'default')) {
                $contentType = 'application/octet-stream';
            } else {
                //additional content types
                if ($contentType == 'docx') {
                    $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
                }

                if ($contentType == 'csv') {
                    $contentType = 'text/csv; charset=Windows-1251';
                }

                if ($contentType == 'text') {
                    $contentType = 'text/plain;';
                }

                if ($contentType == 'jpg') {
                    $contentType = 'Content-Type: image/jpeg';
                }
            }

            header('Content-Type: ' . $contentType);
            header("Content-Transfer-Encoding: Binary");
            header("Content-disposition: attachment; filename=\"" . basename($filePath) . "\"");
            header("Content-Description: File Transfer");
            header("Content-Length: " . filesize($filePath));

            flush(); // this doesn't really matter.
            $fp = fopen($filePath, "r");
            while (!feof($fp)) {
                echo fread($fp, 65536);
                flush(); // this is essential for large downloads
            }
            fclose($fp);
            die();
        } else {
            throw new Exception('DOWNLOAD_FILEPATH_NOT_EXISTS');
        }
    } else {
        throw new Exception('DOWNLOAD_FILEPATH_EMPTY');
    }
}

/**
 * Returns data that contained between two string tags
 * 
 * @param string $openTag - open tag string. Examples: "(", "[", "{", "[sometag]" 
 * @param string $closeTag - close tag string. Examples: ")", "]", "}", "[/sometag]" 
 * @param string $stringToParse - just string that contains some data to parse
 * @param bool   $mutipleResults - extract just first result as string or all matches as array like match=>match
 * 
 * @return string/array
 */
function zb_ParseTagData($openTag, $closeTag, $stringToParse = '', $mutipleResults = false) {
    $result = '';
    if (!empty($openTag) AND !empty($closeTag) AND !empty($stringToParse)) {
        $replacements = array(
            '(' => '\(',
            ')' => '\)',
            '[' => '\[',
            ']' => '\]',
        );

        foreach ($replacements as $eachReplaceTag => $eachReplace) {
            $openTag = str_replace($eachReplaceTag, $eachReplace, $openTag);
            $closeTag = str_replace($eachReplaceTag, $eachReplace, $closeTag);
        }

        $pattern = '!' . $openTag . '(.*?)' . $closeTag . '!si';

        if ($mutipleResults) {
            $result = array();
            if (preg_match_all($pattern, $stringToParse, $matches)) {
                if (isset($matches[1])) {
                    if (!empty($matches[1])) {
                        foreach ($matches[1] as $io => $each) {
                            $result[$each] = $each;
                        }
                    }
                }
            }
        } else {
            if (preg_match($pattern, $stringToParse, $matches)) {
                if (isset($matches[1])) {
                    $result = $matches[1];
                }
            }
        }
    }
    return($result);
}

/**
 * Renders time duration in seconds into formatted human-readable view
 *      
 * @param int $seconds
 * 
 * @return string
 */
function zb_formatTime($seconds) {
    $init = $seconds;
    $days = floor($seconds / 86400);
    $hours = floor(round($seconds / 3600));
    $minutes = floor(round(($seconds / 60)) % 60);
    $seconds = (round($seconds) % 60);

    if ($init < 3600) {
//less than 1 hour
        if ($init < 60) {
//less than minute
            $result = $seconds . ' ' . __('sec.');
        } else {
//more than one minute
            $result = $minutes . ' ' . __('minutes') . ' ' . $seconds . ' ' . __('seconds');
        }
    } else {
        if ($init < 86400) {
//more than hour
            $result = $hours . ' ' . __('hour') . ' ' . $minutes . ' ' . __('minutes') . ' ' . $seconds . ' ' . __('seconds');
        } else {
            $hoursLeft = $hours - ($days * 24);
            $result = $days . ' ' . __('days') . ' ' . $hoursLeft . ' ' . __('hour') . ' ' . $minutes . ' ' . __('minutes') . ' ' . $seconds . ' ' . __('seconds');
        }
    }
    return ($result);
}

/**
 * Renders time duration in seconds into formatted human-readable view without seconds
 *      
 * @param int $seconds
 * 
 * @return string
 */
function wr_formatTimeArchive($seconds) {
    $init = $seconds;
    $days = floor($seconds / 86400);
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds / 60) % 60);
    $seconds = $seconds % 60;

    if ($init < 3600) {
//less than 1 hour
        if ($init < 60) {
//less than minute
            $result = $seconds . ' ' . __('sec.');
        } else {
//more than one minute
            $result = $minutes . ' ' . __('minutes');
        }
    } else {
        if ($init < 86400) {
//more than hour
            $result = $hours . ' ' . __('hour') . ' ' . $minutes . ' ' . __('minutes');
        } else {
            $hoursLeft = $hours - ($days * 24);
            $result = $days . ' ' . __('days') . ' ' . $hoursLeft . ' ' . __('hour') . ' ' . $minutes . ' ' . __('minutes');
        }
    }
    return ($result);
}

/**
 * Validate a Gregorian date 
 * 
 * @param string $date Date in MySQL format
 * @return bool
 */
function zb_checkDate($date) {
    $explode = explode('-', $date);
    @$year = $explode[0];
    @$month = $explode[1];
    @$day = $explode[2];
    $result = @checkdate($month, $day, $year);
    return ($result);
}

/**
 * Checks is time between some other time ranges?
 * 
 * @param string $fromTime start time (format hh:mm OR hh:mm:ss with seconds)
 * @param string $toTime end time
 * @param string $checkTime time to check
 * @param bool $seconds 
 * 
 * @return bool
 */
function zb_isTimeBetween($fromTime, $toTime, $checkTime, $seconds = false) {
    if ($seconds) {
        $formatPostfix = ':s';
    } else {
        $formatPostfix = '';
    }
    $checkTime = strtotime($checkTime);
    $checkTime = date("H:i" . $formatPostfix, $checkTime);
    $f = DateTime::createFromFormat('!H:i' . $formatPostfix, $fromTime);
    $t = DateTime::createFromFormat('!H:i' . $formatPostfix, $toTime);
    $i = DateTime::createFromFormat('!H:i' . $formatPostfix, $checkTime);
    if ($f > $t) {
        $t->modify('+1 day');
    }
    return ($f <= $i && $i <= $t) || ($f <= $i->modify('+1 day') && $i <= $t);
}

/**
 * Checks is date between some other date ranges?
 * 
 * @param string $fromDate start date (format Y-m-d)
 * @param string $toDate end date
 * @param string $checkDate date to check
 * @param bool $seconds 
 * 
 * @return bool
 */
function zb_isDateBetween($fromDate, $toDate, $checkDate) {
    $result = false;
    $fromDate = strtotime($fromDate);
    $toDate = strtotime($toDate);
    $checkDate = strtotime($checkDate);
    $checkDate = date("Y-m-d", $checkDate);
    $checkDate = strtotime($checkDate);
    if ($checkDate >= $fromDate AND $checkDate <= $toDate) {
        $result = true;
    }
    return($result);
}

/**
 * Checks is timestamp between some other time ranges?
 * 
 * @param int $fromTime start time
 * @param int $toTime end time
 * @param int $checkTime time to check
 * 
 * @return bool
 */
function zb_isTimeStampBetween($fromTime, $toTime, $checkTime) {
    $result = false;
    if ($checkTime >= $fromTime AND $checkTime <= $toTime) {
        $result = true;
    }
    return ($result);
}
