<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . "/../vendor/autoload.php";
require_once __DIR__ . "/../secrets.php";
require_once __DIR__ . "/../sql.php";

use StripePayment\library\SecretKey;
use StripePayment\library\UserData;
use StripePayment\library\PaymentData;

#chave da empresa
$sk = new SecretKey($company);
$secretkey = $sk->getKey('producao');

\Stripe\Stripe::setApiKey($secretkey);

$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$endpoint_secret = $sk->getWebhookSecret();

$event = null;

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sig_header,
        $endpoint_secret
    );
} catch (\UnexpectedValueException $e) {
    // Payload inválido
    http_response_code(400);
    exit();
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    // Assinatura inválida
    http_response_code(400);
    exit();
}

switch ($event->type) {
    case 'checkout.session.completed':
        $session = $event->data->object;

        if ($session->payment_status == 'paid') {
            $paymentIntentId = $session->payment_intent;
            $amount = $session->amount_total;
            $currency = $session->currency;
            $customerEmail = $session->customer_details->email;

            #dados do pagamento
            $pay = new PaymentData();
            $paymentData = $pay->getPayment($session->id);

            #dados do usuário
            $us = new UserData();
            $usData = $us->getUserDataByHash($paymentData['controle']);

            #atualizando pagamento
            $pay->updatePayment("pago", $currency, $paymentData['id']);

            #atualizando usuário
            $us->updateUser(1, $usData['id']);

            #salvar purchase
            $pay->insertPurchase($usData['event'], $usData['id'], $paymentData['valor'], $currency, $paymentData['payment_intent'], $paymentData['controle']);
            echo json_encode([
                'code' => 1
            ]);

            // Log de sucesso
            error_log('Pagamento concluído para a sessão ' . $session->id);
        }

        break;
    default:
        http_response_code(200);
        exit();
}

http_response_code(200);