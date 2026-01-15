<?php

namespace App\Http\Requests\Reports;

use App\Models\Device;
use App\Models\Divipole;
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
            'puesto' => ['bail','required', 'string', 'max:20','exists:divipoles,code'],
            'imei'   => ['bail','required', 'string', 'max:50','exists:devices,imei'],
            'lat'    => ['bail','required', 'string', 'max:30'],
            'lon'    => ['bail','required', 'string', 'max:30'],
            'tipo'   => ['bail','required', 'in:checkin,checkout'],
        ];
    }

    public function messages(): array
    {
        return [
            'puesto.required' => __('The position field is required.'),
            'puesto.exists'   => __('Invalid QR code.'),
            'imei.required'   => __('The IMEI field is required.'),
            'imei.string'     => __('The IMEI field must be a string.'),
            'imei.max'        => __('The IMEI field may not be greater than 50 characters.'),
            'imei.exists'     => __('Invalid QR code.'),
            'lat.required'    => __('The latitude field is required.'),
            'lat.string'      => __('The latitude field must be a string.'),
            'lat.max'         => __('The latitude field may not be greater than 30 characters.'),
            'lon.required'    => __('The longitude field is required.'),
            'lon.string'      => __('The longitude field must be a string.'),
            'lon.max'         => __('The longitude field may not be greater than 30 characters.'),
            'tipo.required'   => __('The type field is required.'),
            'tipo.in'         => __('The type field must be either checkin or checkout.'),

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
        if ($position != $device->divipole->code) {
            $this->responseMessage(2);
        }
        // Evitar checkout sin un checkin previo en el mismo día
        if ($this->input('tipo') === 'checkout') {
            $hasCheckinToday = $device->deviceChecks()
                ->whereDate('created_at', now()->toDateString())
                ->where('type', 'checkin')
                ->exists();

            if (! $hasCheckinToday) {
                $this->responseMessage(4);
            }
        }
        $deviceCheck = $device->deviceChecks()
            ->whereDate('created_at', now()->toDateString())
            ->where('type', $this->input('tipo'))
            ->exists();
        if ($deviceCheck) {
            $this->responseMessage(3);
        }
    }

    protected function responseMessage(int $type)
    {
        $message = match ($type) {
            1 => __('Invalid QR code.'),
            2 => __('Device assigned to another polling station.'),
            3 => __('Device already reported.'),
            4 => __('You must check in before checking out.'),
            default => __('Unknown error.'),
        };
        throw new HttpResponseException(response()->json([
            'status'  => 'error',
            'message' => $message,
        ], 422));
    }

}
