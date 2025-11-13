<?php

namespace App\Http\Requests\Reports;

use App\Models\Device;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReportDeviceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'puesto' => ['required', 'string', 'max:10'],
            'imei'   => ['required', 'string', 'max:50'],
            'lat'    => ['required', 'string', 'max:30'],
            'lon'    => ['required', 'string', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'puesto.required' => __('The position field is required.'),
            'imei.required'   => __('The IMEI field is required.'),
            'imei.string'     => __('The IMEI field must be a string.'),
            'imei.max'        => __('The IMEI field may not be greater than 50 characters.'),
            'lat.required'    => __('The latitude field is required.'),
            'lat.string'      => __('The latitude field must be a string.'),
            'lat.max'         => __('The latitude field may not be greater than 30 characters.'),
            'lon.required'    => __('The longitude field is required.'),
            'lon.string'      => __('The longitude field must be a string.'),
            'lon.max'         => __('The longitude field may not be greater than 30 characters.'),
        ];
    }

    /**
     * Override default behavior to return JSON instead of redirecting.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => $validator->errors()->first(),
        ], 422));
    }

    protected function passedValidation()
    {
        $imei = $this->input('imei');
        logger("IMEI ENTRANTE");
        logger($imei);
        $position = $this->input('puesto');
        $device = Device::where('imei', $imei)->first();
        if (!$device && !isset($device->imei)) {
            $this->responseMessage(1);
        }
        if ($position != $device->divipole->code) {
            $this->responseMessage(2);
        }
        if ($device->status) {
            $this->responseMessage(3);
        }
    }

    protected function responseMessage(int $type)
    {
        $message = match ($type) {
            1 => __('Device identifier not found.'),
            2 => __('Device assigned to another polling station.'),
            3 => __('Device already reported.'),
            default => __('Unknown error.'),
        };
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => $message,
        ], 422));
    }

}
