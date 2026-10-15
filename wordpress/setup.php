<?php
// Run using WP-CLI: wp eval-file /path/to/this/setup.php
if (!defined('ABSPATH')) { fwrite(STDERR, "Load WordPress before running setup.\n"); exit(1); }
require_once ABSPATH . 'wp-admin/includes/plugin.php';
if (get_option('s3d_setup_complete')) { echo "Already set up. No existing content changed.\n"; return; }
switch_theme('simulat3d');
$result=activate_plugin('simulat3d-contact/simulat3d-contact.php');
if(is_wp_error($result)) throw new RuntimeException($result->get_error_message());
ob_start(); include get_theme_file_path('patterns/home.php'); $content=ob_get_clean();
$theme_url=home_url('/wp-content/themes/simulat3d');
$content=str_replace(get_theme_file_uri(),$theme_url,$content);
$home=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'Strona główna','post_content'=>$content],true);
if(is_wp_error($home)) throw new RuntimeException($home->get_error_message());
$privacy=wp_insert_post(['post_type'=>'page','post_status'=>'publish','post_title'=>'Prywatność','post_name'=>'prywatnosc','post_content'=>file_get_contents(__DIR__.'/privacy.html')],true);
if(is_wp_error($privacy)) throw new RuntimeException($privacy->get_error_message());
update_option('show_on_front','page'); update_option('page_on_front',$home); update_option('wp_page_for_privacy_policy',$privacy);
update_option('blogname','Simulat3D'); update_option('blogdescription','Symulatory lotnicze, elektronika i druk 3D');
update_option('blog_public',0); update_option('default_comment_status','closed'); update_option('default_ping_status','closed'); update_option('show_avatars',0);
update_option('timezone_string','Europe/Warsaw');
// Site-editor template parts support staging subdirectories without links escaping to production.
foreach(['header','footer'] as $part){
    $text=file_get_contents(get_theme_file_path('parts/'.$part.'.html'));
    $text=str_replace(['"/#','href="/prywatnosc/"'], ['"'.trailingslashit(home_url()).'#','href="'.esc_url(home_url('/prywatnosc/')).'"'],$text);
    if($part==='header') $text=str_replace('<!-- wp:site-title /-->', '<!-- wp:html --><a class="s3d-logo" href="'.esc_url(home_url('/')).'"><img src="'.esc_url($theme_url.'/assets/logo-full.svg').'" alt="Simulat3D" width="190" height="50"></a><!-- /wp:html -->',$text);
    wp_insert_post(['post_type'=>'wp_template_part','post_status'=>'publish','post_name'=>$part,'post_title'=>ucfirst($part),'post_content'=>$text,'tax_input'=>['wp_theme'=>['simulat3d']]]);
}
update_option('s3d_setup_complete',true);
echo "Simulat3D pages, theme and contact plugin configured. Search indexing remains disabled.\n";
