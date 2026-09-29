<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('avatar_member_id') === '') {
            $this->merge(['avatar_member_id' => null]);
        }

        if ($this->boolean('_showcase_titles_present') && ! $this->has('showcase_titles')) {
            $this->merge(['showcase_titles' => []]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:160'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'avatar_member_id' => ['nullable', 'integer', Rule::exists('members', 'id')],
            'selected_title' => [
                'nullable',
                'string',
                Rule::in(array_keys($this->user()->unlockedTitles())),
            ],
            '_showcase_titles_present' => ['sometimes', 'accepted'],
            'showcase_titles' => ['sometimes', 'array', 'max:3'],
            'showcase_titles.*' => [
                'required',
                'string',
                'distinct',
                Rule::in(array_diff(
                    array_keys($this->user()->unlockedTitles()),
                    [$this->input('selected_title', $this->user()->selected_title)],
                )),
            ],
        ];
    }
}
