<?php

namespace App\Http\Requests\Api\NearbySuggest;

use Illuminate\Foundation\Http\FormRequest;

class NearbySuggestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
            'radius' => 'required|numeric',
        ];
    }
}