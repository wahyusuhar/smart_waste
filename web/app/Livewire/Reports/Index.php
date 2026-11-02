<?php

namespace App\Livewire\Reports;

use App\Enums\ReportCategory;
use App\Enums\ReportStatus;
use App\Models\Report;
use DateTime;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    // #[Url] keeps every filter in the query string, so filters survive paging, refresh and shared links.
    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $category = '';

    #[Url(as: 'dari', except: '')]
    public string $from = '';

    #[Url(as: 'sampai', except: '')]
    public string $to = '';

    // Any filter change starts again from page 1; otherwise the user could land on an empty page.
    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'status', 'category', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'status', 'category', 'from', 'to');
        $this->resetPage();
    }

    public function render(): View
    {
        $user = auth()->user();

        $reports = Report::query()
            ->with('user')
            // Regular users only ever see their own reports, whatever filters are sent.
            ->unless($user->isAdmin(), fn (Builder $query) => $query->whereBelongsTo($user))
            ->when(trim($this->search) !== '', function (Builder $query) use ($user) {
                $term = '%'.trim($this->search).'%';
                $query->where(function (Builder $query) use ($term, $user) {
                    $query->where('description', 'like', $term);
                    if ($user->isAdmin()) {
                        $query->orWhereHas('user', fn (Builder $query) => $query->where('name', 'like', $term));
                    }
                });
            })
            ->when(ReportStatus::tryFrom($this->status), fn (Builder $query, ReportStatus $status) => $query->where('status', $status))
            ->when(ReportCategory::tryFrom($this->category), fn (Builder $query, ReportCategory $category) => $query->where('category', $category))
            ->when($this->validDate($this->from), fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($this->validDate($this->to), fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->latest('id')
            ->paginate(10);

        return view('livewire.reports.index', [
            'reports' => $reports,
            'isAdmin' => $user->isAdmin(),
            'hasFilters' => $this->search !== '' || $this->status !== '' || $this->category !== '' || $this->from !== '' || $this->to !== '',
            'statusOptions' => collect(ReportStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all(),
            'categoryOptions' => collect(ReportCategory::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all(),
        ])->title($user->isAdmin() ? 'Laporan' : 'Laporan Saya');
    }

    public function paginationView(): string
    {
        return 'livewire.pagination';
    }

    // Values come from the URL, so anything that is not a real Y-m-d date is ignored.
    private function validDate(string $value): ?string
    {
        $date = DateTime::createFromFormat('!Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value ? $value : null;
    }
}
