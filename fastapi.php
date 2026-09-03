<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Get prompt history from FastAPI.
 *
 * @param string $id
 * @return array
 */
function block_prompt_get_history_from_fastapi(string $id): array {

    $apiurl = 'http://192.168.137.140:8000/api/prompt/entry_prompt/' .
        rawurlencode($id);

    $ch = curl_init($apiurl);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT => 10,
    ]);

    $response = curl_exec($ch);

    if ($response === false) {
        curl_close($ch);
        return [];
    }

    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($httpcode < 200 || $httpcode >= 300) {
        return [];
    }

    $data = json_decode($response, true);

    if (!is_array($data)) {
        return [];
    }

    return $data['shiboleth_response_body']['user_history'] ?? [];
}