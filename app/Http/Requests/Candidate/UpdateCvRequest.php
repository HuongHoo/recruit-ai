<?php

namespace App\Http\Requests\Candidate;

/**
 * Online CVs may change template/colour/objective; uploaded CVs may only be renamed.
 */
class UpdateCvRequest extends StoreOnlineCvRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        if ($this->route('cv')->isOnline()) {
            return parent::rules();
        }

        return [
            'title' => ['required', 'string', 'max:100'],
        ];
    }
}
