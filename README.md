# KAML KAMAL

**Fast. Safe. Reliable.** — منصة لإدارة سيارات Vinstack: مزامنة API، إدارة تجار، إسناد سيارات، ولوحة تاجر.

## التقنيات

- **Backend:** Laravel 12, Sanctum, Queues, Scheduler
- **Frontend:** Vue 3, Pinia, Vue Router, PrimeVue

## المتطلبات

- PHP 8.2+
- Composer
- Node.js 20+ (مطلوب لـ Vite 7)
- SQLite (افتراضي) أو MySQL

## التثبيت

```bash
cd C:\xampp\htdocs\vinstack-lite
composer install
cp .env.example .env   # إن لم يكن .env موجوداً
php artisan key:generate
php artisan migrate --seed
npm install
```

## التشغيل

```bash
php artisan serve
npm run dev
```

افتح: http://127.0.0.1:8000

### حسابات تجريبية

| الدور | رقم الهاتف |
|--------|-------------|
| Admin | 07900000001 |
| Dealer | 07511077812 |

التاجر يمرّ بخطوة التحقق بخطوتين (2FA) بعد إدخال رقم الهاتف.

### مدة جلسة تسجيل الدخول

رموز Sanctum (`spa`) تبقى صالحة **شهراً واحداً** (30 يوماً = 43200 دقيقة) لجميع المستخدمين (أدمن وتاجر). الإعداد في `config/sanctum.php` (`expiration` / `SANCTUM_EXPIRATION`).

## Vinstack API

الـ Base URL الصحيح:

```env
VINSTACK_API_URL=https://app.vinstack.com/api/v1/client
VINSTACK_API_TOKEN=vk_your-key-here
```

أو من لوحة الأدمن → **إعدادات Vinstack**.

المزامنة تجلب السيارات من `GET /autos` (وليس `/vehicles`).

Endpoints متاحة في لوحة الأدمن (live من Vinstack):

| Endpoint | الصفحة |
|----------|--------|
| `/autos` | مزامنة → السيارات |
| `/containers` | الحاويات |
| `/invoices` | الفواتير |
| `/loading-lists`, `/payments`, `/parts`, `/quotes` | API جاهز (فارغ حالياً على حسابك) |

مزامنة يدوية:

```bash
php artisan vinstack:sync
```

مزامنة تلقائية كل ساعة (Scheduler):

```bash
php artisan schedule:work
```

## هيكل API

| Method | Path | الوصف |
|--------|------|--------|
| POST | `/api/login` | تسجيل الدخول برقم الهاتف |
| GET | `/api/admin/vehicles` | قائمة السيارات (أدمن) |
| POST | `/api/admin/vehicles/{id}/assign` | إسناد سيارة |
| GET | `/api/dealer/vehicles` | سيارات التاجر |

## المراحل القادمة

- إشعارات (Email / WhatsApp)
- Redis cache & queue
- Spatie Media Library للصور
