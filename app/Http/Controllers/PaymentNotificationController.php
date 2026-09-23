<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\Order;
use App\Models\Reservation;
use App\Mail\PaymentSuccessMail;
use App\Mail\PaymentFailureMail;
use App\Mail\PaymentPendingMail;

class PaymentNotificationController extends Controller
{
    /**
     * Envoyer une notification de succès de paiement
     */
    public function sendPaymentSuccessNotification(Order $order)
    {
        try {
            $reservation = Reservation::find($order->details['reservation_id'] ?? null);
            if (!$reservation) {
                Log::warning('Payment success notification: Reservation not found', [
                    'order_id' => $order->id
                ]);
                return false;
            }

            $user = $reservation->user;
            if (!$user || !$user->email) {
                Log::warning('Payment success notification: User email not found', [
                    'order_id' => $order->id,
                    'reservation_id' => $reservation->id
                ]);
                return false;
            }

            // Envoyer l'email de confirmation
            Mail::to($user->email)->send(new PaymentSuccessMail($order, $reservation));

            // Envoyer une notification push si configurée
            $this->sendPushNotification($user, [
                'title' => 'Paiement Confirmé',
                'body' => 'Votre paiement a été traité avec succès. Votre réservation est confirmée.',
                'type' => 'payment_success',
                'data' => [
                    'order_id' => $order->id,
                    'reservation_id' => $reservation->id,
                    'amount' => $order->amount
                ]
            ]);

            Log::info('Payment success notification sent', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Payment success notification failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Envoyer une notification d'échec de paiement
     */
    public function sendPaymentFailureNotification(Order $order, string $reason = null)
    {
        try {
            $reservation = Reservation::find($order->details['reservation_id'] ?? null);
            if (!$reservation) {
                Log::warning('Payment failure notification: Reservation not found', [
                    'order_id' => $order->id
                ]);
                return false;
            }

            $user = $reservation->user;
            if (!$user || !$user->email) {
                Log::warning('Payment failure notification: User email not found', [
                    'order_id' => $order->id,
                    'reservation_id' => $reservation->id
                ]);
                return false;
            }

            // Envoyer l'email d'échec
            Mail::to($user->email)->send(new PaymentFailureMail($order, $reservation, $reason));

            // Envoyer une notification push
            $this->sendPushNotification($user, [
                'title' => 'Paiement Échoué',
                'body' => 'Votre paiement n\'a pas pu être traité. Veuillez réessayer.',
                'type' => 'payment_failure',
                'data' => [
                    'order_id' => $order->id,
                    'reservation_id' => $reservation->id,
                    'reason' => $reason
                ]
            ]);

            Log::info('Payment failure notification sent', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'email' => $user->email,
                'reason' => $reason
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Payment failure notification failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Envoyer une notification de paiement en attente
     */
    public function sendPaymentPendingNotification(Order $order)
    {
        try {
            $reservation = Reservation::find($order->details['reservation_id'] ?? null);
            if (!$reservation) {
                Log::warning('Payment pending notification: Reservation not found', [
                    'order_id' => $order->id
                ]);
                return false;
            }

            $user = $reservation->user;
            if (!$user || !$user->email) {
                Log::warning('Payment pending notification: User email not found', [
                    'order_id' => $order->id,
                    'reservation_id' => $reservation->id
                ]);
                return false;
            }

            // Envoyer l'email d'attente
            Mail::to($user->email)->send(new PaymentPendingMail($order, $reservation));

            // Envoyer une notification push
            $this->sendPushNotification($user, [
                'title' => 'Paiement en Attente',
                'body' => 'Votre paiement est en cours de traitement. Vous recevrez une confirmation sous peu.',
                'type' => 'payment_pending',
                'data' => [
                    'order_id' => $order->id,
                    'reservation_id' => $reservation->id
                ]
            ]);

            Log::info('Payment pending notification sent', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Payment pending notification failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Envoyer une notification de rappel de paiement
     */
    public function sendPaymentReminderNotification(Order $order)
    {
        try {
            $reservation = Reservation::find($order->details['reservation_id'] ?? null);
            if (!$reservation) {
                return false;
            }

            $user = $reservation->user;
            if (!$user || !$user->email) {
                return false;
            }

            // Vérifier si le paiement est toujours en attente
            if ($order->status !== 'pending_payment') {
                return false;
            }

            // Envoyer l'email de rappel
            Mail::to($user->email)->send(new PaymentPendingMail($order, $reservation, true));

            // Envoyer une notification push
            $this->sendPushNotification($user, [
                'title' => 'Rappel de Paiement',
                'body' => 'Votre réservation attend votre paiement. Cliquez pour finaliser.',
                'type' => 'payment_reminder',
                'data' => [
                    'order_id' => $order->id,
                    'reservation_id' => $reservation->id,
                    'payment_url' => $this->getPaymentUrl($order)
                ]
            ]);

            Log::info('Payment reminder notification sent', [
                'order_id' => $order->id,
                'user_id' => $user->id
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Payment reminder notification failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Envoyer une notification push
     */
    private function sendPushNotification($user, array $notification)
    {
        try {
            // Ici vous pouvez intégrer avec Firebase, OneSignal, ou autre service de push
            // Pour l'instant, on log juste la notification
            Log::info('Push notification prepared', [
                'user_id' => $user->id,
                'notification' => $notification
            ]);

            // Exemple d'intégration Firebase (à implémenter selon vos besoins)
            /*
            $firebase = app('firebase');
            $messaging = $firebase->getMessaging();
            
            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification(Notification::create($notification['title'], $notification['body']))
                ->withData($notification['data']);
                
            $messaging->send($message);
            */

        } catch (\Exception $e) {
            Log::error('Push notification failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtenir l'URL de paiement pour une commande
     */
    private function getPaymentUrl(Order $order): string
    {
        $paymentMethod = $order->payment_method;
        
        switch ($paymentMethod) {
            case 'cmi':
                return route('payment.cmi.initiate');
            case 'stripe':
                return route('payment.stripe.initiate.public');
            default:
                return route('dashboard');
        }
    }

    /**
     * Envoyer des notifications en lot
     */
    public function sendBulkNotifications(array $orderIds, string $type)
    {
        $orders = Order::whereIn('id', $orderIds)->get();
        $results = [];

        foreach ($orders as $order) {
            switch ($type) {
                case 'success':
                    $results[] = $this->sendPaymentSuccessNotification($order);
                    break;
                case 'failure':
                    $results[] = $this->sendPaymentFailureNotification($order);
                    break;
                case 'pending':
                    $results[] = $this->sendPaymentPendingNotification($order);
                    break;
                case 'reminder':
                    $results[] = $this->sendPaymentReminderNotification($order);
                    break;
            }
        }

        return [
            'total' => count($orders),
            'sent' => count(array_filter($results)),
            'failed' => count($orders) - count(array_filter($results))
        ];
    }
}
