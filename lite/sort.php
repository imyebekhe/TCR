<?php
// Enable error reporting
ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(E_ALL);

// Include the functions file
require "functions.php";

// Read the config.txt file and split it into an array by newline
$configsArray = file_get_contents("config.txt");
if ($configsArray === false) {
    die("Error: Unable to read config.txt");
}
$configsArray = explode("\n", $configsArray);

// Initialize an empty array to hold the sorted configurations
$sortArray = [];

// Loop through each configuration in the configsArray
foreach ($configsArray as $config) {
    // Skip empty lines
    if (trim($config) === "") {
        continue;
    }

    // Detect the type of the configuration
    $configType = detect_type($config);

    // Add the configuration to the corresponding array in sortArray
    $sortArray[$configType][] = urldecode($config);

    // If the configuration is of type "vless" and is a reality, add it to the "reality" array
    if ($configType === "vless" && is_reality($config)) {
        $sortArray["reality"][] = urldecode($config);
    }
}

// Loop through each type of configuration in sortArray
foreach ($sortArray as $type => $configs) {
    // Skip empty types
    if (empty($type) || empty($configs)) {
        continue;
    }

    // Join the configurations into a string and add a header
    $tempConfigs = hiddifyHeader("TCR | " . strtoupper($type)) . implode("\n", $configs);

    // Encode the configurations to base64
    $base64TempConfigs = base64_encode($tempConfigs);

    // Write the normal and base64 versions to files
    file_put_contents("subscriptions/xray/normal/$type", $tempConfigs);
    file_put_contents("subscriptions/xray/base64/$type", $base64TempConfigs);
}

// Print "done!" to the console
echo "Sorting Done!";
