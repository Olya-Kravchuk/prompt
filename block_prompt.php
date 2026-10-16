<?php
require_once(__DIR__ . '/fastapi.php');
defined('MOODLE_INTERNAL') || die();

class block_prompt extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_prompt');
    }

    public function hide_header() {
        return !$this->is_prompt_enabled();
    }

    private function is_prompt_enabled(): bool {
        global $DB;

        $courseid = (int)$this->page->course->id;

        return $DB->record_exists(
            'local_turi_course',
            [
                'courseid' => $courseid,
                'promptenabled' => 1,
            ]
        );
    }
    
    private function process_prompt_submission(): void {
        global $SESSION;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        if (!isset($_POST['block_prompt_submit'])) {
            return;
        }

        require_sesskey();

        $courseid = (int)$this->page->course->id;

        $prompt = trim(
            optional_param('prompt', '', PARAM_TEXT)
        );

        if ($prompt === '') {
            $SESSION->block_prompt_error = get_string(
                'prompt_empty',
                'block_prompt'
            );
            redirect($this->page->url);
        }

        $response = block_prompt_send_prompt(
            $prompt,
            $courseid
        );

        if (empty($response)) {
            $SESSION->block_prompt_error = get_string(
                'prompt_fastapi_error',
                'block_prompt'
            );
        } else {
            $response['prompt'] = $response['prompt'] ?? $prompt;
            $SESSION->block_prompt_response = $response;
        }

        redirect($this->page->url);
    }

    private function get_course_file_url($filename) {
        $courseid = $this->page->course->id;
        $modinfo = get_fast_modinfo($courseid);
        $fs = get_file_storage();

        $searchclean = mb_strtolower(trim($filename));
        $searchnameonly = pathinfo($searchclean, PATHINFO_FILENAME);

        foreach ($modinfo->get_instances_of('resource') as $cm) {
            if (!$cm->uservisible) {
                continue;
            }

            $contextmodule = context_module::instance($cm->id);

            $files = $fs->get_area_files(
                $contextmodule->id,
                'mod_resource',
                'content',
                0,
                'sortorder',
                false
            );

            foreach ($files as $file) {
                if ($file->is_directory()) {
                    continue;
                }

                $moodlefilename = $file->get_filename();
                $moodleclean = mb_strtolower(trim($moodlefilename));
                $moodlenameonly = pathinfo($moodleclean, PATHINFO_FILENAME);

                if ($moodleclean === $searchclean || $moodlenameonly === $searchnameonly) {

                    return moodle_url::make_pluginfile_url(
                        $file->get_contextid(),
                        $file->get_component(),
                        $file->get_filearea(),
                        $file->get_itemid(),
                        $file->get_filepath(),
                        $file->get_filename()
                    );
                }
            }
        }

        return null;
    }

    public function get_content() {

        global $OUTPUT, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }
        if (!$this->is_prompt_enabled()) {
            return $this->content = new stdClass();
        }
        $this->process_prompt_submission();

        global $SESSION;

        $response = $SESSION->block_prompt_response ?? [];
        unset($SESSION->block_prompt_response);

        $error = $SESSION->block_prompt_error ?? '';
        unset($SESSION->block_prompt_error);

        $PAGE->requires->js_call_amd(
            'block_prompt/main',
            'init'
        );
        $PAGE->requires->js_call_amd(
            'block_prompt/chat',
            'init'
        );

        $courseid = (int)$this->page->course->id;

        // $response = block_prompt_send_prompt(
        //     'ales wird gut',
        //     $courseid
        // );
        // echo '<pre style="
        //     background:#111;
        //     color:#0f0;
        //     padding:20px;
        //     margin:20px;
        //     white-space:pre-wrap;
        //     font-family:monospace;
        // ">';

        // echo "===== REQUEST TO FASTAPI =====\n\n";

        // echo htmlspecialchars(
        //     json_encode(
        //         $response['debug_request'] ?? [],
        //         JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        //     ),
        //     ENT_QUOTES | ENT_SUBSTITUTE,
        //     'UTF-8'
        // );

        // echo "\n\n===== RAW RESPONSE FROM FASTAPI =====\n\n";

        // echo htmlspecialchars(
        //     $response['debug_response_raw'] ?? '',
        //     ENT_QUOTES | ENT_SUBSTITUTE,
        //     'UTF-8'
        // );

        // echo "\n\n===== PARSED RESPONSE =====\n\n";

        // echo htmlspecialchars(
        //     json_encode(
        //         $response['debug_response'] ?? [],
        //         JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        //     ),
        //     ENT_QUOTES | ENT_SUBSTITUTE,
        //     'UTF-8'
        // );

        // echo '</pre>';

        $history = $response['user_history'] ?? [];
        $mustachehistory = [];

        foreach ($history as $item) {
            $mustachehistory[] = [
                'id' => uniqid('turn-'),
                'prompt' => $item['question'] ?? '',
                'response' => [
                    'answer' => $item['answer'] ?? '',
                ],
                'date' => $item['timestamp'] ?? '',
            ];
        }

        $sources = $response['sources'] ?? [];
        foreach ($sources as &$source) {
            if (!empty($source['file_name'])) {
                $fileurl = $this->get_course_file_url(
                    $source['file_name']
                );

                if ($fileurl) {
                    $source['file_url'] = $fileurl->out(false);
                }
            }
        }

        unset($source);

        $current = [];

        if (!empty($response['prompt']) || !empty($response['answer'])) {
            $current[] = [
                'prompt' => $response['prompt'] ?? '',
                'answer' => $response['answer'] ?? '',
                'timestamp' => $response['timestamp'] ?? '',
                'sources' => $sources,
                'uncertain' => $response['uncertain'] ?? false,
            ];
        }

        $data = [
            'courseid' => $this->page->course->id,
            'form_action' => $this->page->url->out(false),
            'sesskey' => sesskey(),
            'history' => $mustachehistory,
            'has_history' => !empty($mustachehistory),
            'current' => $current,
            'error' => $error,
            'has_error' => !empty($error),
        ];

        $this->content = new stdClass();

        $this->content->text = $OUTPUT->render_from_template(
            'block_prompt/content',
            $data
        );

        return $this->content;
    }


    public function applicable_formats() {
        return [
            'course-view-*' => true,
            // 'mod-lesson' => false,
            'my' => false,
            // 'site-index' => false
        ];
    }
}