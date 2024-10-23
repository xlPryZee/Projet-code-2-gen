<?php
// generate.php

// $config = include('config.php');

// Retrieve the API key from the config file
$API_KEY = null;//$config['api_key_open_ai'];
$MODEL = 'gpt-4o-mini'; // The model to use
$buildsDir = __DIR__ . '/builds'; // Directory where generated files are stored

// If the API key is not defined, fallback to serving a random build
if (empty($API_KEY)) {
    serveRandomBuild($buildsDir);
    exit;
}

// Read the data from the request body
$data = json_decode(file_get_contents('php://input'), true);
$description = $data['description'] ?? '';

if (empty($description)) {
    respondWithError('No description provided');
    exit;
}

// Increment a build number
$buildNumber = incrementBuildCount('build_count.txt');

// Create a prompt to generate a complete HTML file with inline CSS and JS
$prompt = buildPrompt($description);

// Send the prompt to OpenAI's API and get a response
$generatedFiles = getGeneratedFiles($prompt, $API_KEY, $MODEL);

if (!$generatedFiles) {
    respondWithError('Invalid format received from the API');
    exit;
}

// Check if the builds directory exists, otherwise create it
if (!is_dir($buildsDir)) {
    mkdir($buildsDir, 0775, true);  // Create the directory with 775 permissions
}

// Write the generated files to disk and get their links
$fileLinks = saveGeneratedFiles($generatedFiles, $buildNumber, $buildsDir);

if (empty($fileLinks)) {
    respondWithError('Failed to write generated files');
    exit;
}

// Return the links of the generated files in JSON format
echo json_encode(['links' => $fileLinks]);

/**
 * Serves a random HTML file from the builds directory if available.
 */
function serveRandomBuild($buildsDir) {
    if (is_dir($buildsDir)) {
        $files = glob($buildsDir . '/*.html');
        if ($files && count($files) > 0) {
            $randomFile = $files[array_rand($files)];
            $fileLink = '/builds/' . basename($randomFile);
            echo json_encode(['links' => [$fileLink]]);
        } else {
            respondWithError('No builds available.');
        }
    } else {
        respondWithError('Builds directory not found.');
    }
}

/**
 * Increments the build count stored in a file.
 */
function incrementBuildCount($filePath) {
    if (!file_exists($filePath)) {
        file_put_contents($filePath, 0);
    }
    $buildNumber = intval(file_get_contents($filePath)) + 1;
    file_put_contents($filePath, $buildNumber);
    return $buildNumber;
}

/**
 * Builds the prompt to be sent to OpenAI's API.
 */
function buildPrompt($description) {
    return "You are a professional web developer. Your task is to generate a complete and valid HTML document based on the following user description: " . $description . ".\n" .
           "Please structure the HTML file as follows:\n" .
           "- Include a <style> tag inside the <head> section for any required CSS styles.\n" .
           "- Include a <script> tag just before the closing </body> tag for any necessary JavaScript functionality.\n" .
           "The HTML file should be clean, responsive, and follow best practices in modern web development.\n\n" .
           "Return the result in a JSON format with the following structure:\n" .
           "```json\n" .
           "{\n" .
           '  "files": [\n' .
           '    {\n' .
           '      "file_title": "index.html",\n' .
           '      "content": "<HTML content>"\n' .
           "    }\n" .
           "  ]\n" .
           "}\n" .
           "```\nMake sure that the 'content' field contains the complete HTML, CSS, and JS all in a single HTML file.";
}

/**
 * Sends the prompt to OpenAI's API and retrieves the generated files.
 */
function getGeneratedFiles($prompt, $API_KEY, $MODEL) {
    $postData = json_encode([
        'model' => $MODEL,
        'messages' => [['role' => 'user', 'content' => $prompt]],
        'max_tokens' => 10000
    ]);

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $API_KEY
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

    $response = curl_exec($ch);
    curl_close($ch);

    $response = json_decode($response, true);

    if (!isset($response['choices'][0]['message']['content'])) {
        return null;
    }

    // Clean and decode the response content to remove Markdown tags
    $cleanedResponse = trim($response['choices'][0]['message']['content'], "```json");
    $cleanedResponse = trim($cleanedResponse, "```");

    return json_decode($cleanedResponse, true);
}

/**
 * Saves the generated files to disk and returns their links.
 */
function saveGeneratedFiles($generatedFiles, $buildNumber, $buildsDir) {
    $fileLinks = [];

    if (!isset($generatedFiles['files'])) {
        logError('Invalid format received from the API', $generatedFiles);
        return [];
    }

    foreach ($generatedFiles['files'] as $file) {
        $filename = $buildsDir . "/build_$buildNumber" . '_' . $file['file_title'];
        $result = file_put_contents($filename, $file['content']);

        if ($result === false) {
            logError('Failed to write file', ['filename' => $filename]);
            return [];
        }

        $fileLinks[] = "/builds/build_$buildNumber" . '_' . $file['file_title'];
    }

    return $fileLinks;
}

/**
 * Logs errors to a file for debugging purposes.
 */
function logError($message, $context = []) {
    $logEntry = [
        'error' => $message,
        'context' => $context,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    file_put_contents('error_log.txt', json_encode($logEntry) . "\n", FILE_APPEND);
}

/**
 * Sends an error response in JSON format.
 */
function respondWithError($errorMessage) {
    echo json_encode(['error' => $errorMessage]);
}
?>
