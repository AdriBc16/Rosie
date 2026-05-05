<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

abstract class BaseCrudController extends Controller
{
    protected string $modelClass;
    protected string $primaryKey;
    protected array $storeRules = [];
    protected array $updateRules = [];
    protected array $with = [];
    protected ?string $storeRequestClass = null;
    protected ?string $updateRequestClass = null;

    public function index(Request $request): JsonResponse
    {
        $query = $this->modelClass::query();

        if (!empty($this->with)) {
            $query->with($this->with);
        }

        if ($request->filled('take')) {
            $take = (int) $request->integer('take');
            $result = $query->take($take)->get();
        } else {
            $result = $query->get();
        }

        return response()->json($result);
    }

    public function show(int $id): JsonResponse
    {
        $record = $this->findOrFail($id);
        $record->load($this->with);

        return response()->json($record);
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validateForAction($request, true);
        $data = $this->transformData($data);

        /** @var Model $record */
        $record = $this->modelClass::create($data);
        $record->load($this->with);

        return response()->json($record, 201);
    }

    /**
     * @throws ValidationException
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $record = $this->findOrFail($id);
        $data = $this->validateForAction($request, false);
        $data = $this->transformData($data);

        $record->update($data);
        $record->load($this->with);

        return response()->json($record);
    }

    public function destroy(int $id): JsonResponse
    {
        $record = $this->findOrFail($id);
        $record->delete();

        return response()->json([
            'message' => 'Registro eliminado correctamente',
            'id' => $id,
        ]);
    }

    protected function findOrFail(int $id): Model
    {
        /** @var Model $record */
        $record = $this->modelClass::query()
            ->where($this->primaryKey, $id)
            ->firstOrFail();

        return $record;
    }

    protected function transformData(array $data): array
    {
        return $data;
    }

    /**
     * @throws ValidationException
     */
    protected function validateForAction(Request $request, bool $isStore): array
    {
        $requestClass = $isStore
            ? $this->storeRequestClass
            : ($this->updateRequestClass ?? $this->storeRequestClass);

        if ($requestClass === null) {
            $rules = $isStore
                ? $this->storeRules
                : (empty($this->updateRules) ? $this->storeRules : $this->updateRules);

            return $request->validate($rules);
        }

        /** @var FormRequest $formRequest */
        $formRequest = $requestClass::createFrom($request);
        $formRequest->setContainer(app());
        $formRequest->setRedirector(app('redirect'));
        $formRequest->setRouteResolver($request->getRouteResolver());
        $formRequest->setUserResolver($request->getUserResolver());

        if (!$formRequest->authorize()) {
            throw new AccessDeniedHttpException('This action is unauthorized.');
        }

        $validator = validator(
            $formRequest->all(),
            $formRequest->rules(),
            $formRequest->messages(),
            $formRequest->attributes(),
        );

        if (method_exists($formRequest, 'withValidator')) {
            $formRequest->withValidator($validator);
        }

        return $validator->validate();
    }
}
