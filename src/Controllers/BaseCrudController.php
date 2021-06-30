<?php

namespace RushApp\Core\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller;
use RushApp\Core\Enums\ModelRequestParameters;
use RushApp\Core\Models\BaseModel;
use RushApp\Core\Models\BaseModelTrait;

abstract class BaseCrudController extends Controller
{
    use ResponseTrait;

    protected string $modelClassController;
    protected BaseModel $baseModel;
    protected ?string $storeRequestClass = null;
    protected ?string $updateRequestClass = null;
    protected array $withRelationNames = [];

    public function __construct()
    {
        $entityId = $this->getEntityId();
        // if $entityId - create filled model where $entityId is first parameter from request()->route()->parameters()
        // (only to show, update and destroy)
        // else create empty model
        $this->baseModel = $entityId ? $this->modelClassController::find($entityId) : new $this->modelClassController;
    }

    /** @return BaseModel */
    public function getBaseModel(): BaseModel
    {
        return $this->baseModel;
    }

    /**
     * Return a collections of one or more records with or without translation and with or without paginate
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        //check for paginate data
        $paginate = $request->get(ModelRequestParameters::PAGINATE, false);

        $query = $this->baseModel->getQueryBuilder($request->all(), $this->withRelationNames);

        return $paginate
            ? $this->successResponse($query->paginate($paginate))
            : $this->successResponse($query->get());
    }

    /**
     * Return one record with or without translation
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $entityId = $request->route($this->baseModel->getTableSingularName());
        $query = $this->baseModel->getQueryBuilderOne($request->all(), $entityId, $this->withRelationNames);

        return $this->successResponse($query->first());
    }

    /**
     * Return created a new record
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $this->validateRequest($request, $this->storeRequestClass);

        $modelAttributes = $this->baseModel->createOne($request->all());

        return $this->successResponse($modelAttributes);
    }

    /**
     * Return updated record
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $validationRequestClass = $this->updateRequestClass ?: $this->storeRequestClass;
        $this->validateRequest($request, $validationRequestClass);

        $entityId = $request->route($this->baseModel->getTableSingularName());
        $modelAttributes = $this->baseModel->updateOne($request->all(), $entityId, Auth::id());

        return $this->successResponse($modelAttributes);
    }

    /**
     * Return text message about deleting status
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(Request $request): JsonResponse
    {
        $entityId = $request->route($this->baseModel->getTableSingularName());
        $this->baseModel->deleteOne($entityId, Auth::id());

        return $this->successResponse([
            'message' => __('response_messages.deleted')
        ]);
    }

    /**
     * @param Request $request
     * @param string|null $requestClass
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateRequest(Request $request, ?string $requestClass): void
    {
        if ($requestClass) {
            $validator = Validator::make(
                $request->all(),
                resolve($requestClass)->rules()
            );
            $validator->validate();
        }
    }

    /**
     * get first parameter from route
     * @return int|null
     */
    private function getEntityId(): ?int
    {
        $parameters = request()->route()->parameters();
        return reset($parameters) ?? null;
    }
}
