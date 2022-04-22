<?php

namespace RushApp\Core\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controller;
use RushApp\Core\Enums\ModelRequestParameters;

abstract class BaseCrudController extends Controller
{
    use ResponseTrait, ValidateTrait;

    protected string $modelClassController;
    protected Model $baseModel;
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

    /** @return Model */
    public function getBaseModel(): Model
    {
        return $this->baseModel;
    }

    /**
     * Return a collections of one or more records with or without translation and with or without paginate
     * or return number of records
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $query = $this->baseModel->getQueryBuilder($request->all(), $this->withRelationNames);

        //get number of records
        if ($request->get(ModelRequestParameters::COUNT, false)) {
            return $this->successResponse($query->count());
        }
        //check for paginate data
        if ($paginate = $request->get(ModelRequestParameters::PAGINATE, false)) {
            return $this->successResponse($query->paginate($paginate));
        }

        return $this->successResponse($query->get());
    }

    /**
     * Return one record with or without translation
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request)
    {
        $entityId = $request->route($this->baseModel->getTableSingularName());
        $entityId = !empty($entityId) ? $entityId : $this->getEntityId();

        $query = $this->baseModel->getQueryBuilderOne($request->all(), $entityId, $this->withRelationNames);

        return $this->successResponse($query->first());
    }

    /**
     * Return created a new record
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
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
    public function update(Request $request)
    {
        $validationRequestClass = $this->updateRequestClass ?: $this->storeRequestClass;
        $this->validateRequest($request, $validationRequestClass);

        $entityId = $request->route($this->baseModel->getTableSingularName());
        $entityId = !empty($entityId) ? $entityId : $this->getEntityId();

        $modelAttributes = $this->baseModel->updateOne($request->all(), $entityId, Auth::id());

        return $this->successResponse($modelAttributes);
    }

    /**
     * Return text message about deleting status
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(Request $request)
    {
        $entityId = $request->route($this->baseModel->getTableSingularName());
        $entityId = !empty($entityId) ? $entityId : $this->getEntityId();

        $this->baseModel->deleteOne($entityId, Auth::id());

        return $this->successResponse([
            'message' => __('response_messages.deleted')
        ]);
    }

    /**
     * get first parameter from route
     * @return int|null
     */
    protected function getEntityId(): ?int
    {
        $parameters = request()->route()->parameters();
        return reset($parameters) ?? null;
    }
}
