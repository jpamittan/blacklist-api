<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BlacklistRequest extends FormRequest
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
        $urlRegex = '/^(https?:\/\/)?([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?$/';

        return [
            'mobile_number'         => 'required',
            'name'                  => 'required',
            'country_code'          => 'required',
            'identification_type'   => 'required',
            'identification_number' => 'required',
            'front_of_id_card'      => 'required|regex:' . $urlRegex,
            'birthdate'             => 'required',
            
        ];
    }

    public function messages()
    {
        return [
            'front_of_id_card.regex' => 'The format must be a valid URL.'
        ];
    }
}
