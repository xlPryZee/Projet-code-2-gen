<?php
// generate.php

$config = include('config.php');

// Retrieve the API key from the environment variable
$API_KEY = $config['api_key_open_ai']; // Replace with your API key
$MODEL = 'gpt-4o-mini'; // The model to use
$buildsDir = __DIR__ . '/builds'; // Directory where generated files are stored

// If the API key is not defined, retrieve a random file from the builds directory
if (!$API_KEY) {
    // Check if the builds directory exists and contains files
    if (is_dir($buildsDir)) {
        $files = glob($buildsDir . '/*.html'); // Retrieve all HTML files from the builds directory

        if ($files && count($files) > 0) {
            // Choose a random file
            $randomFile = $files[array_rand($files)];
            // Return the link of the random file to the frontend
            $fileLink = '/builds/' . basename($randomFile);
            echo json_encode(['link' => $fileLink]);
        } else {
            // If no files are found in the builds directory
            echo json_encode(['error' => 'No builds available.']);
        }
    } else {
        // If the builds directory does not exist
        echo json_encode(['error' => 'Builds directory not found.']);
    }
    exit;
}

// Read the data from the request body
$data = json_decode(file_get_contents('php://input'), true);
$description = $data['description'] ?? '';

if (empty($description)) {
    echo json_encode(['error' => 'No description provided']);
    exit;
}

// Increment a build number
if (!file_exists('build_count.txt')) {
    file_put_contents('build_count.txt', 0);
}
$buildNumber = file_get_contents('build_count.txt');
$buildNumber = intval($buildNumber) + 1;
file_put_contents('build_count.txt', $buildNumber);

// Build the prompt to generate a complete HTML file with inline CSS and JS
$prompt = "You are a professional web developer. Your task is to generate a complete and valid HTML document based on the following user description: " . $description . ".\n";
$prompt .= "Please structure the HTML file as follows:\n";
$prompt .= "- Include a <style> tag inside the <head> section for any required CSS styles.\n";
$prompt .= "- Include a <script> tag just before the closing </body> tag for any necessary JavaScript functionality.\n";
$prompt .= "The HTML file should be clean, responsive, and follow best practices in modern web development.\n\n";
$prompt .= "Return the result in a JSON format with the following structure:\n";
$prompt .= "```json\n";
$prompt .= "{\n";
$prompt .= '  "files": [\n';
$prompt .= '    {\n';
$prompt .= '      "file_title": "index.html",\n';
$prompt .= '      "content": "<HTML content>"\n';
$prompt .= "    }\n";
$prompt .= "  ]\n";
$prompt .= "}\n";
$prompt .= "```";
$prompt .= "\nMake sure that the 'content' field contains the complete HTML, CSS, and JS all in a single HTML file.";

// Prepare the request data for GPT-4
$postData = json_encode([
    'model' => $MODEL,
    'messages' => [
        [
            'role' => 'user',
            'content' => $prompt
        ]
    ],
    'max_tokens' => 10000
]);

// Initialize cURL for the API call to GPT-4
$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $API_KEY
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

// Execute the request and get the response
$response = curl_exec($ch);
curl_close($ch);
$response = json_decode($response, true);

// Check that the response contains text
if (!isset($response['choices'][0]['message']['content'])) {
    echo json_encode(['error' => 'No response from the API']);
    exit;
}

// Clean the response content to remove Markdown tags (```json```)
$cleanedResponse = $response['choices'][0]['message']['content'];
$cleanedResponse = trim($cleanedResponse, "```json");
$cleanedResponse = trim($cleanedResponse, "```");

// Decode the cleaned response into JSON
$generatedFiles = json_decode($cleanedResponse, true);

// Check that the JSON contains the expected structure
if (!isset($generatedFiles['files'])) {
    echo json_encode(['error' => 'Invalid format received from the API']);
    //log error
    $error = 'Invalid format received from the API';
    file_put_contents('error_log.txt', json_encode([
        'error' => 'Invalid format received from the API',
        'response' => $cleanedResponse]
    ));
    
    exit;
}

// Check if the builds directory exists, otherwise create it
if (!is_dir($buildsDir)) {
    mkdir($buildsDir, 0775, true);  // Create the directory with 775 permissions
}

// Iterate over the generated files in the JSON and write them to disk
$fileLinks = [];

foreach ($generatedFiles['files'] as $file) {
    // Generate the full path of the file
    $filename = $buildsDir . "/build_$buildNumber" . '_' . $file['file_title'];
    
    // Write the content to the file
    $result = file_put_contents($filename, $file['content']);
    
    // Check if the file write was successful
    if ($result === false) {
        echo json_encode(['error' => 'Failed to write file: ' . $filename]);
        exit;
    }
    
    // Store the link of the generated file
    $fileLinks[] = "/builds/build_$buildNumber" . '_' . $file['file_title'];
}

// Return the links of the generated files in JSON format
echo json_encode(['links' => $fileLinks]);
?>
