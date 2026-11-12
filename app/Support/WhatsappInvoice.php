<?php

namespace App\Support;

use App\Models\Order;

/** Mini invoice sent to a customer on WhatsApp (admin order page): the message text with its placeholders. */
class WhatsappInvoice
{
    public const DEFAULT_MESSAGE = "Dear Sir, your order has been received and it's processing. Thank you.";

    /** Settings message (or the default) with {name} {order} {total} filled in. */
    public static function message(Order $order): string
    {
        $template = trim((string) setting('whatsapp_invoice_message')) ?: self::DEFAULT_MESSAGE;

        return strtr($template, [
            '{name}' => $order->customer_name,
            '{order}' => $order->order_number,
            '{total}' => 'Tk.'.money($order->total, false),
        ]);
    }

    /** 01XXXXXXXXX → 8801XXXXXXXXX (wa.me needs the country code). */
    public static function number(string $mobile): string
    {
        $digits = preg_replace('/\D/', '', $mobile);

        return str_starts_with($digits, '880') ? $digits : (str_starts_with($digits, '0') ? '88'.$digits : '880'.$digits);
    }
}
