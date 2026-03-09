<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Repositories\PaymentRepository;
use App\Repositories\RazorpayRepository;
use App\Settings\StripeSettings;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class WebHookController extends Controller
{
    /**
     * Razorpay payments webhook
     *
     * @param Request $request
     * @param RazorpayRepository $repository
     * @return \Illuminate\Http\JsonResponse
     */
    public function razorpay(Request $request, RazorpayRepository $repository)
    {
        // Verify signature
        $verified = $repository->verifyWebhook($request->getContent(), $request->header('X-Razorpay-Signature'));

        if(!$verified) {
            return response()->json(['success' => false], 400);
        }

        $payload = $request->get('payload');

        // If payment captured payment status as success
        if($request->get('event') == 'payment.captured') {
            $payment = Payment::where('transaction_id', '=', $payload['payment']['entity']['id'])->first();
            if($payment) {
                $payment->status = 'success';
                $payment->update;
            }
        }

        // If payment failed mark payment status as failed
        if($request->get('event') == 'payment.failed') {
            $payment = Payment::where('transaction_id', '=', $payload['payment']['entity']['id'])->first();
            if($payment) {
                $payment->status = 'failed';
                $payment->update;
            }
        }

        return response()->json(['success' => true], 200);
    }

    /**
     * Stripe payment webhook to fulfil the order
     *
     * @param Request $request
     * @param PaymentRepository $paymentRepository
     * @return \Illuminate\Http\JsonResponse
     */
    public function stripe(Request $request, PaymentRepository $paymentRepository)
    {
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;
        $settings = app(StripeSettings::class);
        try {
            $event = Webhook::constructEvent(
                $payload, $sig_header, $settings->webhook_secret
            );
        } catch(\UnexpectedValueException $e) {
            return response()->json(['success' => false], 400);
        } catch(SignatureVerificationException $e) {
            return response()->json(['success' => false], 400);
        }

        // If payment captured payment status as success
        if($event->type== 'checkout.session.completed') {
            $session = $event->data->object;
            $payment = Payment::with(['plan', 'subscription'])->where('payment_id', '=', $session['client_reference_id'])->first();

            // check if payment has been process previously
            if($payment->status == 'success' || $payment->status == 'failed'  || $payment->status == 'cancelled') {
                return response()->json(['success' => true], 200);
            }

            //else update payment status and stripe data
            if($session['payment_status'] == 'paid') {
                $payment->transaction_id = $session['payment_intent'];
                $payment->data->set([
                    'stripe' => [
                        'stripe_signature' => $sig_header
                    ]
                ]);
                $payment->payment_date = Carbon::now()->toDateTimeString();
                $payment->status = 'success';
                $payment->update();

                // create if subscription not exists for the payment
                if(!$payment->subscription) {
                    $subscription = $paymentRepository->createSubscription([
                        'payment_id' => $payment->id,
                        'plan_id' => $payment->plan_id,
                        'user_id' => $payment->user_id,
                        'category_type' => $payment->plan->category_type,
                        'category_id' => $payment->plan->category_id,
                        'duration' => $payment->plan->duration,
                        'status' => 'active'
                    ]);
                }
            }
        }

        // If payment failed mark payment status as failed
        if($event->type == 'charge.failed') {
            $charge = $event->data->object;
            $payment = Payment::where('transaction_id', '=', $charge['client_reference_id'])->first();
            if($payment) {
                $payment->status = 'failed';
                $payment->update;
            }
        }

        return response()->json(['success' => true], 200);
    }
}
