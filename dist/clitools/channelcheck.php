<?php
set_time_limit(0);

function showHelp() {
    $today = date("Y-m-d");
    $help = '╔══════════════════════════════════════════════════════════════════════════════╗' . PHP_EOL;
    $help .= '║                    🐺 WolfRecorder Channel Check 🔍                         ║' . PHP_EOL;
    $help .= '╚══════════════════════════════════════════════════════════════════════════════╝' . PHP_EOL;
    $help .= 'Validates video chunks in channel directory for specific date.' . PHP_EOL . PHP_EOL;
    $help .= 'Usage:' . PHP_EOL;
    $help .= '    php channelcheck.php <channel_dir> <date>' . PHP_EOL . PHP_EOL;
    $help .= 'Arguments:' . PHP_EOL;
    $help .= '    channel_dir - Channel directory path' . PHP_EOL;
    $help .= '    date        - Target date in YYYY-MM-DD format' . PHP_EOL . PHP_EOL;
    $help .= 'Example:' . PHP_EOL;
    $help .= '    📁 Check channel recordings:' . PHP_EOL;
    $help .= '    php channelcheck.php /wrstorage/ab4k8dj2m5n/ ' . $today . PHP_EOL;
    $help .= '╔══════════════════════════════════════════════════════════════════════════════╗' . PHP_EOL;
    $help .= '╚══════════════════════════════════════════════════════════════════════════════╝';
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

function checkVideoChunk($filePath) {
    $command = "ffprobe -v error -select_streams v:0 -show_entries stream=width,height,duration,codec_name -of json " . 
              escapeshellarg($filePath) . " 2>/dev/null";
    $output = shell_exec($command);
    $info = json_decode($output, true);
    
    if (empty($info) || empty($info['streams'])) {
        return false;
    }
    
    return true;
}

function validateFiles($inputDir, $startTimestamp, $endTimestamp) {
    $files = scandir($inputDir);
    if (!$files) {
        die("❌ Error: Cannot read directory $inputDir" . PHP_EOL);
    }
    
    print("🔍 Checking files between " . date('Y-m-d H:i:s', $startTimestamp) . " and " . date('Y-m-d H:i:s', $endTimestamp) . PHP_EOL);
    
    $totalFiles = 0;
    $validFiles = 0;
    $corruptedFiles = array();
    
    foreach ($files as $file) {
        $filePath = $inputDir . DIRECTORY_SEPARATOR . $file;
        if (is_file($filePath) && pathinfo($filePath, PATHINFO_EXTENSION) === 'mp4') {
            $timestamp = (int) pathinfo($filePath, PATHINFO_FILENAME);
            if ($timestamp >= $startTimestamp && $timestamp <= $endTimestamp) {
                $totalFiles++;
                print("🔍 Checking: $file... ");
                
                if (checkVideoChunk($filePath)) {
                    print("✅ OK" . PHP_EOL);
                    $validFiles++;
                } else {
                    print("❌ CORRUPTED" . PHP_EOL);
                    $corruptedFiles[] = $file;
                }
            }
        }
    }
    
    print(PHP_EOL . "📊 Summary:" . PHP_EOL);
    print("Total chunks: $totalFiles" . PHP_EOL);
    print("Valid chunks: $validFiles" . PHP_EOL);
    print("Corrupted chunks: " . count($corruptedFiles) . PHP_EOL);
    
    if (!empty($corruptedFiles)) {
        print(PHP_EOL . "⚠️ Corrupted files:" . PHP_EOL);
        foreach ($corruptedFiles as $file) {
            print("   - $file" . PHP_EOL);
        }
    }
    
    return count($corruptedFiles) === 0;
}

function main() {
    global $argv, $argc;
    
    if ($argc != 3) {
        showHelp();
    }

    $channelDir = $argv[1];
    if (!is_dir($channelDir)) {
        die("❌ Error: Directory $channelDir does not exist" . PHP_EOL);
    }

    $targetDate = $argv[2];
    list($startTimestamp, $endTimestamp) = getTimestampRange($targetDate);
    
    $startTime = microtime(true);
    $isValid = validateFiles($channelDir, $startTimestamp, $endTimestamp);
    $executionTime = round(microtime(true) - $startTime, 2);
    
    print(PHP_EOL . "⏱️ Check completed in {$executionTime}s" . PHP_EOL);
    
    if (!$isValid) {
        print("⚠️ Channel check completed with issues" . PHP_EOL);
        exit(1);
    } else {
        print("✅ All chunks are valid" . PHP_EOL);
        exit(0);
    }
}

main();
