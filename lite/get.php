<?php
// Enable error reporting
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

// Include the functions file
require "functions.php";

// Fetch the JSON data from the API and decode it into an associative array
$sourcesArray = json_decode(file_get_contents("channels.json"), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die("Error decoding JSON: " . json_last_error_msg());
}

// Count the total number of sources
$totalSources = count($sourcesArray);
$configsList = [];

echo "Fetching Configs\n";
$tempCounter = 1;

// Loop through each source in the sources array
foreach ($sourcesArray as $source => $types) {

    // Calculate and print progress
    $percentage = ($tempCounter / $totalSources) * 100;
    echo "\rProgress: [" . str_repeat("=", $tempCounter) . str_repeat(" ", $totalSources - $tempCounter) . "] $percentage%";
    $tempCounter++;

    // Fetch the data from the source
    $tempData = file_get_contents("https://t.me/s/$source");
    if ($tempData === false) {
        echo "\nFailed to fetch data for source: $source\n";
        continue;
    }

    $type = implode("|", $types);
    $tempExtract = extractLinksByType($tempData, $type);
    if (!is_null($tempExtract)) {
        $configsList[$source] = $tempExtract;
    }
}

// Initialize variables for processing
$finalOutput = [];
$locationBased = [];
$needleArray = ["amp%3B"];
$replaceArray = [""];

// Define the hash and IP keys for each type of configuration
$configsHash = [
    "vmess" => "ps",
    "vless" => "hash",
    "trojan" => "hash",
    "tuic" => "hash",
    "hy2" => "hash",
    "ss" => "name",
];
$configsIp = [
    "vmess" => "add",
    "vless" => "hostname",
    "trojan" => "hostname",
    "tuic" => "hostname",
    "hy2" => "hostname",
    "ss" => "server_address",
];

echo "\nProcessing Configs\n";
$totalSources = count($configsList);

// Loop through each source in the configs list
foreach ($configsList as $source => $configs) {
    $totalConfigs = count($configs);

    echo "\n$tempSource/$totalSources\n";

    // Loop through each config in the configs array
    $limitKey = max(0, count($configs) - 2);
    $tempCounter = 1;
    foreach (array_reverse($configs) as $key => $config) {

        // Calculate and print progress
        $percentage = ($tempCounter / $totalConfigs) * 100;
        echo "\rProgress: [" . str_repeat("=", $tempCounter) . str_repeat(" ", $totalConfigs - $tempCounter) . "] $percentage%";
        $tempCounter++;

        // If the config is valid and within the limit
        if (is_valid($config) && $key >= $limitKey) {
            $type = detect_type($config);
            $configHash = $configsHash[$type];
            $configIp = $configsIp[$type];
            $decodedConfig = configParse(explode("<", $config)[0]);
            $configLocation = ip_info($decodedConfig[$configIp])->country ?? "XX";
            $configFlag = ($configLocation === "XX") ? "❔" : (($configLocation === "CF") ? "🚩" : getFlags($configLocation));
            $isEncrypted = isEncrypted($config) ? "🟢" : "🔴";

            $decodedConfig[$configHash] = $configFlag . $configLocation . " | " . $isEncrypted . " | " . $type . " | @" . $source . " | " . strval($key);
            $encodedConfig = reparseConfig($decodedConfig, $type);

            if (substr($encodedConfig, 0, 10) !== "ss://Og==@") {
                $cleanedConfig = str_replace($needleArray, $replaceArray, $encodedConfig);
                $finalOutput[] = $cleanedConfig;
                $locationBased[$configLocation][] = $cleanedConfig;
            }
        }
    }
    $tempSource++;
}

// Clean up and prepare output directories
deleteFolder("subscriptions/location/normal");
deleteFolder("subscriptions/location/base64");
mkdir("subscriptions/location/normal", 0777, true);
mkdir("subscriptions/location/base64", 0777, true);

// Loop through each location in the location-based array
foreach ($locationBased as $location => $configs) {
    $tempConfig = urldecode(implode("\n", $configs));
    $base64TempConfig = base64_encode($tempConfig);

    file_put_contents("subscriptions/location/normal/$location", $tempConfig);
    file_put_contents("subscriptions/location/base64/$location", $base64TempConfig);
}

// Write the final output to a file
file_put_contents("config.txt", implode("\n", $finalOutput));

echo "\nGetting Configs Done!\n";
