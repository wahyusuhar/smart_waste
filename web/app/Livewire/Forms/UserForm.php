<?php

namespace App\Livewire\Forms;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * The user being edited is passed in by the component (where it is #[Locked]) instead of
 * being stored here, so the browser can never point the form at a different account.
 */
class UserForm extends Form
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $role = 'user';

    public string $password = '';

    public string $password_confirmation = '';

    public function setUser(User $user): void
    {
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone ?? '';
        $this->role = $user->role->value;
    }

    /**
     * @param  User|null  $user  null when creating a new account
     */
    public function save(?User $user): User
    {
        $this->validate($this->rulesFor($user));

        $user ??= new User;
        $user->fill([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone ?: null,
        ]);
        // Role is not mass assignable (see User::$fillable), so it is set explicitly here.
        $user->role = UserRole::from($this->role);
        if ($this->password !== '') {
            $user->password = $this->password;
        }
        $user->save();

        return $user;
    }

    /**
     * @return array<string, string>
     */
    public function validationAttributes(): array
    {
        return trans('validation.attributes') + ['role' => 'role'];
    }

    /**
     * @return array<string, mixed>
     */
    private function rulesFor(?User $user): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => [
                'required',
                Rule::enum(UserRole::class),
                // Prevents an admin from locking themselves out of the admin pages.
                function (string $attribute, mixed $value, \Closure $fail) use ($user) {
                    if ($user?->is(auth()->user()) && $value !== UserRole::Admin->value) {
                        $fail('Anda tidak dapat menurunkan role akun Anda sendiri.');
                    }
                },
            ],
            // On edit the password is only reset when the field is filled in.
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
