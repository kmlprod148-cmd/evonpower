<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Services\AutoAssignmentService;

class AutoAssignmentRule implements Rule
{
    protected $modelType;
    protected $field;
    protected $message;

    /**
     * Create a new rule instance.
     */
    public function __construct($modelType, $field = null)
    {
        $this->modelType = $modelType;
        $this->field = $field;
    }

    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value)
    {
        if (empty($value)) {
            return true; // Allow empty values, they will be auto-assigned
        }

        $data = [$this->field ?: $attribute => $value];
        
        return AutoAssignmentService::validateAssignment($this->modelType, $data);
    }

    /**
     * Get the validation error message.
     */
    public function message()
    {
        return $this->message ?: "You don't have permission to assign this {$this->field}.";
    }

    /**
     * Set a custom error message
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }
}
