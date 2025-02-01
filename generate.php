<?php
header('Content-Type: application/json; charset=UTF-8');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (file_exists('config.php')) {
    $config = include('config.php');
} else {
    $config['api_key_open_ai'] = null;
}

$IA_USED = $config['ia_used'];
$API_KEY = $IA_USED === 'gemini' ? $config['api_key_gemini'] : $config['api_key_open_ai'];

$MODEL = $IA_USED === 'gemini' ? ($config['gemini_model'] ?? 'gemini-1.5-flash-latest') : 'gpt-4o-mini';
$buildsDir = __DIR__ . '/builds';

if (empty($API_KEY)) {
    serveRandomBuild($buildsDir);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$description = $data['description'] ?? '';

if (empty($description)) {
    respondWithError('No description provided');
    exit;
}

$prompt = "Génère une page HTML complète avec CSS et JavaScript basée sur cette description : {$description}. Ne renvoie que le code HTML propre, sans explications, ni balises markdown.";
$response = getGeneratedContent($prompt, $API_KEY, $MODEL, $IA_USED);

if (!$response) {
    respondWithError('Failed to generate content');
    exit;
}

if (!is_dir($buildsDir)) {
    mkdir($buildsDir, 0775, true);
}

$buildFileName = 'build_' . time() . '.html';
file_put_contents($buildsDir . '/' . $buildFileName, $response);

echo json_encode(['links' => ['/builds/' . $buildFileName]]);

function getGeneratedContent($prompt, $API_KEY, $MODEL, $IA_USED) {
    $apiUrl = $IA_USED === 'gemini'
        ? "https://generativelanguage.googleapis.com/v1beta/models/$MODEL:generateContent?key=$API_KEY"
        : 'https://api.openai.com/v1/chat/completions';

    $postData = $IA_USED === 'gemini'
        ? json_encode(['contents' => [['role' => 'user', 'parts' => [['text' => $prompt]]]]])
        : json_encode(['model' => $MODEL, 'messages' => [['role' => 'user', 'content' => $prompt]], 'max_tokens' => 10000]);

    $headers = ['Content-Type: application/json'];
    if ($IA_USED === 'openai') {
        $headers[] = 'Authorization: Bearer ' . $API_KEY;
    }

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    curl_close($ch);

    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);
    $content = $IA_USED === 'gemini'
        ? $data['candidates'][0]['content']['parts'][0]['text'] ?? ''
        : $data['choices'][0]['message']['content'] ?? '';

    return cleanGeneratedContent($content);
}

function cleanGeneratedContent($content) {
    return preg_replace('/^```(html|json)?|```$/m', '', trim($content));
}

function respondWithError($errorMessage) {
    echo json_encode(['error' => $errorMessage]);
}

function serveRandomBuild($buildsDir) {
    $files = glob($buildsDir . '/*.html');
    if ($files) {
        $randomFile = $files[array_rand($files)];
        echo json_encode(['links' => ['/builds/' . basename($randomFile)]]);
    } else {
        respondWithError('No builds available.');
    }
}
?>
