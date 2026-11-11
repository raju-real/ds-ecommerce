<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\SettingRepository;
use App\Services\FacebookPixel;
use App\Services\ImageService;
use App\Services\SmsService;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    /** Text settings editable from the form, grouped by tab. */
    public const FIELDS = [
        'general' => ['site_name', 'tagline', 'topbar_text', 'footer_about', 'copyright_text', 'default_delivery_charge'],
        'contact' => ['phone', 'whatsapp', 'email', 'address', 'map_embed_url', 'facebook', 'instagram', 'youtube', 'tiktok', 'messenger'],
        'seo' => ['meta_title', 'meta_description', 'meta_keywords', 'google_analytics_id', 'google_site_verification'],
        // The access token is handled separately (stored encrypted, kept when the field is left empty).
        'facebook' => ['facebook_pixel_id', 'facebook_capi_enabled', 'facebook_test_event_code', 'facebook_domain_verification'],
        'sms' => ['sms_driver', 'sms_api_url', 'sms_api_key', 'sms_sender_id', 'sms_brand', 'sms_otp_template'],
    ];

    public function __construct(protected SettingRepository $settings, protected ImageService $images)
    {
    }

    public function edit()
    {
        return view('admin.settings.edit', ['s' => $this->settings->all(), 'sitemap' => app(\App\Support\Sitemap::class)->summary()]);
    }

    public function update(Request $request)
    {
        $phone = ['nullable', 'string', 'max:20', 'regex:/^\+?[0-9 \-]{6,20}$/'];
        // Verification codes: the whole <meta …> tag may be pasted; keep only its content="…" value.
        foreach (['google_site_verification', 'facebook_domain_verification'] as $key) {
            if ($request->has($key)) {
                $request->merge([$key => self::verificationCode($request->input($key))]);
            }
        }
        $request->validate([
            'site_name' => 'required|string|min:2|max:60',
            'tagline' => 'nullable|string|max:120',
            'topbar_text' => 'nullable|string|max:120',
            'footer_about' => 'nullable|string|max:300',
            'copyright_text' => 'nullable|string|max:120',
            'default_delivery_charge' => 'nullable|numeric|min:0|max:9999',
            'phone' => $phone,
            'whatsapp' => $phone,
            'email' => 'nullable|email|max:150',
            'address' => 'nullable|string|max:255',
            'map_embed_url' => 'nullable|url|max:1000',
            'facebook' => 'nullable|url|max:255', 'instagram' => 'nullable|url|max:255', 'youtube' => 'nullable|url|max:255',
            'tiktok' => 'nullable|url|max:255', 'messenger' => 'nullable|url|max:255',
            'meta_title' => 'nullable|string|max:70',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords' => 'nullable|array|max:'.Seo::MAX_KEYWORDS,
            'meta_keywords.*' => 'nullable|string|max:60',
            'google_analytics_id' => ['nullable', 'string', 'max:30', 'regex:/^(G|UA)-[A-Z0-9\-]+$/'],
            'google_site_verification' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'facebook_domain_verification' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'facebook_pixel_id' => 'nullable|digits_between:10,20',
            'facebook_capi_enabled' => 'nullable|in:0,1',
            'facebook_capi_token' => 'nullable|string|min:20|max:1000',
            'facebook_test_event_code' => 'nullable|alpha_num|max:30',
            'sms_driver' => 'nullable|in:log,bulksmsbd,http',
            'sms_brand' => 'nullable|string|max:40',
            'sms_otp_template' => ['nullable', 'string', 'max:160', 'regex:/\{otp\}/'],
            'sms_api_url' => 'nullable|string|max:500|starts_with:http://,https://|required_if:sms_driver,http', // contains {placeholders}, so not a strict URL
            'sms_api_key' => 'nullable|string|max:255',
            'sms_sender_id' => 'nullable|string|max:30',
            'logo' => upload_rules('logo'),
            'logo_light' => upload_rules('logo'),
            'favicon' => upload_rules('favicon'),
            'og_image' => upload_rules('og_image'),
        ], [
            'phone.regex' => 'Enter a valid phone number.',
            'whatsapp.regex' => 'Enter a valid WhatsApp number.',
            'google_analytics_id.regex' => 'Analytics ID looks like G-XXXXXXXXXX.',
            'facebook_capi_token.min' => 'This does not look like a Conversions API access token.',
            'facebook_test_event_code.alpha_num' => 'Test event code is letters and digits only (e.g. TEST12345).',
            'sms_otp_template.regex' => 'The SMS text must contain {otp} (where the code goes).',
            'sms_api_url.required_if' => 'Enter the API URL for the custom provider.',
            'google_site_verification.regex' => 'Paste the code from Search Console (or the whole <meta name="google-site-verification" …> tag).',
            'facebook_domain_verification.regex' => 'Paste the code from Facebook (or the whole <meta name="facebook-domain-verification" …> tag).',
        ]);

        $values = $request->only(array_merge(...array_values(self::FIELDS)));
        // Shown in page titles / meta tags: store plain text ("&amp;" → "&"), the page escapes it when printing.
        foreach (['site_name', 'tagline', 'meta_title', 'meta_description'] as $key) {
            if (array_key_exists($key, $values)) {
                $values[$key] = Seo::plain($values[$key]);
            }
        }
        if ($request->has('meta_keywords_present')) {
            $values['meta_keywords'] = Seo::keywords($request->input('meta_keywords', [])); // tags → "a, b, c"
        }
        if ($request->has('facebook_present')) {
            $values['facebook_capi_enabled'] = $request->boolean('facebook_capi_enabled') ? '1' : '0';
            $values['facebook_test_event_code'] = trim((string) $request->input('facebook_test_event_code')) ?: null;
            // Token: a new one replaces the saved one (encrypted); empty keeps it; "remove" deletes it.
            if (filled($token = trim((string) $request->input('facebook_capi_token')))) {
                $values['facebook_capi_token'] = Crypt::encryptString($token);
            } elseif ($request->boolean('remove_facebook_capi_token')) {
                $values['facebook_capi_token'] = null;
            }
        }

        $uploads = [
            'logo' => [config('shop.images.logo'), 'max', 'webp'],
            'logo_light' => [config('shop.images.logo'), 'max', 'webp'],
            'favicon' => [config('shop.images.favicon'), 'cover', 'png'],
            'og_image' => [[1200, 630], 'cover', 'jpg'],
        ];
        foreach ($uploads as $key => [$size, $mode, $format]) {
            if ($request->hasFile($key)) {
                $this->images->delete($this->settings->get($key));
                $values[$key] = $this->images->store($request->file($key), 'settings', $size, $mode, $format);
            } elseif ($request->boolean("remove_$key")) {
                $this->images->delete($this->settings->get($key));
                $values[$key] = null;
            }
        }

        $this->settings->save($values);

        return back()->with('success', 'Settings saved.')->withFragment($request->input('tab', ''));
    }

    /** "Check balance" on the SMS tab (BulkSMSBD, saved API key). */
    public function smsBalance(SmsService $sms)
    {
        $result = $sms->balance();

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /** "Send test SMS" on the SMS tab: the OTP text with a sample code, sent with the saved settings. */
    public function smsTest(Request $request, SmsService $sms)
    {
        $data = $request->validate(
            ['mobile' => ['required', 'string', 'regex:/^(\+?880|0)?1[3-9][0-9]{8}$/']],
            ['mobile.regex' => 'Enter a valid Bangladeshi mobile number, e.g. 017XXXXXXXX.', 'mobile.required' => 'Enter a mobile number to send the test to.'],
        );
        if ($sms->isLogDriver()) {
            return response()->json(['ok' => false, 'message' => 'Choose BulkSMSBD (or a custom provider) and save first. "Log only" never sends SMS.'], 422);
        }
        $result = $sms->sendWithResult(preg_replace('/\s+/', '', $data['mobile']), $sms->otpMessage((string) random_int(100000, 999999)));

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    /** `<meta name="…" content="abc123" />` → "abc123"; a plain code is returned trimmed; empty → null. */
    public static function verificationCode(mixed $value): ?string
    {
        $value = trim((string) $value);
        if (preg_match('/content\s*=\s*["\']([^"\']*)["\']/i', $value, $m)) {
            $value = trim($m[1]);
        }

        return $value !== '' ? $value : null;
    }

    /** "Send test event" on the Facebook tab: one PageView to the Conversions API with the saved settings. */
    public function facebookTest(Request $request, FacebookPixel $pixel)
    {
        $code = $request->validate(['code' => 'nullable|alpha_num|max:30'])['code'] ?? null;
        $code = $code ?: setting('facebook_test_event_code');
        if (! $code) {
            return response()->json(['ok' => false, 'message' => 'Enter a test event code (from Events Manager → Test events) first, so this test is not counted as a real visit.'], 422);
        }

        $result = $pixel->send([[
            'event_name' => 'PageView',
            'event_time' => time(),
            'event_id' => 'test.'.Str::random(12),
            'event_source_url' => url('/'),
            'action_source' => 'website',
            'user_data' => $pixel->userData(),
        ]], $code);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }
}
