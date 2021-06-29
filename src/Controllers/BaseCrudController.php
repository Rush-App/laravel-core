<?php

namespace RushApp\Core\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller;
use RushApp\Core\Enums\ModelRequestParameters;
use RushApp\Core\Models\BaseModelTrait;

abstract class BaseCrudController extends Controller
{
    protected string $modelClassController;
    protected Model|BaseModelTrait $baseModel;
    protected ?string $storeRequestClass = null;
    protected ?string $updateRequestClass = null;
    protected array $withRelationNames = [];

    public function __construct()
    {
        $entityId = $this->getEntityId();
        $this->baseModel = $entityId ? $this->modelClassController::find($entityId) : new $this->modelClassController;
    }

    protected function getEntityId(): ?int
    {
        return request()->route()->parameters()[0] ?? null;
    }

    public function index(Request $request)
    {
        //check for paginate data
        $paginate = $request->get(ModelRequestParameters::PAGINATE, false);

        $query = $this->baseModel->getQueryBuilder($request->all(), $this->withRelationNames);

        return $paginate
            ? $this->successResponse($query->paginate($paginate))
            : $this->successResponse($query->get());
    }

    public function show(Request $request)
    {
        $entityId = $request->route($this->baseModel->getTableSingularName());
        $query = $this->baseModel->getQueryBuilderOne($request->all(), $entityId, $this->withRelationNames);

        return $this->successResponse($query->first());
    }

    public function store(Request $request)
    {
        $this->validateRequest($request, $this->storeRequestClass);

        $modelAttributes = $this->baseModel->createOne($request->all());

        return $this->successResponse($modelAttributes);
    }

    public function update(Request $request)
    {
        $validationRequestClass = $this->updateRequestClass ?: $this->storeRequestClass;
        $this->validateRequest($request, $validationRequestClass);

        $entityId = $request->route($this->baseModel->getTableSingularName());
        $modelAttributes = $this->baseModel->updateOne($request->all(), $entityId, Auth::id());

        return $this->successResponse($modelAttributes);
    }

    public function destroy(Request $request)
    {
        $entityId = $request->route($this->baseModel->getTableSingularName());
        $this->baseModel->deleteOne($entityId, Auth::id());

        return $this->successResponse([
            'message' => __('response_messages.deleted')
        ]);
    }

    public function validateRequest(Request $request, ?string $requestClass)
    {
        if ($requestClass) {
            $validator = Validator::make(
                $request->all(),
                resolve($requestClass)->rules()
            );
            $validator->validate();
        }
    }

    protected function successResponse($responseData): JsonResponse
    {
        return response()->json($responseData);
    }

    protected function responseWithError(string $error, int $code): JsonResponse
    {
        return response()->json(['error' => $error], $code);
    }

    public function getBaseModel(): Model
    {
        return $this->baseModel;
    }
}
