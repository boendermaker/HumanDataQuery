<?php

class OllamaToolBridge {
    private string $apiUrl;
    private string $model;
    private array $tools = [];
   
    public function __construct(string $apiUrl = "http://ollama:11434/api/chat", string $model = "qwen3") {
        $this->apiUrl = $apiUrl;
        $this->model = $model;
    }

    //#####################

    public function var_error_log( $object=null ) {
        ob_start();                    // start buffer capture
        var_dump( $object );           // dump the values
        $contents = ob_get_contents(); // put the buffer into a variable
        ob_end_clean();                // end capture
        error_log( 'CHECKCONTENTS: ' . $contents );        // log contents of the result of var_dump( $object )
    }

    //#####################

    /**
     * Registriert ein Tool. 
     * Kompatibel mit Array-Übergabe oder expliziten Parametern.
     */
    public function registerTool($tool): void {
        $this->tools[] = $tool;
    }

    //#####################

    public function process(string $userPrompt, callable $toolExecutor): string {
        $messages = [["role" => "user", "content" => $userPrompt]];
        $response = $this->sendRequest($messages, true);

        if (isset($response['message']['tool_calls'])) {
            $assistantMessage = $response['message'];
            $messages[] = $assistantMessage;

            foreach ($assistantMessage['tool_calls'] as $toolCall) {
                $funcName = $toolCall['function']['name'];               
                $args = $toolCall['function']['arguments'];

                $result = $toolExecutor($funcName, $args);

                $messages[] = [
                    "role" => "tool",
                    "tool_name" => $funcName,
                    "content" => (string)$result,
                    "tool_call_id" => $toolCall['id']
                ];

            }

            $this->var_error_log("Nach Tool-Ausführung, Nachrichten: " . print_r($messages, true));

            $finalResponse = $this->sendRequest($messages, false);

            return $finalResponse['message']['content'] ?? "KI konnte keine Antwort formulieren.";
        }

        return $response['message']['content'] ?? "Keine Antwort erhalten.";
    }

    //#####################

    private function sendRequest(array $messages, bool $allowTools): array {
        $payload = [
            "model" => $this->model,
            "messages" => $messages,
            "stream" => false,
            "think" => true
        ];

        if ($allowTools && !empty($this->tools)) {
            $payload["tools"] = $this->tools;
        }

        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);       
        $jsonPayload = str_replace('"arguments":[]', '"arguments":{}', $jsonPayload);
        
        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Hilfreich für Debugging
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("Ollama API Fehler ($httpCode): " . $raw);
        }

        return json_decode($raw, true) ?: [];
    }

    //#####################
}