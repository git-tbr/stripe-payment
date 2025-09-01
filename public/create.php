<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__."/../vendor/autoload.php";
require_once __DIR__."/../secrets.php";
require_once __DIR__."/../sql.php";

use StripePayment\library\SecretKey;
use StripePayment\library\UserData;
use StripePayment\library\PaymentData;
use Stripe\StripeClient;
use Stripe\Customer;

$data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$userId = $data['userid'];
$company = $data['company'];
$type = 'producao';
$descricao = $data['descricao'];

#chave da empresa
$sk = new SecretKey($company);
$secretkey = $sk->getKey($type);

#dados do usuário
$us = new UserData($userId);
$usData = $us->getUserData();
$user = json_decode($usData['additional_info'], true);

#configuração do pagamento
$moeda = $user['dataPais'] == "Brasil" ? "brl" : "eur";

#valor do pagamento
$valor = intval($us->getValueByCat($usData['subscribe_training_center']));

#instância do stripe client
$stripe = new StripeClient($secretkey);

try {
    $customer = Customer::create([
        'email' => $user['dataEmail'],
        'name' => $user['dataName']
    ]);

    $paymentIntent = $stripe->paymentIntents->create([
        'amount'=>$valor,
        'currency'=>$moeda,
        'description'=>$descricao,
        'automatic_payment_methods'=>['enabled' => true],
        'receipt_email'=>$user['dataEmail'],
        'customer'=>$customer->id,
    ]);
    
    $paymentData = [
        'empresa' => $company,
        'controle' => $usData['control_hash'],
        'valor' => $valor,
        'paymentintent' => $paymentIntent->client_secret,
        'estado' => 'aguardando'
    ];
    
    PaymentData::saveIntent($paymentData);

    $retorno = [
        'code' => 1,
        'clientSecret' => $paymentIntent->client_secret,
    ];

    echo json_encode($retorno);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'code' => 0,
        'error' => $e->getMessage()
    ]);
}