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

use StripePayment\library\UserData;
use StripePayment\library\PaymentData;

try {
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    #dados do pagamento
    $pay = new PaymentData();
    $paymentData = $pay->getPayment($data['client_secret']);

    #dados do usuário
    $us = new UserData();
    $usData = $us->getUserDataByHash($paymentData['controle']);

    #atualizando pagamento
    $estado = $data['estado'];
    $pay->updatePayment($estado, $paymentData['id']);

    #atualizando usuário
    $state = $estado == "pago" ? 1 : 0;
    $us->updateUser($state, $usData['id']);

    #salvar purchase
    $pay->insertPurchase($usData['event'], $usData['id'], $paymentData['valor'], $data['currency'], $paymentData['payment_intent'], $paymentData['controle']);
    echo json_encode([
        'code' => 1
    ]);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'code' => 0,
        'msg' => $e->getMessage()
    ]);
}
