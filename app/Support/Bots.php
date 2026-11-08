<?php

namespace App\Support;

class Bots
{
    /** Crawlers that fetch a page to build a link preview (Facebook, WhatsApp, …) or index it. */
    public static function isLinkPreview(?string $userAgent): bool
    {
        return (bool) preg_match('/facebookexternalhit|facebot|twitterbot|whatsapp|telegrambot|linkedinbot|slackbot|discordbot|skypeuripreview|viber|pinterest|vkshare|embedly|googlebot|bingbot|redditbot|applebot|yandex|baiduspider|duckduckbot|petalbot|semrush|ahrefs/i', (string) $userAgent);
    }
}
