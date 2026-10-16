<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Send prompt to FastAPI.
 *
 * If $prompt is empty, the same endpoint is used only to retrieve
 * the user's history. FastAPI must handle empty prompt accordingly.
 *
 * @param string $prompt
 * @param int $courseid
 * @return array
 */
function block_prompt_send_prompt(string $prompt, int $courseid): array {
    global $USER, $COURSE, $DB;

    $userid = (int)$USER->id;

    if (!$DB->record_exists('course', ['id' => $courseid])) {
        error_log('BLOCK_PROMPT ERROR: Course ID ' . $courseid . ' does not exist in mdl_course.');
        return [];
    }
    
    error_log('PROMPT DEBUG: Before get_course');
    $course = get_course($courseid);
    error_log('PROMPT DEBUG: After get_course');

    error_log('PROMPT DEBUG: Before get_user_hash');
    $userhash = block_prompt_get_user_hash($userid, $courseid);
    error_log('PROMPT DEBUG: After get_user_hash');

    $requestid = block_prompt_generate_request_id($courseid);

    error_log('PROMPT DEBUG: Before get_course_instructor');
    $instructor = block_prompt_get_course_instructor($courseid);
    error_log('PROMPT DEBUG: After get_course_instructor');

    $language = current_language();

    $payload = [
        'request_id' => $requestid,
        'user_id' => $userhash,
        'prompt' => $prompt,
        'course_metadata' => [
            'course_id' => (string)$courseid,
            'course_name' => $course->fullname,
            'instructor' => $instructor,
        ],

        'options' => [
            'language' => $language,
            // 'retrieval' => [
            //     'n_results' => 3,
            // ],
        ],
    ];

    $config = get_config('local_turi_rag');

    $host = $config->prompt_host ?? '';
    $port = $config->prompt_port ?? '';

    if (empty($host) || empty($port)) {
        return [];
    }

    $apiurl = 'http://' . $host . ':' . $port .
        '/api/prompt/entry_prompt';

    $json = json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    if ($json === false) {
        return [];
    }
    error_log('========== FASTAPI REQUEST ==========');
    error_log('URL: ' . $apiurl);
    error_log('REQUEST JSON: ' . $json);

    $ch = curl_init($apiurl);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $json,
        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
        ],

        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);

    $curlerror = curl_error($ch);

    $httpcode = curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );

    if ($response === false) {
        error_log('PROMPT CURL ERROR: ' . $curlerror);
        return [];
    }

    if ($httpcode < 200 || $httpcode >= 300) {
        error_log('PROMPT HTTP ERROR: ' . $httpcode);
        error_log('PROMPT RESPONSE: ' . (string)$response);
        return [];
    }

    $data = json_decode($response, true);

    if (!is_array($data)) {return [];}

    if (
        isset($data['shiboleth_response_body']) &&
        is_array($data['shiboleth_response_body'])
    ) {
        return $data['shiboleth_response_body'];
    }

    return $data;
}

/**
 * Build user hash.
 *
 * @param int $userid
 * @param int $courseid
 * @return string
 */
function block_prompt_get_user_hash(
    int $userid,
    int $courseid
): string {

    $salt = get_config(
        'local_turi_rag',
        'secret_salt'
    );
    if (empty($salt)) {
        throw new moodle_exception('secretsaltmissing', 'block_prompt');
    }

    return hash(
        'sha256',
        'userid:' . $userid .
        '|bereichs_id:' . $courseid .
        $salt
    );
}

/**
 * Generate a request ID.
 *
 * @param int $courseid
 * @param int $userid
 * @return string
 */
function block_prompt_generate_request_id(
    int $courseid,
): string {
    $salt = get_config(
        'local_turi_rag',
        'secret_salt'
    );
    $timestamp = sprintf('%.6f', microtime(true));

    $requestid = $courseid . $timestamp . $salt;

    return substr(
        str_pad($requestid, 77, '0'),
        0,
        77
    );
}

/**
 * Get course instructor.
 *
 * @param int $courseid
 * @return string
 */
function block_prompt_get_course_instructor(int $courseid): string {
    $context = context_course::instance($courseid, IGNORE_MISSING);

    if (!$context) {
        return '';
    }

    $teachers = get_enrolled_users(
        $context,
        'moodle/course:update',
        0,
        'u.id,u.firstname,u.lastname',
        'u.lastname,u.firstname',
        0,
        1
    );

    if (empty($teachers)) {
        return '';
    }

    $teacher = reset($teachers);

    return fullname($teacher);
}