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

//var_dump($data);die();

$userId = $data['userid'];
$company = $data['company'];
$type = 'producao';
$descricao = $data['descricao'];
$urlSucesso = $data['urlsucesso'];
$urlCancelado = $data['urlcancelado'];
$produtoAluno = $data['produtoAluno'];
$produtoNovo = $data['produtoNovo'];
$priceId = $data['categoria'] == 'aluno' ? $produtoAluno : $produtoNovo;

#chave da empresa
$sk = new SecretKey($company);
$secretkey = $sk->getKey($type);

Stripe\Stripe::setApiKey($secretkey);

#dados do usuário
$us = new UserData($userId);
$usData = $us->getUserData();
$user = json_decode($usData['additional_info'], true);

#configuração do pagamento
//$moeda = $user['dataPais'] == "Brasil" ? "brl" : "eur";

#valor do pagamento
$valor = intval($us->getValueByCat($usData['subscribe_training_center']));

#instância do stripe client
$stripe =  new StripeClient($secretkey);

try {
    $customer = Customer::create([
        'email' => $user['dataEmail'],
        'name' => $user['dataName']
    ]);

    $checkout_session = $stripe->checkout->sessions->create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price' => $priceId,
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => $urlSucesso.'session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $urlCancelado,
        'customer' => $customer->id,
        'locale' => 'auto',
    ]);
    
    $paymentData = [
        'empresa' => $company,
        'controle' => $usData['control_hash'],
        'valor' => $valor,
        'paymentintent' => $checkout_session->id,
        'estado' => 'aguardando'
    ];
    
    PaymentData::saveIntent($paymentData);

    $retorno = [
        'code' => 1,
        'url' => $checkout_session->url,
    ];

    echo json_encode($retorno);
} catch (Error $e) {
    http_response_code(500);
    echo json_encode([
        'code' => 0,
        'error' => $e->getMessage()
    ]);
}