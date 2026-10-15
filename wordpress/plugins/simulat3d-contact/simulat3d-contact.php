<?php
/**
 * Plugin Name: Simulat3D — formularz kontaktowy
 * Description: Formularz bez zapisu wiadomości do bazy, z walidacją i istniejącą integracją Cloudflare/Resend.
 * Version: 1.0.0
 * Requires PHP: 8.2
 */
defined('ABSPATH') || exit;

function s3d_contact_live(): bool { return defined('SIMULAT3D_CONTACT_LIVE') && SIMULAT3D_CONTACT_LIVE === true; }
add_shortcode('simulat3d_contact', function () {
    wp_enqueue_script('s3d-contact', plugins_url('contact.js', __FILE__), [], '1.0.0', true);
    ob_start(); ?>
    <form class="s3d-contact-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
      <input type="hidden" name="action" value="simulat3d_contact">
      <?php wp_nonce_field('simulat3d_contact', 's3d_nonce'); ?>
      <label>Imię <input name="name" type="text" maxlength="120" autocomplete="given-name" required></label>
      <label>E-mail <input name="email" type="email" maxlength="254" autocomplete="email" required></label>
      <label>Temat <span>(opcjonalnie)</span><input name="topic" type="text" maxlength="200"></label>
      <label>Wiadomość <textarea name="message" maxlength="5000" rows="5" required></textarea></label>
      <div class="s3d-trap" aria-hidden="true"><label>Pozostaw puste <input name="company_url" type="text" tabindex="-1" autocomplete="off"></label></div>
      <p class="s3d-form-note">Dane wykorzystujemy do odpowiedzi na Twoje zapytanie. <a href="<?php echo esc_url(home_url('/prywatnosc/')); ?>">Prywatność i przechowywanie danych</a>.</p>
      <?php if (!s3d_contact_live()): ?><p class="s3d-form-note">Podgląd testowy — wysyłanie wiadomości jest wyłączone.</p><?php endif; ?>
      <button type="submit">Wyślij wiadomość →</button>
      <p class="s3d-form-status" role="status" aria-live="polite"></p>
    </form>
    <?php return ob_get_clean();
});

function s3d_contact_response(int $code, string $message): never {
    nocache_headers();
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        wp_send_json(['ok' => $code === 200, 'message' => $message], $code);
    }
    wp_die(esc_html($message) . '<p><a href="' . esc_url(home_url('/#kontakt')) . '">Wróć do strony</a></p>', 'Kontakt — Simulat3D', ['response' => $code]);
}

function s3d_contact_submit(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') s3d_contact_response(405, 'Użyj formularza kontaktowego.');
    if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 32768) s3d_contact_response(413, 'Wiadomość jest zbyt długa.');
    foreach (['name','email','topic','message','company_url','s3d_nonce'] as $field) {
        if (isset($_POST[$field]) && !is_string($_POST[$field])) s3d_contact_response(400, 'Nieprawidłowe dane formularza.');
    }
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['s3d_nonce'] ?? '')), 'simulat3d_contact')) {
        s3d_contact_response(403, 'Formularz wygasł. Odśwież stronę i spróbuj ponownie.');
    }
    if (!empty($_POST['company_url'])) s3d_contact_response(400, 'Nie udało się wysłać formularza.');
    $data = [
        'name' => sanitize_text_field(wp_unslash($_POST['name'] ?? '')),
        'email' => trim(wp_unslash($_POST['email'] ?? '')),
        'topic' => sanitize_text_field(wp_unslash($_POST['topic'] ?? '')),
        'message' => sanitize_textarea_field(wp_unslash($_POST['message'] ?? '')),
    ];
    if ($data['name'] === '' || $data['message'] === '' || !is_email($data['email'])) {
        s3d_contact_response(422, 'Podaj imię, poprawny adres e-mail i treść wiadomości.');
    }
    foreach (['name'=>120,'email'=>254,'topic'=>200,'message'=>5000] as $key=>$limit) {
        $length = function_exists('mb_strlen') ? mb_strlen($data[$key], 'UTF-8') : preg_match_all('/./us', $data[$key]);
        if ($length > $limit) s3d_contact_response(422, 'Jedno z pól jest zbyt długie.');
    }
    if (!s3d_contact_live()) s3d_contact_response(503, 'Podgląd testowy: dane są poprawne, ale wiadomość nie została wysłana. Napisz bezpośrednio na office@simulat3d.eu.');

    // Short-lived pseudonymous anti-spam counter; no message, name or email stored in WordPress.
    $key = 's3d_rate_' . hash_hmac('sha256', $_SERVER['REMOTE_ADDR'] ?? '', wp_salt('auth'));
    $rate = get_transient($key);
    if (!is_array($rate)) $rate = ['count'=>0, 'until'=>time()+600];
    if ($rate['count'] >= 5) s3d_contact_response(429, 'Zbyt wiele prób. Spróbuj ponownie za 10 minut lub napisz e-mail.');
    $rate['count']++;
    set_transient($key, $rate, max(1, $rate['until'] - time()));
    $response = wp_remote_post('https://simulat3d-contact.pawfie68.workers.dev', [
        'timeout'=>20, 'redirection'=>0, 'sslverify'=>true,
        'headers'=>['Content-Type'=>'application/json'],
        'body'=>wp_json_encode($data),
    ]);
    if (is_wp_error($response)) s3d_contact_response(502, 'Nie udało się potwierdzić wysyłki. Spróbuj później lub napisz na office@simulat3d.eu.');
    $result=json_decode(wp_remote_retrieve_body($response), true);
    if (wp_remote_retrieve_response_code($response) !== 200 || !is_array($result) || ($result['ok'] ?? false) !== true) {
        s3d_contact_response(502, 'Nie udało się wysłać wiadomości. Spróbuj później lub napisz na office@simulat3d.eu.');
    }
    s3d_contact_response(200, 'Wiadomość została przyjęta do wysyłki. Dziękujemy za kontakt!');
}
add_action('admin_post_nopriv_simulat3d_contact','s3d_contact_submit');
add_action('admin_post_simulat3d_contact','s3d_contact_submit');
