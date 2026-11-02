<div>
    <a href="{{ route('admin.users.index') }}" class="mb-4 inline-block text-sm font-medium text-gray-600 hover:text-gray-900">Kembali ke daftar pengguna</a>

    <x-page-header
        :title="$user ? 'Ubah Pengguna' : 'Tambah Pengguna'"
        :description="$user ? $user->email : 'Buat akun baru untuk admin atau pengguna.'"
    />

    <form wire:submit="save" class="max-w-2xl space-y-6">
        <x-card title="Data akun">
            <div class="space-y-4 p-4">
                <x-input label="Nama" name="form.name" wire:model="form.name" autocomplete="off" />
                <x-input label="Email" name="form.email" type="email" wire:model="form.email" autocomplete="off" />
                <x-input label="Nomor HP (opsional)" name="form.phone" wire:model="form.phone" inputmode="tel" />
                <x-select
                    label="Role"
                    name="form.role"
                    wire:model="form.role"
                    :options="$roleOptions"
                    :disabled="$isSelf"
                />
                @if ($isSelf)
                    <p class="-mt-2 text-[13px] text-gray-500">Role akun Anda sendiri tidak dapat diubah.</p>
                @endif
            </div>
        </x-card>

        <x-card :title="$user ? 'Reset password' : 'Password'">
            <div class="space-y-4 p-4">
                @if ($user)
                    <p class="text-[13px] text-gray-500">Kosongkan jika password tidak ingin diubah.</p>
                @endif
                <x-input label="Password baru" name="form.password" type="password" wire:model="form.password" autocomplete="new-password" hint="Minimal 8 karakter." />
                <x-input label="Konfirmasi password" name="form.password_confirmation" type="password" wire:model="form.password_confirmation" autocomplete="new-password" />
            </div>
        </x-card>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <x-button variant="secondary" :href="route('admin.users.index')">Batal</x-button>
            <x-button type="submit" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">{{ $user ? 'Simpan Perubahan' : 'Tambah Pengguna' }}</span>
                <span wire:loading wire:target="save">Menyimpan…</span>
            </x-button>
        </div>
    </form>
</div>
