<?php
header('Content-Type: application/json; charset=UTF-8');

// Désactiver les erreurs pour éviter les problèmes de parsing JSON
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Chargement de la configuration
$config = file_exists('config.php') ? include('config.php') : [];

// Gestion des clés API de manière sécurisée
$IA_USED = isset($config['ia_used']) ? $config['ia_used'] : 'openai';
$API_KEY = $IA_USED === 'gemini' ? ($config['api_key_gemini'] ?? null) : ($config['api_key_open_ai'] ?? null);
$MODEL = $IA_USED === 'gemini' ? ($config['gemini_model'] ?? 'gemini-1.5-flash-latest') : 'gpt-4o-mini';

// Dossier des builds
$buildsDir = __DIR__ . '/builds';

// Mode démo si aucune clé API n'est configurée
if (empty($API_KEY)) {
    serveRandomBuild($buildsDir);
    exit;
}

// Lecture des données JSON envoyées depuis le frontend
$data = json_decode(file_get_contents('php://input'), true);
$description = $data['description'] ?? '';

if (empty($description)) {
    respondWithError('No description provided.');
    exit;
}

// Préparation du prompt pour l'IA
$prompt = "Génère une page HTML complète avec CSS et JavaScript basée sur cette description : {$description}. Ne renvoie que le code HTML propre, sans explications, ni balises markdown.";

// Appel à l'API pour générer le contenu
$response = getGeneratedContent($prompt, $API_KEY, $MODEL, $IA_USED);

if (!$response) {
    respondWithError('Failed to generate content.');
    exit;
}

// Sauvegarde du code généré dans le dossier des builds
if (!is_dir($buildsDir)) {
    mkdir($buildsDir, 0775, true);
}

$buildFileName = 'build_' . time() . '.html';
file_put_contents($buildsDir . '/' . $buildFileName, $response);

// Réponse JSON valide
echo json_encode(['links' => ["/builds/{$buildFileName}"]]);

// Fonction pour générer du contenu via l'API
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
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        return null;
    }

    $data = json_decode($response, true);
    $content = $IA_USED === 'gemini'
        ? ($data['candidates'][0]['content']['parts'][0]['text'] ?? '')
        : ($data['choices'][0]['message']['content'] ?? '');

    return cleanGeneratedContent($content);
}

// Nettoyage du contenu généré
function cleanGeneratedContent($content) {
    return preg_replace('/^```(html|json)?|```$/m', '', trim($content));
}

// Réponse JSON en cas d'erreur
function respondWithError($errorMessage) {
    echo json_encode(['error' => $errorMessage]);
    exit;
}

// Mode démo : renvoie un fichier HTML aléatoire
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
