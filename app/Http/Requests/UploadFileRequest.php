<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class UploadFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        //sure, why not.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'file' => [
                'bail',
                'required',
                'file',
                'max:' . config('upload.storage.max_filesize'),
                function ($attribute, $value, $fail) {
                    if (!$value instanceof UploadedFile) {
                        return;
                    }

                    if (strlen($value->getClientOriginalName()) > 255) {
                        $fail('The original filename may not be greater than 255 characters.');
                    }

                    $blocked = config('upload.storage.blocked_extensions', []);
                    if (in_array(strtolower($value->getClientOriginalExtension()), $blocked, true)) {
                        $fail('This file type is not allowed.');
                    }
                },
            ],
        ];
    }
}
