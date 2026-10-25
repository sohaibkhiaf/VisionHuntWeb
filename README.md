# توثيق Vision Hunt API

## النطاق والعنوان الأساسي

توفر الواجهة عمليات حسابات اللاعبين ومزامنة المهام والنقاط والاستعلام عن الحدث الحالي. تبدأ جميع المسارات من:

```text
{BASE_URL}/api/v1
```

`BASE_URL` هو عنوان مجلد `public` في تثبيت الموقع. مثال محلي:

```text
http://localhost/visionhunt/api/v1
```

أرسل أجسام الطلب بصيغة JSON UTF-8. تعيد الواجهة JSON بترميز UTF-8، مع `success` وحقول اختيارية مثل `message` و`data`. استخدم رمز HTTP وحقول البيانات لتحديد نتيجة العملية؛ لا تعتمد على مطابقة نص الرسالة.

## المصادقة

يصدر `register.php` و`login.php` رمز Bearer صالحًا لمدة 30 يومًا. أرسله في الطلبات المحمية:

```http
Authorization: Bearer <token>
Content-Type: application/json
```

الرمز 64 خانة hex. يخزن الخادم بصمته فقط. أعد تسجيل الدخول عند انتهاء صلاحيته. الرمز المفقود أو غير الصالح يعيد `401 Unauthorized`.

## قائمة المسارات

| الطريقة | المسار | المصادقة | الوظيفة |
|---|---|---|---|
| `GET` | `/health.php` | لا | التحقق من عمل API واتصال قاعدة البيانات |
| `GET` | `/leaderboard.php` | لا | ترتيب اللاعبين العام مع النقاط وآخر تحديث |
| `POST` | `/register.php` | لا | إنشاء حساب لاعب وإصدار رمز |
| `POST` | `/login.php` | لا | تسجيل دخول وإصدار رمز |
| `POST` | `/profile.php` | Bearer | تحديث الهاتف أو كلمة المرور |
| `POST` | `/logout.php` | Bearer | إبطال الرمز الحالي |
| `GET` | `/player_status.php` | Bearer | جلب حالة اللاعب والنقاط والمهام |
| `POST` | `/complete_task.php` | Bearer | تسجيل مهمة واحدة |
| `POST` | `/restart.php` | Bearer | حذف مهام اللاعب وإعادة النقاط للصفر |
| `GET` | `/current_event.php` | لا | جلب الحدث الجاري أو القادم وآخر حدث منتهٍ |
| `GET` | `/app_version.php` | لا | التحقق من إصدار التطبيق وتوفر تحديث ورابط تحميل APK المباشر |

## فحص إصدار التطبيق والتحديثات — `GET /app_version.php`

لا يحتاج هذا المسار إلى مصادقة. يعيد رقم الإصدار الحالي المعرّف في `APP_VERSION` داخل `config/config.php`، وحالة توفر ملف الـ APK، وحجمه بالبايت، ورابط التحميل المباشر (`download_url`) ليتمكن التطبيق من تنزيل التحديث مباشرةً.

يمكن للتطبيق إرسال إصداره الحالي اختياريًا عبر المعامل `current_version` (أو `version`) ليقوم الخادم بمقارنته وإرجاع نتيجة `update_available` (`true` إذا كان إصدار الخادم أحدث من إصدار التطبيق وملف APK متوفرًا).

```http
GET /api/v1/app_version.php?current_version=1.0.0
```

نجاح — `200 OK`:

```json
{
  "success": true,
  "message": "يتوفر إصدار جديد من التطبيق للتحميل.",
  "data": {
    "version": "1.1.0",
    "current_version": "1.0.0",
    "update_available": true,
    "apk_available": true,
    "apk_size": 15420390,
    "download_url": "http://localhost/visionhunt/download_apk.php?v=1.1.0"
  }
}
```

## فحص الاتصال — `GET /health.php`

لا يحتاج هذا المسار إلى مصادقة أو جسم طلب. يعيد `200 OK` عندما يستجيب API وتكون قاعدة البيانات متاحة، أو `503 Service Unavailable` إذا تعذر الاتصال بقاعدة البيانات. لا يكشف سبب الفشل أو تفاصيل الاتصال.

```http
GET /api/v1/health.php
```

نجاح — `200 OK`:

```json
{
  "success": true,
  "data": {
    "status": "ok",
    "server_time": "2026-10-23 12:00:00"
  }
}
```

فشل الاتصال — `503 Service Unavailable`:

```json
{
  "success": false,
  "data": {
    "status": "unavailable"
  }
}
```

## الترتيب العام — `GET /leaderboard.php`

يعيد هذا المسار معلومات الترتيب العامة نفسها المعروضة في الصفحة الرئيسية: المركز، اسم المستخدم، النقاط، ووقت آخر تحديث. لا يحتاج إلى مصادقة. يعرض 10 لاعبين في الصفحة، ويرتبهم بالنقاط تنازليًا، ثم الأسبق في تحديث نقاطه، ثم اسم المستخدم. يقبل `page` اختياريًا، ويحوّل القيم الأقل من 1 إلى الصفحة الأولى والقيم الأعلى من عدد الصفحات إلى الصفحة الأخيرة.

```http
GET /api/v1/leaderboard.php?page=1
```

نجاح — `200 OK`:

```json
{
  "success": true,
  "data": {
    "players": [
      {
        "rank": 1,
        "username": "player_01",
        "score": 120,
        "updated_at": "2026-10-22 14:00:00"
      }
    ],
    "pagination": {
      "page": 1,
      "per_page": 10,
      "total_players": 23,
      "total_pages": 3
    }
  }
}
```

تكون `players` مصفوفة فارغة عند عدم وجود لاعبين. لا يعيد المسار الهاتف أو أي بيانات خاصة بالحساب.

## رموز HTTP

| الرمز | المعنى |
|---|---|
| `200` | نجح الطلب؛ عدا استجابة عدم وجود حدث التي تعيد `success: false` مع HTTP `200`. |
| `201` | أُنشئ حساب اللاعب. |
| `204` | استجابة `OPTIONS` فارغة. |
| `400` | جسم فارغ أو JSON غير صالح. |
| `413` | حجم جسم الطلب يتجاوز الحد الأقصى، وهو 16 KiB. |
| `401` | بيانات الدخول غير صحيحة أو رمز Bearer مفقود/غير صالح/منتهي. |
| `403` | كلمة المرور القديمة غير صحيحة عند تعديل الحساب. |
| `405` | طريقة HTTP غير مدعومة للمسار. |
| `409` | اسم مستخدم مكرر أو لا يوجد حدث يقبل تسجيل مهمة. |
| `422` | حقل مطلوب مفقود أو قيمة غير مقبولة. |
| `429` | تجاوز حد طلبات تسجيل الدخول أو إنشاء الحساب؛ يحدد `Retry-After` مدة الانتظار بالثواني. |
| `503` | تعذر الحصول على قفل تحديد المعدل مؤقتًا؛ أعد المحاولة بعد ثانية وفق `Retry-After`. |
| `500` | فشل داخلي في عملية تدعم هذا الرمز. |

شكل استجابة الخطأ:

```json
{
  "success": false,
  "message": "رسالة تصف الخطأ"
}
```

تُرجع رسائل الخادم باللغة العربية. قد تختلف صياغتها؛ استخدم رمز HTTP والحقول لتحديد المعالجة.

## إنشاء حساب — `POST /register.php`

اسم المستخدم مطلوب، طوله 3 إلى 50، ويقبل الأحرف الإنجليزية والأرقام والنقطة والشرطة والشرطة السفلية فقط. كلمة المرور مطلوبة وطولها 8 إلى 200. الهاتف اختياري وبحد أقصى 30 حرفًا.

```http
POST /api/v1/register.php
Content-Type: application/json
```

```json
{
  "username": "player_01",
  "password": "strong-password-123",
  "phone": "0550000000"
}
```

حذف `phone` أو إرساله فارغًا يحفظه كـ `null`.

نجاح — `201 Created`:

```json
{
  "success": true,
  "message": "تم إنشاء حساب اللاعب",
  "data": {
    "id": 42,
    "username": "player_01",
    "phone": "0550000000",
    "score": 0,
    "token": "<64-character-hex-token>",
    "token_type": "Bearer",
    "expires_in": 2592000
  }
}
```

الحالات: `409` إذا كان الاسم مسجلًا، `422` إذا خالف حقل القيود، `400` إذا كان JSON فارغًا أو غير صالح.

## تسجيل الدخول — `POST /login.php`

```http
POST /api/v1/login.php
Content-Type: application/json
```

```json
{
  "username": "player_01",
  "password": "strong-password-123"
}
```

نجاح — `200 OK`:

```json
{
  "success": true,
  "message": "تم تسجيل الدخول",
  "data": {
    "player": {
      "id": 42,
      "username": "player_01",
      "phone": "0550000000",
      "score": 40,
      "updated_at": "2026-10-22 14:00:00"
    },
    "token": "<64-character-hex-token>",
    "token_type": "Bearer",
    "expires_in": 2592000
  }
}
```

`expires_in` مدة الصلاحية بالثواني (30 يومًا). تعيد بيانات الدخول غير الصحيحة `401 Unauthorized`.

تُطبّق حدود المعدل على طلبات المصادقة: 10 طلبات تسجيل دخول لكل اسم مستخدم و60 لكل عنوان IP خلال دقيقة واحدة، و10 طلبات إنشاء حساب لكل عنوان IP خلال ساعة. تُحتسب طلبات تسجيل الدخول بغض النظر عن نجاحها، كما تُحتسب طلبات التسجيل قبل التحقق من بياناتها. عند تجاوز الحد، يعيد الخادم `429` مع `Retry-After`.

## حالة اللاعب والمهام — `GET /player_status.php`

يعيد الحالة المعتمدة في الخادم. استخدمه عند بدء التطبيق أو استعادة الاتصال لمزامنة النقاط وقائمة المهام.

```http
GET /api/v1/player_status.php
Authorization: Bearer <token>
```

نجاح — `200 OK`:

```json
{
  "success": true,
  "data": {
    "id": 42,
    "username": "player_01",
    "phone": "0550000000",
    "score": 40,
    "completed_tasks": [
      {
        "id": 81,
        "object_name_ar": "زجاجة",
        "object_name_en": "bottle",
        "rarity": "COMMON",
        "points": 10,
        "confidence": "92.50",
        "created_at": "2026-10-22 13:58:00"
      }
    ],
    "completed_tasks_count": 1,
    "updated_at": "2026-10-22 14:00:00",
    "server_time": "2026-10-22 14:05:00"
  }
}
```

تكون `completed_tasks` مصفوفة فارغة عند عدم وجود مهام. قد تظهر `confidence` كسلسلة رقمية لأن قاعدة البيانات تعيد نوع DECIMAL بهذه الصيغة.

## تحديث الملف الشخصي — `POST /profile.php`

يتطلب Bearer و`old_password`. أرسل `phone` أو `new_password` أو كليهما. لا يمكن تغيير اسم المستخدم. كلمة المرور الجديدة، عند إرسالها، طولها من 8 إلى 200 حرف. إرسال `phone` كسلسلة فارغة يمسح الرقم ويخزنه كـ `null`.

```http
POST /api/v1/profile.php
Authorization: Bearer <token>
Content-Type: application/json
```

```json
{
  "old_password": "strong-password-123",
  "phone": "0551111111",
  "new_password": "another-strong-password"
}
```

نجاح — `200 OK` يعيد الحساب المحدّث في `data`:

```json
{
  "success": true,
  "message": "تم تحديث معلومات الحساب",
  "data": {
    "id": 42,
    "username": "player_01",
    "phone": "0551111111",
    "score": 40,
    "updated_at": "2026-10-22 14:10:00"
  }
}
```

الحالات: `403` لكلمة مرور قديمة غير صحيحة؛ `422` عند غياب كلمة المرور القديمة، أو عدم إرسال حقل للتحديث، أو مخالفة الهاتف/كلمة المرور الجديدة للقيود؛ `401` لرمز غير صالح. تحديث كلمة المرور لا يبطل الرمز الحالي.

## تسجيل الخروج — `POST /logout.php`

يتطلب Bearer ولا يحتاج جسم JSON. يبطل الرمز الذي أُرسل في هذا الطلب فقط.

```http
POST /api/v1/logout.php
Authorization: Bearer <token>
```

نجاح — `200 OK`:

```json
{
  "success": true,
  "message": "تم تسجيل الخروج وإبطال رمز الوصول الحالي"
}
```

أي طلب لاحق بالرمز نفسه يعيد `401`. امسح الرمز من التخزين المحلي عند تسجيل الخروج.

## الحدث الحالي — `GET /current_event.php`

لا يحتاج إلى مصادقة. يعيد الحدث الجاري وحده إن وجد. إذا لم يوجد حدث جارٍ، يعيد الحدث القادم مع أحدث حدث منتهٍ (تم إنهاؤه وحفظ نتائجه) إن وجد. وإذا لم يوجد حدث قادم، يعيد أحدث حدث منتهٍ وحده. يتضمن كل حدث منتهٍ قائمة `players` مرتبة حسب `rank_position`، وبها الترتيب واسم اللاعب ونقاطه. يُحدد أحدث حدث منتهٍ بترتيب `created_at` تنازليًا، ثم `id` تنازليًا عند التساوي. يُعد الحدث جاريًا إذا بدأ ولم ينهه المسؤول يدويًا؛ لذلك يظل جاريًا حتى إن تجاوز `ends_at` إلى أن يُنهى من لوحة الإدارة.

```http
GET /api/v1/current_event.php
```

مثال عند وجود حدث جارٍ — `200 OK` (لا يُرفق معه حدث منتهٍ):

```json
{
  "success": true,
  "message": "تم العثور على حدث جارٍ.",
  "data": {
    "event": {
      "id": 7,
      "title": "تحدي نهاية الأسبوع",
      "description": "اجمع النقاط قبل انتهاء الحدث.",
      "starts_at": "2026-10-22 09:00:00",
      "ends_at": "2026-10-24 21:00:00"
    },
    "event_type": "ongoing",
    "last_ended_event": null
  }
}
```

عند وجود حدث قادم وأحدث حدث منتهٍ، تكون الاستجابة كالتالي. يُختار أحدث حدث منتهٍ حسب `created_at` تنازليًا، وعند التساوي حسب `id` تنازليًا:

```json
{
  "success": true,
  "message": "لا يوجد حدث جارٍ. تم العثور على حدث قادم وأحدث حدث منتهٍ.",
  "data": {
    "event": {
      "id": 8,
      "title": "تحدي الأسبوع القادم",
      "description": "استعد للتحدي القادم.",
      "starts_at": "2026-10-25 09:00:00",
      "ends_at": "2026-10-27 21:00:00"
    },
    "event_type": "upcoming",
    "last_ended_event": {
      "id": 7,
      "title": "تحدي نهاية الأسبوع",
      "description": "اجمع النقاط قبل انتهاء الحدث.",
      "starts_at": "2026-10-20 09:00:00",
      "ends_at": "2026-10-22 21:00:00",
      "players": [
        {
          "rank_position": 1,
          "username": "player_01",
          "score": 120
        },
        {
          "rank_position": 2,
          "username": "player_02",
          "score": 95
        }
      ]
    }
  }
}
```

إذا لم يوجد حدث منتهٍ مع الحدث القادم، تكون قيمة `last_ended_event` هي `null`:

```json
{
  "success": true,
  "message": "لا يوجد حدث جارٍ. تم العثور على حدث قادم.",
  "data": {
    "event": {
      "id": 8,
      "title": "تحدي الأسبوع القادم",
      "description": "استعد للتحدي القادم.",
      "starts_at": "2026-10-25 09:00:00",
      "ends_at": "2026-10-27 21:00:00"
    },
    "event_type": "upcoming",
    "last_ended_event": null
  }
}
```

إذا لم يوجد حدث جارٍ أو قادم، يُعاد أحدث حدث منتهٍ وحده، مع `event_type` بقيمة `ended`:

```json
{
  "success": true,
  "message": "لا يوجد حدث جارٍ أو قادم. هذا آخر حدث منتهٍ.",
  "data": {
    "event": {
      "id": 7,
      "title": "تحدي نهاية الأسبوع",
      "description": "اجمع النقاط قبل انتهاء الحدث.",
      "starts_at": "2026-10-20 09:00:00",
      "ends_at": "2026-10-22 21:00:00",
      "players": [
        {
          "rank_position": 1,
          "username": "player_01",
          "score": 120
        },
        {
          "rank_position": 2,
          "username": "player_02",
          "score": 95
        }
      ]
    },
    "event_type": "ended",
    "last_ended_event": null
  }
}
```

إذا لم توجد أي أحداث، يرجع `200 OK` مع `success: false` و`data: null`:

```json
{
  "success": false,
  "message": "لا يوجد حدث جارٍ أو قادم أو حدث منتهٍ.",
  "data": null
}
```

## تسجيل مهمة — `POST /complete_task.php`

يتطلب Bearer وحدثًا غير معالج، ويجب أن يكون الوقت الحالي ضمن `starts_at <= الآن < ends_at`. يقبل طلبًا واحدًا مهمة واحدة ويضيفها إلى مهام اللاعب الحالية دون حذفها. أرسل المهمة داخل `completed_task` كما في المثال، أو أرسل الحقلين مباشرة في جذر JSON.

```http
POST /api/v1/complete_task.php
Authorization: Bearer <token>
Content-Type: application/json
```

```json
{
  "completed_task": {
    "object_name_en": "bottle",
    "confidence": 92.5
  }
}
```

`object_name_en` اسم COCO معتمد. يحسب الخادم الاسم العربي والندرة والنقاط؛ لا ترسل نقاطًا أو ندرة لحساب النتيجة. `confidence` مطلوب، رقمي، بين 0 و100.

نجاح — `200 OK`:

```json
{
  "success": true,
  "message": "تم تسجيل المهمة وتحديث النقاط بنجاح",
  "data": {
    "username": "player_01",
    "score": 10,
    "completed_tasks_count": 1,
    "completed_tasks": [
      {
        "id": 81,
        "object_name_ar": "زجاجة",
        "object_name_en": "bottle",
        "rarity": "COMMON",
        "points": 10,
        "confidence": "92.50",
        "created_at": "2026-10-22 13:58:00"
      }
    ],
    "updated_at": "2026-10-22 14:00:00"
  }
}
```

إذا سبق تسجيل الكائن نفسه لهذا اللاعب، تكون الاستجابة ناجحة دون تكرار المهمة أو إضافة نقاط. تعيد الاستجابة النقاط والقائمة الحالية.

الكائنات المدعومة:

| الندرة | النقاط | قيم `object_name_en` |
|---|---:|---|
| `COMMON` | 10 | `person`, `car`, `bench`, `bird`, `cat`, `dog`, `backpack`, `umbrella`, `handbag`, `sports ball`, `bottle`, `cup`, `fork`, `knife`, `spoon`, `bowl`, `banana`, `apple`, `sandwich`, `orange`, `carrot`, `pizza`, `cake`, `chair`, `couch`, `potted plant`, `bed`, `tv`, `mouse`, `remote`, `keyboard`, `cell phone`, `sink`, `refrigerator`, `book`, `clock`, `scissors`, `teddy bear`, `toothbrush` |
| `UNCOMMON` | 20 | `bicycle`, `motorcycle`, `bus`, `truck`, `traffic light`, `stop sign`, `sheep`, `cow`, `tie`, `suitcase`, `kite`, `skateboard`, `tennis racket`, `wine glass`, `broccoli`, `hot dog`, `donut`, `toilet`, `laptop`, `microwave`, `oven`, `toaster`, `vase`, `hair drier` |
| `RARE` | 30 | `airplane`, `train`, `boat`, `fire hydrant`, `parking meter`, `horse`, `elephant`, `bear`, `zebra`, `giraffe`, `frisbee`, `skis`, `snowboard`, `baseball bat`, `baseball glove`, `surfboard` |

المسافات في أسماء COCO مهمة. يحوّل الخادم الاسم إلى أحرف صغيرة قبل التحقق.

الحالات: `409` إذا لم يوجد حدث يقبل المهام الآن؛ `422` إذا كان جسم المهمة أو اسم الكائن أو الثقة غير صالح؛ `401` لرمز غير صالح؛ `400` لجسم فارغ أو JSON غير صالح؛ `500` عند فشل حفظ المهمة أو تحديث النقاط.

## إعادة التعيين — `POST /restart.php`

يتطلب Bearer ولا يحتاج إلى جسم JSON. يحذف كل المهام المكتملة للاعب ويعيد النقاط إلى صفر، ولا يحذف حسابه. لا يُسمح بإعادة التعيين إلا أثناء فترة حدث غير منتهٍ يدويًا، من وقت `starts_at` شاملًا إلى ما قبل `ends_at`؛ خارج هذه الفترة يرجع `409` دون تغيير بيانات اللاعب.

```http
POST /api/v1/restart.php
Authorization: Bearer <token>
```

نجاح — `200 OK`:

```json
{
  "success": true,
  "message": "تمت إعادة تعيين نقاط اللاعب وقائمة المهام بنجاح",
  "data": {
    "username": "player_01",
    "score": 0,
    "updated_at": "2026-10-22 14:05:00"
  }
}
```

رمز غير صالح يعيد `401`، وعدم وجود حدث ضمن فترة السماح يعيد `409`، وفشل المعاملة يعيد `500` دون تطبيق التغييرات.

## مثال تسلسل الاستخدام

1. أنشئ حسابًا أو سجل الدخول وخذ `data.token`.
2. أرسل الرمز في `Authorization: Bearer ...` للطلبات المحمية.
3. اجلب الحدث عبر `/current_event.php` والحالة الموثوقة عبر `/player_status.php`.
4. أرسل المهمة المكتشفة إلى `/complete_task.php`.
5. حدّث الحالة المحلية باستخدام `score` و`completed_tasks` المعادين من الخادم.
6. استخدم `/profile.php` لتعديل بيانات الحساب و`/logout.php` لإبطال الرمز.

مثال cURL لتسجيل مهمة:

```bash
curl -i -X POST "http://localhost/visionhunt/api/v1/complete_task.php" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"completed_task":{"object_name_en":"bottle","confidence":92.5}}'
```
