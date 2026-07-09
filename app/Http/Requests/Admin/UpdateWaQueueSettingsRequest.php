<?php

namespace App\Http\Requests\Admin;

use App\Support\DealerNotificationEvents;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWaQueueSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'wa_queue_base_url' => ['nullable', 'string', 'max:500'],
            'wa_queue_sender_id' => ['nullable', 'integer', 'min:1'],
            'wa_queue_enabled' => ['sometimes', 'boolean'],
            'dealer_notification_events' => ['sometimes', 'array'],
            'dealer_notification_events.*' => ['boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);

        if (isset($data['dealer_notification_events']) && is_array($data['dealer_notification_events'])) {
            $allowed = array_keys(DealerNotificationEvents::defaults());
            $data['dealer_notification_events'] = array_intersect_key(
                $data['dealer_notification_events'],
                array_flip($allowed),
            );
        }

        return $data;
    }
}
