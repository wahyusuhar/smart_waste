<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Pengguna')]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', User::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function delete(int $userId): void
    {
        $user = User::findOrFail($userId);
        Gate::authorize('delete', $user);

        // The database removes the reports (cascade), but not their photo files, so collect them first.
        $photoPaths = $user->reports()->pluck('photo_path')->all();
        $user->delete();
        Storage::disk('public')->delete($photoPaths);

        session()->flash('status', "Pengguna {$user->name} beserta laporannya telah dihapus.");
        $this->redirectRoute('admin.users.index');
    }

    public function render(): View
    {
        $users = User::query()
            ->withCount('reports')
            ->when(trim($this->search) !== '', function (Builder $query) {
                $term = '%'.trim($this->search).'%';
                $query->where(fn (Builder $query) => $query
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term));
            })
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.admin.users.index', ['users' => $users]);
    }

    public function paginationView(): string
    {
        return 'livewire.pagination';
    }
}
