<?php

namespace Whilesmart\Campaigns\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Whilesmart\OwnerAccess\Concerns\AuthorizesOwnerRequest;

class RecordEventRequest extends FormRequest
{
    use AuthorizesOwnerRequest;

    public function authorize(): bool
    {
        return $this->authorizeOwnerOfBoundModel('campaign');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:60'],
            'is_conversion' => ['nullable', 'boolean'],
            'source' => ['nullable', 'string', 'max:120'],
            'value' => ['nullable', 'numeric'],
            'label' => ['nullable', 'string', 'max:120'],
            'subject_type' => ['nullable', 'string', 'required_with:subject_id'],
            'subject_id' => ['nullable', 'required_with:subject_type'],
            'visitor_hash' => ['nullable', 'string', 'max:64'],
            'metadata' => ['nullable', 'array'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}
