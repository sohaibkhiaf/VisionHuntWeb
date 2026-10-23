<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
api_method('GET');

$events = current_event_payload();
if ($events['event'] === null) {
    api_json(['success' => false, 'message' => 'لا يوجد حدث جارٍ أو قادم أو حدث منتهٍ.', 'data' => null]);
}

foreach (['event', 'last_ended_event'] as $key) {
    if ($events[$key] !== null) {
        $events[$key]['id'] = (int)$events[$key]['id'];
    }
}

$message = match ($events['event_type']) {
    'ongoing' => 'تم العثور على حدث جارٍ.',
    'upcoming' => $events['last_ended_event']
        ? 'لا يوجد حدث جارٍ. تم العثور على حدث قادم وأحدث حدث منتهٍ.'
        : 'لا يوجد حدث جارٍ. تم العثور على حدث قادم.',
    default => 'لا يوجد حدث جارٍ أو قادم. هذا آخر حدث منتهٍ.',
};
api_json(['success' => true, 'message' => $message, 'data' => $events]);
