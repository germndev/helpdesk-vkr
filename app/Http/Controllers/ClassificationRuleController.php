<?php

namespace App\Http\Controllers;

use App\Models\ClassificationRule;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClassificationRuleController extends Controller
{
    public function index(): View
    {
        $rules = ClassificationRule::query()
            ->with(['triggers', 'responsibles'])
            ->orderBy('name')
            ->get();

        return view('admin.classification-rules.index', [
            'pageTitle' => 'Правила классификации',
            'menuItems' => $this->buildAdminMenu('classification-rules'),
            'rules' => $rules,
        ]);
    }

    public function create(): View
    {
        return view('admin.classification-rules.form', [
            'pageTitle' => 'Создание правила',
            'menuItems' => $this->buildAdminMenu('classification-rules'),
            'rule' => new ClassificationRule([
                'is_active' => true,
            ]),
            'supportUsers' => $this->supportUsers(),
            'formAction' => route('admin.classification-rules.store'),
            'formMethod' => 'post',
            'submitLabel' => 'Сохранить правило',
            'triggerRows' => old('triggers', [['phrase' => '', 'weight' => 1]]),
            'selectedResponsibles' => old('responsibles', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $rule = ClassificationRule::query()->create($data['rule']);
        $rule->triggers()->createMany($data['triggers']);
        $rule->responsibles()->sync($data['responsibles']);

        return redirect()
            ->route('admin.classification-rules.index')
            ->with('status_message', 'Правило классификации создано.');
    }

    public function edit(ClassificationRule $classificationRule): View
    {
        $classificationRule->load(['triggers', 'responsibles']);

        return view('admin.classification-rules.form', [
            'pageTitle' => 'Редактирование правила',
            'menuItems' => $this->buildAdminMenu('classification-rules'),
            'rule' => $classificationRule,
            'supportUsers' => $this->supportUsers(),
            'formAction' => route('admin.classification-rules.update', $classificationRule),
            'formMethod' => 'put',
            'submitLabel' => 'Сохранить изменения',
            'triggerRows' => old(
                'triggers',
                $classificationRule->triggers
                    ->map(fn ($trigger) => [
                        'phrase' => $trigger->phrase,
                        'weight' => $trigger->weight,
                    ])
                    ->all() ?: [['phrase' => '', 'weight' => 1]],
            ),
            'selectedResponsibles' => old('responsibles', $classificationRule->responsibles->pluck('id')->all()),
        ]);
    }

    public function update(Request $request, ClassificationRule $classificationRule): RedirectResponse
    {
        $data = $this->validatedData($request);

        $classificationRule->update($data['rule']);
        $classificationRule->triggers()->delete();
        $classificationRule->triggers()->createMany($data['triggers']);
        $classificationRule->responsibles()->sync($data['responsibles']);

        return redirect()
            ->route('admin.classification-rules.index')
            ->with('status_message', 'Правило классификации обновлено.');
    }

    private function validatedData(Request $request): array
    {
        $triggers = collect($request->input('triggers', []))
            ->map(fn ($trigger) => [
                'phrase' => trim((string) ($trigger['phrase'] ?? '')),
                'weight' => (int) ($trigger['weight'] ?? 0),
            ])
            ->filter(fn ($trigger) => $trigger['phrase'] !== '')
            ->values()
            ->all();

        $request->merge([
            'triggers' => $triggers,
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'responsibles' => ['required', 'array', 'min:1'],
            'responsibles.*' => ['integer', Rule::exists('users', 'id')],
            'is_active' => ['nullable', 'boolean'],
            'triggers' => ['required', 'array', 'min:1'],
            'triggers.*.phrase' => ['required', 'string', 'max:255'],
            'triggers.*.weight' => ['required', 'integer', 'min:1', 'max:5'],
        ]);

        return [
            'rule' => [
                'name' => $validated['name'],
                'type' => 'category',
                'target_value' => $validated['name'],
                'assigned_to' => null,
                'is_active' => $request->boolean('is_active'),
            ],
            'responsibles' => $validated['responsibles'],
            'triggers' => $validated['triggers'],
        ];
    }

    private function supportUsers()
    {
        return User::query()
            ->whereIn('role', ['admin', 'support'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
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
