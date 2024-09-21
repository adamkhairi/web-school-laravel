<?php

namespace App\Http\Requests;

use App\Enums\RoleType;
use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    public function authorize()
    {
        // Ensure the role is converted to RoleType
        return $this->user()->hasRole(RoleType::from('Admin'));
    }

    public function rules()
    {
        return [
            'name' => 'required|string|unique:roles,name',
            'description' => 'nullable|string',
        ];
    }
}
