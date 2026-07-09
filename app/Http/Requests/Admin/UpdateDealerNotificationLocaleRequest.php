<?php

namespace App\Http\Requests\Admin;

use App\Support\SupportedLocale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDealerNotificationLocaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'locale' => ['required', 'string', Rule::in(array_merge(SupportedLocale::CODES, ['default']))],
        ];
    }
}
