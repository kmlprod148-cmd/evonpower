<?php

namespace App\Http\Controllers\API;

use App\Core\Http\Controllers\ApiController;
use App\Models\PricingPlan;
use App\Models\PricingRuleCondition;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PricingRuleConditionController extends ApiController
{
    /**
     * Get all rule conditions for a pricing plan
     */
    public function index(Request $request = null)
    {
        try {
            $planId = $request ? $request->query('plan_id') : null;
            
            if ($planId) {
                $pricingPlan = PricingPlan::find($planId);
                if (!$pricingPlan) {
                    return $this->sendNotFound('Plan tarifaire non trouvé');
                }

                $conditions = $pricingPlan->ruleConditions()
                    ->orderBy('priority', 'desc')
                    ->orderBy('id')
                    ->get();

                return $this->sendSuccess($conditions);
            }

            // Return all conditions if no plan_id specified
            $conditions = PricingRuleCondition::with('pricingPlan')
                ->orderBy('priority', 'desc')
                ->orderBy('id')
                ->get();

            return $this->sendSuccess($conditions);
        } catch (\Exception $e) {
            return $this->sendError('Erreur lors de la récupération des conditions', ['exception' => $e->getMessage()], 500);
        }
    }

    /**
     * Get a single rule condition
     */
    public function show(int $id)
    {
        try {
            $condition = PricingRuleCondition::with('pricingPlan')->find($id);
            if (!$condition) {
                return $this->sendNotFound('Condition non trouvée');
            }

            return $this->sendSuccess($condition);
        } catch (\Exception $e) {
            return $this->sendError('Erreur lors de la récupération de la condition', ['exception' => $e->getMessage()], 500);
        }
    }

    /**
     * Create a new rule condition
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pricing_plan_id' => 'required|exists:pricing_plans,id',
            'name' => 'required|string|max:255',
            'condition_type' => 'required|in:and,or,single',
            'conditions' => 'required|array|min:1',
            'conditions.*.field' => 'required|string',
            'conditions.*.operator' => 'required|in:eq,ne,gt,gte,lt,lte,in,not_in,contains,between',
            'conditions.*.value' => 'required',
            'rate_type' => 'required|in:fixed,time,energy,percentage',
            'price_value' => 'required|numeric',
            'is_percentage' => 'boolean',
            'apply_type' => 'required|in:add,multiply,replace',
            'priority' => 'integer|min:0',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        try {
            $pricingPlan = PricingPlan::find($request->input('pricing_plan_id'));
            if (!$pricingPlan) {
                return $this->sendNotFound('Plan tarifaire non trouvé');
            }

            $condition = PricingRuleCondition::create([
                'pricing_plan_id' => $request->input('pricing_plan_id'),
                'name' => $request->input('name'),
                'condition_type' => $request->input('condition_type'),
                'conditions' => $request->input('conditions'),
                'rate_type' => $request->input('rate_type'),
                'price_value' => $request->input('price_value'),
                'is_percentage' => $request->boolean('is_percentage', false),
                'apply_type' => $request->input('apply_type'),
                'priority' => $request->input('priority', 0),
                'is_active' => $request->boolean('is_active', true),
                'description' => $request->input('description'),
                'metadata' => $request->input('metadata'),
            ]);

            // Clear pricing cache for this plan
            app(\App\Services\PricingCalculationService::class)->clearPlanCache($pricingPlan->id);

            return $this->sendSuccess($condition, 'Condition créée avec succès', 201);
        } catch (\Exception $e) {
            return $this->sendError('Erreur lors de la création de la condition', ['exception' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a rule condition
     */
    public function update(Request $request, int $id)
    {
        $condition = PricingRuleCondition::find($id);
        if (!$condition) {
            return $this->sendNotFound('Condition non trouvée');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'condition_type' => 'in:and,or,single',
            'conditions' => 'array|min:1',
            'conditions.*.field' => 'string',
            'conditions.*.operator' => 'in:eq,ne,gt,gte,lt,lte,in,not_in,contains,between',
            'conditions.*.value' => '',
            'rate_type' => 'in:fixed,time,energy,percentage',
            'price_value' => 'numeric',
            'is_percentage' => 'boolean',
            'apply_type' => 'in:add,multiply,replace',
            'priority' => 'integer|min:0',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        try {
            $updateData = array_filter([
                'name' => $request->input('name'),
                'condition_type' => $request->input('condition_type'),
                'conditions' => $request->has('conditions') ? $request->input('conditions') : null,
                'rate_type' => $request->input('rate_type'),
                'price_value' => $request->input('price_value'),
                'is_percentage' => $request->has('is_percentage') ? $request->boolean('is_percentage') : null,
                'apply_type' => $request->input('apply_type'),
                'priority' => $request->input('priority'),
                'is_active' => $request->has('is_active') ? $request->boolean('is_active') : null,
                'description' => $request->input('description'),
                'metadata' => $request->has('metadata') ? $request->input('metadata') : null,
            ], fn($v) => $v !== null);

            $condition->update($updateData);

            // Clear pricing cache
            app(\App\Services\PricingCalculationService::class)->clearPlanCache($condition->pricing_plan_id);

            return $this->sendSuccess($condition->fresh(), 'Condition mise à jour avec succès');
        } catch (\Exception $e) {
            return $this->sendError('Erreur lors de la mise à jour de la condition', ['exception' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a rule condition
     */
    public function destroy(int $id)
    {
        try {
            $condition = PricingRuleCondition::find($id);
            if (!$condition) {
                return $this->sendNotFound('Condition non trouvée');
            }

            $planId = $condition->pricing_plan_id;
            $condition->delete();

            // Clear pricing cache
            app(\App\Services\PricingCalculationService::class)->clearPlanCache($planId);

            return $this->sendSuccess(null, 'Condition supprimée avec succès');
        } catch (\Exception $e) {
            return $this->sendError('Erreur lors de la suppression de la condition', ['exception' => $e->getMessage()], 500);
        }
    }

    /**
     * Test if a rule condition matches given context
     */
    public function testMatch(Request $request, int $id)
    {
        $condition = PricingRuleCondition::find($id);
        if (!$condition) {
            return $this->sendNotFound('Condition non trouvée');
        }

        $validator = Validator::make($request->all(), [
            'duration' => 'integer|min:0',
            'energy' => 'numeric|min:0',
            'power' => 'numeric|min:0',
            'customer_segment' => 'string|max:50',
            'location_zone' => 'string|max:100',
            'quantity' => 'integer|min:1',
            'datetime' => 'date',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Erreur de validation', $validator->errors(), 422);
        }

        try {
            $context = [
                'datetime' => \Carbon\Carbon::parse($request->input('datetime', now())),
                'duration' => $request->input('duration', 0),
                'energy' => $request->input('energy', 0),
                'power' => $request->input('power'),
                'customer_segment' => $request->input('customer_segment'),
                'location_zone' => $request->input('location_zone'),
                'quantity' => $request->input('quantity', 1),
                'day_of_week' => \Carbon\Carbon::parse($request->input('datetime', now()))->dayOfWeek,
                'is_weekend' => in_array(
                    \Carbon\Carbon::parse($request->input('datetime', now()))->dayOfWeek,
                    [0, 6]
                ),
            ];

            $matches = $condition->matches($context);

            return $this->sendSuccess([
                'condition_id' => $condition->id,
                'condition_name' => $condition->name,
                'matches' => $matches,
                'context' => $context,
            ]);
        } catch (\Exception $e) {
            return $this->sendError('Erreur lors du test de la condition', ['exception' => $e->getMessage()], 500);
        }
    }

    /**
     * Get available condition fields
     */
    public function getFields()
    {
        $fields = [
            [
                'key' => 'duration',
                'label' => 'Durée (minutes)',
                'type' => 'number',
                'operators' => ['eq', 'ne', 'gt', 'gte', 'lt', 'lte', 'between'],
            ],
            [
                'key' => 'energy',
                'label' => 'Énergie (kWh)',
                'type' => 'number',
                'operators' => ['eq', 'ne', 'gt', 'gte', 'lt', 'lte', 'between'],
            ],
            [
                'key' => 'power',
                'label' => 'Puissance (kW)',
                'type' => 'number',
                'operators' => ['eq', 'ne', 'gt', 'gte', 'lt', 'lte', 'between'],
            ],
            [
                'key' => 'customer_segment',
                'label' => 'Segment client',
                'type' => 'string',
                'operators' => ['eq', 'ne', 'in', 'not_in'],
            ],
            [
                'key' => 'location_zone',
                'label' => 'Zone géographique',
                'type' => 'string',
                'operators' => ['eq', 'ne', 'contains', 'in', 'not_in'],
            ],
            [
                'key' => 'quantity',
                'label' => 'Quantité',
                'type' => 'number',
                'operators' => ['eq', 'ne', 'gt', 'gte', 'lt', 'lte', 'between'],
            ],
            [
                'key' => 'day_of_week',
                'label' => 'Jour de la semaine',
                'type' => 'select',
                'options' => [
                    0 => 'Dimanche',
                    1 => 'Lundi',
                    2 => 'Mardi',
                    3 => 'Mercredi',
                    4 => 'Jeudi',
                    5 => 'Vendredi',
                    6 => 'Samedi',
                ],
                'operators' => ['eq', 'ne', 'in', 'not_in'],
            ],
            [
                'key' => 'hour',
                'label' => 'Heure',
                'type' => 'number',
                'operators' => ['eq', 'ne', 'gt', 'gte', 'lt', 'lte', 'between'],
            ],
            [
                'key' => 'is_weekend',
                'label' => 'Est un week-end',
                'type' => 'boolean',
                'operators' => ['eq'],
            ],
        ];

        return $this->sendSuccess($fields);
    }

    /**
     * Get available operators
     */
    public function getOperators()
    {
        $operators = [
            ['key' => 'eq', 'label' => 'Égal (=)'],
            ['key' => 'ne', 'label' => 'Different (!=)'],
            ['key' => 'gt', 'label' => 'Supérieur (>)'],
            ['key' => 'gte', 'label' => 'Supérieur ou égal (>=)'],
            ['key' => 'lt', 'label' => 'Inférieur (<)'],
            ['key' => 'lte', 'label' => 'Inférieur ou égal (<=)'],
            ['key' => 'in', 'label' => 'Dans la liste'],
            ['key' => 'not_in', 'label' => 'Pas dans la liste'],
            ['key' => 'contains', 'label' => 'Contient'],
            ['key' => 'between', 'label' => 'Entre deux valeurs'],
        ];

        return $this->sendSuccess($operators);
    }

    /**
     * Get available apply types
     */
    public function getApplyTypes()
    {
        $types = [
            ['key' => 'add', 'label' => 'Ajouter au prix'],
            ['key' => 'multiply', 'label' => 'Multiplier le prix'],
            ['key' => 'replace', 'label' => 'Remplacer le prix'],
        ];

        return $this->sendSuccess($types);
    }
}
