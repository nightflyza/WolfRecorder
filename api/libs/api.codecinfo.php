<?php

class CodecInfo {
    
    /**
     * Contains CLI abstraction instance
     *
     * @var object
     */
    protected $cliFF='';

    
    public function __construct() {
      $this->initCliFF();
    }

    /**
     * inits CLI abstraction instance of cliFF class
     *
     * @return void
     */
    protected function initCliFF() {
        $this->cliFF=new cliFF();
    }

    /**
     * Parses fps string to float
     *
     * @param string $fpsString fps string
     * 
     * @return float
     */
    protected function parseFps($fpsString) {
        $result=0;
        if (strpos($fpsString, '/') !== false) {
            list($num, $den) = explode('/', $fpsString);
            $num = floatval($num);
            $den = floatval($den);
            $result= ($den != 0) ? $num / $den : 0;
        } else {
            $result=floatval($fpsString);
        }
        $result=round($result, 3);
        return($result);
    }

    /**
     * Converts video dimensions to approximate megapixels
     *
     * @param int $width
     * @param int $height
     * 
     * @return float
     */
    protected function dimensionsToMP($width, $height) {
        $result=round(($width * $height) / 1000000, 2);
        return($result);
    }

    /**
     * Returns existing video file data as filename, size, width, height, codec, fullcodec, fpsDeclared, fpsReal, duration, bitrate, format
     *
     * @param string $videoPath path to video file
     * 
     * @return array
     */
    public function getFileData($videoPath) {
        $result=array();
        if (file_exists($videoPath)) {
            $command=$this->cliFF->getFFprobePath().' '.$this->cliFF->getCodecInfoOpts().' '.escapeshellarg($videoPath);
            $output = shell_exec($command);
            $info = @json_decode($output, true);
        
            if ($info) {
            $videoStream = null;
            foreach ($info['streams'] as $stream) {
                if ($stream['codec_type'] === 'video') {
                    $videoStream = $stream;
                    break;
                }
            }
        
            if ($videoStream) {
                $fpsDeclared= ($videoStream['r_frame_rate']) ? $this->parseFps($videoStream['r_frame_rate']) : 0;
                $fpsAvg= ($videoStream['avg_frame_rate']) ? $this->parseFps($videoStream['avg_frame_rate']) : 0;
                
                $result=array(
                    'filename' => @$info['format']['filename'],
                    'size' => @$info['format']['size'],
                    'width' => @$videoStream['width'],
                    'height' => @$videoStream['height'],
                    'mpix' => $this->dimensionsToMP(@$videoStream['width'], @$videoStream['height']),
                    'codec' => @$videoStream['codec_name'],
                    'fullcodec' => @$videoStream['codec_long_name'],
                    'fpsReal' => $fpsAvg,
                    'fpsDeclared' => $fpsDeclared,
                    'duration' => @$info['format']['duration'],
                    'bitrate' => @$info['format']['bit_rate'],
                    'format' => @$info['format']['format_name'],
                );    
            }
            }
    }
        return($result);
    }

      



}