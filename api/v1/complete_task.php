<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
api_method('POST');
$player = api_auth_player();
$data = api_body();

// Map each COCO label to its Arabic name, rarity, and point value.
// Keep this mapping aligned with CocoTasks.kt in the Android app.
const COCO_OBJECTS = [
    'person'          => ['شخص',                   'COMMON',   10],
    'bicycle'         => ['دراجة هوائية',            'UNCOMMON', 20],
    'car'             => ['سيارة',                   'COMMON',   10],
    'motorcycle'      => ['دراجة نارية',             'UNCOMMON', 20],
    'airplane'        => ['طائرة',                   'RARE',     30],
    'bus'             => ['حافلة',                   'UNCOMMON', 20],
    'train'           => ['قطار',                    'RARE',     30],
    'truck'           => ['شاحنة',                   'UNCOMMON', 20],
    'boat'            => ['قارب',                    'RARE',     30],
    'traffic light'   => ['إشارة مرور',              'UNCOMMON', 20],
    'fire hydrant'    => ['صنبور إطفاء الحريق',      'RARE',     30],
    'stop sign'       => ['علامة قف',                'UNCOMMON', 20],
    'parking meter'   => ['عداد مواقف السيارات',     'RARE',     30],
    'bench'           => ['مقعد عام',                'COMMON',   10],
    'bird'            => ['طائر',                    'COMMON',   10],
    'cat'             => ['قط',                      'COMMON',   10],
    'dog'             => ['كلب',                     'COMMON',   10],
    'horse'           => ['حصان',                    'RARE',     30],
    'sheep'           => ['خروف',                    'UNCOMMON', 20],
    'cow'             => ['بقرة',                    'UNCOMMON', 20],
    'elephant'        => ['فيل',                     'RARE',     30],
    'bear'            => ['دب',                      'RARE',     30],
    'zebra'           => ['حمار وحشي',               'RARE',     30],
    'giraffe'         => ['زرافة',                   'RARE',     30],
    'backpack'        => ['حقيبة ظهر',               'COMMON',   10],
    'umbrella'        => ['مظلة',                    'COMMON',   10],
    'handbag'         => ['حقيبة يد',                'COMMON',   10],
    'tie'             => ['ربطة عنق',                'UNCOMMON', 20],
    'suitcase'        => ['حقيبة سفر',               'UNCOMMON', 20],
    'frisbee'         => ['قرص فريسبي',              'RARE',     30],
    'skis'            => ['زلاجات ثلجية',             'RARE',     30],
    'snowboard'       => ['لوح تزلج ثلجي',           'RARE',     30],
    'sports ball'     => ['كرة رياضية',              'COMMON',   10],
    'kite'            => ['طائرة ورقية',              'UNCOMMON', 20],
    'baseball bat'    => ['مضرب بيسبول',             'RARE',     30],
    'baseball glove'  => ['قفاز بيسبول',             'RARE',     30],
    'skateboard'      => ['لوح تزلج',                'UNCOMMON', 20],
    'surfboard'       => ['لوح ركوب الأمواج',         'RARE',     30],
    'tennis racket'   => ['مضرب تنس',                'UNCOMMON', 20],
    'bottle'          => ['زجاجة',                   'COMMON',   10],
    'wine glass'      => ['كأس زجاجي',               'UNCOMMON', 20],
    'cup'             => ['كوب',                     'COMMON',   10],
    'fork'            => ['شوكة',                    'COMMON',   10],
    'knife'           => ['سكين',                    'COMMON',   10],
    'spoon'           => ['ملعقة',                   'COMMON',   10],
    'bowl'            => ['وعاء',                    'COMMON',   10],
    'banana'          => ['موز',                     'COMMON',   10],
    'apple'           => ['تفاحة',                   'COMMON',   10],
    'sandwich'        => ['شطيرة',                   'COMMON',   10],
    'orange'          => ['برتقالة',                  'COMMON',   10],
    'broccoli'        => ['بروكلي',                  'UNCOMMON', 20],
    'carrot'          => ['جزر',                     'COMMON',   10],
    'hot dog'         => ['هوت دوغ',                 'UNCOMMON', 20],
    'pizza'           => ['بيتزا',                   'COMMON',   10],
    'donut'           => ['دونات',                   'UNCOMMON', 20],
    'cake'            => ['كعكة',                    'COMMON',   10],
    'chair'           => ['كرسي',                    'COMMON',   10],
    'couch'           => ['أريكة',                   'COMMON',   10],
    'potted plant'    => ['نبات في أصيص',            'COMMON',   10],
    'bed'             => ['سرير',                    'COMMON',   10],
    'dining table'    => ['طاولة طعام',              'COMMON',   10],
    'toilet'          => ['مرحاض',                   'UNCOMMON', 20],
    'tv'              => ['تلفاز',                   'COMMON',   10],
    'laptop'          => ['حاسوب محمول',              'UNCOMMON', 20],
    'mouse'           => ['فأرة حاسوب',              'COMMON',   10],
    'remote'          => ['جهاز تحكم عن بعد',         'COMMON',   10],
    'keyboard'        => ['لوحة مفاتيح',             'COMMON',   10],
    'cell phone'      => ['هاتف محمول',              'COMMON',   10],
    'microwave'       => ['ميكروويف',                'UNCOMMON', 20],
    'oven'            => ['فرن',                     'UNCOMMON', 20],
    'toaster'         => ['محمصة خبز',               'UNCOMMON', 20],
    'sink'            => ['حوض',                     'COMMON',   10],
    'refrigerator'    => ['ثلاجة',                   'COMMON',   10],
    'book'            => ['كتاب',                    'COMMON',   10],
    'clock'           => ['ساعة',                    'COMMON',   10],
    'vase'            => ['مزهرية',                  'UNCOMMON', 20],
    'scissors'        => ['مقص',                     'COMMON',   10],
    'teddy bear'      => ['دب محشو',                 'COMMON',   10],
    'hair drier'      => ['مجفف شعر',                'UNCOMMON', 20],
    'toothbrush'      => ['فرشاة أسنان',              'COMMON',   10],
];

// Validate the submitted task list.
$rawTask = $data['completed_task'] ?? $data;
if (!is_array($rawTask) || array_is_list($rawTask)) {
    api_json(['success' => false, 'message' => 'يجب إرسال completed_task ككائن واحد.'], 422);
}

$cleanTasks = [];
$num = 1;

{
    $task = $rawTask;

    // object_name_en stores the COCO label used to calculate points.
    $label = strtolower(trim((string)($task['object_name_en'] ?? '')));
    if ($label === '') {
        api_json(['success' => false, 'message' => "object_name_en مطلوب في المهمة رقم {$num}."], 422);
    }

    if (!isset(COCO_OBJECTS[$label])) {
        api_json(['success' => false, 'message' => "الكائن \"{$label}\" في المهمة رقم {$num} غير معروف."], 422);
    }

    [$arName, $rarity, $pts] = COCO_OBJECTS[$label];

    $confidence = $task['confidence'] ?? null;
    if (!is_numeric($confidence) || (float)$confidence < 0 || (float)$confidence > 100) {
        api_json(['success' => false, 'message' => "confidence في المهمة رقم {$num} يجب أن تكون نسبة بين 0 و100."], 422);
    }

    $cleanTasks[] = [
        'object_name_ar' => $arName,
        'object_name_en' => $label,
        'rarity'         => $rarity,
        'points'         => $pts,
        'confidence'     => round((float)$confidence, 2),
    ];
}

// Replace the current task data and update the player's score in one transaction.
$pdo = db();
try {
    $pdo->beginTransaction();

    // Lock the active event so finishing it cannot race with this score update.
    $now = date('Y-m-d H:i:s');
    $eventLock = $pdo->prepare('SELECT id FROM events WHERE processed_at IS NULL AND starts_at <= ? AND ends_at > ? ORDER BY starts_at DESC, id DESC LIMIT 1 FOR UPDATE');
    $eventLock->execute([$now, $now]);
    if (!$eventLock->fetchColumn()) {
        $pdo->rollBack();
        api_json(['success' => false, 'message' => 'لا يمكن تسجيل المهام أو النقاط لعدم وجود حدث جارٍ.'], 409);
    }

    $task = $cleanTasks[0];
    $exists = $pdo->prepare('SELECT id FROM completed_tasks WHERE player_id = ? AND object_name_en = ? LIMIT 1');
    $exists->execute([(int)$player['id'], $task['object_name_en']]);
    $alreadyCompleted = (bool)$exists->fetchColumn();
    if (!$alreadyCompleted) {
        $insert = $pdo->prepare(
            'INSERT INTO completed_tasks (player_id, object_name_ar, object_name_en, rarity, points, confidence)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $insert->execute([
            (int)$player['id'], $task['object_name_ar'], $task['object_name_en'],
            $task['rarity'], $task['points'], $task['confidence'],
        ]);
    }

    $oldScoreStmt = $pdo->prepare('SELECT score FROM players WHERE id = ? FOR UPDATE');
    $oldScoreStmt->execute([(int)$player['id']]);
    $oldScore = (int)$oldScoreStmt->fetchColumn();

    $scoreStmt = $pdo->prepare('SELECT COALESCE(SUM(points), 0) FROM completed_tasks WHERE player_id = ?');
    $scoreStmt->execute([(int)$player['id']]);
    $totalScore = (int)$scoreStmt->fetchColumn();
    if ($totalScore !== $oldScore) {
        $pdo->prepare('UPDATE players SET score = ?, updated_at = NOW() WHERE id = ?')
            ->execute([$totalScore, (int)$player['id']]);
    }

    $tasksStmt = $pdo->prepare('SELECT id, object_name_ar, object_name_en, rarity, points, confidence, created_at FROM completed_tasks WHERE player_id = ? ORDER BY created_at, id');
    $tasksStmt->execute([(int)$player['id']]);
    $currentTasks = $tasksStmt->fetchAll();
    $updatedPlayer = $pdo->prepare('SELECT updated_at FROM players WHERE id = ?');
    $updatedPlayer->execute([(int)$player['id']]);
    $updatedAt = $updatedPlayer->fetchColumn();

    $pdo->commit();
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log((string)$ex);
    api_json(['success' => false, 'message' => 'فشلت مزامنة البيانات؛ لم يتم اعتماد التغييرات.'], 500);
}

api_json([
    'success' => true,
    'message' => $alreadyCompleted ? 'سبق تسجيل هذه المهمة؛ لم تتم إضافتها مرة أخرى.' : 'تم تسجيل المهمة وتحديث النقاط بنجاح.',
    'data' => [
        'username'              => $player['username'],
        'score'                 => $totalScore,
        'completed_tasks_count' => count($currentTasks),
        'completed_tasks'       => $currentTasks,
        'updated_at'            => $updatedAt,
    ],
]);
