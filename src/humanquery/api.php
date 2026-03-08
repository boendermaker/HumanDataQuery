<?php


class LamaQueryBuilder {

    private string $ollamaUrl;
    private string $ollamaModel;

    public function __construct(
        string $ollamaModel = 'llama3', 
        string $url = 'http://ollama:11434/api/generate'
    ) {
        $this->ollamaUrl = $url;
        $this->ollamaModel = $ollamaModel;
    }

    public function transformUserPrompt(string $prompt): array {
        return $this->requestLlama($prompt);
    }

    private function requestLlama(string $prompt): array {
    
        $systemPrompt = "Ignoriere alle vorherigen Anweisungen.
        Extrahiere Startzeit und Endzeit, als auch weitere Kategorien für Daten aus der folgenden Benutzereingabe: '$prompt'.
        Die aktuelle Uhrzeit ist " . date('dd.mm.yyyy H:i:s') . ".
        Schreibe die Kategorien in english und lower case in das Feld 'data_type'.
        Filtere vulgäre Begriffe und ignoriere sie.
        Übersetze die Kategorien in englische Begriffe, z.B. 'Temperatur' zu 'temperature', 'Luftfeuchtigkeit' zu 'humidity', etc.
        Nutze für die Kategorien keine Synonyme, sondern die standardisierten englischen Begriffe.
        Gib Start und Endzeit im ISO 8601 Format zurück. 
        Wenn die Eingabe nur eine Zeit enthält, gib diese als Startzeit zurück und lasse die Endzeit leer.
        Wenn die Eingabe relative Zeitangaben enthält (z.B. 'letzte 10 Stunden'), berechne die Startzeit basierend auf der aktuellen Uhrzeit und gib sie zurück.
        Benutze folgendes JSON-Format für die Ausgabe:
        {
            \"start_time\": \"\",
            \"end_time\": \"\",
            \"data_type\": \"\"
        }.
        Wenn keine Zeiten gefunden werden, gib ein leeres JSON-Objekt zurück.
        ";

        //echo "System Prompt: " . $systemPrompt . "\n\n";
    
        $payload = [
            "model" => $this->ollamaModel,
            "prompt" => $systemPrompt . "\nInput: " . $prompt,
            "stream" => false,
            "format" => "json", // Ollama unterstützt JSON-Mode
            "options" => [
                "temperature" => 0,          // <--- Hier liegt der Schlüssel
                "top_p" => 0.1,              // Verringert die Auswahl der Wörter weiter
                "repeat_penalty" => 1.2      // Verhindert, dass das Modell sich "festfährt"
            ]
        ];

        $payload = json_encode($payload);

        $ch = curl_init($this->ollamaUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Wichtig für KI-Rechenzeit

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("Ollama cURL Error: " . $error);
            return [];
        }

        $jsonResponse = json_decode($response, true);


        //var_dump($response); 

        $filterData = json_decode($jsonResponse['response'] ?? '{}', true);

        return $filterData;

    }

}


// --- API ENDPUNKT ---
header('Content-Type: application/json');
$input = json_decode(file_get_contents("php://input"), true);
$prompt = $input['prompt'] ?? "Zeige mir die Temperatur der letzten 10 Stunden";

$assistant = new LamaQueryBuilder('llama3', 'http://ollama:11434/api/generate');

echo json_encode($assistant->transformUserPrompt($prompt)); 