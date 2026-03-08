<?php
require_once 'toolbridge.class.php';

$toolFunctions = [
    'get_weather' => function($args) {
        return "In " . ($args['city'] ?? 'unbekannt') . " sind es 21 Grad.";
    },
    'get_time' => function() {
        return "Die aktuelle Uhrzeit ist " . date("H:i") . " Uhr.";
    },
    'get_boilertemperature' => function() {
        return "Die aktuelle Temperatur des Boilers beträgt 75 Grad Celsius.";
    }
];

// --- REST API Handling ---
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);
$prompt = $input['prompt'] ?? null;

if (!$prompt) {
    echo json_encode(["error" => "Prompt fehlt"]);
    exit;
}

// Klasse instanziieren
$bridge = new OllamaToolBridge();

// Tools registrieren (Beschreibungen für die KI)
$bridge->registerTool([
    "type" => "function",
    "function" => [
        "name" => "get_weather",
        "description" => "Gibt die Temperatur einer Stadt zurück",
        "parameters" => [
            "type" => "object",
            "properties" => ["city" => ["type" => "string"]],
            "required" => ["city"]
        ]
    ]
]);

$bridge->registerTool([
    "type" => "function",
    "function" => [
        "name" => "get_time",
        "description" => "Gibt die aktuelle Uhrzeit zurück",
        "parameters" => [
            "type" => "object",
            "properties" => new stdClass(),
            "required" => [],
        ]
    ]
]);

$bridge->registerTool([
    "type" => "function",
    "function" => [
        "name" => "get_boilertemperature",
        "description" => "Gibt die Temperatur des Warmwasser Boilers zurück",
        "parameters" => [
            "type" => "object",
            "properties" => new stdClass(),
            "required" => [],
        ]
    ]
]);

// Verarbeitung starten
$finalAnswer = $bridge->process($prompt, function($name, $args) use ($toolFunctions) {
    if (isset($toolFunctions[$name])) {
        return $toolFunctions[$name]($args);
    }
    return "Funktion nicht gefunden.";
});

echo json_encode([
    "status" => "success",
    "output" => $finalAnswer
]);