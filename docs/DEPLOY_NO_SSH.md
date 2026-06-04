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
