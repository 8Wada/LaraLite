<?php

namespace App\Controllers;

/**
 * Abstract base controller for CRUD operations.
 *
 * This class provides standard CRUD (Create, Read, Update, Delete) functionality
 * for Eloquent models. It handles HTTP requests, validates input data, and returns
 * standardized responses using the ResponseHelper utility. Child classes must define
 * the associated model class and validation rules.
 *
 * @property string $modelClass The fully qualified class name of the associated Eloquent model.
 * @property array $rules Validation rules for create and update operations.
 * @property array $messages Custom validation error messages.
 */
abstract class CrudController extends Controller
{
    /**
     * The fully qualified class name of the associated Eloquent model.
     *
     * @var string
     */
    protected string $modelClass;

    /**
     * Validation rules for create and update operations.
     *
     * @var array
     */
    protected array $rules = [];

    /**
     * Custom validation error messages.
     *
     * @var array
     */
    protected array $messages = [];

    /**
     * Entity name for responses.
     * @var string
     */
    protected string $singularEntityName = 'record';
    protected string $pluralEntityName = 'records';
    

    /**
     * Retrieve all active records.
     *
     * Fetches all active records from the associated model and returns them
     * in a successful response. Handles exceptions and returns an error response
     * if the operation fails.
     *
     * @return mixed A success response with the records or an error response.
     */
    public function index()
    {
        try {
            $records = $this->modelClass::getActiveRecords();
            return $this->successResponse("All {$this->pluralEntityName} fetched successfully", $records->toArray());
        } catch (\Throwable $th) {
            return $this->operationErrorResponse("Failed to fetch {$this->pluralEntityName}", $th);
        }
    }

    /**
     * Retrieve a single active record by its identifier.
     *
     * Fetches an active record by its primary key from the request parameters.
     * Returns a success response with the record if found, a not found response
     * if the record does not exist, or an error response if the operation fails.
     *
     * @param object $req The request object containing the parameters.
     * @return mixed A success response with the record, a not found response, or an error response.
     */
    public function show($req)
    {
        $key = $req->params->id ?? null;
        if (!$key) {
            return $this->badRequestResponse("Missing {$this->singularEntityName} identifier");
        }

        try {
            $record = $this->modelClass::findActiveById($key);
            if (!$record) {
                return $this->notFoundResponse(ucfirst($this->singularEntityName) . " not found");
            }

            return $this->successResponse(ucfirst($this->singularEntityName) . " fetched successfully", $record->toArray());
        } catch (\Throwable $th) {
            return $this->operationErrorResponse("Failed to fetch {$this->singularEntityName}", $th);
        }
    }

    /**
     * Create a new record.
     *
     * Validates the request body against defined rules, generates a UUID if required,
     * and creates a new record using the associated model. Returns a success response
     * on creation or an error response if validation or creation fails.
     *
     * @param object $req The request object containing the body data.
     * @return mixed A success response, a validation error response, or an operation error response.
     */
    public function store($req)
    {
        error_log('Request completo: ' . print_r($req, true));
    error_log('Body: ' . print_r($req->body, true));
    error_log('Body as array: ' . print_r((array) $req->body, true));
    
    $data = (array) $req->body;
    error_log('Data después de cast: ' . print_r($data, true));
        $errors = $this->validate($data, $this->rules, $this->messages);

        if (!empty($errors)) {
            return $this->validationFailedResponse($errors);
        }

        try {
            // Genera UUID si el modelo tiene campo UUID definido
            $uuidField = $this->modelClass::getUuidField();
            if ($uuidField && !isset($data[$uuidField])) {
                $data[$uuidField] = $this->generateUuid();
            }

            $this->modelClass::createRecord($data);
            return $this->successResponse(ucfirst($this->singularEntityName) . " created successfully");
        } catch (\Throwable $th) {
            return $this->operationErrorResponse("Failed to create {$this->singularEntityName}", $th);
        }
    }

    /**
     * Update an existing record.
     *
     * Validates the request body against defined rules and updates the record
     * identified by the primary key in the request parameters. Returns a success
     * response on update, a not found response if the record does not exist,
     * or an error response if validation or update fails.
     *
     * @param object $req The request object containing the parameters and body data.
     * @return mixed A success response, a validation error response, a not found response, or an operation error response.
     */
    public function update($req)
    {
        $key = $req->params->id ?? null;
        if (!$key) {
            return $this->badRequestResponse("Missing {$this->singularEntityName} identifier");
        }

        $data = (array) $req->body;
        $errors = $this->validate($data, $this->rules, $this->messages);

        if (!empty($errors)) {
            return $this->validationFailedResponse($errors);
        }

        try {
            $record = $this->modelClass::findActiveById($key);
            if (!$record) {
                return $this->notFoundResponse(ucfirst($this->singularEntityName) . " not found");
            }

            $this->modelClass::updateRecord($key, $data);
            return $this->successResponse(ucfirst($this->singularEntityName) . " updated successfully");
        } catch (\Throwable $th) {
            return $this->operationErrorResponse("Failed to update {$this->singularEntityName}", $th);
        }
    }

    /**
     * Soft delete a record.
     *
     * Soft deletes the record identified by the primary key in the request parameters.
     * Returns a success response on deletion, a not found response if the record
     * does not exist, or an error response if the operation fails.
     *
     * @param object $req The request object containing the parameters.
     * @return mixed A success response, a not found response, or an operation error response.
     */
    public function destroy($req)
    {
        $key = $req->params->id ?? null;
        if (!$key) {
            return $this->badRequestResponse("Missing {$this->singularEntityName} identifier");
        }

        try {
            $record = $this->modelClass::findActiveById($key);
            if (!$record) {
                return $this->notFoundResponse(ucfirst($this->singularEntityName) . " not found");
            }

            $this->modelClass::softDelete($key);
            return $this->successResponse(ucfirst($this->singularEntityName) . " deleted successfully");
        } catch (\Throwable $th) {
            return $this->operationErrorResponse("Failed to delete {$this->singularEntityName}", $th);
        }
    }
}