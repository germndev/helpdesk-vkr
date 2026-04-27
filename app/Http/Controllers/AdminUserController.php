<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->orderByDesc('id')
            ->get();

        return view('admin.users.index', [
            'pageTitle' => 'Список пользователей',
            'menuItems' => $this->buildAdminMenu('users'),
            'users' => $users,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'pageTitle' => 'Создание пользователя',
            'menuItems' => $this->buildAdminMenu('users'),
            'formAction' => route('admin.users.store'),
            'formMethod' => 'post',
            'submitLabel' => 'Сохранить',
            'isEdit' => false,
            'userEntity' => new User(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);
        $this->assertAvatarExtension($request);
        $avatarPath = $this->storeAvatar($request);

        $user = new User();
        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];
        $user->login = $validated['login'];
        $user->role = $validated['role'];
        $user->password = $validated['password'];
        $user->name = trim($validated['last_name'].' '.$validated['first_name']);
        $user->email = null;
        $user->avatar_path = $avatarPath;
        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('status_message', 'Пользователь создан.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', [
            'pageTitle' => 'Редактирование пользователя',
            'menuItems' => $this->buildAdminMenu('users'),
            'formAction' => route('admin.users.update', $user),
            'formMethod' => 'put',
            'submitLabel' => 'Сохранить',
            'isEdit' => true,
            'userEntity' => $user,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $this->validatePayload($request, $user);
        $this->assertAvatarExtension($request);
        $avatarPath = $this->storeAvatar($request, $user);

        $user->first_name = $validated['first_name'];
        $user->last_name = $validated['last_name'];
        $user->login = $validated['login'];
        $user->role = $validated['role'];
        $user->name = trim($validated['last_name'].' '.$validated['first_name']);
        $user->avatar_path = $avatarPath;

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()
            ->route('admin.users.index')
            ->with('status_message', 'Пользователь обновлен.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $currentUser = auth()->user();

        if ($currentUser && $currentUser->id === $user->id) {
            return redirect()
                ->back()
                ->with('status_message', 'Нельзя удалить текущего авторизованного пользователя.');
        }

        $hasRelatedTickets = Ticket::query()
            ->where('user_id', $user->id)
            ->orWhere('assigned_to', $user->id)
            ->exists();

        if ($hasRelatedTickets) {
            return redirect()
                ->back()
                ->with('status_message', 'Нельзя удалить пользователя с привязанными заявками.');
        }

        $this->deleteAvatarFile($user->avatar_path);
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('status_message', 'Пользователь удален.');
    }

    private function validatePayload(Request $request, ?User $user = null): array
    {
        $passwordRules = $user
            ? ['nullable', 'string', 'min:6', 'max:255']
            : ['required', 'string', 'min:6', 'max:255'];

        return $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'login' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'login')->ignore($user?->id),
            ],
            'password' => $passwordRules,
            'role' => ['required', Rule::in(['user', 'support', 'admin'])],
            'avatar' => ['nullable', 'file', 'max:4096'],
        ]);
    }

    private function assertAvatarExtension(Request $request): void
    {
        if (! $request->hasFile('avatar')) {
            return;
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp'];
        $extension = strtolower((string) $request->file('avatar')->getClientOriginalExtension());

        if (! in_array($extension, $allowedExtensions, true)) {
            throw ValidationException::withMessages([
                'avatar' => 'Допустимые форматы: jpg, jpeg, png, webp, gif, bmp.',
            ]);
        }
    }

    private function storeAvatar(Request $request, ?User $user = null): ?string
    {
        if (! $request->hasFile('avatar')) {
            return $user?->avatar_path;
        }

        $file = $request->file('avatar');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $filename = Str::uuid()->toString().'.'.$extension;

        $this->deleteAvatarFile($user?->avatar_path);

        $targetDirectory = public_path('storage/avatars');
        if (! is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0755, true);
        }

        $file->move($targetDirectory, $filename);

        return 'avatars/'.$filename;
    }

    private function deleteAvatarFile(?string $avatarPath): void
    {
        if (! $avatarPath) {
            return;
        }

        $publicFilePath = public_path('storage/'.ltrim($avatarPath, '/'));
        if (is_file($publicFilePath)) {
            @unlink($publicFilePath);
        }

        $legacyStoragePath = storage_path('app/public/'.ltrim($avatarPath, '/'));
        if (is_file($legacyStoragePath)) {
            @unlink($legacyStoragePath);
        }
    }

    private function buildAdminMenu(string $active): array
    {
        return [
            ['type' => 'link', 'label' => 'Главная', 'icon' => 'home.svg', 'route' => 'dashboard', 'active' => $active === 'dashboard'],
            ['type' => 'link', 'label' => 'Заявки', 'icon' => 'requests.svg', 'route' => 'admin.tickets.index', 'active' => $active === 'tickets'],
            ['type' => 'link', 'label' => 'Пользователи', 'icon' => 'user.svg', 'route' => 'admin.users.index', 'active' => $active === 'users'],
            ['type' => 'link', 'label' => 'Правила классификации', 'icon' => 'categories.svg', 'route' => 'admin.classification-rules.index', 'active' => $active === 'classification-rules'],
        ];
    }
}
