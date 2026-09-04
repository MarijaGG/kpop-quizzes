<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class FavoriteUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['favorite_groups', 'favorite_members', 'favorite_albums'] as $field) {
            $values = $this->input($field, []);
            $this->merge([$field => array_map(fn ($value) => $value === '' ? null : $value, $values)]);
        }
    }

    public function rules(): array
    {
        $data = json_decode(file_get_contents(resource_path('data/api.json')), true) ?? [];
        $groupIds = array_column($data['groups'] ?? [], 'id');
        $memberIds = array_column($data['members'] ?? [], 'id');
        $albumIds = array_column($data['albums'] ?? [], 'id');

        return [
            'favorite_groups' => ['array', 'size:3'],
            'favorite_groups.*' => ['nullable', 'integer', Rule::in($groupIds)],
            'favorite_members' => ['array', 'size:3'],
            'favorite_members.*' => ['nullable', 'integer', Rule::in($memberIds)],
            'favorite_albums' => ['array', 'size:3'],
            'favorite_albums.*' => ['nullable', 'integer', Rule::in($albumIds)],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            foreach (['favorite_groups', 'favorite_members', 'favorite_albums'] as $field) {
                $values = array_filter($this->input($field, []), fn ($value) => $value !== null);
                if (count($values) !== count(array_unique($values))) {
                    $validator->errors()->add($field, 'Choose each favourite only once.');
                }
            }
        });
    }

    public function authorize(): bool
    {
        return Auth::check();
    }
}
