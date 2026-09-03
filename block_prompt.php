<?php
require_once(__DIR__ . '/fastapi.php');
defined('MOODLE_INTERNAL') || die();

class block_prompt extends block_base {

    public function init() {
        $this->title = get_string('pluginname', 'block_prompt');
    }

    public function get_content() {

        global $OUTPUT, $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $PAGE->requires->js_call_amd(
            'block_prompt/main',
            'init'
        );

        /*
        * Отримуємо історію з FastAPI.
        *
        * Тут поки передаємо ID = 123.
        * Пізніше можна замінити на реальний Moodle user/course ID.
        */
        $history = block_prompt_get_history_from_fastapi('123');

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

        /*
        * Дані для Mustache template.
        */
        $data = [
            'history' => $mustachehistory,
            'answer' => get_string('answer', 'block_prompt'),
        ];

        /*
        * Створюємо контент блоку.
        */
        $this->content = new stdClass();

        $this->content->text = $OUTPUT->render_from_template(
            'block_prompt/content',
            $data
        );

        return $this->content;
    }

    // public function get_content() {

    //     global $OUTPUT, $PAGE;

    //     if ($this->content !== null) {
    //         return $this->content;
    //     }

    //     $PAGE->requires->js_call_amd(
    //         'block_prompt/main',
    //         'init'
    //     );
    //     // або в Mustache:
    //     // {{#js}}
    //     // require(['block_prompt/main'], (module) => module.init());
    //     // {{/js}}

    //     // Fake prompt history.
    //     $history = [
    //         [
    //             'id' => 'turn-001',
    //             'prompt' => 'Erkläre mir kurz, was eine Datenbank ist.',
    //             'response' => [
    //                 'answer' => 'Eine Datenbank ist ein System zur strukturierten Speicherung und Verwaltung von Informationen. Sie ermöglicht es, Daten schnell zu speichern, zu suchen und zu bearbeiten.'
    //             ],
    //             'date' => '15.08.2026, 09:32'
    //         ],
    //         [
    //             'id' => 'turn-002',
    //             'prompt' => 'Welche Vorteile bietet MariaDB?',
    //             'response' => [
    //                 'answer' => 'MariaDB ist ein relationales Datenbankmanagementsystem. Zu den Vorteilen gehören gute Performance, MySQL-Kompatibilität und die Open-Source-Lizenz.'
    //             ],
    //             'date' => '15.08.2026, 10:14'
    //         ],
    //         [
    //             'id' => 'turn-003',
    //             'prompt' => 'Wo sollte die IP-Adresse des Datenbank-Servers eingetragen werden?',
    //             'response' => [
    //                 'answer' => 'Die IP-Adresse des Datenbank-Servers, in diesem Fall MariaDB in einem separaten Container, sollte in der Datei phpmyadmin/conf.d/config-db.php eingetragen werden.'
    //             ],
    //             'date' => '16.08.2026, 13:41'
    //         ],
    //         [
    //             'id' => 'turn-004',
    //             'prompt' => 'Was ist der Unterschied zwischen einem Container und einer virtuellen Maschine?',
    //             'response' => [
    //                 'answer' => 'Ein Container teilt sich den Kernel des Host-Systems und ist deshalb meist leichter und schneller. Eine virtuelle Maschine enthält ein vollständiges Gastbetriebssystem.'
    //             ],
    //             'date' => '16.08.2026, 15:22'
    //         ],
    //         [
    //             'id' => 'turn-005',
    //             'prompt' => 'Erstelle eine kurze Zusammenfassung zum Thema Docker.',
    //             'response' => [
    //                 'answer' => 'Docker ermöglicht es, Anwendungen zusammen mit ihren Abhängigkeiten in isolierten Containern auszuführen.'
    //             ],
    //             'date' => '17.08.2026, 09:05'
    //         ]
    //     ];

    //     // Data for Mustache template.
    //     $data = [
    //         'history' => $history,
    //         'answer' => get_string('answer', 'block_prompt'),
    //     ];

    //     // Create block content.
    //     $this->content = new stdClass();

    //     $this->content->text = $OUTPUT->render_from_template(
    //         'block_prompt/content',
    //         $data
    //     );

    //     return $this->content;
    // }

    public function applicable_formats() {
        return [
            'course-view' => true,
            'mod-lesson' => true,
            'my' => false,
            'site-index' => false
        ];
    }
}