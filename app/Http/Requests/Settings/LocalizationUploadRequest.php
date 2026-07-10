<?php

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use JsonException;

class LocalizationUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'locale' => ['required', 'string', 'regex:/^[a-z]{2}(-[A-Z]{2})?$/', 'max:8'],
            'label' => ['required', 'string', 'max:40'],
            'messages' => ['required', 'file', 'mimes:json', 'max:128'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $file = $this->file('messages');

                if (! $file) {
                    return;
                }

                try {
                    $messages = json_decode(
                        file_get_contents($file->getRealPath()) ?: '{}',
                        true,
                        512,
                        JSON_THROW_ON_ERROR,
                    );
                } catch (JsonException) {
                    $validator->errors()->add('messages', 'The messages file must contain valid JSON.');

                    return;
                }

                if (! is_array($messages) || array_is_list($messages)) {
                    $validator->errors()->add('messages', 'The messages file must contain a JSON object.');
                }
            },
        ];
    }
}
