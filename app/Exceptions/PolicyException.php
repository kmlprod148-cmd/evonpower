<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PolicyException extends Exception
{
    protected $policy;
    protected $action;
    protected $model;

    public function __construct($message = '', $policy = null, $action = null, $model = null, $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->policy = $policy;
        $this->action = $action;
        $this->model = $model;
    }

    /**
     * Get the policy that caused the exception
     */
    public function getPolicy()
    {
        return $this->policy;
    }

    /**
     * Get the action that was attempted
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Get the model that was being accessed
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Render the exception into an HTTP response
     */
    public function render(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Access Denied',
                'message' => $this->getMessage(),
                'policy' => $this->getPolicy(),
                'action' => $this->getAction(),
                'model' => $this->getModel(),
            ], 403);
        }

        // Store error information in session for the 403 page
        session()->flash('policy_error', $this->getMessage());
        session()->flash('policy_info', [
            'policy' => $this->getPolicy(),
            'action' => $this->getAction(),
            'model' => $this->getModel(),
        ]);

        return redirect()->route('errors.403');
    }

    /**
     * Create a policy exception with contextual information
     */
    public static function denied($policy, $action, $model = null, $user = null)
    {
        $message = self::generateMessage($policy, $action, $model, $user);
        
        return new self($message, $policy, $action, $model);
    }

    /**
     * Generate a user-friendly error message
     */
    protected static function generateMessage($policy, $action, $model, $user)
    {
        $userRole = $user ? $user->getRoleNames()->first() : 'Unknown';
        $modelName = $model ? class_basename($model) : 'Resource';
        
        $messages = [
            'view' => "You don't have permission to view this {$modelName}.",
            'create' => "You don't have permission to create {$modelName}.",
            'update' => "You don't have permission to update this {$modelName}.",
            'delete' => "You don't have permission to delete this {$modelName}.",
            'manage' => "You don't have permission to manage this {$modelName}.",
        ];

        $baseMessage = $messages[$action] ?? "You don't have permission to {$action} this {$modelName}.";
        
        $roleMessages = [
            'admin' => "As an admin, you should have access to all resources. Please contact support if this error persists.",
            'integrator' => "As an integrator, you can only access resources within your integrator scope.",
            'partner' => "As a partner, you can only access resources within your partner scope.",
            'operator' => "As an operator, you can only access resources within your integrator's scope.",
        ];

        $roleMessage = $roleMessages[$userRole] ?? "Please contact your administrator for access to this resource.";
        
        return $baseMessage . ' ' . $roleMessage;
    }
}
