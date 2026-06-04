# خطة إدخال السيارات يدوياً — vinstack-lite

**المستودع الفعلي:** `C:\xampp\htdocs\vinstack-lite` (تطبيق Laravel كامل).  
**ملاحظة:** `C:\Users\ALAA-PC\Projects\vinstack-lite` يحتوي ملفاً جزئياً فقط وليس التطبيق الكامل.

---

## ملخص بالعربية (للمستخدم)

| الهدف | الحل المقترح |
|--------|----------------|
| إضافة سيارات مثل المزامنة + يدوياً | مزامنة Vinstack تبقى كما هي؛ مسار جديد **إدخال يدوي** مع `source = manual` |
| نموذج سهل | صفحة/حوار **إضافة سيارة** (3–5 خطوات) بالعربية، حقول مطلوبة قليلة |
| فك الشاصي | زر «فك الشاصي» يستدعي NHTSA VPIC ويملأ السنة/الصانع/الموديل/الوقود تلقائياً |
| قوائم منسدلة | قيم مثل **دبي، مرسين** تُدار من **إعدادات المدير** (JSON) وليست حرة الكتابة |
| التجار | السيارة اليدوية **قابلة للإسناد** مثل المزامنة؛ التاجر يراها فقط بعد الإسناد |

**تدفق UX موصى به (4 خطوات):**

1. **الشاصي** — إدخال VIN (17 حرفاً) + «فك الشاصي» (VPIC).
2. **المواصفات** — مراجعة/تعديل: سنة، صانع، موديل، نوع الوقود، لون (اختياري).
3. **الشحن والبيع** — قوائم: نقطة التحميل، الوجهة، المزاد، اللوت، تواريخ، حاوية/حجز.
4. **حفظ** — إنشاء السيارة والعودة للقائمة (شارة «يدوي»)؛ الإسناد للتاجر من القائمة كالعادة.

---

## 1. الوضع الحالي (ما قرأناه)

### جدول `vehicles`

| عمود | ملاحظة |
|------|--------|
| `vinstack_id` | **unique، مطلوب** — يعيق السجلات اليدوية بدون معرّف Vinstack |
| `vin`, `make`, `model`, `year`, `price` | أعمدة علوية للعرض والفرز |
| `status` | `VehicleStatus` (مثلاً `available` عند الإنشاء) |
| `images` | JSON مصفوفة URLs |
| `raw_data` | JSON — **نسخة كاملة من payload Vinstack** + `images_by_stage` بعد المزامنة |
| `notes` | نص حر |

### `SyncVehiclesAction`

- يجلب `/autos` من Vinstack.
- يطابق بـ `vinstack_id`؛ ينشئ أو يحدّث.
- `raw_data` = `[...$item, 'images_by_stage' => ...]`.
- يعيّن `status = available` عند الإنشاء فقط.

### `VehicleDetailService`

- يدمج `raw_data` المحلي مع `VinstackService::auto($vin)` عند وجود VIN.
- أقسام العرض تعتمد مفاتيح `raw_data` (انظر §3).
- سيارات يدوية بدون Vinstack: يجب **تخطي** استدعاء API أو سيفشل/يبطئ بلا فائدة.

### واجهة المدير `VehiclesPage.vue`

- قائمة + بحث + إسناد تاجر + درج تفاصيل.
- نص فارغ يذكر بالفعل: «بعد المزامنة من Vinstack **أو الإضافة اليدوية**» — الواجهة غير مبنية بعد.

### التاجر

- `Dealer\VehicleController`: فقط مركبات لها `assignments` نشطة للتاجر.
- **لا فرق** بين يدوي ومزامن بعد الإسناد — نفس `AssignVehicleAction`.

### شكل `raw_data` المتوقع (من الكود)

مفاتيح مستخدمة في `VehicleDetailService` و`vehicleMeta.js`:

**معلومات المركبة:** `vin`, `year`, `make`, `model`, `vehicle_type`, `fuel_type`, `color`, `weight`  
**البيع:** `auction`, `buyer`, `lot`, `purchase_date`, `value`  
**تواريخ:** `purchase_date`, `arrived_terminal_date`, `left_terminal`, `title_received`  
**الشحن:** `shipping_method`, `delivery_type`, `container_number`, `booking_number`, `loading_point`, `destination`, `location`  
**أخرى:** `status`, `title_status`, `title_type`, `keys`  
**صور (مزامنة):** `thumbnail_url`, `images`, `images_by_stage`, `created_at` (للترتيب `newestFirst`)

---

## 2. NHTSA VPIC — عينة VIN `4T1DAACK5SU031835`

**Endpoint:**  
`GET https://vpic.nhtsa.dot.gov/api/vehicles/decodevinvalues/{VIN}?format=json`

**استجابة:** `Results[0]` كائن مسطح (~140 مفتاحاً). هذا VIN: Toyota Camry 2025 HEV، فك نظيف (`ErrorCode: "0"`).

### جدول الربط VPIC → أعمدة / `raw_data`

| مفتاح VPIC (`Results[0]`) | قيمة العينة | حقلنا | ملاحظة |
|---------------------------|-------------|--------|--------|
| `VIN` | 4T1DAACK5SU031835 | `vin` + `raw_data.vin` | |
| `ModelYear` | 2025 | `year` + `raw_data.year` | int |
| `Make` | TOYOTA | `make` + `raw_data.make` | normalise Title Case في UI |
| `Model` | Camry | `model` + `raw_data.model` | |
| `FuelTypePrimary` | Gasoline | `raw_data.fuel_type` | |
| `FuelTypeSecondary` | Electric | دمج مع Primary | مثال: `Gasoline / Electric (HEV)` |
| `ElectrificationLevel` | HEV (...) | `raw_data.electrification_level` | اختياري في النموذج |
| `VehicleType` | PASSENGER CAR | `raw_data.vehicle_type` | أو `BodyClass` |
| `BodyClass` | Sedan/Saloon | `raw_data.body_class` | عرض إضافي |
| `DriveType` | 4x2 | `raw_data.drive_type` | |
| `Doors` | 4 | `raw_data.doors` | |
| `DisplacementL` | 2.5 | `raw_data.displacement_l` | |
| `EngineCylinders` | 4 | `raw_data.engine_cylinders` | |
| `EngineHP` | 184 | `raw_data.engine_hp` | |
| `EngineModel` | A25A-FXS | `raw_data.engine_model` | |
| `TransmissionStyle` | CVT (...) | `raw_data.transmission` | |
| `PlantCountry` | UNITED STATES (USA) | `raw_data.plant_country` | |
| `PlantCity` | GEORGETOWN | `raw_data.plant_city` | |
| `PlantState` | KENTUCKY | `raw_data.plant_state` | |
| `Manufacturer` | TOYOTA MOTOR... | `raw_data.manufacturer` | |
| `Series` | 80 Series | `raw_data.series` | |
| `GVWR` | Class 1C: ... | `raw_data.gvwr` | |
| `ErrorCode` / `ErrorText` | 0 / clean | `raw_data.vpic_error` | عرض تحذير إن ≠ 0 |
| *(كامل Results[0])* | — | `raw_data.vpic` | حفظ snapshot للتدقيق |

**لا يوفّر VPIC (تبقى يدوية/إعدادات):** `color`, `weight`, `auction`, `lot`, `loading_point`, `destination`, `container_number`, `booking_number`, `purchase_date`, `value`, `keys`, `title_status`, صور المزاد.

**تطبيع الوقود للعرض (`vehicleFuelClass`):**

```text
إذا FuelTypeSecondary غير فارغ و Primary = Gasoline و Secondary = Electric → fuel_type = "Hybrid (Gasoline/Electric)"
وإلا إذا Secondary فارغ → Primary فقط
```

---

## 3. التصميم المقترح

### 3.1 قاعدة البيانات

**Migration:** `add_source_to_vehicles_table`

```php
$table->string('source', 20)->default('vinstack')->index(); // enum: vinstack | manual
$table->string('vinstack_id')->nullable()->change();       // manual → NULL
// unique partial: vinstack_id unique WHERE NOT NULL (أو unique مركّب مع source)
$table->unique('vin'); // اختياري: منع تكرار VIN إن كان مطلوباً عملياً
```

**Enum PHP:** `App\Enums\VehicleSource: Vinstack, Manual`

**نموذج `Vehicle`:** أضف `source` إلى `$fillable` + cast.

**حماية المزامنة:** في `SyncVehiclesAction`، عند التحديث:

```php
if ($vehicle && $vehicle->source === VehicleSource::Manual) {
    continue; // أو تحديث vinstack_id فقط إن وُجدت مطابقة لاحقاً — افتراضي: skip
}
```

### 3.2 إعدادات القوائم (Admin Settings)

**خيار أ (أقل تغييراً):** عمود JSON على `vinstack_settings`:

```json
{
  "shipping_destinations": ["Dubai", "Mersin", "Jebel Ali"],
  "loading_points": ["New York", "Savannah", "Houston"],
  "auctions": ["Copart", "IAAI", "Manheim"],
  "shipping_methods": ["RoRo", "Container"],
  "delivery_types": ["Door", "Port"],
  "title_types": ["Clean", "Salvage"]
}
```

**API:**

- `GET /api/admin/settings/vehicle-options` — للنموذج
- `PUT /api/admin/settings/vehicle-options` — من تبويب جديد في `SettingsPage.vue` (محرر قائمة بسيط: إضافة/حذف سطر)

**عام للتجار (اختياري):** توسيع `GET /api/settings/public` بوجهات الشحن فقط إن لزم.

### 3.3 API

| Method | Path | وصف |
|--------|------|-----|
| `GET` | `/api/admin/vehicles/decode-vin/{vin}` | Proxy VPIC → `{ data: { mapped, vpic, valid } }` |
| `POST` | `/api/admin/vehicles` | إنشاء يدوي — `StoreManualVehicleRequest` |
| `GET` | `/api/admin/settings/vehicle-options` | قوائم منسدلة |
| `PUT` | `/api/admin/settings/vehicle-options` | تحديث القوائم |

**`DecodeVinController` (أو method على `VehicleController`):**

- Validate VIN: 17 حرفاً، بدون I/O/Q.
- `Http::timeout(15)->get("https://vpic.nhtsa.dot.gov/api/vehicles/decodevinvalues/{$vin}?format=json")`
- Map عبر `VpicDecoderService::mapToRawData(array $row): array`
- لا يخزّن في DB.

**`CreateManualVehicleAction`:**

```php
Vehicle::create([
    'source' => VehicleSource::Manual,
    'vinstack_id' => null,
    'vin' => $request->vin,
    'make' => $request->make,
    'model' => $request->model,
    'year' => $request->year,
    'price' => $request->price,
    'status' => VehicleStatus::Available,
    'images' => [],
    'raw_data' => [
        ...$request->rawFields(),
        'source' => 'manual',
        'created_at' => now()->toIso8601String(),
        'vpic' => $request->input('vpic'), // اختياري من خطوة الفك
    ],
    'notes' => $request->notes,
]);
```

**`VehicleDetailService`:**

```php
if ($vehicle->source === VehicleSource::Manual) {
    $fresh = [];
}
```

### 3.4 Vue

| ملف | دور |
|-----|-----|
| `resources/js/components/ManualVehicleForm.vue` | نموذج متعدد الخطوات أو صفحة واحدة |
| `resources/js/pages/admin/ManualVehiclePage.vue` | اختياري: route `admin/vehicles/new` |
| تعديل `VehiclesPage.vue` | زر «إضافة سيارة» يفتح الحوار/التوجيه |
| تعديل `SettingsPage.vue` | قسم «خيارات نموذج السيارة» |
| `resources/js/api/vehicles.js` | `decodeVin(vin)`, `createManualVehicle(payload)` |

**مكونات PrimeVue:** `InputText`, `InputNumber`, `Select`, `DatePicker`, `Button`, `Stepper` (4 خطوات).

**شارة في القائمة:** `VehicleListRow` — إذا `source === 'manual'` → pill «يدوي».

### 3.5 التاجر والإسناد

| سؤال | جواب |
|------|------|
| هل تُسند السيارة اليدوية؟ | **نعم** — نفس `POST .../assign` |
| هل يراها التاجر قبل الإسناد؟ | **لا** — `whereHas('assignments'...)` |
| صور Vinstack؟ | غالباً فارغة؛ رفع عبر `VehicleUploadedImage` كالحالي |
| مزامنة لاحقة بنفس VIN في Vinstack؟ | سياسة: إما ربط يدوي (`vinstack_id` + `source` يبقى manual) أو سجل منفصل — **يوصى بعدم الدمج التلقائي** في V1 |

---

## 4. مراحل التنفيذ

### المرحلة 0 — تخطيط (هذا المستند) ✅

### المرحلة 1 — Backend أساس (~1–2 يوم)

- [ ] Migration `source` + `vinstack_id` nullable
- [ ] `VehicleSource` enum + تحديث Model
- [ ] `VpicDecoderService` + `GET decode-vin`
- [ ] `CreateManualVehicleAction` + `POST /admin/vehicles` + FormRequest
- [ ] تعديل `SyncVehiclesAction` + `VehicleDetailService`
- [ ] اختبارات Feature: decode mock HTTP، create manual، sync skip

### المرحلة 2 — إعدادات القوائم (~0.5 يوم)

- [ ] JSON `vehicle_options` على settings
- [ ] GET/PUT endpoints + واجهة Settings

### المرحلة 3 — Frontend (~1–2 يوم)

- [ ] `ManualVehicleForm.vue` + ربط API
- [ ] زر إضافة في `VehiclesPage`
- [ ] شارة «يدوي» في القائمة

### المرحلة 4 — تحسينات (~اختياري)

- [ ] تحقق VIN مكرر قبل الحفظ
- [ ] رفع صور من نفس النموذج
- [ ] تصدير/طباعة ملخص السيارة اليدوية
- [ ] ربط لاحق بسجل Vinstack إن ظهر في المزامنة

---

## 5. Scaffolding مضاف (هيكل فقط)

| ملف | حالة |
|-----|------|
| `database/migrations/2026_06_04_200000_add_source_to_vehicles_table.php` | migration فارغة تقريباً |
| `app/Enums/VehicleSource.php` | enum |
| `app/Http/Controllers/Admin/ManualVehicleController.php` | stubs `store`, `decodeVin` |
| `routes/api.php` | routes مسجّلة |

**تشغيل بعد الدمج الكامل:** `php artisan migrate`

---

## 6. مخاطر وقرارات

1. **`vinstack_id` unique:** يجب nullable أو معرّف synthetic؛ التوصية: **NULL + source**.
2. **تعارض VIN:** manual ثم sync بنفس VIN — تعريف سياسة واضحة في المرحلة 4.
3. **VPIC خارجي:** timeout + رسالة عربية عند الفشل؛ السماح بالإكمال يدوياً.
4. **تطبيع Make/Model:** VPIC أحرف كبيرة — `Str::title()` عند الحفظ.
5. **SQLite vs MySQL:** `newestFirst` يعتمد `raw_data.created_at` — ضبطه عند الإنشاء اليدوي.

---

## 7. مراجع كود حالية

| مكوّن | مسار |
|--------|------|
| Model | `app/Models/Vehicle.php` |
| Migration | `database/migrations/2026_06_03_180002_create_vehicles_table.php` |
| Sync | `app/Actions/SyncVehiclesAction.php` |
| Details | `app/Services/VehicleDetailService.php` |
| Admin list | `resources/js/pages/admin/VehiclesPage.vue` |
| Meta UI | `resources/js/utils/vehicleMeta.js` |
| Routes | `routes/api.php` |

---

*آخر تحديث: 2026-06-04 — تخطيط أولي + stubs.*
