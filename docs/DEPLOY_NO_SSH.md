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

### على السيرفر

| البند | المطلوب |
|--------|---------|
| **جذر الموقع (Document root)** | مجلد `public/` فقط — ليس جذر المستودع |
| **`.env`** | أنشئه يدوياً على السيرفر (انسخ من `.env.example`). **لا** تضعه في Git |
| **المايغريشن** | لوحة الإدارة → **الإعدادات** → **تشغيل المايغريشن** |
| **`storage/`** | قابل للكتابة من PHP (صلاحيات 775 أو ما تدعمه الاستضافة) |
| **`storage/app/backups`** | أنشئه إن لم يكن موجوداً — قابل للكتابة (نسخ قاعدة البيانات من الإعدادات) |
| **`public/build/`** | يأتي من Git بعد `npm run build` محلياً — **لا حاجة لـ npm على السيرفر** |
| **`public/hot`** | لا يرفع — للتطوير فقط (`npm run dev`) |

### صور المرفوعات (`public/storage`)

التطبيق يخزن الصور في `storage/app/public` ويعرضها عبر `/storage/...`.

- **مطلوب** رابط رمزي: `public/storage` → `storage/app/public`
- إن **لا يوجد SSH**:
  - من لوحة الاستضافة: أنشئ symlink بنفس المسارين، أو
  - شغّل مرة واحدة محلياً: `php artisan storage:link` ثم ارفع مجلد `public/storage` إن سمحت الاستضافة بنسخ الرابط (بعض اللوحات تدعم ذلك)، أو
  - بديل ضعيف: انسخ محتويات `storage/app/public` إلى `public/storage` يدوياً بعد كل رفع صور (غير مُفضَّل للإنتاج).

بدون `public/storage` لن تظهر صور المركبات المرفوعة.

### ما يبقى خارج Git (عمداً)

- `.env` — إعدادات السيرفر
- `node_modules` — ثقيل؛ البناء محلياً فقط
- `public/hot` — Vite dev server
- `public/storage` — رابط/ملفات runtime على السيرفر

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

### On the server

| Item | Requirement |
|------|-------------|
| **Document root** | Point the vhost to the `public/` folder only |
| **`.env`** | Create on the server from `.env.example`; never commit |
| **Migrations** | Admin → **Settings** → run migrations in-app (Arabic UI: **تشغيل المايغريشن**) |
| **`storage/`** | Writable by the web server user |
| **`storage/app/backups`** | Must exist and be writable for DB backup/restore in Settings |
| **`public/build/`** | Tracked in Git; no `npm` on the server |
| **`public/hot`** | Dev only; keep out of production uploads |

### Uploaded images (`public/storage`)

Vehicle uploads use the `public` disk (`storage/app/public`, URLs under `/storage/...`). Laravel expects:

```text
public/storage  →  storage/app/public   (symlink)
```

Without SSH, create that symlink in the hosting control panel, or run `php artisan storage:link` once locally and deploy the link if your host allows it. **Uploaded images will 404** until this exists.

### Intentionally not in Git

- `.env`
- `node_modules` (build assets locally; ship `public/build/`)
- `public/hot`
- `public/storage` (per-server symlink)

### Workflow checklist

- [ ] `composer install --no-dev`
- [ ] `npm run build`
- [ ] Commit `public/build/`
- [ ] Push / FTP (no `.env`, no `node_modules`)
- [ ] Server `.env` + document root = `public/`
- [ ] Writable `storage/` and `storage/app/backups`
- [ ] `public/storage` symlink for uploads
- [ ] Admin → Settings → migrations
