<?php

namespace App\Modules\ChargingPoints\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConnectChargingPointRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'protocol' => 'required|in:ocpp1.6,ocpp2.0.1,custom',
            'connection_type' => 'required|in:websocket,soap,json',
            'endpoint' => 'required|url',
            'credentials.username' => 'required_if:protocol,custom',
            'credentials.password' => 'required_if:protocol,custom'
        ];
    }
}