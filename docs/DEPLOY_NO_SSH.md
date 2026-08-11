# نشر بدون SSH / Deploy without SSH

دليل مختصر لاستضافة لا تسمح بأوامر الطرفية (FTP أو Git فقط).

---

## العربية (مختصر)

### على جهازك (قبل الرفع)

1. **Composer** (مرة عند تغيير PHP dependencies):
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
2. **واجهة Vue (Vite)** — بعد أي تعديل على `resources/js` أو `resources/css`:
   ```bash
   npm install
   npm run build
   ```
3. **Git**: أضف واعْمَل commit لمجلد `public/build/` (مُتتبَّع في المستودع). **لا** ترفع `node_modules` ولا `.env`.
4. ارفع المشروع: `git push` ثم على السيرفر `git pull`، أو FTP لمجلد التطبيق كاملاً (مع `vendor/` إن لم يكن Composer على الاستضافة).

### على السيرفر — خياران لجذر الموقع

| الخيار | جذر الموقع (Document root) | ملاحظة |
|--------|---------------------------|--------|
| **أ (مُفضَّل)** | مجلد `public/` فقط | الإعداد القياسي لـ Laravel |
| **ب** | **جذر المشروع** (مجلد التطبيق أو مجلد الساب‌دومين) | للاستضافة التي لا تسمح بتغيير الجذر إلى `public/` — انظر القسم التالي |

---

### الخيار ب: جذر الموقع = جذر المشروع (بدون SSH)

عندما تضطر اللوحة إلى أن يكون **Document root** = مجلد المشروع (وليس `public/`)، المستودع يتضمّن ملفات جاهزة في **جذر المشروع**:

| الملف | الدور |
|-------|--------|
| `index.php` | نقطة دخول Laravel (مسارات `vendor` و`bootstrap` و`storage` من الجذر) |
| `.htaccess` | Apache: إعادة توجيه `/build` و`/images` و`/storage` إلى `public/…` + حظر `vendor` و`.env` |
| `web.config` | IIS: نفس المنطق إن كانت الاستضافة Windows |

**لا حاجة** لنسخ `public/build` إلى مجلد `build/` في الجذر — القواعد تعيد كتابة الطلبات إلى `public/build/`.

#### ما يجب ضبطه على السيرفر

| البند | المطلوب |
|--------|---------|
| **`.env`** | أنشئه يدوياً. **`APP_URL`** = عنوان الموقع الكامل (مثال `https://yourdomain.com`) — مهم لروابط Vite و`asset()` |
| **`public/build/`** | يبقى كما هو بعد `npm run build` محلياً؛ الروابط `/build/assets/...` تعمل عبر `.htaccess` |
| **`public/storage`** | رابط رمزي إلى `storage/app/public` (صور المرفوعات على `/storage/...`) |
| **`storage/`** | قابل للكتابة (775 أو ما تدعمه الاستضافة) |
| **`storage/app/backups`** | أنشئه إن لم يكن — للنسخ الاحتياطي من الإعدادات |
| **المايغريشن** | لوحة الإدارة → **الإعدادات** → **تشغيل المايغريشن** |

#### Vite والأصول الثابتة

- بعد `npm run build` تبقى الملفات في **`public/build/`** (مُتتبَّعة في Git).
- مع جذر الموقع = جذر المشروع، المتصفح يطلب `/build/...` والملف `.htaccess` يوجّهها إلى `public/build/...`.
- تأكد أن **`APP_URL`** في `.env` على السيرفر صحيح؛ لا حاجة عادةً لتعديل `vite.config.js` إذا كان التطبيق على نفس النطاق.
- إن كان التطبيق في **مجلد فرعي** (مثال `https://domain.com/app/`)، قد تحتاج ضبط `ASSET_URL` أو مسار التطبيق في `.env` — راجع دعم الاستضافة.

#### صور المرفوعات (`public/storage`)

- **مطلوب**: `public/storage` → `storage/app/public`
- بدون SSH: symlink من لوحة الاستضافة، أو `php artisan storage:link` محلياً ثم رفع الرابط إن أمكن.
- بدون `public/storage` لن تظهر صور المركبات على `/storage/...`.

#### ما يبقى خارج Git (عمداً)

- `.env`
- `node_modules`
- `public/hot` (تطوير فقط)
- `public/storage` (على السيرفر)

---

### الخيار أ: جذر الموقع = `public/` (قياسي)

| البند | المطلوب |
|--------|---------|
| **جذر الموقع** | مجلد `public/` فقط — ليس جذر المستودع |
| **`.env`** | أنشئه يدوياً على السيرفر (انسخ من `.env.example`). **لا** تضعه في Git |
| **`public/build/`** | من Git بعد `npm run build` محلياً — **لا حاجة لـ npm على السيرفر** |
| **`public/hot`** | لا يرفع — للتطوير فقط |

باقي البنود (`storage/`، المايغريشن، `public/storage`) كما في الجدول أعلاه.

---

### المزامنة التلقائية من Vinstack (Cron)

تفعيل «تفعيل المزامنة التلقائية» في **الإعدادات** يحفظ التفضيل فقط — التنفيذ الفعلي يحتاج **مهمة cron** على السيرفر (Laravel Scheduler لا يعمل وحده).

#### المتطلبات

| البند | المطلوب |
|--------|---------|
| **الإعدادات** | تفعيل المزامنة التلقائية + حفظ + توكن API صالح |
| **Cron** | أحد الخيارين أدناه |
| **بدون cron** | استخدم زر **مزامنة الآن** يدوياً من الإعدادات |

#### الخيار 1 — جدولة Laravel (مُفضَّل)

تشغيل `schedule:run` كل دقيقة؛ التطبيق يُنفِّذ `vinstack:sync` كل **6 ساعات** عند تفعيل المزامنة:

```text
* * * * * cd /path/to/vinstack-lite && php artisan schedule:run >> /dev/null 2>&1
```

**cPanel → Cron Jobs:**

- **الدقيقة:** `*`
- **الساعة / اليوم / الشهر / يوم الأسبوع:** `*`
- **الأمر:** استبدل `/path/to/vinstack-lite` بمسار المشروع الفعلي (مثال: `/home/user/public_html/vinstack-lite`)

#### الخيار 2 — أمر مباشر كل 6 ساعات

بدون `schedule:run` — يحترم إعداد «تفعيل المزامنة التلقائية» ويتخطى التنفيذ إن كان معطّلاً:

```text
0 */6 * * * cd /path/to/vinstack-lite && php artisan vinstack:sync >> /dev/null 2>&1
```

**cPanel:** الدقيقة `0`، الساعة `*/6`، الباقي `*`.

#### ملاحظات

- استبدل `php` بمسار PHP على الاستضافة إن لزم (مثال: `/usr/local/bin/php` أو `/usr/bin/php8.2`).
- مع **جذر الموقع = جذر المشروع** (الخيار ب)، نفِّذ الأمر من مجلد المشروع حيث يوجد `artisan`.
- النجاح/الفشل يُسجَّل في `storage/logs/laravel.log`.
- آخر مزامنة تلقائية تظهر في الإعدادات بعد أول تشغيل ناجح عبر cron.

---

### SQLite في الإنتاج (جلسات / كاش / قفل الملف)

عند `DB_CONNECTION=sqlite` تكون **كل** بيانات التطبيق في ملف واحد `database/database.sqlite`. إذا كان `SESSION_DRIVER=database` و`CACHE_STORE=database` فإن الجلسات والكاش يكتبان في **نفس الملف** مع Sanctum وجدول الطابور، وهذا يسبب أخطاء شائعة:

- `SQLSTATE[HY000]: General error: 5 database is locked`
- وأحيانًا تلف الملف (`database disk image is malformed`)

**موصى به على السيرفر مع SQLite (ضعه في `.env` ثم امسح كاش الإعدادات):**

```env
DB_CONNECTION=sqlite
DB_BUSY_TIMEOUT=20000
DB_JOURNAL_MODE=wal
DB_SYNCHRONOUS=NORMAL

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

ملاحظات تشغيل:

1. بعد تعديل `.env`: نفّذ `php artisan config:clear` (أو أعد بناء `config:cache` بعد التعديل).
2. تأكد أن المجلدات قابلة للكتابة: `storage/framework/sessions` و`storage/framework/cache` و`database/` (وضع WAL ينشئ ملفات `-wal` / `-shm` بجانب `database.sqlite`).
3. `QUEUE_CONNECTION=sync` مناسب للاستضافة المشتركة بدون عامل طابور دائم. إذا استخدمت `database` يجب تشغيل `php artisan queue:work` باستمرار — وهذا يزيد ضغط الكتابة على SQLite.
4. التطبيق يفعّل WAL و`busy_timeout` تلقائيًا عبر `config/database.php`، ويخفّف تحديثات `personal_access_tokens.last_used_at` (كل ~5 دقائق بدل كل طلب API).
5. بعد التحويل إلى جلسات ملفات قد يحتاج المستخدمون لتسجيل الدخول مرة أخرى.

التفاصيل الكاملة لاستعادة ملف تالف في القسم الإنجليزي أدناه: **SQLite production note**.

---

## English

### Local machine (before upload)

1. **Composer** (when `composer.json` / lock changes):
   ```bash
   composer install --no-dev --optimize-autoloader
   ```
2. **Frontend** (after any change under `resources/js` or `resources/css`):
   ```bash
   npm install
   npm run build
   ```
3. **Commit** the generated `public/build/` directory. Do **not** commit `node_modules` or `.env`.
4. Deploy via **git pull** on the host or **FTP** the full app tree (include `vendor/` if the host cannot run Composer).

### Document root options

| Option | Document root | Notes |
|--------|---------------|--------|
| **A (preferred)** | `public/` only | Standard Laravel |
| **B** | **Project root** | When the panel cannot point to `public/` — use root `index.php`, `.htaccess`, and `web.config` |

### Option B: project root as document root

Root files ship in the repo:

- `index.php` — Laravel front controller with paths relative to project root
- `.htaccess` — rewrites `/build`, `/images`, `/storage` to `public/…`; denies `vendor`, `.env`, etc.
- `web.config` — same for IIS

No duplicate `build/` folder at project root is required.

Set **`APP_URL`** correctly in server `.env`. Built assets stay in `public/build/`; URLs like `/build/assets/...` are rewritten to that folder.

### Option A: `public/` as document root

Point the vhost to `public/` only. Migrations: Admin → **Settings**. Writable `storage/` and `storage/app/backups`. Symlink `public/storage` for uploads.

### Workflow checklist

- [ ] `composer install --no-dev`
- [ ] `npm run build`
- [ ] Commit `public/build/`
- [ ] Push / FTP (no `.env`, no `node_modules`)
- [ ] Server `.env` + correct document root (`public/` **or** project root per option above)
- [ ] Writable `storage/` and `storage/app/backups`
- [ ] `public/storage` symlink for uploads
- [ ] Admin → Settings → migrations
- [ ] With SQLite: `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync` (see below)

---

### SQLite production note (sessions / cache / locks / corruption)

This app defaults to **one** file: `database/database.sqlite` for **all** Eloquent data. If `SESSION_DRIVER=database` and `CACHE_STORE=database`, session and cache rows share that same file with Sanctum token touches and (optionally) the jobs table. Concurrent PHP-FPM writers on shared hosting commonly produce:

- `SQLSTATE[HY000]: General error: 5 database is locked` (often on `sessions` / `personal_access_tokens`)
- later: `database disk image is malformed`

**Recommended `.env` when staying on SQLite:**

```env
DB_CONNECTION=sqlite
DB_BUSY_TIMEOUT=20000
DB_JOURNAL_MODE=wal
DB_SYNCHRONOUS=NORMAL

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

Then run `php artisan config:clear` (or rebuild config cache). Ensure `storage/framework/sessions`, `storage/framework/cache`, and `database/` are writable (WAL creates sibling `-wal` / `-shm` files). Prefer MySQL/MariaDB for multi-process production when the host provides it.

App-side mitigations already in code:

- SQLite connector pragmas: `journal_mode=WAL`, `busy_timeout=20000`, `synchronous=NORMAL`
- Sanctum `last_used_at` writes throttled (~5 minutes) via `App\Models\PersonalAccessToken`

**If the SQLite file is already corrupted** (path like `/home/…/database/database.sqlite`):

1. **Immediate unblock (sessions only):** set `SESSION_DRIVER=file` and `CACHE_STORE=file` in server `.env`, clear config cache if used (`php artisan config:clear`), ensure session/cache dirs are writable. Users must log in again. This does **not** fix corrupted app tables.
2. **Backup first:** copy `database.sqlite` (+ `-wal` / `-shm` if present) off the live path before any repair.
3. **Integrity check** (SSH or local after download):
   ```bash
   sqlite3 database/database.sqlite 'PRAGMA integrity_check;'
   ```
4. **Dump-and-restore if check fails but dump still works:**
   ```bash
   sqlite3 database/database.sqlite ".recover" | sqlite3 database/database.recovered.sqlite
   # or: sqlite3 database/database.sqlite .dump > dump.sql
   # then import dump.sql into a new empty .sqlite file
   ```
   Put the app in maintenance, replace the live file with the recovered one, restore permissions, re-enable.
5. **Replace vs repair:** use dump/recover when business data must be kept. Replace with a fresh `database.sqlite` + migrate/seed **only** if there is no usable dump **and** Admin → Settings backups under `storage/app/backups` (or another backup) can restore. Never delete the corrupted file until a verified copy exists.
6. After recovery, keep session/cache on **file** (or move the app DB to MySQL) so the failure mode does not repeat.
