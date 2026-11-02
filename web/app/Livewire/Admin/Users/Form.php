<?php

namespace App\Livewire\Admin\Users;

use App\Enums\UserRole;
use App\Livewire\Forms\UserForm;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Create and edit page for user accounts (admin only).
 */
class Form extends Component
{
    #[Locked]
    public ?User $user = null;

    public UserForm $form;

    public function mount(?User $user = null): void
    {
        // On /admin/users/create the container passes an empty model, hence the exists check.
        if ($user?->exists) {
            Gate::authorize('update', $user);

            $this->user = $user;
            $this->form->setUser($user);

            return;
        }

        Gate::authorize('create', User::class);
    }

    public function save(): void
    {
        $this->user
            ? Gate::authorize('update', $this->user)
            : Gate::authorize('create', User::class);

        $saved = $this->form->save($this->user);

        session()->flash('status', $this->user
            ? "Data pengguna {$saved->name} disimpan."
            : "Pengguna {$saved->name} berhasil ditambahkan.");
        $this->redirectRoute('admin.users.index');
    }

    public function render(): View
    {
        return view('livewire.admin.users.form', [
            'roleOptions' => collect(UserRole::cases())->mapWithKeys(fn ($role) => [$role->value => $role->label()])->all(),
            'isSelf' => $this->user?->is(auth()->user()) ?? false,
        ])->title($this->user ? 'Ubah Pengguna' : 'Tambah Pengguna');
    }
}
