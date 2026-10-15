@extends('gentelella::page')

@section('title', $pageTitle)
@section('page_key', 'user')
@section('breadcrumb', 'Home > Sistem > ' . $pageTitle)

@section('content')
    <x-gentelella::card title="Manajemen User">
        <x-slot:options>
            <button type="button" class="btn btn-primary btn-sm" data-open-user-modal aria-haspopup="dialog"
                @if ($errors->any()) data-open-on-load data-editing-id="{{ old('editing_id') }}" @endif>
                Tambah User
            </button>
        </x-slot>

        @if (session('success'))
            <div class="alert alert-success" role="status">
                <div class="alert-body">{{ session('success') }}</div>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger" role="alert">
                <div class="alert-body">{{ session('error') }}</div>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">No.</th>
                        <th scope="col">Nama</th>
                        <th scope="col">Email</th>
                        <th scope="col">Role</th>
                        <th scope="col">Dibuat</th>
                        <th scope="col">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $users->firstItem() + $loop->index }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span class="badge {{ $user->role === 'admin' ? 'badge-primary' : 'badge-secondary' }}">
                                    {{ $user->role }}
                                </span>
                            </td>
                            <td>{{ $user->created_at?->format('d M Y') }}</td>
                            <td>
                                <button type="button" class="btn btn-outline btn-sm" data-edit-user
                                    data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}"
                                    data-user-email="{{ $user->email }}" data-user-role="{{ $user->role }}"
                                    data-update-url="{{ route('user.update', $user) }}" aria-haspopup="dialog">
                                    Edit
                                </button>
                                @if (auth()->id() !== $user->id)
                                    <form method="POST" action="{{ route('user.destroy', $user) }}" class="user-delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-danger btn-sm" data-delete-user
                                            data-user-name="{{ $user->name }}" aria-haspopup="dialog">
                                            Hapus
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">Belum ada data user.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $users->links() }}
    </x-gentelella::card>

    <template id="user-modal-template">
        <form method="POST" action="{{ route('user.store') }}" data-user-form>
            @csrf
            <input type="hidden" name="_method" value="" data-user-method disabled>
            <input type="hidden" name="editing_id" value="{{ old('editing_id') }}" data-user-editing-id>

            <div class="form-group">
                <label for="user-name" class="form-label">Nama</label>
                <input id="user-name" name="name" type="text" class="form-control" value="{{ old('name') }}"
                    maxlength="255" required>
                @error('name')
                    <div class="form-error" role="alert">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="user-email" class="form-label">Email</label>
                <input id="user-email" name="email" type="email" class="form-control" value="{{ old('email') }}"
                    maxlength="255" required>
                @error('email')
                    <div class="form-error" role="alert">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="user-role" class="form-label">Role</label>
                <select id="user-role" name="role" class="form-control" required>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" @selected(old('role', 'user') === $role)>
                            {{ ucfirst($role) }}
                        </option>
                    @endforeach
                </select>
                @error('role')
                    <div class="form-error" role="alert">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="user-password" class="form-label">Password</label>
                <input id="user-password" name="password" type="password" class="form-control" minlength="8" required
                    autocomplete="new-password">
                <div class="form-help" data-user-password-help>Password minimal 8 karakter.</div>
                @error('password')
                    <div class="form-error" role="alert">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label for="user-password-confirmation" class="form-label">Konfirmasi Password</label>
                <input id="user-password-confirmation" name="password_confirmation" type="password"
                    class="form-control" minlength="8" required autocomplete="new-password">
            </div>
        </form>
    </template>
@endsection
