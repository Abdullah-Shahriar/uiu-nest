<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$userMessage = $input['message'] ?? '';

if (empty($userMessage)) {
    echo json_encode(['error' => 'Message is required.']);
    exit;
}

// Groq API Details
$apiKey = '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';
$apiUrl = 'https://api.groq.com/openai/v1/chat/completions';

// System Prompt
$systemPrompt = "You are the AI Concierge for 'UIU Nest', a premium student housing platform. 
Your goal is to understand a user's natural language housing requirements and convert them into a structured JSON search query.

The available amenities in our database are exact slugs: 
['wifi', 'ac', 'attached_bath', 'shared_bath', 'furnished', 'balcony', 'parking', 'laundry', 'security', 'cctv', 'study_room', 'rooftop', 'generator', 'lift']

SEMANTIC MAPPING INSTRUCTIONS:
1. Price: If the user mentions a budget/price limit (e.g., 'under 6k', 'budget 5000'), extract it as an integer into 'max_price'.
2. Location/Distance: If the user says 'near UIU', 'close to campus', set the 'sort_by' parameter to 'distance_asc'.
3. Surveillance/Security: If the user asks for 'good surveillance cameras', 'good security', or 'safe', include both 'cctv' and 'security' in the amenities array.
4. Views/Outdoors: If the user asks for a 'good view', 'fresh air', or 'outside space', include 'balcony' (and optionally 'rooftop') in the amenities array.
5. Bathroom requirements: If the user asks for a 'bathtub', 'great bathroom', 'shower', include 'attached_bath' in the amenities array.

RULES:
1. You MUST respond ONLY with a valid JSON object. Do not include any markdown backticks (no ```json), conversational text, or anything else outside the JSON block.

JSON SCHEMA EXPECTED:
{
  \"max_price\": integer or null,
  \"amenities_required\": [array of matching amenity slugs],
  \"sort_by\": \"string (use 'distance_asc' if they want near campus, else null)\",
  \"search_keyword\": \"string or null for general location/name search\",
  \"user_friendly_message\": \"A friendly conversational message acknowledging their request (e.g. 'I'd love to help you find a place near UIU! I'm filtering for properties under 6000 BDT that have great security and a balcony.')\"
}";

$data = [
    'model' => 'llama-3.3-70b-versatile',
    'messages' => [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userMessage]
    ],
    'temperature' => 0.1 // Low temperature for consistent JSON output
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo json_encode(['error' => 'cURL error: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}
curl_close($ch);

$decodedResponse = json_decode($response, true);
if (isset($decodedResponse['choices'][0]['message']['content'])) {
    $aiContent = $decodedResponse['choices'][0]['message']['content'];
    
    // Sometimes LLMs wrap JSON in markdown block even when told not to. Clean it just in case.
    $aiContent = str_replace(['```json', '```'], '', $aiContent);
    $aiContent = trim($aiContent);
    
    // Validate that it's JSON
    $jsonObj = json_decode($aiContent, true);
    if ($jsonObj === null) {
        // Fallback if LLM messed up the format
        echo json_encode([
            'user_friendly_message' => "I had a little trouble understanding that format. Could you try rephrasing your requirements?",
            'max_price' => null,
            'amenities_required' => []
        ]);
        exit;
    }
    
    echo json_encode($jsonObj);
} else {
    echo json_encode(['error' => 'Invalid response from AI API.', 'raw' => $response]);
}
