<?php

namespace App\Services;

use App\Models\ChargingPoint;
use App\Models\Transaction;
use App\Models\Integrator;
use App\Models\Partner;
use App\Models\BusinessProfile;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AdvancedFeeCalculationService
{
    protected $chargingPointFeeCalculationService;
    protected $detailedFeeCalculationService;

    public function __construct(
        ChargingPointFeeCalculationService $chargingPointFeeCalculationService,
        DetailedFeeCalculationService $detailedFeeCalculationService
    ) {
        $this->chargingPointFeeCalculationService = $chargingPointFeeCalculationService;
        $this->detailedFeeCalculationService = $detailedFeeCalculationService;
    }

    /**
     * Calculate comprehensive fees with detailed breakdown for charging point creators
     * Implements the specific business logic:
     * - Admin Part = Fees applied on integrator if station is created by integrator
     * - Integrator Part = Fees applied by integrator if operator is attached to integrator
     */
    public function calculateComprehensiveFees(ChargingPoint $chargingPoint, float $baseAmount = 0): array
    {
        try {
            DB::beginTransaction();

            // Identify the creator of the charging point
            $creator = $this->identifyChargingPointCreator($chargingPoint);
            $creatorType = $this->getCreatorType($creator);
            
            // Get business profile for fee calculation
            $businessProfile = $this->getBusinessProfileForCreator($creator);
            
            // Calculate base fees using existing service
            $baseFeeBreakdown = $this->chargingPointFeeCalculationService->calculateChargingPointCreatorFees(
                $chargingPoint, 
                $baseAmount
            );

            // Calculate comprehensive fee breakdown
            $comprehensiveBreakdown = [
                'charging_point_info' => $this->getChargingPointInfo($chargingPoint),
                'creator_info' => $this->getCreatorInfo($creator),
                'creator_type' => $creatorType,
                'business_profile_info' => $this->getBusinessProfileInfo($businessProfile),
                'base_amount' => $baseAmount,
                'fee_calculation' => $this->calculateDetailedFeeBreakdown($businessProfile, $baseAmount, $creatorType),
                'admin_part' => $this->calculateAdminPart($chargingPoint, $creator, $creatorType, $baseAmount),
                'integrator_part' => $this->calculateIntegratorPart($chargingPoint, $creator, $creatorType, $baseAmount),
                'operator_part' => $this->calculateOperatorPart($chargingPoint, $creator, $creatorType, $baseAmount),
                'partner_part' => $this->calculatePartnerPart($chargingPoint, $creator, $creatorType, $baseAmount),
                'total_breakdown' => [],
                'summary' => [],
                'applied_fees_details' => []
            ];

            // Calculate totals and create summary
            $comprehensiveBreakdown['total_breakdown'] = $this->calculateTotalBreakdown($comprehensiveBreakdown);
            $comprehensiveBreakdown['summary'] = $this->createComprehensiveSummary($comprehensiveBreakdown);
            $comprehensiveBreakdown['applied_fees_details'] = $this->getAppliedFeesDetails($comprehensiveBreakdown);

            // Log detailed calculation
            Log::info('Comprehensive fee calculation completed', [
                'charging_point_id' => $chargingPoint->id,
                'creator_type' => $creatorType,
                'creator_id' => $creator ? $creator->id : null,
                'base_amount' => $baseAmount,
                'total_fees' => $comprehensiveBreakdown['summary']['total_fees'],
                'admin_part' => $comprehensiveBreakdown['admin_part']['total'],
                'integrator_part' => $comprehensiveBreakdown['integrator_part']['total'],
                'operator_part' => $comprehensiveBreakdown['operator_part']['total']
            ]);

            DB::commit();
            return $comprehensiveBreakdown;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in comprehensive fee calculation', [
                'charging_point_id' => $chargingPoint->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->getDefaultComprehensiveBreakdown();
        }
    }

    /**
     * Calculate Admin Part - Fees applied on integrator if station is created by integrator
     */
    private function calculateAdminPart(ChargingPoint $chargingPoint, $creator, string $creatorType, float $baseAmount): array
    {
        $adminPart = [
            'applies' => false,
            'reason' => '',
            'fees' => [
                'fixed_fee' => 0,
                'percentage_fee' => 0,
                'activation_fee' => 0,
                'transaction_fee' => 0,
                'total' => 0
            ],
            'breakdown' => []
        ];

        // Admin fees apply when station is created by integrator
        if ($creatorType === 'integrator' && $creator instanceof Integrator) {
            $adminPart['applies'] = true;
            $adminPart['reason'] = 'Station créée par intégrateur - frais admin appliqués';
            
            $businessProfile = $creator->businessProfile;
            if ($businessProfile) {
                // Admin fixed fees
                $adminPart['fees']['fixed_fee'] = (float) ($businessProfile->admin_fee_fixed ?? 0);
                
                // Admin percentage fees
                if ($businessProfile->admin_fee_percentage > 0 && $baseAmount > 0) {
                    $adminPart['fees']['percentage_fee'] = ($baseAmount * (float) $businessProfile->admin_fee_percentage) / 100;
                }
                
                // Activation fees
                $adminPart['fees']['activation_fee'] = (float) ($businessProfile->base_fee_amount ?? 0);
                
                // Transaction fees from business profile
                $transactionConfig = $businessProfile->transaction_fee_config ?? [];
                if (isset($transactionConfig['fixed_amount'])) {
                    $adminPart['fees']['transaction_fee'] += (float) $transactionConfig['fixed_amount'];
                }
                if (isset($transactionConfig['percentage']) && $baseAmount > 0) {
                    $adminPart['fees']['transaction_fee'] += ($baseAmount * (float) $transactionConfig['percentage']) / 100;
                }
                
                $adminPart['fees']['total'] = $adminPart['fees']['fixed_fee'] + 
                                            $adminPart['fees']['percentage_fee'] + 
                                            $adminPart['fees']['activation_fee'] + 
                                            $adminPart['fees']['transaction_fee'];
                
                $adminPart['breakdown'] = [
                    [
                        'type' => 'admin_fixed',
                        'label' => 'Frais admin fixes',
                        'amount' => $adminPart['fees']['fixed_fee'],
                        'description' => 'Frais administratifs fixes'
                    ],
                    [
                        'type' => 'admin_percentage',
                        'label' => 'Frais admin pourcentage',
                        'amount' => $adminPart['fees']['percentage_fee'],
                        'description' => "Frais administratifs: {$businessProfile->admin_fee_percentage}%"
                    ],
                    [
                        'type' => 'activation_fee',
                        'label' => 'Frais d\'activation',
                        'amount' => $adminPart['fees']['activation_fee'],
                        'description' => 'Frais d\'activation de la borne'
                    ],
                    [
                        'type' => 'transaction_fee',
                        'label' => 'Frais de transaction',
                        'amount' => $adminPart['fees']['transaction_fee'],
                        'description' => 'Frais de transaction admin'
                    ]
                ];
            }
        } else {
            $adminPart['reason'] = 'Station non créée par intégrateur - pas de frais admin';
        }

        return $adminPart;
    }

    /**
     * Calculate Integrator Part - Fees applied by integrator if operator is attached to integrator
     */
    private function calculateIntegratorPart(ChargingPoint $chargingPoint, $creator, string $creatorType, float $baseAmount): array
    {
        $integratorPart = [
            'applies' => false,
            'reason' => '',
            'fees' => [
                'fixed_fee' => 0,
                'percentage_fee' => 0,
                'commission_fee' => 0,
                'total' => 0
            ],
            'breakdown' => []
        ];

        // Check if there's an integrator involved (either as creator or through partner relationship)
        $integrator = null;
        $integratorBusinessProfile = null;

        if ($creatorType === 'integrator' && $creator instanceof Integrator) {
            $integrator = $creator;
            $integratorBusinessProfile = $integrator->businessProfile;
            $integratorPart['applies'] = true;
            $integratorPart['reason'] = 'Station créée par intégrateur - frais intégrateur appliqués';
        } elseif ($creatorType === 'partner' && $creator instanceof Partner && $creator->integrator_id) {
            $integrator = $creator->integrator;
            $integratorBusinessProfile = $integrator->businessProfile;
            $integratorPart['applies'] = true;
            $integratorPart['reason'] = 'Opérateur attaché à intégrateur - frais intégrateur appliqués';
        }

        if ($integratorPart['applies'] && $integratorBusinessProfile) {
            // Integrator fixed fees
            $integratorPart['fees']['fixed_fee'] = (float) ($integratorBusinessProfile->integrator_fee_fixed ?? 0);
            
            // Integrator percentage fees
            if ($integratorBusinessProfile->integrator_fee_percentage > 0 && $baseAmount > 0) {
                $integratorPart['fees']['percentage_fee'] = ($baseAmount * (float) $integratorBusinessProfile->integrator_fee_percentage) / 100;
            }
            
            // Commission fees
            if ($integratorBusinessProfile->integrator_commission > 0 && $baseAmount > 0) {
                $integratorPart['fees']['commission_fee'] = ($baseAmount * (float) $integratorBusinessProfile->integrator_commission) / 100;
            }
            
            $integratorPart['fees']['total'] = $integratorPart['fees']['fixed_fee'] + 
                                             $integratorPart['fees']['percentage_fee'] + 
                                             $integratorPart['fees']['commission_fee'];
            
            $integratorPart['breakdown'] = [
                [
                    'type' => 'integrator_fixed',
                    'label' => 'Frais intégrateur fixes',
                    'amount' => $integratorPart['fees']['fixed_fee'],
                    'description' => 'Frais fixes de l\'intégrateur'
                ],
                [
                    'type' => 'integrator_percentage',
                    'label' => 'Frais intégrateur pourcentage',
                    'amount' => $integratorPart['fees']['percentage_fee'],
                    'description' => "Frais intégrateur: {$integratorBusinessProfile->integrator_fee_percentage}%"
                ],
                [
                    'type' => 'integrator_commission',
                    'label' => 'Commission intégrateur',
                    'amount' => $integratorPart['fees']['commission_fee'],
                    'description' => "Commission intégrateur: {$integratorBusinessProfile->integrator_commission}%"
                ]
            ];
        } else {
            $integratorPart['reason'] = 'Pas d\'intégrateur impliqué - pas de frais intégrateur';
        }

        return $integratorPart;
    }

    /**
     * Calculate Operator Part - Revenue remaining for operator
     */
    private function calculateOperatorPart(ChargingPoint $chargingPoint, $creator, string $creatorType, float $baseAmount): array
    {
        $operatorPart = [
            'applies' => false,
            'reason' => '',
            'revenue' => [
                'base_revenue' => $baseAmount,
                'deducted_fees' => 0,
                'net_revenue' => 0
            ],
            'breakdown' => []
        ];

        // Operator gets remaining revenue after all fees are deducted
        $operatorPart['applies'] = true;
        $operatorPart['reason'] = 'Revenus restants pour l\'opérateur après déduction des frais';
        
        // This will be calculated after all other parts are determined
        $operatorPart['revenue']['base_revenue'] = $baseAmount;
        
        $operatorPart['breakdown'] = [
            [
                'type' => 'operator_revenue',
                'label' => 'Revenus opérateur',
                'amount' => 0, // Will be calculated in total breakdown
                'description' => 'Revenus nets de l\'opérateur'
            ]
        ];

        return $operatorPart;
    }

    /**
     * Calculate Partner Part - Fees for partner if applicable
     */
    private function calculatePartnerPart(ChargingPoint $chargingPoint, $creator, string $creatorType, float $baseAmount): array
    {
        $partnerPart = [
            'applies' => false,
            'reason' => '',
            'fees' => [
                'fixed_fee' => 0,
                'percentage_fee' => 0,
                'commission_fee' => 0,
                'total' => 0
            ],
            'breakdown' => []
        ];

        // Check if there's a partner involved
        $partner = null;
        $partnerBusinessProfile = null;

        if ($creatorType === 'partner' && $creator instanceof Partner) {
            $partner = $creator;
            $partnerBusinessProfile = $partner->businessProfile;
            $partnerPart['applies'] = true;
            $partnerPart['reason'] = 'Station créée par partenaire - frais partenaire appliqués';
        } elseif ($creatorType === 'integrator' && $creator instanceof Integrator) {
            // Check if integrator has partners that might be involved
            $partnerPart['reason'] = 'Station créée par intégrateur - vérification des partenaires';
        }

        if ($partnerPart['applies'] && $partnerBusinessProfile) {
            // Partner fixed fees
            $partnerPart['fees']['fixed_fee'] = (float) ($partnerBusinessProfile->partner_fee_fixed ?? 0);
            
            // Partner percentage fees
            if ($partnerBusinessProfile->partner_fee_percentage > 0 && $baseAmount > 0) {
                $partnerPart['fees']['percentage_fee'] = ($baseAmount * (float) $partnerBusinessProfile->partner_fee_percentage) / 100;
            }
            
            // Commission fees
            if ($partnerBusinessProfile->partner_commission > 0 && $baseAmount > 0) {
                $partnerPart['fees']['commission_fee'] = ($baseAmount * (float) $partnerBusinessProfile->partner_commission) / 100;
            }
            
            $partnerPart['fees']['total'] = $partnerPart['fees']['fixed_fee'] + 
                                          $partnerPart['fees']['percentage_fee'] + 
                                          $partnerPart['fees']['commission_fee'];
            
            $partnerPart['breakdown'] = [
                [
                    'type' => 'partner_fixed',
                    'label' => 'Frais partenaire fixes',
                    'amount' => $partnerPart['fees']['fixed_fee'],
                    'description' => 'Frais fixes du partenaire'
                ],
                [
                    'type' => 'partner_percentage',
                    'label' => 'Frais partenaire pourcentage',
                    'amount' => $partnerPart['fees']['percentage_fee'],
                    'description' => "Frais partenaire: {$partnerBusinessProfile->partner_fee_percentage}%"
                ],
                [
                    'type' => 'partner_commission',
                    'label' => 'Commission partenaire',
                    'amount' => $partnerPart['fees']['commission_fee'],
                    'description' => "Commission partenaire: {$partnerBusinessProfile->partner_commission}%"
                ]
            ];
        } else {
            $partnerPart['reason'] = 'Pas de partenaire impliqué - pas de frais partenaire';
        }

        return $partnerPart;
    }

    /**
     * Calculate detailed fee breakdown
     */
    private function calculateDetailedFeeBreakdown(?BusinessProfile $businessProfile, float $baseAmount, string $creatorType): array
    {
        $feeBreakdown = [
            'charging_fees' => ['fixed' => 0, 'percentage' => 0, 'total' => 0],
            'transaction_fees' => ['fixed' => 0, 'percentage' => 0, 'total' => 0],
            'activation_fees' => ['base' => 0, 'setup' => 0, 'total' => 0],
            'admin_fees' => ['fixed' => 0, 'percentage' => 0, 'total' => 0],
            'total_fees' => 0
        ];

        if ($businessProfile) {
            // Charging fees
            $chargeConfig = $businessProfile->charge_fee_config ?? [];
            $feeBreakdown['charging_fees']['fixed'] = (float) ($chargeConfig['fixed_amount'] ?? 0);
            $feeBreakdown['charging_fees']['percentage'] = ($baseAmount * (float) ($chargeConfig['percentage'] ?? 0)) / 100;
            $feeBreakdown['charging_fees']['total'] = $feeBreakdown['charging_fees']['fixed'] + $feeBreakdown['charging_fees']['percentage'];

            // Transaction fees
            $transactionConfig = $businessProfile->transaction_fee_config ?? [];
            $feeBreakdown['transaction_fees']['fixed'] = (float) ($transactionConfig['fixed_amount'] ?? 0);
            $feeBreakdown['transaction_fees']['percentage'] = ($baseAmount * (float) ($transactionConfig['percentage'] ?? 0)) / 100;
            $feeBreakdown['transaction_fees']['total'] = $feeBreakdown['transaction_fees']['fixed'] + $feeBreakdown['transaction_fees']['percentage'];

            // Activation fees
            $feeBreakdown['activation_fees']['base'] = (float) ($businessProfile->base_fee_amount ?? 0);
            $feeBreakdown['activation_fees']['setup'] = (float) ($businessProfile->terminal_fee_amount ?? 0);
            $feeBreakdown['activation_fees']['total'] = $feeBreakdown['activation_fees']['base'] + $feeBreakdown['activation_fees']['setup'];

            // Admin fees
            $feeBreakdown['admin_fees']['fixed'] = (float) ($businessProfile->admin_fee_fixed ?? 0);
            $feeBreakdown['admin_fees']['percentage'] = ($baseAmount * (float) ($businessProfile->admin_fee_percentage ?? 0)) / 100;
            $feeBreakdown['admin_fees']['total'] = $feeBreakdown['admin_fees']['fixed'] + $feeBreakdown['admin_fees']['percentage'];
        }

        $feeBreakdown['total_fees'] = $feeBreakdown['charging_fees']['total'] + 
                                     $feeBreakdown['transaction_fees']['total'] + 
                                     $feeBreakdown['activation_fees']['total'] + 
                                     $feeBreakdown['admin_fees']['total'];

        return $feeBreakdown;
    }

    /**
     * Calculate total breakdown
     */
    private function calculateTotalBreakdown(array $comprehensiveBreakdown): array
    {
        $adminTotal = $comprehensiveBreakdown['admin_part']['fees']['total'] ?? 0;
        $integratorTotal = $comprehensiveBreakdown['integrator_part']['fees']['total'] ?? 0;
        $partnerTotal = $comprehensiveBreakdown['partner_part']['fees']['total'] ?? 0;
        $totalFees = $adminTotal + $integratorTotal + $partnerTotal;
        $operatorRevenue = $comprehensiveBreakdown['base_amount'] - $totalFees;
        $finalTotalAmount = $comprehensiveBreakdown['base_amount'] + $totalFees;

        // Update operator part with calculated revenue
        $comprehensiveBreakdown['operator_part']['revenue']['deducted_fees'] = $totalFees;
        $comprehensiveBreakdown['operator_part']['revenue']['net_revenue'] = max(0, $operatorRevenue);
        $comprehensiveBreakdown['operator_part']['breakdown'][0]['amount'] = max(0, $operatorRevenue);

        return [
            'admin_total' => $adminTotal,
            'integrator_total' => $integratorTotal,
            'partner_total' => $partnerTotal,
            'operator_total' => max(0, $operatorRevenue),
            'total_fees' => $totalFees,
            'net_revenue' => max(0, $operatorRevenue),
            'base_amount' => $comprehensiveBreakdown['base_amount'],
            'final_total_amount' => $finalTotalAmount
        ];
    }

    /**
     * Create comprehensive summary
     */
    private function createComprehensiveSummary(array $comprehensiveBreakdown): array
    {
        $totalBreakdown = $comprehensiveBreakdown['total_breakdown'];
        
        return [
            'total_fees' => $totalBreakdown['total_fees'],
            'net_revenue' => $totalBreakdown['net_revenue'],
            'final_total_amount' => $totalBreakdown['final_total_amount'],
            'fee_distribution' => [
                'Admin' => $totalBreakdown['admin_total'],
                'Intégrateur' => $totalBreakdown['integrator_total'],
                'Partenaire' => $totalBreakdown['partner_total'],
                'Opérateur' => $totalBreakdown['operator_total']
            ],
            'has_fees' => $totalBreakdown['total_fees'] > 0,
            'creator_type' => $comprehensiveBreakdown['creator_type'],
            'applies_admin_fees' => $comprehensiveBreakdown['admin_part']['applies'],
            'applies_integrator_fees' => $comprehensiveBreakdown['integrator_part']['applies'],
            'applies_partner_fees' => $comprehensiveBreakdown['partner_part']['applies']
        ];
    }

    /**
     * Get applied fees details
     */
    private function getAppliedFeesDetails(array $comprehensiveBreakdown): array
    {
        $details = [];

        if ($comprehensiveBreakdown['admin_part']['applies']) {
            $details[] = [
                'type' => 'admin',
                'label' => 'Frais Admin',
                'amount' => $comprehensiveBreakdown['admin_part']['fees']['total'],
                'reason' => $comprehensiveBreakdown['admin_part']['reason'],
                'breakdown' => $comprehensiveBreakdown['admin_part']['breakdown']
            ];
        }

        if ($comprehensiveBreakdown['integrator_part']['applies']) {
            $details[] = [
                'type' => 'integrator',
                'label' => 'Frais Intégrateur',
                'amount' => $comprehensiveBreakdown['integrator_part']['fees']['total'],
                'reason' => $comprehensiveBreakdown['integrator_part']['reason'],
                'breakdown' => $comprehensiveBreakdown['integrator_part']['breakdown']
            ];
        }

        if ($comprehensiveBreakdown['partner_part']['applies']) {
            $details[] = [
                'type' => 'partner',
                'label' => 'Frais Partenaire',
                'amount' => $comprehensiveBreakdown['partner_part']['fees']['total'],
                'reason' => $comprehensiveBreakdown['partner_part']['reason'],
                'breakdown' => $comprehensiveBreakdown['partner_part']['breakdown']
            ];
        }

        $details[] = [
            'type' => 'operator',
            'label' => 'Revenus Opérateur',
            'amount' => $comprehensiveBreakdown['operator_part']['revenue']['net_revenue'],
            'reason' => $comprehensiveBreakdown['operator_part']['reason'],
            'breakdown' => $comprehensiveBreakdown['operator_part']['breakdown']
        ];

        return $details;
    }

    /**
     * Identify charging point creator
     */
    private function identifyChargingPointCreator(ChargingPoint $chargingPoint)
    {
        // Priority 1: Direct integrator
        if ($chargingPoint->integrator_id) {
            return Integrator::find($chargingPoint->integrator_id);
        }

        // Priority 2: Partner
        if ($chargingPoint->partner_id) {
            return Partner::find($chargingPoint->partner_id);
        }

        // Priority 3: Group (if applicable)
        if ($chargingPoint->group_id) {
            $group = $chargingPoint->group;
            if ($group) {
                if ($group->integrator_id) {
                    return Integrator::find($group->integrator_id);
                }
                if ($group->partner_id) {
                    return Partner::find($group->partner_id);
                }
            }
        }

        return null;
    }

    /**
     * Get creator type
     */
    private function getCreatorType($creator): string
    {
        if ($creator instanceof Integrator) {
            return 'integrator';
        } elseif ($creator instanceof Partner) {
            return 'partner';
        }
        return 'unknown';
    }

    /**
     * Get business profile for creator
     */
    private function getBusinessProfileForCreator($creator): ?BusinessProfile
    {
        if (!$creator) {
            return null;
        }

        if ($creator instanceof Integrator) {
            return $creator->businessProfile;
        }

        if ($creator instanceof Partner) {
            return $creator->businessProfile;
        }

        return null;
    }

    /**
     * Get charging point info
     */
    private function getChargingPointInfo(ChargingPoint $chargingPoint): array
    {
        return [
            'id' => $chargingPoint->id,
            'name' => $chargingPoint->name,
            'serial_number' => $chargingPoint->serial_number,
            'status' => $chargingPoint->status,
            'location' => $chargingPoint->location,
            'integrator_id' => $chargingPoint->integrator_id,
            'partner_id' => $chargingPoint->partner_id,
            'group_id' => $chargingPoint->group_id
        ];
    }

    /**
     * Get creator info
     */
    private function getCreatorInfo($creator): array
    {
        if (!$creator) {
            return [
                'type' => 'unknown',
                'id' => null,
                'name' => 'Unknown',
                'email' => null
            ];
        }

        return [
            'type' => class_basename($creator),
            'id' => $creator->id,
            'name' => $creator->name,
            'email' => $creator->email ?? null
        ];
    }

    /**
     * Get business profile info
     */
    private function getBusinessProfileInfo(?BusinessProfile $businessProfile): ?array
    {
        if (!$businessProfile) {
            return null;
        }

        return [
            'id' => $businessProfile->id,
            'name' => $businessProfile->name,
            'description' => $businessProfile->description,
            'is_active' => $businessProfile->is_active,
            'is_public' => $businessProfile->is_public,
            'commission_rates' => [
                'operator' => $businessProfile->operator_commission,
                'integrator' => $businessProfile->integrator_commission,
                'owner' => $businessProfile->owner_commission,
                'partner' => $businessProfile->partner_commission
            ]
        ];
    }

    /**
     * Get default comprehensive breakdown
     */
    private function getDefaultComprehensiveBreakdown(): array
    {
        return [
            'charging_point_info' => ['id' => null, 'name' => 'Unknown'],
            'creator_info' => ['type' => 'unknown', 'id' => null, 'name' => 'Unknown', 'email' => null],
            'creator_type' => 'unknown',
            'business_profile_info' => null,
            'base_amount' => 0,
            'fee_calculation' => [
                'charging_fees' => ['fixed' => 0, 'percentage' => 0, 'total' => 0],
                'transaction_fees' => ['fixed' => 0, 'percentage' => 0, 'total' => 0],
                'activation_fees' => ['base' => 0, 'setup' => 0, 'total' => 0],
                'admin_fees' => ['fixed' => 0, 'percentage' => 0, 'total' => 0],
                'total_fees' => 0
            ],
            'admin_part' => ['applies' => false, 'reason' => 'Erreur de calcul', 'fees' => ['total' => 0], 'breakdown' => []],
            'integrator_part' => ['applies' => false, 'reason' => 'Erreur de calcul', 'fees' => ['total' => 0], 'breakdown' => []],
            'operator_part' => ['applies' => false, 'reason' => 'Erreur de calcul', 'revenue' => ['net_revenue' => 0], 'breakdown' => []],
            'partner_part' => ['applies' => false, 'reason' => 'Erreur de calcul', 'fees' => ['total' => 0], 'breakdown' => []],
            'total_breakdown' => ['total_fees' => 0, 'net_revenue' => 0],
            'summary' => ['total_fees' => 0, 'has_fees' => false],
            'applied_fees_details' => []
        ];
    }

    /**
     * Apply comprehensive fees to transaction
     */
    public function applyComprehensiveFeesToTransaction(Transaction $transaction): bool
    {
        try {
            $chargingPoint = $transaction->chargingPoint;
            if (!$chargingPoint) {
                return false;
            }

            $comprehensiveBreakdown = $this->calculateComprehensiveFees($chargingPoint, $transaction->price_total ?? 0);
            
            // Update transaction with comprehensive fees
            $transaction->update([
                'business_profile_fee_breakdown' => $comprehensiveBreakdown,
                'creator_charging_fees' => $comprehensiveBreakdown['fee_calculation']['charging_fees']['total'],
                'creator_transaction_fees' => $comprehensiveBreakdown['fee_calculation']['transaction_fees']['total'],
                'creator_activation_fees' => $comprehensiveBreakdown['fee_calculation']['activation_fees']['total'],
                'creator_admin_fees' => $comprehensiveBreakdown['admin_part']['fees']['total'],
                'creator_fees_total' => $comprehensiveBreakdown['total_breakdown']['total_fees'],
                'creator_fees_applied_at' => now(),
                'creator_fees_source' => $comprehensiveBreakdown['creator_type'],
                'price_total' => $transaction->price_total + $comprehensiveBreakdown['total_breakdown']['total_fees']
            ]);

            Log::info('Comprehensive fees applied to transaction', [
                'transaction_id' => $transaction->id,
                'charging_point_id' => $chargingPoint->id,
                'creator_type' => $comprehensiveBreakdown['creator_type'],
                'total_fees' => $comprehensiveBreakdown['total_breakdown']['total_fees'],
                'admin_part' => $comprehensiveBreakdown['admin_part']['fees']['total'],
                'integrator_part' => $comprehensiveBreakdown['integrator_part']['fees']['total']
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error applying comprehensive fees to transaction', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
