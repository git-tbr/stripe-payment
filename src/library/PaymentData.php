<?php

namespace StripePayment\library;

class PaymentData
{
    public static function saveIntent($data)
    {
        sql([
            "statement" => "INSERT INTO pagamentos.pedidos_stripe 
                            SET     empresa=?, 
                                    controle=?, 
                                    valor=?, 
                                    payment_intent=?, 
                                    estado=?",
            "types" => "isiss",
            "parameters" => [
                $data['empresa'],
                $data['controle'],
                $data['valor'],
                $data['paymentintent'],
                $data['estado']
            ]
        ]);
    }

    public function getPayment($paymentIntent)
    {
        $search = sql([
            "statement" => "SELECT * FROM pagamentos.pedidos_stripe WHERE payment_intent = ?",
            "types" => "s",
            "parameters" => [
                $paymentIntent
            ],
            "only_first_row" => "1"
        ]);

        return isset($search) ? $search : 0;
    }

    public function updatePayment($estado, $id)
    {
        sql([
            "statement" => "UPDATE pagamentos.pedidos_stripe SET estado=? WHERE id=?",
            "types" => "si",
            "parameters" => [
                $estado,
                $id
            ]
        ]);
    }

    public function insertPurchase($evento, $usuario, $valor, $moeda, $py, $controlhash)
    {
        sql([
            "statement" => "INSERT INTO     tbrevent.purchase 
                                    SET     `event`=?, 
                                            register=?, 
                                            `value`=?,
                                            currency=?,
                                            `date`=now(),
                                            date_update=now(),
                                            `status`='2',
                                            payment_ref_code=?,
                                            payment_status='Pago',
                                            account='stripe',
                                            control_hash=?",
            "types" => "iidsss",
            "parameters" => [
                $evento,
                $usuario,
                floatVal($valor / 100),
                $moeda,
                $py,
                $controlhash
            ]
        ]);
    }
}
