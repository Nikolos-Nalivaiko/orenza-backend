<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/**
 * Base request for the API: guarantees the shared JSON error envelope.
 */
abstract class ApiFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @throws ValidationException
     */
    protected function failedValidation(Validator $validator): never
    {
        throw new ValidationException($validator, ApiResponse::error(
            message: __('messages.http.validation_failed'),
            status: Response::HTTP_UNPROCESSABLE_ENTITY,
            errorCode: 'validation_failed',
            errors: $validator->errors()->toArray(),
        ));
    }
}
