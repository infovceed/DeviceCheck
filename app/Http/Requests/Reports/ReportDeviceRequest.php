<?php

namespace App\Http\Requests\Reports;

use App\Models\Configuration;
use App\Models\Device;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
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
            'lat'    => ['bail','required', 'string', 'max:30'],
            'lon'    => ['bail','required', 'string', 'max:30'],
            'tipo'   => ['bail','required', 'in:checkin,checkout'],
            'imei'   => ['bail','required', 'string', 'max:50'],
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
        $imei = (string) $this->input('imei');
        $position = (string) $this->input('puesto');
        $type = (string) $this->input('tipo');

        $currentWorkShiftId = $this->currentWorkShiftId();

        $device = Device::query()
            ->where('imei', $imei)
            ->where('work_shift_id', $currentWorkShiftId)
            ->first();

        if ($device === null) {
            $this->responseMessage(1);
        }

        if (! $device->is_backup && ($device->divipole?->code !== $position)) {
            $this->responseMessage(2);
        }

        [$dayStart, $dayEnd] = $this->todayRange();

        if ($type === 'checkout') {
            $hasCheckinToday = $device->deviceChecks()
                ->where('created_at', '>=', $dayStart)
                ->where('created_at', '<', $dayEnd)
                ->where('type', 'checkin')
                ->exists();

            if (! $hasCheckinToday) {
                $this->responseMessage(4);
            }
        }

        $deviceCheck = $device->deviceChecks()
            ->where('created_at', '>=', $dayStart)
            ->where('created_at', '<', $dayEnd)
            ->where('type', $type)
            ->exists();
        if ($deviceCheck) {
            $this->responseMessage(3);
        }
    }

    private function currentWorkShiftId(): ?int
    {
        $value = cache()->remember('config.current_work_shift_id', 30, function (): ?int {
            return Configuration::query()
                ->whereKey(1)
                ->value('current_work_shift_id');
        });

        return $value !== null ? (int) $value : null;
    }

    /**
     * @return array{0: \Illuminate\Support\Carbon, 1: \Illuminate\Support\Carbon}
     */
    private function todayRange(): array
    {
        $start = now()->startOfDay();

        return [$start, (clone $start)->addDay()];
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
