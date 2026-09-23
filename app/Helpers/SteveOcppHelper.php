<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Config;

class SteveOcppHelper
{
    /**
     * Obtenir l'URL OCPP WebSocket pour une borne spécifique
     * 
     * @param string $chargeBoxId L'identifiant unique de la borne
     * @return string URL WebSocket complète
     */
    public static function getWebSocketUrl(string $chargeBoxId): string
    {
        $baseUrl = Config::get('steve.ocpp_websocket_endpoint', 'ws://158.69.27.239:8180/steve/websocket/CentralSystemService/');
        return rtrim($baseUrl, '/') . '/' . $chargeBoxId;
    }

    /**
     * Obtenir l'URL OCPP SOAP
     * 
     * @return string URL SOAP
     */
    public static function getSoapUrl(): string
    {
        return Config::get('steve.ocpp_soap_endpoint', 'http://158.69.27.239:8180/steve/services/CentralSystemService');
    }

    /**
     * Générer les informations de configuration OCPP pour une borne
     * 
     * @param string $chargeBoxId L'identifiant unique de la borne
     * @return array Tableau avec les informations de configuration
     */
    public static function getConfigurationInfo(string $chargeBoxId): array
    {
        return [
            'chargeBoxId' => $chargeBoxId,
            'ocpp_versions' => [
                'OCPP 1.6 JSON (WebSocket)' => [
                    'url' => self::getWebSocketUrl($chargeBoxId),
                    'protocol' => 'WebSocket',
                    'recommended' => true,
                    'description' => 'Protocole moderne avec communication bidirectionnelle'
                ],
                'OCPP 1.6 SOAP' => [
                    'url' => self::getSoapUrl(),
                    'protocol' => 'SOAP/XML over HTTP',
                    'recommended' => false,
                    'description' => 'Protocole legacy pour compatibilité'
                ],
                'OCPP 1.5 SOAP' => [
                    'url' => self::getSoapUrl(),
                    'protocol' => 'SOAP/XML over HTTP',
                    'recommended' => false,
                    'description' => 'Version ancienne, utiliser 1.6 si possible'
                ]
            ],
            'steve_web_interface' => Config::get('steve.ocpp_base_url', 'http://158.69.27.239:8180') . '/steve/manager/home',
            'configuration_steps' => [
                '1. Accéder au menu de configuration de votre borne de recharge',
                '2. Sélectionner la version OCPP supportée par votre borne',
                '3. Entrer l\'URL du Central System (voir ci-dessus)',
                '4. Entrer le ChargeBox ID: ' . $chargeBoxId,
                '5. Activer la connexion et redémarrer la borne',
                '6. Vérifier dans l\'interface Steve que la borne est connectée'
            ]
        ];
    }

    /**
     * Générer un QR code pour la configuration OCPP (nécessite simplesoftwareio/simple-qrcode)
     * 
     * @param string $chargeBoxId
     * @param string $format Format du QR code ('svg', 'png', 'eps')
     * @return string|null Code QR en format demandé ou null si package non installé
     */
    public static function generateConfigQrCode(string $chargeBoxId, string $format = 'svg'): ?string
    {
        if (!class_exists('\SimpleSoftwareIO\QrCode\Facades\QrCode')) {
            return null;
        }

        $url = self::getWebSocketUrl($chargeBoxId);
        $configData = json_encode([
            'type' => 'ocpp_config',
            'chargeBoxId' => $chargeBoxId,
            'url' => $url,
            'protocol' => 'OCPP 1.6 JSON'
        ]);

        try {
            return \SimpleSoftwareIO\QrCode\Facades\QrCode::format($format)
                ->size(300)
                ->errorCorrection('H')
                ->generate($configData);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Formater une URL OCPP pour l'affichage
     * 
     * @param string $url
     * @return string HTML formaté
     */
    public static function formatUrlForDisplay(string $url): string
    {
        $parts = parse_url($url);
        $protocol = $parts['scheme'] ?? '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = $parts['path'] ?? '';

        return sprintf(
            '<span class="protocol">%s://</span><span class="host">%s</span><span class="port">%s</span><span class="path">%s</span>',
            htmlspecialchars($protocol),
            htmlspecialchars($host),
            htmlspecialchars($port),
            htmlspecialchars($path)
        );
    }

    /**
     * Vérifier si les endpoints OCPP sont configurés
     * 
     * @return array Status de configuration
     */
    public static function checkConfiguration(): array
    {
        $soapUrl = Config::get('steve.ocpp_soap_endpoint');
        $wsUrl = Config::get('steve.ocpp_websocket_endpoint');
        $baseUrl = Config::get('steve.ocpp_base_url');

        return [
            'soap_configured' => !empty($soapUrl),
            'websocket_configured' => !empty($wsUrl),
            'base_url_configured' => !empty($baseUrl),
            'all_configured' => !empty($soapUrl) && !empty($wsUrl) && !empty($baseUrl),
            'urls' => [
                'soap' => $soapUrl,
                'websocket' => $wsUrl,
                'base' => $baseUrl
            ]
        ];
    }

    /**
     * Obtenir les exemples de configuration pour documentation
     * 
     * @param string $chargeBoxId
     * @return array
     */
    public static function getConfigurationExamples(string $chargeBoxId): array
    {
        return [
            'websocket' => [
                'title' => 'Configuration OCPP 1.6 JSON (WebSocket) - Recommandé',
                'fields' => [
                    'ChargeBox ID' => $chargeBoxId,
                    'OCPP Version' => '1.6J (JSON)',
                    'Central System URL' => self::getWebSocketUrl($chargeBoxId),
                    'Protocol' => 'WebSocket',
                    'Port' => '8180'
                ]
            ],
            'soap' => [
                'title' => 'Configuration OCPP 1.6 SOAP',
                'fields' => [
                    'ChargeBox ID' => $chargeBoxId,
                    'OCPP Version' => '1.6S (SOAP)',
                    'Central System URL' => self::getSoapUrl(),
                    'Protocol' => 'SOAP/XML',
                    'Port' => '8180'
                ]
            ]
        ];
    }
}

