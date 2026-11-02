<div>
    <x-page-header title="Pengguna" description="Kelola akun admin dan pengguna.">
        <x-button :href="route('admin.users.create')">Tambah Pengguna</x-button>
    </x-page-header>

    <x-card>
        <div class="border-b border-gray-200 p-4">
            <div class="max-w-md">
                <x-input label="Cari nama, email, atau nomor HP" name="search" type="search" wire:model.live.debounce.400ms="search" placeholder="Ketik kata kunci" />
            </div>
        </div>

        <div class="flex min-h-10 items-center px-4 py-2 text-[13px] text-gray-500">
            <span wire:loading.remove>{{ $users->total() }} pengguna</span>
            <span wire:loading>Memuat…</span>
        </div>

        <div wire:loading.class="opacity-60" class="border-t border-gray-200">
            @if ($users->isEmpty())
                @if ($search !== '')
                    <x-empty-state message="Tidak ada pengguna yang cocok dengan pencarian.">
                        <x-button variant="secondary" wire:click="$set('search', '')">Hapus pencarian</x-button>
                    </x-empty-state>
                @else
                    <x-empty-state message="Belum ada pengguna." />
                @endif
            @else
                {{-- Below 640px: stacked items, like the report list. --}}
                <ul class="divide-y divide-gray-100 sm:hidden">
                    @foreach ($users as $user)
                        <li wire:key="user-item-{{ $user->id }}" class="px-4 py-3">
                            <div class="flex items-start justify-between gap-3">
                                <a href="{{ route('admin.users.edit', $user) }}" class="min-w-0">
                                    <p class="truncate text-sm font-medium text-gray-900">{{ $user->name }}</p>
                                    <p class="truncate text-[13px] text-gray-500">{{ $user->email }}</p>
                                </a>
                                <x-role-badge :role="$user->role" />
                            </div>
                            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-[13px] text-gray-500">
                                <span>{{ $user->phone ?? 'Tanpa nomor HP' }}</span>
                                <span>{{ $user->reports_count }} laporan</span>
                            </div>
                            <div class="mt-3 flex gap-5 text-sm">
                                <a href="{{ route('admin.users.edit', $user) }}" class="font-medium text-accent hover:text-accent-hover">Ubah</a>
                                @can('delete', $user)
                                    <button
                                        type="button"
                                        class="font-medium text-red-700 hover:text-red-800"
                                        wire:click="delete({{ $user->id }})"
                                        wire:confirm="Hapus {{ $user->name }}? {{ $user->reports_count }} laporan miliknya beserta fotonya juga akan dihapus."
                                    >Hapus</button>
                                @endcan
                            </div>
                        </li>
                    @endforeach
                </ul>

                <div class="hidden overflow-x-auto sm:block">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-left text-[13px] text-gray-500">
                            <tr>
                                <th scope="col" class="px-4 py-2.5 font-medium">Nama</th>
                                <th scope="col" class="px-4 py-2.5 font-medium">Email</th>
                                <th scope="col" class="px-4 py-2.5 font-medium">Nomor HP</th>
                                <th scope="col" class="px-4 py-2.5 font-medium">Role</th>
                                <th scope="col" class="px-4 py-2.5 text-right font-medium">Laporan</th>
                                <th scope="col" class="px-4 py-2.5"><span class="sr-only">Aksi</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($users as $user)
                                <tr wire:key="user-row-{{ $user->id }}" class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="font-medium text-gray-900 hover:underline">{{ $user->name }}</a>
                                        @if ($user->is(auth()->user()))
                                            <span class="ml-1 text-[13px] text-gray-500">(Anda)</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-700">{{ $user->email }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap text-gray-700">{{ $user->phone ?? '—' }}</td>
                                    <td class="px-4 py-3"><x-role-badge :role="$user->role" /></td>
                                    <td class="px-4 py-3 text-right text-gray-700 tabular-nums">{{ $user->reports_count }}</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <a href="{{ route('admin.users.edit', $user) }}" class="font-medium text-accent hover:text-accent-hover">Ubah</a>
                                        @can('delete', $user)
                                            <button
                                                type="button"
                                                class="ml-4 font-medium text-red-700 hover:text-red-800"
                                                wire:click="delete({{ $user->id }})"
                                                wire:confirm="Hapus {{ $user->name }}? {{ $user->reports_count }} laporan miliknya beserta fotonya juga akan dihapus."
                                            >Hapus</button>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{ $users->links() }}
    </x-card>
</div>
