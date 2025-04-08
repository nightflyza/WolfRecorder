<?php
set_time_limit(0);

function showHelp() {
    $today = date("Y-m-d");
    $help = '+------------------------------------------------------------------------------+' . PHP_EOL;
    $help .= '|                    🐺 WolfRecorder Export Tool 📅                             |' . PHP_EOL;
    $help .= '+------------------------------------------------------------------------------+' . PHP_EOL;
    $help .= 'Exports video chunks for a specific date into a single MP4 file.' . PHP_EOL . PHP_EOL;
    $help .= 'Usage:' . PHP_EOL;
    $help .= '    php dateexport.php <input_dir> <date> <output_file>' . PHP_EOL . PHP_EOL;
    $help .= 'Arguments:' . PHP_EOL;
    $help .= '    input_dir   - Directory containing video chunks' . PHP_EOL;
    $help .= '    date        - Target date in YYYY-MM-DD format' . PHP_EOL;
    $help .= '    output_file - Path to output MP4 file' . PHP_EOL . PHP_EOL;
    $help .= 'Examples:' . PHP_EOL;
    $help .= '    # Export to current directory:' . PHP_EOL;
    $help .= '    php dateexport.php /wrstorage/ab4k8dj2m5n/ ' . $today . ' ./camera.mp4' . PHP_EOL . PHP_EOL;
    $help .= '    # Export with absolute path:' . PHP_EOL;
    $help .= '    php dateexport.php /wrstorage/n5m2k8w3v6y/ ' . $today . ' /home/user/videos/export.mp4' . PHP_EOL;
    $help .= '+------------------------------------------------------------------------------+';
    die($help . PHP_EOL);
}

function getTimestampRange($dateStr) {
    $startTime = strtotime($dateStr . ' 00:00:00');
    $endTime = strtotime($dateStr . ' 23:59:59');
    if ($startTime === false || $endTime === false) {
        die("❌ Error: Invalid date format. Use YYYY-MM-DD" . PHP_EOL);
    }
    return array($startTime, $endTime);
}

function filterFiles($inputDir, $startTimestamp, $endTimestamp) {
    $filesToConcat = array();
    $files = scandir($inputDir);
    
    print("🔍 Searching for files between " . date('Y-m-d H:i:s', $startTimestamp) . " and " . date('Y-m-d H:i:s', $endTimestamp) . PHP_EOL);
    
    foreach ($files as $file) {
        $filePath = $inputDir . DIRECTORY_SEPARATOR . $file;
        if (is_file($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'mp4') {
            $timestamp = (int) pathinfo($filePath, PATHINFO_FILENAME);
            if ($timestamp >= $startTimestamp && $timestamp <= $endTimestamp) {
                $filesToConcat[] = $filePath;
                print("✅ Including: $file (" . date('Y-m-d H:i:s', $timestamp) . ")" . PHP_EOL);
            }
        }
    }
    return $filesToConcat;
}

function createConcatFile($filesToConcat, $concatFilePath) {
    $file = fopen($concatFilePath, 'w');
    if ($file === false) {
        die("❌ Error: Cannot create concat file $concatFilePath" . PHP_EOL);
    }
    foreach ($filesToConcat as $filePath) {
        fwrite($file, "file '$filePath'" . PHP_EOL);
    }
    fclose($file);
    print("📝 Created concat file with " . count($filesToConcat) . " entries" . PHP_EOL);
}

function concatenateVideos($concatFilePath, $outputFile) {
    print("🎬 Starting FFmpeg concatenation..." . PHP_EOL);
    $command = "ffmpeg -y -f concat -safe 0 -i " . escapeshellarg($concatFilePath) . " -c copy " . escapeshellarg($outputFile) . " 2>/dev/null";
    system($command, $returnCode);
    if ($returnCode !== 0) {
        die("❌ Error: FFmpeg concatenation failed" . PHP_EOL);
    }
}

function main() {
    $startTime = microtime(true);
    global $argv, $argc;
    if ($argc != 4) {
        showHelp();
    }

    $inputDir = $argv[1];
    if (!is_dir($inputDir)) {
        die("❌ Error: Directory $inputDir does not exist" . PHP_EOL);
    }

    $targetDate = $argv[2];
    list($startTimestamp, $endTimestamp) = getTimestampRange($targetDate);

    $outputFile = $argv[3];
    $outputDir = dirname($outputFile);
    if (!is_dir($outputDir)) {
        die("❌ Error: Output directory does not exist: $outputDir" . PHP_EOL);
    }
    if (!is_writable($outputDir)) {
        die("❌ Error: Output directory is not writable: $outputDir" . PHP_EOL);
    }

    $filesToConcat = filterFiles($inputDir, $startTimestamp, $endTimestamp);
    if (empty($filesToConcat)) {
        die("❌ Error: No files found for date $targetDate in $inputDir" . PHP_EOL);
    }

    sort($filesToConcat);

    $concatFilePath = '/tmp/concat_' . time() . '.txt';
    createConcatFile($filesToConcat, $concatFilePath);

    concatenateVideos($concatFilePath, $outputFile);

    unlink($concatFilePath);
    $executionTime = round(microtime(true) - $startTime, 2);
    print("✨ Export complete: $outputFile (took {$executionTime}s)" . PHP_EOL);
}

main();
