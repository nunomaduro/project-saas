<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\EventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateActivityRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'events' => ['required', 'array'],
            'events.*.type' => ['required', 'string', Rule::enum(EventType::class)],
            'events.*.payload' => ['required', 'array'],
            'events.*.payload.url' => [
                'required_if:events.*.type,'.EventType::View->value,
                'string',
            ],
        ];
    }
}
