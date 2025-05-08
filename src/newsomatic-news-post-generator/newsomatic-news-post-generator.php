<?php
/** 
Plugin Name: Newsomatic Automatic Post Generator
Plugin URI: //1.envato.market/coderevolution
Description: This plugin will generate content for you, even in your sleep using NewsomaticAPI
Author: CodeRevolution
Version: 3.3.3
Author URI: //coderevolution.ro
License: Commercial. For personal use only. Not to give away or resell.
Text Domain: newsomatic-news-post-generator
*/
/*  
Copyright 2016 - 2024 CodeRevolution
*/
const NEWSOMATIC_MAJOR_VERSION = '3.3.3';
defined('ABSPATH') or die();
require_once (dirname(__FILE__) . "/res/other/plugin-dash.php"); 
function newsomatic_get_version() {
    $plugin_data = get_file_data( __FILE__  , array('Version' => 'Version'), false);
    return $plugin_data['Version'];
}
function newsomatic_isSecure() {
  return
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || $_SERVER['SERVER_PORT'] == 443;
}
function newsomatic_redirect($url, $statusCode = 301)
{
   if(!function_exists('wp_redirect'))
   {
       include_once( ABSPATH . 'wp-includes/pluggable.php' );
   }
   wp_redirect($url, $statusCode);
   die();
}
function newsomatic_truncate_title($title, $max_length = 255) 
{
    if (strlen($title) > $max_length) {
        $title = substr($title, 0, $max_length);
    }
	if (function_exists ( 'iconv' )) 
    {
        return iconv ( 'utf-8', 'utf-8//IGNORE', $title );
    } 
    else 
    {
        return $title;
    }
    return $title;
}
function newsomatic_load_textdomain() {
    load_plugin_textdomain( 'newsomatic-news-post-generator', false, basename( dirname( __FILE__ ) ) . '/languages' ); 
}
add_action( 'init', 'newsomatic_load_textdomain' );

function newsomatic_wpse_allowedtags() {
    return '<script>,<style>,<br>,<em>,<i>,<ul>,<ol>,<li>,<a>,<p>,<img>,<video>,<audio>'; 
}
function newsomatic_custom_wp_trim_excerpt($raw_excerpt, $excerpt_word_count, $more_url, $read_more) {
    $wpse_excerpt = $raw_excerpt;
    $wpse_excerpt = strip_shortcodes( $wpse_excerpt );
    $wpse_excerpt = str_replace(']]>', ']]&gt;', $wpse_excerpt);
    $wpse_excerpt = strip_tags($wpse_excerpt, newsomatic_wpse_allowedtags());
        $tokens = array();
        $excerptOutput = '';
        $count = 0;
        preg_match_all('/(<[^>]+>|[^<>\s]+)\s*/u', $wpse_excerpt, $tokens);
        foreach ($tokens[0] as $token) { 

            if ($count >= $excerpt_word_count && preg_match('/[\,\;\?\.\!]\s*$/uS', $token)) { 
                $excerptOutput .= trim($token);
                break;
            }
            $count++;
            $excerptOutput .= $token;
        }
    $wpse_excerpt = trim(force_balance_tags($excerptOutput));
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    if($read_more == ' ' || $more_url == ''){
        $excerpt_end = '';
    }
    else{
        if($read_more == '')
        {
            if (isset($newsomatic_Main_Settings['read_more_text']) && $newsomatic_Main_Settings['read_more_text'] != '') {
                $read_more = $newsomatic_Main_Settings['read_more_text'];
            }
            else
            {
                $read_more = esc_html__('Read More', 'newsomatic-news-post-generator');
            }
        }
        $excerpt_end = ' <a href="' . esc_url_raw($more_url) . '" target="_blank">&nbsp;&raquo;&nbsp;' . esc_html($read_more) . '</a>'; 
    }
    $wpse_excerpt .= $excerpt_end;
    return $wpse_excerpt;
}
function newsomatic_assign_var(&$target, $var, $root = false) {
	static $cnt = 0;
    $key = key($var);
    if(is_array($var[$key])) 
        newsomatic_assign_var($target[$key], $var[$key], false);
    else {
        if($key==0)
		{
			if($cnt == 0 && $root == true)
			{
				$target['_newsomaticr_nonce'] = $var[$key];
				$cnt++;
			}
			elseif($cnt == 1 && $root == true)
			{
				$target['_wp_http_referer'] = $var[$key];
				$cnt++;
			}
			else
			{
				$target[] = $var[$key];
			}
		}
        else
		{
            $target[$key] = $var[$key];
		}
    }   
}


function newsomatic_preg_grep_keys( $pattern, $input, $flags = 0 )
{
    if(!is_array($input))
    {
        return array();
    }
    $keys = preg_grep( $pattern, array_keys( $input ), $flags );
    $vals = array();
    foreach ( $keys as $key )
    {
        $vals[$key] = $input[$key];
    }
    return $vals;
}

function newsomatic_is_demo_server()
{
    $demo_server_ip = '143.198.112.144';
    $demo_server_url = '143.198.112.144';
    $current_server_ip = $_SERVER['SERVER_ADDR'];
    $current_server_name = $_SERVER['SERVER_NAME'];
    if ($current_server_ip === $demo_server_ip && strpos($current_server_name, $demo_server_url) !== false) 
    {
        return true;
    }
    return false;
}
function newsomatic_is_activated($plugin_slug, &$uoptions)
{
    if(newsomatic_is_demo_server())
    {
        return 2;
    }
    $blacklisted_purchase_codes = array();
    if (is_multisite()) 
    {
        $main_site_id = get_network()->site_id;
        switch_to_blog($main_site_id);
        $uoptions = get_option($plugin_slug . '_registration', array());
        restore_current_blog();
    } 
    else 
    {
        $uoptions = get_option($plugin_slug . '_registration', array());
    }
    if(isset($uoptions['item_id']) && isset($uoptions['item_name']) && isset($uoptions['created_at']) && isset($uoptions['buyer']) && isset($uoptions['licence']) && isset($uoptions['supported_until']))
    {
        if($uoptions['item_id'] == '20039739' || $uoptions['item_id'] == '13371337' || $uoptions['item_id'] == '19200046')
        {
            if(strstr($uoptions['item_name'], 'Mega') !== false || strstr($uoptions['item_name'], 'Item') !== false || strstr($uoptions['item_name'], 'Newsomatic') !== false)
            {
                if($uoptions['created_at'] === '10.10.2020' || $uoptions['supported_until'] === '10.10.2030')
                {
                    return -1;
                }
                $supported_until = strtotime($uoptions['supported_until']);
                $created_at = strtotime($uoptions['created_at']);
                if(($created_at !== false || $uoptions['created_at'] === 'NA') && $supported_until !== false)
                {
                    if($created_at !== false)
                    {
                        $mintime = strtotime('19.12.2016');
                        if($created_at < $mintime)
                        {
                            return -1;
                        }
                    }
                    $yourtime = strtotime('+1 year +1 day');
                    if ($supported_until > $yourtime) 
                    {
                        return -1;
                    }
                    $username_pattern = '/^[a-zA-Z0-9\s_-]+$/';
                    if (!preg_match($username_pattern, $uoptions['buyer'])) 
                    {
                        return -1;
                    }
                    if($uoptions['licence'] === 'Regular License' || $uoptions['licence'] === 'Extended License' || $uoptions['licence'] === 'Custom License')
                    {
                        if(in_array($uoptions['code'], $blacklisted_purchase_codes))
                        {
                            return -1;
                        }
                        $pattern = '/^[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}$/';
                        if(preg_match($pattern, $uoptions['code']))
                        {
                            return true;
                        }
                        else
                        {
                            return -1;
                        }
                    }
                    else
                    {
                        return -1;
                    }
                }
                else
                {
                    return -1;
                }
            }
            else
            {
                return -1;
            }
        }
        else
        {
            return -1;
        }
    }
    return false;
}
$language_names = array(
    esc_html__("Disabled", 'newsomatic-news-post-generator'),
    esc_html__("Afrikaans (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Albanian (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Arabic (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Amharic (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Armenian (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Belarusian (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Bulgarian (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Catalan (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Chinese Simplified (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Croatian (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Czech (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Danish (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Dutch (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("English (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Estonian (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Filipino (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Finnish (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("French (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Galician (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("German (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Greek (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Hebrew (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Hindi (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Hungarian (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Icelandic (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Indonesian (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Irish (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Italian (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Japanese (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Korean (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Latvian (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Lithuanian (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Norwegian (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Macedonian (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Malay (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Maltese (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Persian (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Polish (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Portuguese (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Romanian (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Russian (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Serbian (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Slovak (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Slovenian (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Spanish (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Swahili (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Swedish (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Thai (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Turkish (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Ukrainian (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Vietnamese (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Welsh (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Yiddish (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Tamil (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Azerbaijani (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Kannada (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Basque (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Bengali (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Latin (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Chinese Traditional (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Esperanto (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Georgian (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Telugu (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Gujarati (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Haitian Creole (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Urdu (Google Translate)", 'newsomatic-news-post-generator'),
    
    esc_html__("Burmese (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Bosnian (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Cebuano (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Chichewa (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Corsican (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Frisian (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Scottish Gaelic (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Hausa (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Hawaian (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Hmong (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Igbo (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Javanese (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Kazakh (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Khmer (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Kurdish (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Kyrgyz (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Lao (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Luxembourgish (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Malagasy (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Malayalam (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Maori (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Marathi (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Mongolian (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Nepali (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Pashto (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Punjabi (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Samoan (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Sesotho (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Shona (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Sindhi (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Sinhala (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Somali (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Sundanese (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Swahili (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Tajik (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Uzbek (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Xhosa (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Yoruba (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Zulu (Google Translate)", 'newsomatic-news-post-generator'),

    esc_html__("Assammese (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Aymara (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Bambara (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Bhojpuri (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Dhivehi (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Dogri (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Ewe (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Guarani (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Ilocano (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Kinyarwanda (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Konkani (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Krio (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Kurdish - Sorani (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Lingala (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Luganda (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Maithili (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Meiteilon (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Mizo (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Odia (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Oromo (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Quechua (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Sanskrit (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Sepedi (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Tatar (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Tigrinya (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Tsonga (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Turkmen (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Twi (Google Translate)", 'newsomatic-news-post-generator'),
    esc_html__("Uyghur (Google Translate)", 'newsomatic-news-post-generator')
);
$language_codes = array(
    "disabled",
    "af",
    "sq",
    "ar",
    "am",
    "hy",
    "be",
    "bg",
    "ca",
    "zh-CN",
    "hr",
    "cs",
    "da",
    "nl",
    "en",
    "et",
    "tl",
    "fi",
    "fr",
    "gl",
    "de",
    "el",
    "iw",
    "hi",
    "hu",
    "is",
    "id",
    "ga",
    "it",
    "ja",
    "ko",
    "lv",
    "lt",
    "no",
    "mk",
    "ms",
    "mt",
    "fa",
    "pl",
    "pt",
    "ro",
    "ru",
    "sr",
    "sk",
    "sl",
    "es",
    "sw",
    "sv",   
    "th",
    "tr",
    "uk",
    "vi",
    "cy",
    "yi",
    "ta",
    "az",
    "kn",
    "eu",
    "bn",
    "la",
    "zh-TW",
    "eo",
    "ka",
    "te",
    "gu",
    "ht",
    "ur",
    
    "my",
    "bs",
    "ceb",
    "ny",
    "co",
    "fy",
    "gd",
    "ha",
    "haw",
    "hmn",
    "ig",
    "jw",
    "kk",
    "km",
    "ku",
    "ky",
    "lo",
    "lb",
    "mg",
    "ml",
    "mi",
    "mr",
    "mn",
    "ne",
    "ps",
    "pa",
    "sm",
    "st",
    "sn",
    "sd",
    "si",
    "so",
    "su",
    "sw",
    "tg",
    "uz",
    "xh",
    "yo",
    "zu",

    "as",
    "ay",
    "bm",
    "bho",
    "dv",
    "doi",
    "ee",
    "gn",
    "ilo",
    "rw",
    "gom",
    "kri",
    "ckb",
    "ln",
    "lg",
    "mai",
    "mni-Mtei",
    "lus",
    "or",
    "om",
    "qu",
    "sa",
    "nso",
    "tt",
    "ti",
    "ts",
    "tk",
    "ak",
    "ug"
);
$language_names_deepl = array(
    "English (DeepL)",
    "German (DeepL)",
    "French (DeepL)",
    "Spanish (DeepL)",
    "Italian (DeepL)",
    "Dutch (DeepL)",
    "Polish (DeepL)",
    "Russian (DeepL)",
    "Portuguese (DeepL)",
    "Chinese (DeepL)",
    "Japanese (DeepL)",
    "Bulgarian (DeepL)",
    "Czech (DeepL)",
    "Danish (DeepL)",
    "Greek (DeepL)",
    "Estonian (DeepL)",
    "Finnish (DeepL)",
    "Hungarian (DeepL)",
    "Lithuanian (DeepL)",
    "Latvian (DeepL)",
    "Romanian (DeepL)",
    "Slovak (DeepL)",
    "Slovenian (DeepL)",
    "Swedish (DeepL)",
    "Indonesian (DeepL)",
    "Turkish (DeepL)",
    "Korean (DeepL)",
    "Norwegian (DeepL)",
  "Ukrainian (DeepL)",
  "Arabic (DeepL)"
  );
    $language_codes_deepl = array(
        "EN-",
        "DE-",
        "FR-",
        "ES-",
        "IT-",
        "NL-",
        "PL-",
        "RU-",
        "PT-",
        "ZH-",
        "JA-",
        "BG-",
        "CS-",
        "DA-",
        "EL-",
        "ET-",
        "FI-",
        "HU-",
        "LT-",
        "LV-",
        "RO-",
        "SK-",
        "SL-",
        "SV-",
        "ID-",
        "TR-",
        "KO-",
        "NB-",
      "UK-",
      "AR-"
    );
 $language_names_bing = array(
    "English (Microsoft Translator)",
    "Arabic (Microsoft Translator)",
    "Bosnian (Latin) (Microsoft Translator)",
    "Bulgarian (Microsoft Translator)",
    "Catalan (Microsoft Translator)",
    "Chinese Simplified (Microsoft Translator)",
    "Chinese Traditional (Microsoft Translator)",
    "Croatian (Microsoft Translator)",
    "Czech (Microsoft Translator)",
    "Danish (Microsoft Translator)",
    "Dutch (Microsoft Translator)",
    "Estonian (Microsoft Translator)",
    "Finnish (Microsoft Translator)",
    "French (Microsoft Translator)",
    "German (Microsoft Translator)",
    "Greek (Microsoft Translator)",
    "Haitian Creole (Microsoft Translator)",
    "Hebrew (Microsoft Translator)",
    "Hindi (Microsoft Translator)",
    "Hmong Daw (Microsoft Translator)",
    "Hungarian (Microsoft Translator)",
    "Indonesian (Microsoft Translator)",
    "Italian (Microsoft Translator)",
    "Japanese (Microsoft Translator)",
    "Kiswahili (Microsoft Translator)",
    "Klingon (Microsoft Translator)",
    "Klingon (pIqaD) (Microsoft Translator)",
    "Korean (Microsoft Translator)",
    "Latvian (Microsoft Translator)",
    "Lithuanian (Microsoft Translator)",
    "Malay (Microsoft Translator)",
    "Maltese (Microsoft Translator)",
    "Norwegian (Microsoft Translator)",
    "Persian (Microsoft Translator)",
    "Polish (Microsoft Translator)",
    "Portuguese (Microsoft Translator)",
    "Queretaro Otomi (Microsoft Translator)",
    "Romanian (Microsoft Translator)",
    "Russian (Microsoft Translator)",
    "Serbian (Cyrillic) (Microsoft Translator)",
    "Serbian (Latin) (Microsoft Translator)",
    "Slovak (Microsoft Translator)",
    "Slovenian (Microsoft Translator)",
    "Spanish (Microsoft Translator)",
    "Swedish (Microsoft Translator)",
    "Thai (Microsoft Translator)",
    "Turkish (Microsoft Translator)",
    "Ukrainian (Microsoft Translator)",
    "Urdu (Microsoft Translator)",
    "Vietnamese (Microsoft Translator)",
    "Welsh (Microsoft Translator)",
    "Yucatec Maya (Microsoft Translator)",
  
    "Afrikaans (Microsoft Translator)",
    "Albanian (Microsoft Translator)",
    "Amharic (Microsoft Translator)",
    "Armenian (Microsoft Translator)",
    "Assamese (Microsoft Translator)",
    "Azerbaijani (Microsoft Translator)",
    "Bangla (Microsoft Translator)", 
    "Bashkir (Microsoft Translator)",
    "Basque (Microsoft Translator)",
    "Cantonese (Microsoft Translator)",
    "Chinese (Literary) (Microsoft Translator)",
    "Dari (Microsoft Translator)",
    "Divehi (Microsoft Translator)",
    "Faroese (Microsoft Translator)",
    "Fijian (Microsoft Translator)",
    "Filipino (Microsoft Translator)",
    "French (Canada) (Microsoft Translator)",
    "Galician (Microsoft Translator)",
    "Georgian (Microsoft Translator)",
    "Gujarati (Microsoft Translator)",
    "Icelandic (Microsoft Translator)",
    "Inuinnaqtun (Microsoft Translator)",
    "Inuktitut (Microsoft Translator)",
    "Inuktitut (Latin) (Microsoft Translator)",
    "Irish (Microsoft Translator)",
    "Kannada (Microsoft Translator)",
    "Kazakh (Microsoft Translator)",
    "Khmer (Microsoft Translator)",
    "Kurdish (Central) (Microsoft Translator)",
    "Kurdish (Northern) (Microsoft Translator)",
    "Kyrgyz (Cyrillic) (Microsoft Translator)",
    "Lao (Microsoft Translator)",
    "Macedonian (Microsoft Translator)",
    "Malagasy (Microsoft Translator)",
    "Malayalam (Microsoft Translator)",
    "Maori (Microsoft Translator)",
    "Marathi (Microsoft Translator)",
    "Mongolian (Cyrillic) (Microsoft Translator)",
    "Mongolian (Traditional) (Microsoft Translator)",
    "Myanmar (Microsoft Translator)",
    "Nepali (Microsoft Translator)",
    "Odia (Microsoft Translator)",
    "Pashto (Microsoft Translator)",
    "Portuguese (Portugal) (Microsoft Translator)",
    "Punjabi (Microsoft Translator)",
    "Samoan (Microsoft Translator)",
    "Somali (Arabic) (Microsoft Translator)",
    "Swahili (Latin) (Microsoft Translator)",
    "Tahitian (Microsoft Translator)",
    "Tamil (Microsoft Translator)",
    "Tatar (Latin) (Microsoft Translator)",
    "Telugu (Microsoft Translator)",
    "Tibetan (Microsoft Translator)",
    "Tigrinya (Microsoft Translator)",
    "Tongan (Microsoft Translator)",
    "Turkmen (Latin) (Microsoft Translator)",
    "Upper Sorbian (Microsoft Translator)",
    "Uyghur (Arabic) (Microsoft Translator)",
    "Uzbek (Microsoft Translator)",
    "Zulu (Microsoft Translator)"
    );

    $language_codes_bing = array(
        "en!",
        "ar!",
        "bs-Latn!",
        "bg!",
        "ca!",
        "zh-CHS!",
        "zh-CHT!",
        "hr!",
        "cs!",
        "da!",
        "nl!",
        "et!",
        "fi!",
        "fr!",
        "de!",
        "el!",
        "ht!",
        "he!",
        "hi!",
        "mww!",
        "hu!",
        "id!",
        "it!",
        "ja!",
        "sw!",
        "tlh!",
        "tlh-Qaak!",
        "ko!",
        "lv!",
        "lt!",
        "ms!",
        "mt!",
        "nor!",
        "fa!",
        "pl!",
        "pt!",
        "otq!",
        "ro!",
        "ru!",
        "sr-Cyrl!",
        "sr-Latn!",
        "sk!",
        "sl!",
        "es!",
        "sv!",
        "th!",
        "tr!",
        "uk!",
        "ur!",
        "vi!",
        "cy!",
        "yua!",
        
        "af!",
        "sq!",
        "am!",
        "hy!",
        "as!",
        "az!",
        "bn!",
        "ba!",
        "eu!",
        "yue!",
        "lzh!",
        "prs!",
        "dv!",
        "fo!",
        "fj!",
        "fil!",
        "fr-ca!",
        "gl!",
        "ka!",
        "gu!",
        "is!",
        "ikt!",
        "iu!",
        "iu-Latn!",
        "ga!",
        "kn!",
        "kk!",
        "km!",
        "ku!",
        "kmr!",
        "ky!",
        "lo!",
        "mk!",
        "mg!",
        "ml!",
        "mi!",
        "mr!",
        "mn-Cyrl!",
        "mn-Mong!",
        "my!",
        "ne!",
        "or!",
        "ps!",
        "pt-pt!",
        "pa!",
        "sm!",
        "so!",
        "sw!",
        "ty!",
        "ta!",
        "tt!",
        "te!",
        "bo!",
        "ti!",
        "to!",
        "tk!",
        "hsb!",
        "ug!",
        "uz!",
        "zu!"
    );
function newsomatic_replace_attachment_url($att_url, $att_id) {
    {
         $post_id = get_the_ID();
         wp_suspend_cache_addition(true);
         $metas = get_post_custom($post_id);
         wp_suspend_cache_addition(false);
         $rez_meta = newsomatic_preg_grep_keys('#.+?_featured_img#i', $metas);
         if(count($rez_meta) > 0)
         {
             foreach($rez_meta as $rm)
             {
                 if(isset($rm[0]) && $rm[0] != '' && filter_var($rm[0], FILTER_VALIDATE_URL))
                 {
                    return $rm[0];
                 }
             }
         }
    }
    return $att_url;
}


function newsomatic_replace_attachment_image_src($image, $att_id, $size) {
    {
        $post_id = get_the_ID();
        wp_suspend_cache_addition(true);
        $metas = get_post_custom($post_id);
        wp_suspend_cache_addition(false);
        $rez_meta = newsomatic_preg_grep_keys('#.+?_featured_img#i', $metas);
        if(count($rez_meta) > 0)
        {
            foreach($rez_meta as $rm)
            {
                if(isset($rm[0]) && $rm[0] != '' && filter_var($rm[0], FILTER_VALIDATE_URL))
                {
                    return array($rm[0], 0, 0, false);
                }
            }
        }
     }
     return $image;
}

function newsomatic_thumbnail_external_replace( $html, $post_id, $thumb_id ) {
    
    wp_suspend_cache_addition(true);
    $metas = get_post_custom($post_id);
    wp_suspend_cache_addition(false);
    $rez_meta = newsomatic_preg_grep_keys('#.+?_featured_img#i', $metas);
    if(count($rez_meta) > 0)
    {
        foreach($rez_meta as $rm)
        {
            if(isset($rm[0]) && $rm[0] != '' && filter_var($rm[0], FILTER_VALIDATE_URL))
            {
                $alt = get_post_field( 'post_title', $post_id ) . ' ' .  esc_html__( 'thumbnail', 'newsomatic-news-post-generator' );
                $attr = array( 'alt' => $alt );
                $attx = get_post($thumb_id);
                $attr = apply_filters( 'wp_get_attachment_image_attributes', $attr, $attx , 'thumbnail');
                $attr = array_map( 'esc_attr', $attr );
                $html = sprintf( '<img src="%s"', esc_url_raw($rm[0]) );
                foreach ( $attr as $name => $value ) {
                    $html .= " " . esc_html($name) . "=" . '"' . esc_attr($value) . '"';
                }
                $html .= ' />';
                return $html;
            }
        }
    }
    return $html;
}
$plugin = plugin_basename(__FILE__);
if(is_admin())
{
    if($_SERVER["REQUEST_METHOD"]==="POST" && !empty($_POST["coderevolution_max_input_var_data"])) {
        $vars = explode("&", $_POST["coderevolution_max_input_var_data"]);
        $coderevolution_max_input_var_data = array();
        foreach($vars as $var) {
            parse_str($var, $variable);
            newsomatic_assign_var($_POST, $variable, true);
        }
        unset($_POST["coderevolution_max_input_var_data"]);
    }
    $plugin_slug = explode('/', $plugin);
    $plugin_slug = $plugin_slug[0];
    $uoptions = array();
    $is_activated = newsomatic_is_activated($plugin_slug, $uoptions);
    if($is_activated === true || $is_activated === 2)
    {
        require "update-checker/plugin-update-checker.php";
        $fwdu3dcarPUC = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker("https://wpinitiate.com/auto-update/?action=get_metadata&slug=newsomatic-news-post-generator", __FILE__, "newsomatic-news-post-generator");
    }
    else
    {
        add_action('admin_notices', 'newsomatic_license_not_activated_notice');
        add_action("after_plugin_row_{$plugin}", function( $plugin_file, $plugin_data, $status ) {
            $plugin_url = 'https://codecanyon.net/item/newsomatic-automatic-news-post-generator-plugin-for-wordpress/20039739';
            echo '<tr class="active"><td>&nbsp;</td><td colspan="2"><p class="cr_auto_update">';
          echo sprintf( wp_kses( __( 'The plugin is not registered. Automatic updating is disabled. Please purchase a license for it from <a href="%s" target="_blank">here</a> and register  the plugin from the \'Main Settings\' menu using your purchase code. <a href="%s" target="_blank">How I find my purchase code?', 'newsomatic-news-post-generator'), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), esc_url_raw( 'https://1.envato.market/c/1264868/275988/4415?u=' . urlencode($plugin_url)), esc_url_raw('//www.youtube.com/watch?v=NElJ5t_Wd48') );     
          echo '</a></p> </td></tr>';
        }, 10, 3 );
        add_action('admin_enqueue_scripts', 'newsomatic_admin_enqueue_all');
        add_filter("plugin_action_links_$plugin", 'newsomatic_add_activation_link');
    }
    add_action('admin_menu', 'newsomatic_register_my_custom_menu_page');
    add_action('network_admin_menu', 'newsomatic_register_my_custom_menu_page');
    add_filter("plugin_action_links_$plugin", 'newsomatic_add_support_link');
    add_filter("plugin_action_links_$plugin", 'newsomatic_add_settings_link');
    add_filter("plugin_action_links_$plugin", 'newsomatic_add_rating_link');
    add_action('add_meta_boxes', 'newsomatic_add_meta_box');
    add_action('admin_init', 'newsomatic_register_mysettings');
    require(dirname(__FILE__) . "/res/newsomatic-rules-list.php");
    require(dirname(__FILE__) . "/res/newsomatic-all-list.php");
    require(dirname(__FILE__) . "/res/newsomatic-logs.php");
    require(dirname(__FILE__) . "/res/newsomatic-helper.php");
    require(dirname(__FILE__) . "/res/newsomatic-offer.php");
}
function newsomatic_admin_enqueue_all()
{
    $reg_css_code = '.cr_auto_update{background-color:#fff8e5;margin:5px 20px 15px 20px;border-left:4px solid #fff;padding:12px 12px 12px 12px !important;border-left-color:#ffb900;}';
    wp_register_style( 'newsomatic-plugin-reg-style', false );
    wp_enqueue_style( 'newsomatic-plugin-reg-style' );
    wp_add_inline_style( 'newsomatic-plugin-reg-style', $reg_css_code );
}

function newsomatic_add_activation_link($links)
{
    $settings_link = '<a href="admin.php?page=newsomatic_admin_settings">' . esc_html__('Activate Plugin License', 'newsomatic-news-post-generator') . '</a>';
    array_push($links, $settings_link);
    return $links;
}
use \Eventviva\ImageResize;

function newsomatic_register_my_custom_menu_page()
{
    $plugin = plugin_basename(__FILE__);
    $plugin_slug = explode('/', $plugin);
    $plugin_slug = $plugin_slug[0];
    $uoptions = array();
    $is_activated = newsomatic_is_activated($plugin_slug, $uoptions);
    if($is_activated === true || $is_activated === 2)
    {
        require(dirname(__FILE__) . "/res/newsomatic-main.php");
        add_menu_page('Newsomatic Post Generator', 'Newsomatic Post Generator', 'manage_options', 'newsomatic_admin_settings', 'newsomatic_admin_settings', plugins_url('images/icon.png', __FILE__));
        $main = add_submenu_page('newsomatic_admin_settings', esc_html__("Main Settings", 'newsomatic-news-post-generator'), esc_html__("Main Settings", 'newsomatic-news-post-generator'), 'manage_options', 'newsomatic_admin_settings');
        add_action( 'load-' . $main, 'newsomatic_load_all_admin_js' );
        add_action( 'load-' . $main, 'newsomatic_load_main_admin_js' );
        $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
        if (isset($newsomatic_Main_Settings['newsomatic_enabled']) && $newsomatic_Main_Settings['newsomatic_enabled'] == 'on') {
            $latest = add_submenu_page('newsomatic_admin_settings', esc_html__('Top News To Posts', 'newsomatic-news-post-generator'), esc_html__('Top News To Posts', 'newsomatic-news-post-generator'), 'manage_options', 'newsomatic_items_panel', 'newsomatic_items_panel');
            add_action( 'load-' . $latest, 'newsomatic_load_admin_js' );
            add_action( 'load-' . $latest, 'newsomatic_load_all_admin_js' );
            $custom = add_submenu_page('newsomatic_admin_settings', esc_html__('Custom News To Posts', 'newsomatic-news-post-generator'), esc_html__('Custom News To Posts', 'newsomatic-news-post-generator'), 'manage_options', 'newsomatic_all_panel', 'newsomatic_all_panel');
            add_action( 'load-' . $custom, 'newsomatic_load_admin_js' );
            add_action( 'load-' . $custom, 'newsomatic_load_all_admin_js' );
            $crawl = add_submenu_page('newsomatic_admin_settings', esc_html__('Crawling Helper', 'newsomatic-news-post-generator'), esc_html__('Crawling Helper', 'newsomatic-news-post-generator'), 'manage_options', 'newsomatic_helper', 'newsomatic_helper');
            add_action( 'load-' . $crawl, 'newsomatic_load_all_admin_js' );
            add_action( 'load-' . $crawl, 'newsomatic_load_helper_js' );
            $tips = add_submenu_page('newsomatic_admin_settings', esc_html__('Tips & Tricks', 'newsomatic-news-post-generator'), esc_html__('Tips & Tricks', 'newsomatic-news-post-generator'), 'manage_options', 'newsomatic_recommendations', 'newsomatic_recommendations');
            add_action( 'load-' . $tips, 'newsomatic_load_all_admin_js' );
            $logs = add_submenu_page('newsomatic_admin_settings', esc_html__("Activity & Logging", 'newsomatic-news-post-generator'), esc_html__("Activity & Logging", 'newsomatic-news-post-generator'), 'manage_options', 'newsomatic_logs', 'newsomatic_logs');
            add_action( 'load-' . $logs, 'newsomatic_load_all_admin_js' );
        }
    }
    else
    {
        require(dirname(__FILE__) . "/res/newsomatic-activation.php");
        add_menu_page('Newsomatic Post Generator', 'Newsomatic Post Generator', 'manage_options', 'newsomatic_admin_settings', 'newsomatic_admin_settings', plugins_url('images/icon.png', __FILE__));
        $main = add_submenu_page('newsomatic_admin_settings', esc_html__("Activation", 'newsomatic-news-post-generator'), esc_html__("Activation", 'newsomatic-news-post-generator'), 'manage_options', 'newsomatic_admin_settings');
        add_action( 'load-' . $main, 'newsomatic_load_all_admin_js' );
        add_action( 'load-' . $main, 'newsomatic_load_main_admin_js' );
        add_action( 'load-' . $main, 'newsomatic_load_activation' );
    }
}
function newsomatic_load_admin_js(){
    add_action('admin_enqueue_scripts', 'newsomatic_enqueue_admin_js');
}

function newsomatic_enqueue_admin_js(){
    wp_register_style('newsomatic-autocss', plugins_url('styles/jquery.gcomplete.default-themes.css', __FILE__), false, NEWSOMATIC_MAJOR_VERSION);
    wp_enqueue_style('newsomatic-autocss');
    wp_enqueue_script('newsomatic-autocomplete', plugins_url('scripts/jquery.gcomplete.0.1.2.js', __FILE__), array('jquery'), NEWSOMATIC_MAJOR_VERSION, true);
    wp_enqueue_script('newsomatic-footer-script', plugins_url('scripts/footer.js', __FILE__), array('jquery'), NEWSOMATIC_MAJOR_VERSION, true);
    $cr_miv = ini_get('max_input_vars');
	if($cr_miv === null || $cr_miv === false || !is_numeric($cr_miv))
	{
        $cr_miv = '9999999';
    }
    $footer_conf_settings = array(
        'max_input_vars' => $cr_miv,
        'plugin_dir_url' => plugin_dir_url(__FILE__),
        'ajaxurl' => admin_url('admin-ajax.php')
    );
    wp_localize_script('newsomatic-footer-script', 'mycustomsettings', $footer_conf_settings);
    wp_register_style('newsomatic-rules-style', plugins_url('styles/newsomatic-rules.css', __FILE__), false, NEWSOMATIC_MAJOR_VERSION);
    wp_enqueue_style('newsomatic-rules-style');
    wp_enqueue_script('newsomatic-select2', plugins_url('scripts/select2.js', __FILE__), array('jquery'), NEWSOMATIC_MAJOR_VERSION, true);
    wp_register_style('newsomatic-select2-style', plugins_url('styles/select2.css', __FILE__), false, NEWSOMATIC_MAJOR_VERSION);
    wp_enqueue_style('newsomatic-select2-style');
}
function newsomatic_load_helper_js(){
    add_action('admin_enqueue_scripts', 'newsomatic_admin_load_helper');
}
function newsomatic_admin_load_helper()
{
    wp_enqueue_script('newsomatic-helper-script', plugins_url('scripts/helper.js', __FILE__), array('jquery'), NEWSOMATIC_MAJOR_VERSION, true);
}
function newsomatic_load_main_admin_js(){
    add_action('admin_enqueue_scripts', 'newsomatic_enqueue_main_admin_js');
}
function newsomatic_load_activation(){
    add_action('admin_enqueue_scripts', 'newsomatic_enqueue_activation');
}

function newsomatic_enqueue_activation(){
    wp_register_style('newsomatic-activation-style', plugins_url('styles/newsomatic-activation.css', __FILE__), false, NEWSOMATIC_MAJOR_VERSION);
    wp_enqueue_style('newsomatic-activation-style');
}
function newsomatic_enqueue_main_admin_js(){
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    wp_enqueue_script('newsomatic-main-script', plugins_url('scripts/main.js', __FILE__), array('jquery'), NEWSOMATIC_MAJOR_VERSION);
    $plugin = plugin_basename(__FILE__);
    $plugin_slug = explode('/', $plugin);
    $plugin_slug = $plugin_slug[0];
    $footer_conf_settings = array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'plugin_slug' => $plugin_slug
    );
    wp_localize_script('newsomatic-main-script', 'mycustomsettings', $footer_conf_settings);
    if(!isset($newsomatic_Main_Settings['best_user']))
    {
        $best_user = '';
    }
    else
    {
        $best_user = $newsomatic_Main_Settings['best_user'];
    }
    if(!isset($newsomatic_Main_Settings['best_password']))
    {
        $best_password = '';
    }
    else
    {
        $best_password = $newsomatic_Main_Settings['best_password'];
    }
    $header_main_settings = array(
        'best_user' => $best_user,
        'best_password' => $best_password
    );
    wp_localize_script('newsomatic-main-script', 'mycustommainsettings', $header_main_settings);
}
function newsomatic_load_all_admin_js(){
    add_action('admin_enqueue_scripts', 'newsomatic_admin_load_files');
}
function newsomatic_add_rating_link($links)
{
    $settings_link = '<a href="//codecanyon.net/downloads" target="_blank" title="Rate">
            <i class="wdi-rate-stars"><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="#ffb900" stroke="#ffb900" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-star"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="#ffb900" stroke="#ffb900" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-star"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="#ffb900" stroke="#ffb900" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-star"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="#ffb900" stroke="#ffb900" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-star"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg><svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="#ffb900" stroke="#ffb900" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-star"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon></svg></i></a>';
    array_push($links, $settings_link);
    return $links;
}

function newsomatic_add_support_link($links)
{
    $settings_link = '<a href="//coderevolution.ro/knowledge-base/" target="_blank">' . esc_html__('Support', 'newsomatic-news-post-generator') . '</a>';
    array_push($links, $settings_link);
    return $links;
}

function newsomatic_add_settings_link($links)
{
    $settings_link = '<a href="admin.php?page=newsomatic_admin_settings">' . esc_html__('Settings', 'newsomatic-news-post-generator') . '</a>';
    array_push($links, $settings_link);
    return $links;
}

function newsomatic_add_meta_box()
{
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    if (isset($newsomatic_Main_Settings['newsomatic_enabled']) && $newsomatic_Main_Settings['newsomatic_enabled'] === 'on') {
        if (isset($newsomatic_Main_Settings['enable_metabox']) && $newsomatic_Main_Settings['enable_metabox'] == 'on') {
            foreach ( get_post_types( '', 'names' ) as $post_type ) {
               add_meta_box('newsomatic_meta_box_function_add', esc_html__('Newsomatic Automatic Post Generator Information', 'newsomatic-news-post-generator'), 'newsomatic_meta_box_function', $post_type, 'advanced', 'default', array('__back_compat_meta_box' => true));
            }
            
        }
    }
}

add_filter('cron_schedules', 'newsomatic_add_cron_schedule');
function newsomatic_add_cron_schedule($schedules)
{
    $schedules['newsomatic_cron'] = array(
        'interval' => 3600,
        'display' => esc_html__('Newsomatic Cron', 'newsomatic-news-post-generator')
    );
    $schedules['minutely'] = array(
        'interval' => 60,
        'display' => esc_html__('Once A Minute', 'newsomatic-news-post-generator')
    );
    $schedules['weekly']        = array(
        'interval' => 604800,
        'display' => esc_html__('Once Weekly', 'newsomatic-news-post-generator')
    );
    $schedules['monthly']       = array(
        'interval' => 2592000,
        'display' => esc_html__('Once Monthly', 'newsomatic-news-post-generator')
    );
    return $schedules;
}
function newsomatic_auto_clear_log()
{
    global $wp_filesystem;
    if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ){
        include_once(ABSPATH . 'wp-admin/includes/file.php');$creds = request_filesystem_credentials( site_url() );
       wp_filesystem($creds);
    }
    if ($wp_filesystem->exists(WP_CONTENT_DIR . '/newsomatic_info.log')) {
        $wp_filesystem->delete(WP_CONTENT_DIR . '/newsomatic_info.log');
    }
}

register_deactivation_hook(__FILE__, 'newsomatic_my_deactivation');
function newsomatic_my_deactivation()
{
    wp_clear_scheduled_hook('newsomaticaction');
    wp_clear_scheduled_hook('newsomaticactionclear');
    $running = array();
    update_option('newsomatic_running_list', $running, false);
}
add_action('newsomaticaction', 'newsomatic_cron');
add_action('newsomaticactionclear', 'newsomatic_auto_clear_log');

function newsomatic_cron_schedule()
{
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    if (isset($newsomatic_Main_Settings['newsomatic_enabled']) && $newsomatic_Main_Settings['newsomatic_enabled'] === 'on') {
        if (!wp_next_scheduled('newsomaticaction')) {
            $unlocker = get_option('newsomatic_minute_running_unlocked', false);
            if($unlocker == '1')
            {
                $rez = wp_schedule_event(time(), 'minutely', 'newsomaticaction');
            }
            else
            {
                $rez = wp_schedule_event(time(), 'hourly', 'newsomaticaction');
            }
            
            if ($rez === FALSE) {
                newsomatic_log_to_file('[Scheduler] Failed to schedule newsomaticaction to newsomatic_cron!');
            }
        }
        
        if (isset($newsomatic_Main_Settings['enable_logging']) && $newsomatic_Main_Settings['enable_logging'] === 'on' && isset($newsomatic_Main_Settings['auto_clear_logs']) && $newsomatic_Main_Settings['auto_clear_logs'] !== 'No') {
            if (!wp_next_scheduled('newsomaticactionclear')) {
                $rez = wp_schedule_event(time(), $newsomatic_Main_Settings['auto_clear_logs'], 'newsomaticactionclear');
                if ($rez === FALSE) {
                    newsomatic_log_to_file('[Scheduler] Failed to schedule newsomaticactionclear to ' . $newsomatic_Main_Settings['auto_clear_logs'] . '!');
                }
                add_option('newsomatic_schedule_time', $newsomatic_Main_Settings['auto_clear_logs'], '', false);
            } else {
                if (!get_option('newsomatic_schedule_time')) {
                    wp_clear_scheduled_hook('newsomaticactionclear');
                    $rez = wp_schedule_event(time(), $newsomatic_Main_Settings['auto_clear_logs'], 'newsomaticactionclear');
                    add_option('newsomatic_schedule_time', $newsomatic_Main_Settings['auto_clear_logs'], '', false);
                    if ($rez === FALSE) {
                        newsomatic_log_to_file('[Scheduler] Failed to schedule newsomaticactionclear to ' . $newsomatic_Main_Settings['auto_clear_logs'] . '!');
                    }
                } else {
                    $the_time = get_option('newsomatic_schedule_time');
                    if ($the_time != $newsomatic_Main_Settings['auto_clear_logs']) {
                        wp_clear_scheduled_hook('newsomaticactionclear');
                        delete_option('newsomatic_schedule_time');
                        $rez = wp_schedule_event(time(), $newsomatic_Main_Settings['auto_clear_logs'], 'newsomaticactionclear');
                        add_option('newsomatic_schedule_time', $newsomatic_Main_Settings['auto_clear_logs'], '', false);
                        if ($rez === FALSE) {
                            newsomatic_log_to_file('[Scheduler] Failed to schedule newsomaticactionclear to ' . $newsomatic_Main_Settings['auto_clear_logs'] . '!');
                        }
                    }
                }
            }
        } else {
            if (!wp_next_scheduled('newsomaticactionclear')) {
                delete_option('newsomatic_schedule_time');
            } else {
                wp_clear_scheduled_hook('newsomaticactionclear');
                delete_option('newsomatic_schedule_time');
            }
        }
    } else {
        if (wp_next_scheduled('newsomaticaction')) {
            wp_clear_scheduled_hook('newsomaticaction');
        }
        
        if (!wp_next_scheduled('newsomaticactionclear')) {
            delete_option('newsomatic_schedule_time');
        } else {
            wp_clear_scheduled_hook('newsomaticactionclear');
            delete_option('newsomatic_schedule_time');
        }
    }
}
function newsomatic_cron()
{
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    if (isset($newsomatic_Main_Settings['run_after']) && $newsomatic_Main_Settings['run_after'] != '' && isset($newsomatic_Main_Settings['run_before']) && $newsomatic_Main_Settings['run_before'] != '') 
    {
        $exit = true;
        $mytime = date("H:i");
        $min_time = $newsomatic_Main_Settings['run_after'];
        $max_time = $newsomatic_Main_Settings['run_before'];
        $date1 = DateTime::createFromFormat('H:i', $mytime);
        $date2 = DateTime::createFromFormat('H:i', $min_time);
        $date3 = DateTime::createFromFormat('H:i', $max_time);
        if ($date1 > $date2 && $date1 < $date3)
        {
            $exit = false;
        }
        if($exit == true)
        {
            return;
        }
    }
    $GLOBALS['wp_object_cache']->delete('newsomatic_rules_list', 'options');
    if (!get_option('newsomatic_rules_list')) {
        $rules = array();
    } else {
        $rules = get_option('newsomatic_rules_list');
    }
    $unlocker = get_option('newsomatic_minute_running_unlocked', false);
    if (!empty($rules)) {
        $cont = 0;
        foreach ($rules as $request => $bundle[]) {
            $bundle_values   = array_values($bundle);
            $myValues        = $bundle_values[$cont];
            $array_my_values = array_values($myValues);for($iji=0;$iji<count($array_my_values);++$iji){if(is_string($array_my_values[$iji])){$array_my_values[$iji]=stripslashes($array_my_values[$iji]);}}
            $schedule        = isset($array_my_values[1]) ? $array_my_values[1] : '24';
            $active          = isset($array_my_values[2]) ? $array_my_values[2] : '0';
            $last_run        = isset($array_my_values[3]) ? $array_my_values[3] : newsomatic_get_date_now();
            if ($active == '1') {
                $now                = newsomatic_get_date_now();
                if($unlocker == '1')
                {
                    $nextrun        = newsomatic_add_minute($last_run, $schedule);
                    $newsomatic_hour_diff = (int) newsomatic_minute_diff($now, $nextrun);
                }
                else
                {
                    $nextrun            = newsomatic_add_hour($last_run, $schedule);
                    $newsomatic_hour_diff = (int) newsomatic_hour_diff($now, $nextrun);
                }
                if ($newsomatic_hour_diff >= 0) {
                    newsomatic_run_rule($cont, 0);
                }
            }
            $cont = $cont + 1;
        }
    }
    $GLOBALS['wp_object_cache']->delete('newsomatic_all_list', 'options');
    if (!get_option('newsomatic_all_list')) {
        $rules2 = array();
    } else {
        $rules2 = get_option('newsomatic_all_list');
    }
    if (!empty($rules2)) {
        $cont2 = 0;
        foreach ($rules2 as $request2 => $bundle2[]) {
            $bundle_values2   = array_values($bundle2);
            $myValues2        = $bundle_values2[$cont2];
            $array_my_values2 = array_values($myValues2);for($iji=0;$iji<count($array_my_values2);++$iji){if(is_string($array_my_values2[$iji])){$array_my_values2[$iji]=stripslashes($array_my_values2[$iji]);}}
            $schedule2        = isset($array_my_values2[1]) ? $array_my_values2[1] : '24';
            $active2          = isset($array_my_values2[2]) ? $array_my_values2[2] : '0';
            $last_run2        = isset($array_my_values2[3]) ? $array_my_values2[3] : newsomatic_get_date_now();
            if ($active2 == '1') {
                $now2                = newsomatic_get_date_now();
                if($unlocker == '1')
                {
                    $nextrun2        = newsomatic_add_minute($last_run2, $schedule2);
                    $newsomatic_hour_diff2 = (int) newsomatic_minute_diff($now2, $nextrun2);
                }
                else
                {
                    $nextrun2            = newsomatic_add_hour($last_run2, $schedule2);
                    $newsomatic_hour_diff2 = (int) newsomatic_hour_diff($now2, $nextrun2);
                }
                
                if ($newsomatic_hour_diff2 >= 0) {
                    newsomatic_run_rule($cont2, 1);
                }
            }
            $cont2 = $cont2 + 1;
        }
    }
    $running = array();
    update_option('newsomatic_running_list', $running);
}

function newsomatic_log_to_file($str)
{
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    if (isset($newsomatic_Main_Settings['enable_logging']) && $newsomatic_Main_Settings['enable_logging'] == 'on') {
        $d = date("j-M-Y H:i:s e", current_time( 'timestamp' ));
        error_log("[$d] " . $str . "<br/>\r\n", 3, __DIR__.'/newsomatic_info.log');
    }
}
$newsomatic_cats_failed = false;
function newsomatic_get_categories()
{
	$categories_option_value = get_option('newsomaticapi_categories_list');
	if(isset($categories_option_value['category_list']) && isset($categories_option_value['last_updated']))
	{
		if( (time() - $categories_option_value['last_updated']) < 686400 )
		{
			return $categories_option_value;
		}
	}
    if($GLOBALS['newsomatic_cats_failed'] !== true)
    {
        $categories = newsomatic_update_categories();
        if(is_array($categories))
        {
            if(count($categories) == 0)
            {
                $GLOBALS['newsomatic_cats_failed'] = true;
            }
            return $categories;
        }
        else
        {
            $GLOBALS['newsomatic_cats_failed'] = true;
        }
    }
	return false;
}
function newsomatic_lazy_loading_auto_fix($cont)
{
    preg_match_all('{<img .*?>}s', $cont, $imgsMatchs);
    $imgs_count = count($imgsMatchs[0]);
    if($imgs_count < 1)
    {
        return $cont;
    }
    $our_lazy_tag = '';
    if(stristr($cont, ' data-src=')) 
    {
        $our_lazy_tag = 'data-src';
    } 
    elseif(stristr($cont, ' data-lazy-src=')) 
    {
        $our_lazy_tag = 'data-lazy-src';
    } 
    elseif(stristr($cont, ' data-ezsrc=')) 
    {
        $our_lazy_tag = 'data-ezsrc';
    } 
    elseif(stristr($cont, ' data-pagespeed-lazy-src=')) 
    {
        $our_lazy_tag = 'data-pagespeed-lazy-src';
    } 
    else 
    {
        $lazy_suspected = false;
        $images_plain = implode (' ', $imgsMatchs[0]);
        if (stristr($images_plain, 'lazy')) 
        {
            $lazy_suspected = true;
        } 
        else 
        {
            preg_match_all('{ src[\s]?=[\s]?["|\'](.*?)["|\']}', $images_plain, $srcs_matches);
            $found_srcs_count = count($srcs_matches[0]);
            $unique_srcs_count = count(array_unique($srcs_matches[1]));
            if ($found_srcs_count != 0) 
            {
                $diff_percentage = ($found_srcs_count-$unique_srcs_count)*100/$found_srcs_count;
            } 
            else 
            {
                $diff_percentage = 0;
            }
            if ($diff_percentage > 39) 
            {
                $lazy_suspected = true;
            }
        }
        if ($lazy_suspected) 
        {
            $remove_src_attr = preg_replace ('{ src[\s]?=[\s]?["|\'].*?["|\']}', ' ', $images_plain);
            $remove_these = array (' alt', ' srcset', ' data-srcset', ' class', ' id', ' title');
            $remove_src_attr = str_replace($remove_these, ' ', $remove_src_attr);
            $remove_src_attr = preg_replace('{ [\w|-]*?[\s]?=[\s]?["|\'].{1,9}?["|\']}s', ' ', $remove_src_attr);
            preg_match_all('{( [\w|-]*?)[\s]?=[\s]?["|\'][^",]*?/[^",]*?["|\']}', $remove_src_attr, $possible_src_matches);
            $src_match_unique = array_unique($possible_src_matches[1]);
            if (isset($src_match_unique[0])) 
            {
                $our_lazy_tag = $src_match_unique[0];
            }
        }
    }
    $our_lazy_tag = trim($our_lazy_tag);
    if ($our_lazy_tag == 'src' || $our_lazy_tag == '')
    {
        return $cont;
    }
    if (!stristr($cont, $our_lazy_tag))
    {
        return $cont;
    }
    foreach ($imgsMatchs[0] as $imgMatch) 
    {
        if (stristr($imgMatch, $our_lazy_tag)) 
        {
            $newImg = $imgMatch;
            $newImg = str_replace ( ' src=', ' previous-src=', $newImg );
            $newImg = preg_replace ( '{ previous-src=[\'|"].*?[\'|"] }', ' ', $newImg );
            $newImg = str_replace ( ' ' . $our_lazy_tag, ' src', $newImg );
            $cont = str_replace ( $imgMatch, $newImg, $cont );
        }
    }
    return $cont;
}
function newsomatic_update_categories()
{
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
	if (isset($newsomatic_Main_Settings['newsapi_active']) && trim($newsomatic_Main_Settings['newsapi_active']) == 'on') 
	{
		if (isset($newsomatic_Main_Settings['app_id']) && trim($newsomatic_Main_Settings['app_id']) !== '') {
			$categories = array();
			$feed_uri='https://newsapi.org/v2/sources?apiKey=' . trim($newsomatic_Main_Settings['app_id']);
			$exec = newsomatic_get_web_page_api($feed_uri);
			if ($exec === FALSE) {
				$ret = array();
				return $ret;
			}
			if (stristr($exec, 'sources') === FALSE) {
				$ret = array();
				return $ret;
			}
			$exec = json_decode($exec);
			foreach($exec->sources as $api_category)
			{
				if(isset($api_category->id))
				{
					$categories[$api_category->id] = $api_category->name;
				}
			}
			$news_categories = array(
				'category_list' => $categories,
				'last_updated' => time()
			);
			update_option('newsomaticapi_categories_list', $news_categories);
			return $news_categories;
		}
		else
		{
			$ret = array();
			return $ret;
		}
	}
	else
	{
		if (isset($newsomatic_Main_Settings['newsomatic_app_id']) && trim($newsomatic_Main_Settings['newsomatic_app_id']) !== '') {
            if(strlen(trim($newsomatic_Main_Settings['newsomatic_app_id'])) != 66 && strlen(trim($newsomatic_Main_Settings['newsomatic_app_id'])) != 50)
            {
                $ret = array();
                return $ret;
            }
			$categories = array();
            if(strlen(trim($newsomatic_Main_Settings['newsomatic_app_id'])) == 66)
            {
                $feed_uri = 'https://newsomaticapi.com/apis/news/v1/sources?apikey=' . trim($newsomatic_Main_Settings['newsomatic_app_id']);
			    $exec = newsomatic_get_web_page_api($feed_uri);
            }
            elseif(strlen(trim($newsomatic_Main_Settings['newsomatic_app_id'])) == 50)
            {
                $feed_uri = 'https://newsomaticapi.p.rapidapi.com/sources?apikey=' . trim($newsomatic_Main_Settings['newsomatic_app_id']);
                $xheaders = array();
                $xheaders[] = "x-rapidapi-key: " . trim($newsomatic_Main_Settings['newsomatic_app_id']);
                $xheaders[] = "x-rapidapi-host: newsomaticapi.p.rapidapi.com";
                $xheaders[] = "content-type: application/octet-stream";
                $xheaders[] = "useQueryString: true";
			    $exec = newsomatic_get_web_page_api($feed_uri, $xheaders);
            }
			
			if ($exec === FALSE) {
				$ret = array();
				return $ret;
			}
			if (stristr($exec, 'sources') === FALSE) {
				$ret = array();
				return $ret;
			}
			$exec = json_decode($exec);
            if(isset($exec->apicalls))
            {
                update_option('newsomaticapi_calls', esc_html($exec->apicalls));
            }
			foreach($exec->sources as $api_category)
			{
				if(isset($api_category->name))
				{
					$categories[urlencode($api_category->name)] = $api_category->name;
				}
			}
			arsort($categories);
			$news_categories = array(
				'category_list' => array_reverse($categories, true),
				'last_updated' => time()
			);
			update_option('newsomaticapi_categories_list', $news_categories);
			return $news_categories;
		}
		else
		{
			$ret = array();
			return $ret;
		}
	}
}
function newsomatic_delete_all_posts()
{
    $failed                 = false;
    $number                 = 0;
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    $post_list = array();
    $postsPerPage = 50000;
    $paged = 0;
    do
    {
        $postOffset = $paged * $postsPerPage;
        $query = array(
            'post_status' => array(
                'publish',
                'draft',
                'pending',
                'trash',
                'private',
                'future'
            ),
            'post_type' => array(
                'any'
            ),
            'numberposts' => $postsPerPage,
            'meta_key' => 'newsomatic_parent_rule',
            'fields' => 'ids',
            'offset'  => $postOffset
        );
        $got_me = get_posts($query);
        $post_list = array_merge($post_list, $got_me);
        $paged++;
    }while(!empty($got_me));
    wp_suspend_cache_addition(true);
    foreach ($post_list as $post) {
        $index = get_post_meta($post, 'newsomatic_parent_rule', true);
        if (isset($index) && $index !== '') {
            $args             = array(
                'post_parent' => $post
            );
            $post_attachments = get_children($args);
            if (isset($post_attachments) && !empty($post_attachments)) {
                foreach ($post_attachments as $attachment) {
                    wp_delete_attachment($attachment->ID, true);
                }
            }
            $res = wp_delete_post($post, true);
            if ($res === false) {
                $failed = true;
            } else {
                $number++;
            }
        }
    }
    wp_suspend_cache_addition(false);
    if ($failed === true) {
        if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
            newsomatic_log_to_file('[PostDelete] Failed to delete all posts!');
        }
    } else {
        if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
            newsomatic_log_to_file('[PostDelete] Successfuly deleted ' . esc_html($number) . ' posts!');
        }
    }
}

function newsomatic_replaceContentShortcodesAgain($the_content, $item_cat, $item_tags)
{
    $the_content = str_replace('%%item_cat%%', $item_cat, $the_content);
    $the_content = str_replace('%%item_tags%%', $item_tags, $the_content);
    return $the_content;
}
function newsomatic_replaceContentShortcodes($the_content, $just_title, $content, $description, $author, $media, $date, $orig_content, $img_attr, $item_url, $item_image, $author_link, $source_name, $source_id, $query_string)
{
    $matches = array();
    $i = 0;
    preg_match_all('~%regex\(\s*\"([^"]+?)\s*"\s*[,;]\s*\"([^"]*)\"\s*(?:[,;]\s*\"([^"]*?)\s*\")?(?:[,;]\s*\"([^"]*?)\s*\")?(?:[,;]\s*\"([^"]*?)\s*\")?\)%~si', $the_content, $matches);
    if (is_array($matches) && count($matches) && is_array($matches[0])) {
        for($i = 0; $i < count($matches[0]); $i++)
        {
            if (isset($matches[0][$i])) $fullmatch = $matches[0][$i];
            if (isset($matches[1][$i])) $search_in = newsomatic_replaceContentShortcodes($matches[1][$i], $just_title, $content, $description, $author, $media, $date, $orig_content, $img_attr, $item_url, $item_image, $author_link, $source_name, $source_id, $query_string);
            if (isset($matches[2][$i])) $matchpattern = $matches[2][$i];
            if (isset($matches[3][$i])) $element = $matches[3][$i];
            if (isset($matches[4][$i])) $delimeter = $matches[4][$i];if (isset($matches[5][$i])) $counter = $matches[5][$i];
            if (isset($matchpattern)) {
               if (preg_match('<^[\/#%+~[\]{}][\s\S]*[\/#%+~[\]{}]$>', $matchpattern, $z)) {
                  $ret = preg_match_all($matchpattern, $search_in, $submatches, PREG_PATTERN_ORDER);
               }
               else {
                  $ret = preg_match_all('~'.$matchpattern.'~si', $search_in, $submatches, PREG_PATTERN_ORDER);
               }
            }
            if (isset($submatches)) {
               if (is_array($submatches)) {
                  $empty_elements = array_keys($submatches[0], "");
                  foreach ($empty_elements as $e) {
                     unset($submatches[0][$e]);
                  }
                  $submatches[0] = array_unique($submatches[0]);
                  if (!is_numeric($element)) {
                     $element = 0;
                  }if (!is_numeric($counter)) {
                     $counter = 0;
                  }
                  if(isset($submatches[(int)($element)]))
                  {
                      $matched = $submatches[(int)($element)];
                  }
                  else
                  {
                      $matched = '';
                  }
                  $matched = array_unique((array)$matched);
                  if (empty($delimeter) || $delimeter == 'null') {
                     if (isset($matched[$counter])) $matched = $matched[$counter];
                  }
                  else {
                     $matched = implode($delimeter, $matched);
                  }
                  if (empty($matched)) {
                     $the_content = str_replace($fullmatch, '', $the_content);
                  } else {
                     $the_content = str_replace($fullmatch, $matched, $the_content);
                  }
               }
            }
        }
    }
    preg_match_all('~%regextext\(\s*\"([^"]+?)\s*"\s*,\s*\"([^"]*)\"\s*(?:,\s*\"([^"]*?)\s*\")?(?:,\s*\"([^"]*?)\s*\")?(?:,\s*\"([^"]*?)\s*\")?\)%~si', $the_content, $matches);
    if (is_array($matches) && count($matches) && is_array($matches[0])) {
        for($i = 0; $i < count($matches[0]); $i++)
        {
            if (isset($matches[0][$i])) $fullmatch = $matches[0][$i];
            if (isset($matches[1][$i])) $search_in = newsomatic_replaceContentShortcodes($matches[1][$i], $just_title, $content, $description, $author, $media, $date, $orig_content, $img_attr, $item_url, $item_image, $author_link, $source_name, $source_id, $query_string);
            if (isset($matches[2][$i])) $matchpattern = $matches[2][$i];
            if (isset($matches[3][$i])) $element = $matches[3][$i];
            if (isset($matches[4][$i])) $delimeter = $matches[4][$i];if (isset($matches[5][$i])) $counter = $matches[5][$i];
            $search_in = strip_tags($search_in, '<p><br>');
            $search_in = preg_replace("/<p[^>]*?>/", "", $search_in);
            $search_in = str_replace("</p>", "<br />", $search_in);
            $search_in = preg_replace('/\<br(\s*)?\/?\>/i', "\r\n\r\n", $search_in);
            $search_in = preg_replace('/^(?:\r|\n|\r\n)+/', '', $search_in);
            if (isset($matchpattern)) {
               if (preg_match('<^[\/#%+~[\]{}][\s\S]*[\/#%+~[\]{}]$>', $matchpattern, $z)) {
                  $ret = preg_match_all($matchpattern, $search_in, $submatches, PREG_PATTERN_ORDER);
               }
               else {
                  $ret = preg_match_all('~'.$matchpattern.'~si', $search_in, $submatches, PREG_PATTERN_ORDER);
               }
            }
            if (isset($submatches)) {
               if (is_array($submatches)) {
                  $empty_elements = array_keys($submatches[0], "");
                  foreach ($empty_elements as $e) {
                     unset($submatches[0][$e]);
                  }
                  $submatches[0] = array_unique($submatches[0]);
                  if (!is_numeric($element)) {
                     $element = 0;
                  }if (!is_numeric($counter)) {
                     $counter = 0;
                  }
                  if(isset($submatches[(int)($element)]))
                  {
                      $matched = $submatches[(int)($element)];
                  }
                  else
                  {
                      $matched = '';
                  }
                  $matched = array_unique((array)$matched);
                  if (empty($delimeter) || $delimeter == 'null') {
                     if (isset($matched[$counter])) $matched = $matched[$counter];
                  }
                  else {
                     $matched = implode($delimeter, $matched);
                  }
                  if (empty($matched)) {
                     $the_content = str_replace($fullmatch, '', $the_content);
                  } else {
                     $the_content = str_replace($fullmatch, $matched, $the_content);
                  }
               }
            }
        }
    }
    $spintax = new Newsomatic_Spintax();
    $the_content = $spintax->process($the_content);
    $pcxxx = explode('<!- template ->', $the_content);
    $the_content = $pcxxx[array_rand($pcxxx)];
    $the_content = str_replace('%%random_sentence%%', newsomatic_random_sentence_generator(), $the_content);
    $the_content = str_replace('%%random_sentence2%%', newsomatic_random_sentence_generator(false), $the_content);
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    if (isset($newsomatic_Main_Settings['read_more']) && $newsomatic_Main_Settings['read_more'] != '') 
    {
        $rm_text = $newsomatic_Main_Settings['read_more'];
    }
    else
    {
        $rm_text = esc_html__('Read More', 'newsomatic-news-post-generator');
    }
    $the_content = str_replace('%%item_url%%', $item_url, $the_content);
    $the_content = str_replace('%%item_read_more_button%%', newsomatic_getReadMoreButton($item_url, $rm_text, $newsomatic_Main_Settings), $the_content);
    $the_content = str_replace('%%item_show_image%%', newsomatic_getItemImage($item_image), $the_content);
    $the_content = str_replace('%%item_image_URL%%', $item_image, $the_content);
    $the_content = str_replace('%%author_link%%', $author_link, $the_content);
    if (isset($newsomatic_Main_Settings['custom_html'])) {
        $the_content = str_replace('%%custom_html%%', $newsomatic_Main_Settings['custom_html'], $the_content);
    }
    if (isset($newsomatic_Main_Settings['custom_html2'])) {
        $the_content = str_replace('%%custom_html2%%', $newsomatic_Main_Settings['custom_html2'], $the_content);
    }
    $img_attr = str_replace('%%image_source_name%%', '', $img_attr);
    $img_attr = str_replace('%%image_source_url%%', '', $img_attr);
    $img_attr = str_replace('%%image_source_website%%', '', $img_attr);
    $the_content = str_replace('%%royalty_free_image_attribution%%', $img_attr, $the_content);
    
    $the_content = str_replace('%%item_title%%', $just_title, $the_content);
    $the_content = str_replace('%%item_source%%', $source_name, $the_content);
    $the_content = str_replace('%%item_source_id%%', $source_id, $the_content);
    $the_content = str_replace('%%item_content%%', $content, $the_content);
    $the_content = str_replace('%%keyword_search%%', $query_string, $the_content);
    $the_content = str_replace('%%item_original_content%%', $orig_content, $the_content);
    $the_content = str_replace('%%item_content_plain_text%%', newsomatic_getPlainContent($content), $the_content);
    $the_content = str_replace('%%item_description%%', $description, $the_content);
    $the_content = str_replace('%%author%%', $author, $the_content);
    $the_content = str_replace('%%item_media%%', $media, $the_content);
    if ((isset($newsomatic_Main_Settings['date_format']) && $newsomatic_Main_Settings['date_format'] !== ''))
    {
        $timest = strtotime($date);
        if($timest != false)
        {
            $tmp_date = date($newsomatic_Main_Settings['date_format'], $timest);
            if($tmp_date != false)
            {
                $date = $tmp_date;
            }
        }
    }
    $the_content = str_replace('%%item_date%%', $date, $the_content);
    $the_content = preg_replace_callback('#%%random_image_url\[([^\]]*?)\]%%#', function ($matches) {
        $my_img = newsomatic_get_random_image_google($matches[1]);
        return $my_img;
    }, $the_content);
    $the_content = preg_replace_callback('#%%random_image\[([^\]]*?)\]%%#', function ($matches) {
        $my_img = newsomatic_get_random_image_google($matches[1]);
        return '<img src="' . $my_img . '">';
    }, $the_content);
    $the_content = newsomatic_replaceSynergyShortcodes($the_content);
    return $the_content;
}
function newsomatic_replaceTitleShortcodes($the_content, $just_title, $content, $item_url, $date, $author, $source_name, $source_id)
{
    $matches = array();
    $i = 0;
    preg_match_all('~%regex\(\s*\"([^"]+?)\s*"\s*[,;]\s*\"([^"]*)\"\s*(?:[,;]\s*\"([^"]*?)\s*\")?(?:[,;]\s*\"([^"]*?)\s*\")?(?:[,;]\s*\"([^"]*?)\s*\")?\)%~si', $the_content, $matches);
    if (is_array($matches) && count($matches) && is_array($matches[0])) {
        for($i = 0; $i < count($matches[0]); $i++)
        {
            if (isset($matches[0][$i])) $fullmatch = $matches[0][$i];
            if (isset($matches[1][$i])) $search_in = newsomatic_replaceTitleShortcodes($matches[1][$i], $just_title, $content, $item_url, $date, $author, $source_name, $source_id);
            if (isset($matches[2][$i])) $matchpattern = $matches[2][$i];
            if (isset($matches[3][$i])) $element = $matches[3][$i];
            if (isset($matches[4][$i])) $delimeter = $matches[4][$i];if (isset($matches[5][$i])) $counter = $matches[5][$i];
            if (isset($matchpattern)) {
               if (preg_match('<^[\/#%+~[\]{}][\s\S]*[\/#%+~[\]{}]$>', $matchpattern, $z)) {
                  $ret = preg_match_all($matchpattern, $search_in, $submatches, PREG_PATTERN_ORDER);
               }
               else {
                  $ret = preg_match_all('~'.$matchpattern.'~si', $search_in, $submatches, PREG_PATTERN_ORDER);
               }
            }
            if (isset($submatches)) {
               if (is_array($submatches)) {
                  $empty_elements = array_keys($submatches[0], "");
                  foreach ($empty_elements as $e) {
                     unset($submatches[0][$e]);
                  }
                  $submatches[0] = array_unique($submatches[0]);
                  if (!is_numeric($element)) {
                     $element = 0;
                  }if (!is_numeric($counter)) {
                     $counter = 0;
                  }
                  if(isset($submatches[(int)($element)]))
                  {
                      $matched = $submatches[(int)($element)];
                  }
                  else
                  {
                      $matched = '';
                  }
                  $matched = array_unique((array)$matched);
                  if (empty($delimeter) || $delimeter == 'null') {
                     if (isset($matched[$counter])) $matched = $matched[$counter];
                  }
                  else {
                     $matched = implode($delimeter, $matched);
                  }
                  if (empty($matched)) {
                     $the_content = str_replace($fullmatch, '', $the_content);
                  } else {
                     $the_content = str_replace($fullmatch, $matched, $the_content);
                  }
               }
            }
        }
    }
    $spintax = new Newsomatic_Spintax();
    $the_content = $spintax->process($the_content);
    $pcxxx = explode('<!- template ->', $the_content);
    $the_content = $pcxxx[array_rand($pcxxx)];
    $the_content = str_replace('%%random_sentence%%', newsomatic_random_sentence_generator(), $the_content);
    $the_content = str_replace('%%random_sentence2%%', newsomatic_random_sentence_generator(false), $the_content);
    $the_content = str_replace('%%item_title%%', $just_title, $the_content);
    $the_content = str_replace('%%item_source%%', $source_name, $the_content);
    $the_content = str_replace('%%item_source_id%%', $source_id, $the_content);
    $the_content = str_replace('%%item_description%%', $content, $the_content);
    $the_content = str_replace('%%item_url%%', $item_url, $the_content);
    if ((isset($newsomatic_Main_Settings['date_format']) && $newsomatic_Main_Settings['date_format'] !== ''))
    {
        $timest = strtotime($date);
        if($timest != false)
        {
            $tmp_date = date($newsomatic_Main_Settings['date_format'], $timest);
            if($tmp_date != false)
            {
                $date = $tmp_date;
            }
        }
    }
    $the_content = str_replace('%%item_date%%', $date, $the_content);
    $the_content = str_replace('%%author%%', $author, $the_content);
    $the_content = newsomatic_replaceSynergyShortcodes($the_content);
    return $the_content;
}

function newsomatic_replaceTitleShortcodesAgain($the_content, $item_cat, $item_tags)
{
    $the_content = str_replace('%%item_cat%%', $item_cat, $the_content);
    $the_content = str_replace('%%item_tags%%', $item_tags, $the_content);
    return $the_content;
}

add_shortcode( 'newsomatic-display-posts', 'newsomatic_display_posts_shortcode' );
function newsomatic_display_posts_shortcode( $atts ) {
    if ( is_admin() ) 
    {
        return;
    }
	$original_atts = $atts;
	$atts = shortcode_atts( array(
		'author'               => '',
		'category'             => '',
		'category_display'     => '',
		'category_label'       => 'Posted in: ',
		'content_class'        => 'content',
		'date_format'          => '(n/j/Y)',
		'date'                 => '',
		'date_column'          => 'post_date',
		'date_compare'         => '=',
		'date_query_before'    => '',
		'date_query_after'     => '',
		'date_query_column'    => '',
		'date_query_compare'   => '',
		'display_posts_off'    => false,
		'excerpt_length'       => false,
		'excerpt_more'         => false,
		'excerpt_more_link'    => false,
		'exclude_current'      => false,
		'id'                   => false,
		'ignore_sticky_posts'  => false,
		'image_size'           => false,
		'include_author'       => false,
		'include_content'      => false,
		'include_date'         => false,
		'include_excerpt'      => false,
		'include_link'         => true,
		'include_title'        => true,
		'meta_key'             => '',
		'meta_value'           => '',
		'no_posts_message'     => '',
		'offset'               => 0,
		'order'                => 'DESC',
		'orderby'              => 'date',
		'post_parent'          => false,
		'post_status'          => 'publish',
		'post_type'            => 'post',
		'posts_per_page'       => '10',
		'tag'                  => '',
		'tax_operator'         => 'IN',
		'tax_include_children' => true,
		'tax_term'             => false,
		'taxonomy'             => false,
		'time'                 => '',
		'title'                => '',
        'title_color'          => '#000000',
        'excerpt_color'        => '#000000',
        'link_to_source'       => '',
        'title_font_size'      => '100%',
        'excerpt_font_size'    => '100%',
        'read_more_text'       => '',
		'wrapper'              => 'ul',
		'wrapper_class'        => 'display-posts-listing',
		'wrapper_id'           => false,
        'ruleid'               => '',
        'ruletype'             => ''
	), $atts, 'display-posts' );
	if( $atts['display_posts_off'] )
		return;
    $ruleid               = sanitize_text_field( $atts['ruleid'] );
    $ruletype             = sanitize_text_field( $atts['ruletype'] );
	$author               = sanitize_text_field( $atts['author'] );
	$category             = sanitize_text_field( $atts['category'] );
	$category_display     = 'true' == $atts['category_display'] ? 'category' : sanitize_text_field( $atts['category_display'] );
	$category_label       = sanitize_text_field( $atts['category_label'] );
	$content_class        = array_map( 'sanitize_html_class', ( explode( ' ', $atts['content_class'] ) ) );
	$date_format          = sanitize_text_field( $atts['date_format'] );
	$date                 = sanitize_text_field( $atts['date'] );
	$date_column          = sanitize_text_field( $atts['date_column'] );
	$date_compare         = sanitize_text_field( $atts['date_compare'] );
	$date_query_before    = sanitize_text_field( $atts['date_query_before'] );
	$date_query_after     = sanitize_text_field( $atts['date_query_after'] );
	$date_query_column    = sanitize_text_field( $atts['date_query_column'] );
	$date_query_compare   = sanitize_text_field( $atts['date_query_compare'] );
	$excerpt_length       = intval( $atts['excerpt_length'] );
	$excerpt_more         = sanitize_text_field( $atts['excerpt_more'] );
	$excerpt_more_link    = filter_var( $atts['excerpt_more_link'], FILTER_VALIDATE_BOOLEAN );
	$exclude_current      = filter_var( $atts['exclude_current'], FILTER_VALIDATE_BOOLEAN );
	$id                   = $atts['id'];
	$ignore_sticky_posts  = filter_var( $atts['ignore_sticky_posts'], FILTER_VALIDATE_BOOLEAN );
	$image_size           = sanitize_key( $atts['image_size'] );
	$include_title        = filter_var( $atts['include_title'], FILTER_VALIDATE_BOOLEAN );
	$include_author       = filter_var( $atts['include_author'], FILTER_VALIDATE_BOOLEAN );
	$include_content      = filter_var( $atts['include_content'], FILTER_VALIDATE_BOOLEAN );
	$include_date         = filter_var( $atts['include_date'], FILTER_VALIDATE_BOOLEAN );
	$include_excerpt      = filter_var( $atts['include_excerpt'], FILTER_VALIDATE_BOOLEAN );
	$include_link         = filter_var( $atts['include_link'], FILTER_VALIDATE_BOOLEAN );
	$meta_key             = sanitize_text_field( $atts['meta_key'] );
	$meta_value           = sanitize_text_field( $atts['meta_value'] );
	$no_posts_message     = sanitize_text_field( $atts['no_posts_message'] );
	$offset               = intval( $atts['offset'] );
	$order                = sanitize_key( $atts['order'] );
	$orderby              = sanitize_key( $atts['orderby'] );
	$post_parent          = $atts['post_parent'];
	$post_status          = $atts['post_status'];
	$post_type            = sanitize_text_field( $atts['post_type'] );
	$posts_per_page       = intval( $atts['posts_per_page'] );
	$tag                  = sanitize_text_field( $atts['tag'] );
	$tax_operator         = $atts['tax_operator'];
	$tax_include_children = filter_var( $atts['tax_include_children'], FILTER_VALIDATE_BOOLEAN );
	$tax_term             = sanitize_text_field( $atts['tax_term'] );
	$taxonomy             = sanitize_key( $atts['taxonomy'] );
	$time                 = sanitize_text_field( $atts['time'] );
	$shortcode_title      = sanitize_text_field( $atts['title'] );
    $title_color          = sanitize_text_field( $atts['title_color'] );
    $excerpt_color        = sanitize_text_field( $atts['excerpt_color'] );
    $link_to_source       = sanitize_text_field( $atts['link_to_source'] );
    $excerpt_font_size    = sanitize_text_field( $atts['excerpt_font_size'] );
    $title_font_size      = sanitize_text_field( $atts['title_font_size'] );
    $read_more_text       = sanitize_text_field( $atts['read_more_text'] );
	$wrapper              = sanitize_text_field( $atts['wrapper'] );
	$wrapper_class        = array_map( 'sanitize_html_class', ( explode( ' ', $atts['wrapper_class'] ) ) );
	if( !empty( $wrapper_class ) )
		$wrapper_class = ' class="' . implode( ' ', $wrapper_class ) . '"';
	$wrapper_id = sanitize_html_class( $atts['wrapper_id'] );
	if( !empty( $wrapper_id ) )
		$wrapper_id = ' id="' . esc_html($wrapper_id) . '"';
	$args = array(
		'category_name'       => $category,
		'order'               => $order,
		'orderby'             => $orderby,
		'post_type'           => explode( ',', $post_type ),
		'posts_per_page'      => $posts_per_page,
		'tag'                 => $tag,
	);
	if ( ! empty( $date ) || ! empty( $time ) || ! empty( $date_query_after ) || ! empty( $date_query_before ) ) {
		$initial_date_query = $date_query_top_lvl = array();
		$valid_date_columns = array(
			'post_date', 'post_date_gmt', 'post_modified', 'post_modified_gmt',
			'comment_date', 'comment_date_gmt'
		);
		$valid_compare_ops = array( '=', '!=', '>', '>=', '<', '<=', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN' );
		$dates = newsomatic_sanitize_date_time( $date );
		if ( ! empty( $dates ) ) {
			if ( is_string( $dates ) ) {
				$timestamp = strtotime( $dates );
				$dates = array(
					'year'   => date( 'Y', $timestamp ),
					'month'  => date( 'm', $timestamp ),
					'day'    => date( 'd', $timestamp ),
				);
			}
			foreach ( $dates as $arg => $segment ) {
				$initial_date_query[ $arg ] = $segment;
			}
		}
		$times = newsomatic_sanitize_date_time( $time, 'time' );
		if ( ! empty( $times ) ) {
			foreach ( $times as $arg => $segment ) {
				$initial_date_query[ $arg ] = $segment;
			}
		}
		$before = newsomatic_sanitize_date_time( $date_query_before, 'date', true );
		if ( ! empty( $before ) ) {
			$initial_date_query['before'] = $before;
		}
		$after = newsomatic_sanitize_date_time( $date_query_after, 'date', true );
		if ( ! empty( $after ) ) {
			$initial_date_query['after'] = $after;
		}
		if ( ! empty( $date_query_column ) && in_array( $date_query_column, $valid_date_columns ) ) {
			$initial_date_query['column'] = $date_query_column;
		}
		if ( ! empty( $date_query_compare ) && in_array( $date_query_compare, $valid_compare_ops ) ) {
			$initial_date_query['compare'] = $date_query_compare;
		}
		if ( ! empty( $date_column ) && in_array( $date_column, $valid_date_columns ) ) {
			$date_query_top_lvl['column'] = $date_column;
		}
		if ( ! empty( $date_compare ) && in_array( $date_compare, $valid_compare_ops ) ) {
			$date_query_top_lvl['compare'] = $date_compare;
		}
		if ( ! empty( $initial_date_query ) ) {
			$date_query_top_lvl[] = $initial_date_query;
		}
		$args['date_query'] = $date_query_top_lvl;
	}
    if($ruleid != '' && $ruletype != '')
    {
        $q_arr = array();
        $temp_arr['key'] = 'newsomatic_parent_rule1';
        $temp_arr['value'] = $ruleid;
        $q_arr[] = $temp_arr;
        $temp_arr2['key'] = 'newsomatic_parent_type';
        $temp_arr2['value'] = $ruletype;
        $q_arr[] = $temp_arr2;
        $args['meta_query'] = $q_arr;
    }
    elseif($ruleid != '')
    {
        $args['meta_key'] = 'newsomatic_parent_rule1';
        $args['meta_value'] = $ruleid;
    }
    elseif($ruletype != '')
    {
        $args['meta_key'] = 'newsomatic_parent_type';
        $args['meta_value'] = $ruletype;
    }
	if( $ignore_sticky_posts )
		$args['ignore_sticky_posts'] = true;
	 
	if( $id ) {
		$posts_in = array_map( 'intval', explode( ',', $id ) );
		$args['post__in'] = $posts_in;
	}
	if( is_singular() && $exclude_current )
		$args['post__not_in'] = array( get_the_ID() );
	if( !empty( $author ) ) {
		if( 'current' == $author && is_user_logged_in() )
			$args['author_name'] = wp_get_current_user()->user_login;
		elseif( 'current' == $author )
            $unrelevar = false;
			 
		else
			$args['author_name'] = $author;
	}
	if( !empty( $offset ) )
		$args['offset'] = $offset;
	$post_status = explode( ', ', $post_status );
	$validated = array();
	$available = array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash', 'any' );
	foreach ( $post_status as $unvalidated )
		if ( in_array( $unvalidated, $available ) )
			$validated[] = $unvalidated;
	if( !empty( $validated ) )
		$args['post_status'] = $validated;
	if ( !empty( $taxonomy ) && !empty( $tax_term ) ) {
		if( 'current' == $tax_term ) {
			global $post;
			$terms = wp_get_post_terms(get_the_ID(), $taxonomy);
			$tax_term = array();
			foreach ($terms as $term) {
				$tax_term[] = $term->slug;
			}
		}else{
			$tax_term = explode( ', ', $tax_term );
		}
		if( !in_array( $tax_operator, array( 'IN', 'NOT IN', 'AND' ) ) )
			$tax_operator = 'IN';
		$tax_args = array(
			'tax_query' => array(
				array(
					'taxonomy'         => $taxonomy,
					'field'            => 'slug',
					'terms'            => $tax_term,
					'operator'         => $tax_operator,
					'include_children' => $tax_include_children,
				)
			)
		);
		$count = 2;
		$more_tax_queries = false;
		while(
			isset( $original_atts['taxonomy_' . $count] ) && !empty( $original_atts['taxonomy_' . $count] ) &&
			isset( $original_atts['tax_' . esc_html($count) . '_term'] ) && !empty( $original_atts['tax_' . esc_html($count) . '_term'] )
		):
			$more_tax_queries = true;
			$taxonomy = sanitize_key( $original_atts['taxonomy_' . $count] );
	 		$terms = explode( ', ', sanitize_text_field( $original_atts['tax_' . esc_html($count) . '_term'] ) );
	 		$tax_operator = isset( $original_atts['tax_' . esc_html($count) . '_operator'] ) ? $original_atts['tax_' . esc_html($count) . '_operator'] : 'IN';
	 		$tax_operator = in_array( $tax_operator, array( 'IN', 'NOT IN', 'AND' ) ) ? $tax_operator : 'IN';
	 		$tax_include_children = isset( $original_atts['tax_' . esc_html($count) . '_include_children'] ) ? filter_var( $atts['tax_' . esc_html($count) . '_include_children'], FILTER_VALIDATE_BOOLEAN ) : true;
	 		$tax_args['tax_query'][] = array(
	 			'taxonomy'         => $taxonomy,
	 			'field'            => 'slug',
	 			'terms'            => $terms,
	 			'operator'         => $tax_operator,
	 			'include_children' => $tax_include_children,
	 		);
			$count++;
		endwhile;
		if( $more_tax_queries ):
			$tax_relation = 'AND';
			if( isset( $original_atts['tax_relation'] ) && in_array( $original_atts['tax_relation'], array( 'AND', 'OR' ) ) )
				$tax_relation = $original_atts['tax_relation'];
			$args['tax_query']['relation'] = $tax_relation;
		endif;
		$args = array_merge_recursive( $args, $tax_args );
	}
	if( $post_parent !== false ) {
		if( 'current' == $post_parent ) {
			global $post;
			$post_parent = get_the_ID();
		}
		$args['post_parent'] = intval( $post_parent );
	}
	$wrapper_options = array( 'ul', 'ol', 'div' );
	if( ! in_array( $wrapper, $wrapper_options ) )
		$wrapper = 'ul';
	$inner_wrapper = 'div' == $wrapper ? 'div' : 'li';
	$listing = new WP_Query( apply_filters( 'display_posts_shortcode_args', $args, $original_atts ) );
	if ( ! $listing->have_posts() ) {
		return apply_filters( 'display_posts_shortcode_no_results', wpautop( $no_posts_message ) );
	}
	$inner = '';
    wp_suspend_cache_addition(true);
	while ( $listing->have_posts() ): $listing->the_post(); global $post;
		$image = $date = $author = $excerpt = $content = '';
		if ( $include_title && $include_link ) {
            if($link_to_source == 'yes')
            {
                $source_url = get_post_meta($post->ID, 'newsomatic_post_url', true);
                if($source_url != '')
                {
                    $title = '<a class="newsomatic_display_title" href="' . esc_url_raw($source_url) . '"><span class="cr_display_span" >' . get_the_title() . '</span></a>';
                }
                else
                {
                    $title = '<a class="newsomatic_display_title" href="' . apply_filters( 'the_permalink', get_permalink() ) . '"><span class="cr_display_span" >' . get_the_title() . '</span></a>';
                }
            }
            else
            {
                $title = '<a class="newsomatic_display_title" href="' . apply_filters( 'the_permalink', get_permalink() ) . '"><span class="cr_display_span" >' . get_the_title() . '</span></a>';
            }
		} elseif( $include_title ) {
			$title = '<span class="newsomatic_display_title" class="cr_display_span">' . get_the_title() . '</span>';
		} else {
			$title = '';
		}
		if ( $image_size && has_post_thumbnail() && $include_link ) {
            if($link_to_source == 'yes')
            {
                $source_url = get_post_meta($post->ID, 'newsomatic_post_url', true);
                if($source_url != '')
                {
                    $image = '<a class="newsomatic_display_image" href="' . esc_url_raw($source_url) . '">' . get_the_post_thumbnail( get_the_ID(), $image_size ) . '</a> <br/>';
                }
                else
                {
                    $image = '<a class="newsomatic_display_image" href="' . get_permalink() . '">' . get_the_post_thumbnail( get_the_ID(), $image_size ) . '</a> <br/>';
                }
            }
            else
            {
                $image = '<a class="newsomatic_display_image" href="' . get_permalink() . '">' . get_the_post_thumbnail( get_the_ID(), $image_size ) . '</a> <br/>';
            }
		} elseif( $image_size && has_post_thumbnail() ) {
			$image = '<span class="newsomatic_display_image">' . get_the_post_thumbnail( get_the_ID(), $image_size ) . '</span> <br/>';
		}
		if ( $include_date )
			$date = ' <span class="date">' . get_the_date( $date_format ) . '</span>';
		if( $include_author )
			$author = apply_filters( 'display_posts_shortcode_author', ' <span class="newsomatic_display_author">by ' . get_the_author() . '</span>', $original_atts );
		if ( $include_excerpt ) {
			if( $excerpt_length || $excerpt_more || $excerpt_more_link ) {
				$length = $excerpt_length ? $excerpt_length : apply_filters( 'excerpt_length', 55 );
				$more   = $excerpt_more ? $excerpt_more : apply_filters( 'excerpt_more', '' );
				$more   = $excerpt_more_link ? ' <a href="' . get_permalink() . '">' . esc_html($more) . '</a>' : ' ' . esc_html($more);
				if( has_excerpt() && apply_filters( 'display_posts_shortcode_full_manual_excerpt', false ) ) {
					$excerpt = $post->post_excerpt . $more;
				} elseif( has_excerpt() ) {
					$excerpt = wp_trim_words( strip_shortcodes( $post->post_excerpt ), $length, $more );
				} else {
					$excerpt = wp_trim_words( strip_shortcodes( $post->post_content ), $length, $more );
				}
			} else {
				$excerpt = get_the_excerpt();
			}
			$excerpt = ' <br/><br/> <span class="newsomatic_display_excerpt" class="cr_display_excerpt_adv">' . $excerpt . '</span>';
            if($read_more_text != '')
            {
                if($link_to_source == 'yes')
                {
                    $source_url = get_post_meta($post->ID, 'newsomatic_post_url', true);
                    if($source_url != '')
                    {
                        $excerpt .= '<br/><a href="' . esc_url_raw($source_url) . '"><span class="newsomatic_display_excerpt" class="cr_display_excerpt_adv">' . esc_html($read_more_text) . '</span></a>';
                    }
                    else
                    {
                        $excerpt .= '<br/><a href="' . get_permalink() . '"><span class="newsomatic_display_excerpt" class="cr_display_excerpt_adv">' . esc_html($read_more_text) . '</span></a>';
                    }
                }
                else
                {
                    $excerpt .= '<br/><a href="' . get_permalink() . '"><span class="newsomatic_display_excerpt" class="cr_display_excerpt_adv">' . esc_html($read_more_text) . '</span></a>';
                }
            }
		}
		if( $include_content ) {
			add_filter( 'shortcode_atts_display-posts', 'newsomatic_display_posts_off', 10, 3 );
			$content = '<div class="' . implode( ' ', $content_class ) . '">' . apply_filters( 'the_content', get_the_content() ) . '</div>';
			remove_filter( 'shortcode_atts_display-posts', 'newsomatic_display_posts_off', 10, 3 );
		}
		$category_display_text = '';
		if( $category_display && is_object_in_taxonomy( get_post_type(), $category_display ) ) {
			$terms = get_the_terms( get_the_ID(), $category_display );
			$term_output = array();
			foreach( $terms as $term )
				$term_output[] = '<a href="' . get_term_link( $term, $category_display ) . '">' . esc_html($term->name) . '</a>';
			$category_display_text = ' <span class="category-display"><span class="category-display-label">' . esc_html($category_label) . '</span> ' . trim(implode( ', ', $term_output ), ', ') . '</span>';
			$category_display_text = apply_filters( 'display_posts_shortcode_category_display', $category_display_text );
		}
		$class = array( 'listing-item' );
		$class = array_map( 'sanitize_html_class', apply_filters( 'display_posts_shortcode_post_class', $class, $post, $listing, $original_atts ) );
		$output = '<br/><' . esc_html($inner_wrapper) . ' class="' . implode( ' ', $class ) . '">' . $image . $title . $date . $author . $category_display_text . $excerpt . $content . '</' . esc_html($inner_wrapper) . '><br/><br/><hr class="cr_hr_dot"/>';		$inner .= apply_filters( 'display_posts_shortcode_output', $output, $original_atts, $image, $title, $date, $excerpt, $inner_wrapper, $content, $class );
	endwhile; wp_reset_postdata();
    wp_suspend_cache_addition(false);
	$open = apply_filters( 'display_posts_shortcode_wrapper_open', '<' . $wrapper . $wrapper_class . $wrapper_id . '>', $original_atts );
	$close = apply_filters( 'display_posts_shortcode_wrapper_close', '</' . esc_html($wrapper) . '>', $original_atts );
	$return = $open;
	if( $shortcode_title ) {
		$title_tag = apply_filters( 'display_posts_shortcode_title_tag', 'h2', $original_atts );
		$return .= '<' . esc_html($title_tag) . ' class="display-posts-title">' . esc_html($shortcode_title) . '</' . esc_html($title_tag) . '>' . "\n";
	}
	$return .= $inner . $close;
    $reg_css_code = '.cr_hr_dot{border-top: dotted 1px;}.cr_display_span{font-size:' . esc_html($title_font_size) . ';color:' . esc_html($title_color) . ' !important;}.cr_display_excerpt_adv{font-size:' . esc_html($excerpt_font_size) . ';color:' . esc_html($excerpt_color) . ' !important;}';
    wp_register_style( 'newsomatic-display-style', false );
    wp_enqueue_style( 'newsomatic-display-style' );
    wp_add_inline_style( 'newsomatic-display-style', $reg_css_code );
	return $return;
}
function newsomatic_sanitize_date_time( $date_time, $type = 'date', $accepts_string = false ) {
	if ( empty( $date_time ) || ! in_array( $type, array( 'date', 'time' ) ) ) {
		return array();
	}
	$segments = array();
	if (
		true === $accepts_string
		&& ( false !== strpos( $date_time, ' ' ) || false === strpos( $date_time, '-' ) )
	) {
		if ( false !== $timestamp = strtotime( $date_time ) ) {
			return $date_time;
		}
	}
	$parts = array_map( 'absint', explode( 'date' == $type ? '-' : ':', $date_time ) );
	if ( 'date' == $type ) {
		$year = $month = $day = 1;
		if ( count( $parts ) >= 3 ) {
			list( $year, $month, $day ) = $parts;
			$year  = ( $year  >= 1 && $year  <= 9999 ) ? $year  : 1;
			$month = ( $month >= 1 && $month <= 12   ) ? $month : 1;
			$day   = ( $day   >= 1 && $day   <= 31   ) ? $day   : 1;
		}
		$segments = array(
			'year'  => $year,
			'month' => $month,
			'day'   => $day
		);
	} elseif ( 'time' == $type ) {
		$hour = $minute = $second = 0;
		switch( count( $parts ) ) {
			case 3 :
				list( $hour, $minute, $second ) = $parts;
				$hour   = ( $hour   >= 0 && $hour   <= 23 ) ? $hour   : 0;
				$minute = ( $minute >= 0 && $minute <= 60 ) ? $minute : 0;
				$second = ( $second >= 0 && $second <= 60 ) ? $second : 0;
				break;
			case 2 :
				list( $hour, $minute ) = $parts;
				$hour   = ( $hour   >= 0 && $hour   <= 23 ) ? $hour   : 0;
				$minute = ( $minute >= 0 && $minute <= 60 ) ? $minute : 0;
				break;
			default : break;
		}
		$segments = array(
			'hour'   => $hour,
			'minute' => $minute,
			'second' => $second
		);
	}

	return apply_filters( 'display_posts_shortcode_sanitized_segments', $segments, $date_time, $type );
}

function newsomatic_display_posts_off( $out, $pairs, $atts ) {
	$out['display_posts_off'] = apply_filters( 'display_posts_shortcode_inception_override', true );
	return $out;
}
add_shortcode( 'newsomatic-list-posts', 'newsomatic_list_posts' );
function newsomatic_list_posts( $atts ) {
    if ( is_admin() ) 
    {
        return;
    }
    ob_start();
    extract( shortcode_atts( array (
        'type' => 'any',
        'order' => 'ASC',
        'orderby' => 'title',
        'posts' => 50,
        'posts_per_page' => 50,
        'category' => '',
        'ruleid' => '',
        'ruletype' => ''
    ), $atts ) );
    if($posts_per_page != 50)
    {
        $posts = $posts_per_page;
    }
    $options = array(
        'post_type' => $type,
        'order' => $order,
        'orderby' => $orderby,
        'posts_per_page' => $posts,
        'category_name' => $category
    );
    if($ruleid != '' && $ruletype != '')
    {
        $q_arr = array();
        $temp_arr['key'] = 'newsomatic_parent_rule1';
        $temp_arr['value'] = $ruleid;
        $q_arr[] = $temp_arr;
        $temp_arr2['key'] = 'newsomatic_parent_type';
        $temp_arr2['value'] = $ruletype;
        $q_arr[] = $temp_arr2;
        $options['meta_query'] = $q_arr;
    }
    elseif($ruleid != '')
    {
        $options['meta_key'] = 'newsomatic_parent_rule1';
        $options['meta_value'] = $ruleid;
    }
    elseif($ruletype != '')
    {
        $options['meta_key'] = 'newsomatic_parent_type';
        $options['meta_value'] = $ruletype;
    }
    
    $query = new WP_Query( $options );
    if ( $query->have_posts() ) { ?>
        <ul class="clothes-listing">
            <?php while ( $query->have_posts() ) : $query->the_post(); ?>
            <li id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <a href="<?php echo esc_url_raw(get_permalink()); ?>"><?php echo esc_html(get_the_title());?></a>
            </li>
            <?php endwhile;
            wp_reset_postdata(); ?>
        </ul>
    <?php $myvariable = ob_get_clean();
    return $myvariable;
    }
    return '';
}

add_action('wp_ajax_newsomatic_my_action', 'newsomatic_my_action_callback');
function newsomatic_my_action_callback()
{
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    $failed                 = false;
    $del_id                 = $_POST['id'];
    $type                   = $_POST['type'];
    $how                    = $_POST['how'];
    if($how == 'duplicate')
    {
        if($type == 1)
        {
            $GLOBALS['wp_object_cache']->delete('newsomatic_all_list', 'options');
            if (!get_option('newsomatic_all_list')) {
                $rules = array();
            } else {
                $rules = get_option('newsomatic_all_list');
            }
            if (!empty($rules)) {
                $found            = 0;
                $cont = 0;
                foreach ($rules as $request => $bundle[]) {
                    if ($cont == $del_id) {
                        $copy_bundle = $rules[$request];
                        $rules[] = $copy_bundle;
                        $found   = 1;
                        break;
                    }
                    $cont = $cont + 1;
                }
                if($found == 0)
                {
                    newsomatic_log_to_file('newsomatic_all_list index not found: ' . $del_id);
                    echo 'nochange';
                    die();
                }
                else
                {
                    update_option('newsomatic_all_list', $rules, false);
                    echo 'ok';
                    die();
                }
            } else {
                newsomatic_log_to_file('newsomatic_all_list empty!');
                echo 'nochange';
                die();
            }
        }
        else
        {
            $GLOBALS['wp_object_cache']->delete('newsomatic_rules_list', 'options');
            if (!get_option('newsomatic_rules_list')) {
                $rules = array();
            } else {
                $rules = get_option('newsomatic_rules_list');
            }
            if (!empty($rules)) {
                $found            = 0;
                $cont = 0;
                foreach ($rules as $request => $bundle[]) {
                    if ($cont == $del_id) {
                        $copy_bundle = $rules[$request];
                        $rules[] = $copy_bundle;
                        $found   = 1;
                        break;
                    }
                    $cont = $cont + 1;
                }
                if($found == 0)
                {
                    newsomatic_log_to_file('newsomatic_rules_list index not found: ' . $del_id);
                    echo 'nochange';
                    die();
                }
                else
                {
                    update_option('newsomatic_rules_list', $rules, false);
                    echo 'ok';
                    die();
                }
            } else {
                newsomatic_log_to_file('newsomatic_rules_list empty!');
                echo 'nochange';
                die();
            }
        } 
    }
    $force_delete           = true;
    $number                 = 0;
    if ($how == 'trash') {
        $force_delete = false;
    }
    $post_list = array();
    $postsPerPage = 50000;
    $paged = 0;
    do
    {
        $postOffset = $paged * $postsPerPage;
        $query = array(
            'post_status' => array(
                'publish',
                'draft',
                'pending',
                'trash',
                'private',
                'future'
            ),
            'post_type' => array(
                'any'
            ),
            'numberposts' => $postsPerPage,
            'meta_key' => 'newsomatic_parent_rule',
            'fields' => 'ids',
            'offset'  => $postOffset
        );
        $got_me = get_posts($query);
        $post_list = array_merge($post_list, $got_me);
        $paged++;
    }while(!empty($got_me));
    wp_suspend_cache_addition(true);
    foreach ($post_list as $post) {
        $index = get_post_meta($post, 'newsomatic_parent_rule', true);
        if ($index == $type . '-' . $del_id) {
            $args             = array(
                'post_parent' => $post
            );
            $post_attachments = get_children($args);
            if (isset($post_attachments) && !empty($post_attachments)) {
                foreach ($post_attachments as $attachment) {
                    wp_delete_attachment($attachment->ID, true);
                }
            }
            $res = wp_delete_post($post, $force_delete);
            if ($res === false) {
                $failed = true;
            } else {
                $number++;
            }
        }
    }
    wp_suspend_cache_addition(false);
    if ($failed === true) {
        if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
            newsomatic_log_to_file('[PostDelete] Failed to delete all posts for rule id: ' . esc_html($del_id) . '!');
        }
        echo 'failed';
    } else {
        if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
            newsomatic_log_to_file('[PostDelete] Successfuly deleted ' . esc_html($number) . ' posts for rule id: ' . esc_html($del_id) . '!');
        }
        if ($number == 0) {
            echo 'nochange';
        } else {
            echo 'ok';
        }
    }
    die();
}

add_action('wp_ajax_newsomatic_activation', 'newsomatic_activation');
function newsomatic_activation()
{
    $code                 = $_POST['code'];
    $nonce                   = $_POST['nonce'];
    if(!wp_verify_nonce( $nonce, 'activation-secret-nonce'))
    {
        echo 'You are not allowed to do this action!';
        die();
    }
    $plugin = plugin_basename(__FILE__);
    $plugin_slug = explode('/', $plugin);
    $plugin_slug = $plugin_slug[0];
    if(strlen(trim($code)) != 36 || strstr($code, '-') == false)
    {
        newsomatic_log_to_file('Invalid registration code submitted: ' . $code);
        echo 'Invalid registration code submitted!';
        die();
    }
    else
    {
        $ch = curl_init('https://wpinitiate.com/verify-purchase/purchase.php');
        if($ch !== false)
        {
            $data           = array();
            $data['code']   = trim($code);
            $data['siteURL']   = get_bloginfo('url');
            $data['siteName']   = get_bloginfo('name');
            $data['siteEmail']   = get_bloginfo('admin_email');
            $fdata = "";
            foreach ($data as $key => $val) {
                $fdata .= "$key=" . urlencode(trim($val)) . "&";
            }
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fdata);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            $result = curl_exec($ch);
            if($result === false)
            {
                curl_close($ch);
                newsomatic_log_to_file('Failed to get verification response: ' . curl_error($ch));
                echo 'Failed to get verification response: ' . curl_error($ch);
                die();
            }
            else
            {
                $rj = json_decode($result, true);
                if(isset($rj['error']))
                {
                    echo $rj['error'];
                    die();
                }
                elseif(isset($rj['item_name']))
                {
                    $rj['code'] = $code;
                    if($rj['item_id'] == '20039739' || $rj['item_id'] == '13371337' || $rj['item_id'] == '19200046')
                    {
                        update_option($plugin_slug . '_registration', $rj);
                    }
                    else
                    {
                        newsomatic_log_to_file('Invalid response from purchase code verification (are you sure you inputted the right purchase code?): ' . print_r($rj, true));
                        echo 'Invalid response from purchase code verification (are you sure you inputted the right purchase code?): ' . print_r($rj, true);
                        die();
                    }
                }
                else
                {
                    newsomatic_log_to_file('Invalid json from purchase code verification: ' . print_r($result, true));
                    echo 'Invalid json from purchase code verification: ' . print_r($result, true);
                    die();
                }
            }
            curl_close($ch);
        }
        else
        {
            newsomatic_log_to_file('Failed to init curl when trying to make purchase verification.');
            echo 'Failed to init curl!';
            die();
        }
    }
    echo 'ok';
    die();
}
add_action('wp_ajax_newsomatic_revoke', 'newsomatic_revoke');
function newsomatic_revoke()
{
    $nonce                   = $_POST['nonce'];
    if(!wp_verify_nonce($nonce, 'activation-secret-nonce'))
    {
        echo 'You are not allowed to do this action!';
        die();
    }
    $plugin = plugin_basename(__FILE__);
    $plugin_slug = explode('/', $plugin);
    $plugin_slug = $plugin_slug[0];
    $ch = curl_init('https://wpinitiate.com/verify-purchase/revoke.php');
    if($ch !== false)
    {
        $data           = array();
        $data['siteURL']   = get_bloginfo('url');

        $purchase_code = '';
        $uoptions = array();
        newsomatic_is_activated($plugin_slug, $uoptions);
        if(isset($uoptions['code']))
        {
            $purchase_code = $uoptions['code'];
        }
        if(!empty($purchase_code))
        {
            $data['purchaseCode']   = $purchase_code;
        }

        $fdata = "";
        foreach ($data as $key => $val) {
            $fdata .= "$key=" . urlencode(trim($val)) . "&";
        }
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $result = curl_exec($ch);
        if($result === false)
        {
            update_option($plugin_slug . '_registration', false);
        }
        else
        {
            update_option($plugin_slug . '_registration', false);
        }
    }
    else
    {
        newsomatic_log_to_file('Failed to init curl to revoke verification response.');
        echo 'Failed to init curl!';
        die();
    }
    echo 'ok';
    die();
}

add_action('wp_ajax_newsomatic_run_my_action', 'newsomatic_run_my_action_callback');
function newsomatic_run_my_action_callback()
{
    $run_id = $_POST['id'];
    $run_type = isset($_POST['type']) ? $_POST['type'] : 0;
    echo newsomatic_run_rule($run_id, $run_type, 0);
    die();
}

function newsomatic_clearFromList($param, $type)
{
    $GLOBALS['wp_object_cache']->delete('newsomatic_running_list', 'options');
    $running = get_option('newsomatic_running_list');
    if($running !== false)
    {
        $key = array_search(array(
            $param => $type
        ), $running);
        if ($key !== FALSE) {
            unset($running[$key]);
            update_option('newsomatic_running_list', $running);
        }
    }
}

function newsomatic_get_random_user_agent() {
	$agents = array(
		"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36",
		"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.101 Safari/537.36",
		"Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36",
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36",
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/603.3.8 (KHTML, like Gecko) Version/10.1.2 Safari/603.3.8",
		"Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.101 Safari/537.36",
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.101 Safari/537.36",
		"Mozilla/5.0 (Windows NT 10.0; WOW64; rv:55.0) Gecko/20100101 Firefox/55.0",
		"Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:55.0) Gecko/20100101 Firefox/55.0",
		"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.90 Safari/537.36",
		"Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko",
		"Mozilla/5.0 (Windows NT 6.1; WOW64; rv:55.0) Gecko/20100101 Firefox/55.0",
		"Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:55.0) Gecko/20100101 Firefox/55.0",
		"Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36",
		"Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36 Edge/15.15063",
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10.12; rv:55.0) Gecko/20100101 Firefox/55.0",
		"Mozilla/5.0 (Windows NT 10.0; WOW64; rv:54.0) Gecko/20100101 Firefox/54.0",
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36",
		"Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36",
		"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.101 Safari/537.36"
	);
	$rand   = rand( 0, count( $agents ) - 1 );
	return trim( $agents[ $rand ] );
}
function newsomatic_get_web_page($url, $custom_cookies = '')
{
	if($url == 'http://null')
	{
		return false;
	}
    $content = false;
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    $delay = '';
    if (isset($newsomatic_Main_Settings['request_delay']) && $newsomatic_Main_Settings['request_delay'] != '') 
    {
        if(stristr($newsomatic_Main_Settings['request_delay'], ',') !== false)
        {
            $tempo = explode(',', $newsomatic_Main_Settings['request_delay']);
            if(isset($tempo[1]) && is_numeric(trim($tempo[1])) && is_numeric(trim($tempo[0])))
            {
                $delay = rand(trim($tempo[0]), trim($tempo[1]));
            }
        }
        else
        {
            if(is_numeric(trim($newsomatic_Main_Settings['request_delay'])))
            {
                $delay = intval(trim($newsomatic_Main_Settings['request_delay']));
            }
        }
    }
    if($delay != '' && is_numeric($delay))
    {
        $GLOBALS['wp_object_cache']->delete('newsomatic_last_time', 'options');
        $last_time = get_option('newsomatic_last_time', false);
        if($last_time !== false && intval(((intval($last_time) - time()) * 1000 + $delay ) * 1000) > 0)
        {
            $sleep_time = intval(((intval($last_time) - time()) * 1000 + $delay ) * 1000);
            if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) 
            {
                newsomatic_log_to_file('Delay between requests set, waiting ' . ($sleep_time/1000) . ' ms');
            }
            if($sleep_time < 21600000)
            {
                usleep($sleep_time);
            }
        }
    }
    if (isset($newsomatic_Main_Settings['user_agent']) && $newsomatic_Main_Settings['user_agent'] != '') {
        $user_agent = $newsomatic_Main_Settings['user_agent'];
    }
    else
    {
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36';
    }
    $ckc = array();
    if($custom_cookies != '')
    {
        if(class_exists('WP_Http_Cookie'))
        {
            if(!function_exists('http_parse_cookie')){
                function http_parse_cookie($szHeader, $object = true){
                    $obj		 = new stdClass;
                    $arrCookie	 = array();
                    $arrObj		 = array();
                    $arrCookie =  explode("\n", $szHeader);
                    for($i = 0; $i<count($arrCookie); $i++){
                        $cookie			 = $arrCookie[$i];
                        $attributes		 = explode(';', $cookie);
                        $arrCookie[$i]	 = array();
                        foreach($attributes as $attrEl){
                            $tmp = explode('=', $attrEl, 2);
                            if(count($tmp)<2){
                                continue;
                            }
                            $key	 = trim($tmp[0]);
                            $value	 = trim($tmp[1]);
                            if($key=='version'||$key=='path'||$key=='expires'||$key=='domain'||$key=='comment'){
                                if(!isset($arrObj[$key])){
                                    $arrObj[$key] = $value;
                                }
                            }else{
                                $arrObj['cookies'][$key] = $value;
                            }
                        }
                    }
                    if($object===true){
                        $obj	 = (object)$arrObj;
                        $return	 = $obj;
                    }else{
                        $return = $arrObj;
                    }
                    return $return;
                }
            }
            $CP = http_parse_cookie($custom_cookies);
            if(isset($CP->cookies))
            {
                foreach ( $CP->cookies as $xname => $xcookie ) {
                    $ckc[] = new WP_Http_Cookie( array( 'name' => $xname, 'value' => $xcookie ) );
                }
            }
        }
    }
    if (!isset($newsomatic_Main_Settings['proxy_url']) || $newsomatic_Main_Settings['proxy_url'] == '' || $newsomatic_Main_Settings['proxy_url'] == 'disable' || $newsomatic_Main_Settings['proxy_url'] == 'disabled') {
        $args = array(
           'timeout'     => 20,
           'redirection' => 10,
           'user-agent'  => $user_agent,
           'blocking'    => true,
           'headers'     => array(),
           'cookies'     => $ckc,
           'body'        => null,
           'compress'    => false,
           'decompress'  => true,
           'sslverify'   => false,
           'stream'      => false,
           'filename'    => null
        );
        $ret_data            = wp_remote_get(html_entity_decode($url), $args);  
        $response_code       = wp_remote_retrieve_response_code( $ret_data );
        $response_message    = wp_remote_retrieve_response_message( $ret_data );
        if($delay != '' && is_numeric($delay))
        {
            update_option('newsomatic_last_time', time());
        }        
        if ( 200 != $response_code ) {
        } else {
            $content = wp_remote_retrieve_body( $ret_data );
        }
    }
    if($content === false)
    {
        if(function_exists('curl_version') && filter_var($url, FILTER_VALIDATE_URL))
        {
            if (isset($newsomatic_Main_Settings['user_agent']) && $newsomatic_Main_Settings['user_agent'] != '') {
                $user_agent = $newsomatic_Main_Settings['user_agent'];
            }
            else
            {
                $user_agent = newsomatic_get_random_user_agent();
            }
            $options    = array(
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_COOKIEJAR => get_temp_dir() . 'newsomaticcookie.txt',
                CURLOPT_COOKIEFILE => get_temp_dir() . 'newsomaticcookie.txt',
                CURLOPT_POST => false,
                CURLOPT_USERAGENT => $user_agent,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_ENCODING => "",
                CURLOPT_AUTOREFERER => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0
            );
            $ch = curl_init($url);
            if ($ch === FALSE) {
                newsomatic_log_to_file('curl not inited: ' . $url);
                $allowUrlFopen = preg_match('/1|yes|on|true/i', ini_get('allow_url_fopen'));
                if ($allowUrlFopen) {
                    global $wp_filesystem;
                if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ){
                    include_once(ABSPATH . 'wp-admin/includes/file.php');$creds = request_filesystem_credentials( site_url() );
                    wp_filesystem($creds);
                }
                return $wp_filesystem->get_contents($url);
                }
            }
            if($custom_cookies != '')
            {
                $headers   = array();
                $headers[] = 'Cookie: ' . $custom_cookies;
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_COOKIE , $custom_cookies);
                unset($options[CURLOPT_COOKIEJAR]);
                unset($options[CURLOPT_COOKIEFILE]);
            }
            if (isset($newsomatic_Main_Settings['proxy_url']) && $newsomatic_Main_Settings['proxy_url'] != '' && $newsomatic_Main_Settings['proxy_url'] != 'disable' && $newsomatic_Main_Settings['proxy_url'] != 'disabled') {
				$prx = explode(',', $newsomatic_Main_Settings['proxy_url']);
                $randomness = array_rand($prx);
                $options[CURLOPT_PROXY] = trim($prx[$randomness]);
                if (isset($newsomatic_Main_Settings['proxy_auth']) && $newsomatic_Main_Settings['proxy_auth'] != '') 
                {
                    $prx_auth = explode(',', $newsomatic_Main_Settings['proxy_auth']);
                    if(isset($prx_auth[$randomness]) && trim($prx_auth[$randomness]) != '')
                    {
                        $options[CURLOPT_PROXYUSERPWD] = trim($prx_auth[$randomness]);
                    }
                }
            }
            if (isset($newsomatic_Main_Settings['custom_ciphers']) && $newsomatic_Main_Settings['custom_ciphers'] != '') {
                curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, $newsomatic_Main_Settings['custom_ciphers']);
            }
            curl_setopt_array($ch, $options);
            
            $content = curl_exec($ch);
            if($delay != '' && is_numeric($delay))
            {
                update_option('newsomatic_last_time', time());
            }
            if($content === false)
            {
                newsomatic_log_to_file('Error occurred in curl: ' . curl_error($ch) . ', url: ' . $url);
                $allowUrlFopen = preg_match('/1|yes|on|true/i', ini_get('allow_url_fopen'));
                if ($allowUrlFopen) {
                    global $wp_filesystem;
                if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ){
                    include_once(ABSPATH . 'wp-admin/includes/file.php');$creds = request_filesystem_credentials( site_url() );
                    wp_filesystem($creds);
                }
                return $wp_filesystem->get_contents($url);
                }
            }
            curl_close($ch);
        }
        else
        {
            $allowUrlFopen = preg_match('/1|yes|on|true/i', ini_get('allow_url_fopen'));
            if ($allowUrlFopen) {
                global $wp_filesystem;
                if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ){
                    include_once(ABSPATH . 'wp-admin/includes/file.php');$creds = request_filesystem_credentials( site_url() );
                    wp_filesystem($creds);
                }
                if($delay != '' && is_numeric($delay))
                {
                    update_option('newsomatic_last_time', time());
                }
                return $wp_filesystem->get_contents($url);
            }
        }
    }
    return $content;
}

function newsomatic_get_web_page_api($url, $headers = false)
{
    $content = false;
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    if (isset($newsomatic_Main_Settings['user_agent']) && $newsomatic_Main_Settings['user_agent'] != '') {
        $user_agent = $newsomatic_Main_Settings['user_agent'];
    }
    else
    {
        $user_agent = 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36';
    }
    $rapidapi = false;
    if($headers == false)
    {
        $headers = array(
            'referer' => home_url()
        );
    }
    else
    {
        $headers['referer'] = home_url();
        $rapidapi = true;
    }
    if (!isset($newsomatic_Main_Settings['proxy_url']) || $newsomatic_Main_Settings['proxy_url'] == '' || $newsomatic_Main_Settings['proxy_url'] == 'disable' || $newsomatic_Main_Settings['proxy_url'] == 'disabled') {
        $args = array(
           'timeout'     => 30,
           'redirection' => 10,
           'user-agent'  => $user_agent,
           'blocking'    => true,
           'headers'     => $headers,
           'cookies'     => array(),
           'body'        => null,
           'compress'    => false,
           'decompress'  => true,
           'sslverify'   => false,
           'stream'      => false,
           'filename'    => null
        );
        $ret_data            = wp_remote_get(html_entity_decode($url), $args);  
        $response_code       = wp_remote_retrieve_response_code( $ret_data );
        $response_message    = wp_remote_retrieve_response_message( $ret_data );        
        if ( 200 != $response_code ) {
        } else {
            $content = wp_remote_retrieve_body( $ret_data );
        }
    }
    if($content === false)
    {
        if(function_exists('curl_version') && filter_var($url, FILTER_VALIDATE_URL))
        {
            if (isset($newsomatic_Main_Settings['user_agent']) && $newsomatic_Main_Settings['user_agent'] != '') {
                $user_agent = $newsomatic_Main_Settings['user_agent'];
            }
            else
            {
                $user_agent = newsomatic_get_random_user_agent();
            }
            $options    = array(
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_COOKIEJAR => get_temp_dir() . 'newsomaticcookie.txt',
                CURLOPT_COOKIEFILE => get_temp_dir() . 'newsomaticcookie.txt',
                CURLOPT_POST => false,
                CURLOPT_USERAGENT => $user_agent,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_REFERER => home_url(),
                CURLOPT_HEADER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_ENCODING => "",
                CURLOPT_AUTOREFERER => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0
            );
            $ch = curl_init($url);
            if ($ch === FALSE) {
                newsomatic_log_to_file('curl not inited: ' . $url);
                $allowUrlFopen = preg_match('/1|yes|on|true/i', ini_get('allow_url_fopen'));
                if ($allowUrlFopen) {
                    if($rapidapi == true)
                    {
                        return false;
                    }
                    global $wp_filesystem;
                    if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ){
                        include_once(ABSPATH . 'wp-admin/includes/file.php');$creds = request_filesystem_credentials( site_url() );
                        wp_filesystem($creds);
                    }
                    return $wp_filesystem->get_contents($url);
                }
            }
            if (isset($newsomatic_Main_Settings['proxy_url']) && $newsomatic_Main_Settings['proxy_url'] != '' && $newsomatic_Main_Settings['proxy_url'] != 'disable' && $newsomatic_Main_Settings['proxy_url'] != 'disabled') {
				$prx = explode(',', $newsomatic_Main_Settings['proxy_url']);
                $randomness = array_rand($prx);
                $options[CURLOPT_PROXY] = trim($prx[$randomness]);
                if (isset($newsomatic_Main_Settings['proxy_auth']) && $newsomatic_Main_Settings['proxy_auth'] != '') 
                {
                    $prx_auth = explode(',', $newsomatic_Main_Settings['proxy_auth']);
                    if(isset($prx_auth[$randomness]) && trim($prx_auth[$randomness]) != '')
                    {
                        $options[CURLOPT_PROXYUSERPWD] = trim($prx_auth[$randomness]);
                    }
                }
            }
            if (isset($newsomatic_Main_Settings['custom_ciphers']) && $newsomatic_Main_Settings['custom_ciphers'] != '') {
                curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, $newsomatic_Main_Settings['custom_ciphers']);
            }
            curl_setopt_array($ch, $options);
            
            $content = curl_exec($ch);
            if($content === false)
            {
                newsomatic_log_to_file('Error occured in curl: ' . curl_error($ch) . ', url: ' . $url);
                $allowUrlFopen = preg_match('/1|yes|on|true/i', ini_get('allow_url_fopen'));
                if ($allowUrlFopen) {
                    if($rapidapi == true)
                    {
                        return false;
                    }
                    global $wp_filesystem;
                    if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ){
                        include_once(ABSPATH . 'wp-admin/includes/file.php');$creds = request_filesystem_credentials( site_url() );
                        wp_filesystem($creds);
                    }
                    return $wp_filesystem->get_contents($url);
                }
            }
            curl_close($ch);
        }
        else
        {
            $allowUrlFopen = preg_match('/1|yes|on|true/i', ini_get('allow_url_fopen'));
            if ($allowUrlFopen) {
                if($rapidapi == true)
                {
                    return false;
                }
                global $wp_filesystem;
                if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ){
                    include_once(ABSPATH . 'wp-admin/includes/file.php');$creds = request_filesystem_credentials( site_url() );
                    wp_filesystem($creds);
                }
                return $wp_filesystem->get_contents($url);
            }
        }
    }
    return $content;
}

function newsomatic_utf8_encode($str)
{
    if(function_exists('mb_detect_encoding') && function_exists('mb_convert_encoding'))
    {
        $enc = mb_detect_encoding($str);
        if ($enc !== FALSE) {
            $str = mb_convert_encoding($str, 'UTF-8', $enc);
        } else {
            $str = mb_convert_encoding($str, 'UTF-8');
        }
    }
    return $str;
}

function newsomatic_strip_images($content)
{
    $content = preg_replace("/<img[^>]+\>/i", "", $content); 
    return $content;
}

function newsomatic_get_full_content($url, $type, $getname, $only_text, $single, $inner, $encoding, $content_percent, $allow_tags, $use_phantom, &$full_html, &$html_dl_failed, $custom_cookies = '')
{
    require_once (dirname(__FILE__) . "/res/simple_html_dom.php"); 
    if($getname == '' && stristr($url, 'cnn.com/') !== false)
    {
        $type = 'class';
        $getname = 'zn-body__paragraph';
        $single = '0';
    }
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    $extract = '';
    $htmlcontent = '';
    $got_phantom = false;
    if($use_phantom == '1')
    {
        $htmlcontent = newsomatic_get_page_PhantomJS($url, $custom_cookies, '', true);
        if($htmlcontent !== false)
        {
            $got_phantom = true;
        }
    }
    elseif($use_phantom == '2')
    {
        $htmlcontent = newsomatic_get_page_Puppeteer($url, $custom_cookies, '', true, '');
        if($htmlcontent !== false)
        {
            $got_phantom = true;
        }
    }
    elseif($use_phantom == '4')
    {
        $htmlcontent = newsomatic_get_page_PuppeteerAPI($url, $custom_cookies, '', true, '', '');
        if($htmlcontent !== false)
        {
            $got_phantom = true;
        }
    }
    elseif($use_phantom == '5')
    {
        $htmlcontent = newsomatic_get_page_TorAPI($url, $custom_cookies, '', true, '', '');
        if($htmlcontent !== false)
        {
            $got_phantom = true;
        }
    }
    elseif($use_phantom == '6')
    {
        $htmlcontent = newsomatic_get_page_PhantomJSAPI($url, $custom_cookies, '', true, '', '');
        if($htmlcontent !== false)
        {
            $got_phantom = true;
        }
    }
    if(stristr($url, 'cbc.ca') !== false)
    {
        if(stristr($htmlcontent, '<title>Access Denied</title>') !== false)
        {
            $got_phantom = false;
        }
    }
    if($got_phantom === false)
    {
        $htmlcontent = newsomatic_get_web_page($url, $custom_cookies);
    }
    if($htmlcontent === FALSE)
    {
        if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
            newsomatic_log_to_file('newsomatic_get_web_page failed for: ' . $url . ', query: ' . $getname . ', type: ' . $type . '!');
        }
        $html_dl_failed = true;
        return false;
    }
    if ($encoding != 'UTF-8' && $encoding != 'NO_CHANGE')
    {
        $extract_temp = FALSE;
        if($encoding !== 'AUTO')
        {
            if(function_exists('iconv'))
            {
                $extract_temp = iconv($encoding, "UTF-8//IGNORE", $htmlcontent);
            }
        }
        else
        {
            if(function_exists('mb_detect_encoding') && function_exists('iconv'))
            {
                $temp_enc = mb_detect_encoding($htmlcontent, 'auto');
                if ($temp_enc !== FALSE && $temp_enc != 'UTF-8')
                {
                    $extract_temp = iconv($temp_enc, "UTF-8//IGNORE", $htmlcontent);
                }
            }
        }
        if($extract_temp !== FALSE)
        {
            $htmlcontent = $extract_temp;
        }
        else
        {
            if($encoding !== 'AUTO')
            {
                if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                    newsomatic_log_to_file('Failed to convert to encoding ' . $encoding);
                }
            }
        }
    }
    $full_html = $htmlcontent;
    if($getname == '' || $type == 'auto')
    {
        $extract = newsomatic_convert_readable_html($htmlcontent);
    }
    else
    {
        if ($type == 'regex') {
            $matches     = array();
            $rezz = preg_match_all($getname, $htmlcontent, $matches);
            if ($rezz === FALSE) {
                if(isset($newsomatic_Main_Settings['skip_no_class']) && $newsomatic_Main_Settings['skip_no_class'] == 'on')
                {
                    if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                        newsomatic_log_to_file('Full content preg_match_all failed for expr: ' . $getname . '!');
                    }
                    return false;
                }
                $extract = newsomatic_convert_readable_html($htmlcontent);
            }
            if ($inner == '1')
            {
                if(isset($matches[1][0]))
                {
                    foreach ($matches[1] as $match) {
                        $extract .= $match;
                        if ($single == '1') {
                            break;
                        }
                    }
                }
            }
            else
            {
                if(isset($matches[0][0]))
                {
                    foreach ($matches[0] as $match) {
                        $extract .= $match;
                        if ($single == '1') {
                            break;
                        }
                    }
                }
            }
        } elseif ($type == 'xpath' || $type == 'visual') {
            $extractok = false;
            $html_dom_original_html = newsomatic_str_get_html($htmlcontent);
            if(stristr($getname, ' or ') === false && $html_dom_original_html !== false && method_exists($html_dom_original_html, 'find')){
                $ret = $html_dom_original_html->find( trim($getname) );
                foreach ($ret as $item ) {
                    $extractok = true;
                    if($inner == '1'){
                        $extract = $extract . $item->innertext ;
                    }else{
                        $extract = $extract . $item->outertext ;
                    }
                    if ($single == '1') {
                        break;
                    }		
                }
                $html_dom_original_html->clear();
                unset($html_dom_original_html);
            }
            if($extractok == false)
            {
                $doc = new DOMDocument;
                $internalErrors = libxml_use_internal_errors(true);
                $doc->loadHTML('<?xml encoding="utf-8" ?>' . $htmlcontent);
                libxml_use_internal_errors($internalErrors);
                $xpath = new \DOMXpath($doc);
                $articles = $xpath->query(trim($getname));
                if($articles !== false && $articles->length > 0)
                {
                    foreach($articles as $container) {
                        if(method_exists($container, 'saveHTML'))
                        {
                            $extract .= $container->saveHTML();
                        }
                        elseif(isset($container->ownerDocument) && method_exists($container->ownerDocument, 'saveHTML'))
                        {
                            $extract .= $container->ownerDocument->saveHTML($container);
                        }
                        elseif(isset($container->nodeValue))
                        {
                            $extract .= $container->nodeValue;
                        }
                    }
                }
            }
        } elseif ($type == 'class' && strstr(trim($getname), ' ') === false) {
            $extractok = false;
            $html_dom_original_html = newsomatic_str_get_html($htmlcontent);
            if($html_dom_original_html !== false && method_exists($html_dom_original_html, 'find')){
                $getnames = explode(',', $getname);
                foreach($getnames as $gname)
                {
                    $ret = $html_dom_original_html->find( '//*[contains(@class, "' . trim($gname) . '")]' );
                    foreach ($ret as $item ) {
                        $extractok = true;
                        if($inner == '1'){
                            $extract = $extract . $item->innertext ;
                        }else{
                            $extract = $extract . $item->outertext ;
                        }
                        if ($single == '1') {
                            break;
                        }		
                    }
                }
                $html_dom_original_html->clear();
                unset($html_dom_original_html);
            }
            if($extractok == false)
            {
                $doc = new DOMDocument;
                $internalErrors = libxml_use_internal_errors(true);
                $doc->loadHTML('<?xml encoding="utf-8" ?>' . $htmlcontent);
                libxml_use_internal_errors($internalErrors);
                $xpath = new \DOMXpath($doc);
                $getnames = explode(',', $getname);
                foreach($getnames as $gname)
                {
                    $articles = $xpath->query(trim('//*[contains(@class, "' . $gname . '")]'));
                    if($articles === false || $articles->length == 0)
                    {
                        $articles = $xpath->query('//*[@'.$type.'="'.trim($gname).'"]');
                    }
                    if($articles !== false && $articles->length > 0)
                    {
                        foreach($articles as $container) {
                            if(method_exists($container, 'saveHTML'))
                            {
                                $extract .= $container->saveHTML();
                            }
                            elseif(isset($container->ownerDocument) && method_exists($container->ownerDocument, 'saveHTML'))
                            {
                                $extract .= $container->ownerDocument->saveHTML($container);
                            }
                            elseif(isset($container->nodeValue))
                            {
                                $extract .= $container->nodeValue;
                            }
                        }
                    }
                }
            }
        } else {
            $extractok = false;
            $html_dom_original_html = newsomatic_str_get_html($htmlcontent);
            if($html_dom_original_html !== false && method_exists($html_dom_original_html, 'find')){
                $getnames = explode(',', $getname);
                foreach($getnames as $gname)
                {
                    $ret = $html_dom_original_html->find('//*[@'.$type.'="'.trim($gname).'"]');
                    foreach ($ret as $item ) {
                        $extractok = true;
                        if($inner == '1'){
                            $extract = $extract . $item->innertext ;
                        }else{
                            $extract = $extract . $item->outertext ;
                        }
                        if ($single == '1') {
                            break;
                        }		
                    }
                }
                $html_dom_original_html->clear();
                unset($html_dom_original_html);
            }
            if($extractok == false)
            {
                $doc = new DOMDocument;
                $internalErrors = libxml_use_internal_errors(true);
                $doc->loadHTML('<?xml encoding="utf-8" ?>' . $htmlcontent);
                libxml_use_internal_errors($internalErrors);
                $xpath = new \DOMXpath($doc);
                $getnames = explode(',', $getname);
                foreach($getnames as $gname)
                {
                    $articles = $xpath->query('//*[@'.$type.'="'.trim($gname).'"]');
                    if($articles !== false && $articles->length > 0)
                    {
                        foreach($articles as $container) {
                            if(method_exists($container, 'saveHTML'))
                            {
                                $extract .= $container->saveHTML();
                            }
                            elseif(isset($container->ownerDocument) && method_exists($container->ownerDocument, 'saveHTML'))
                            {
                                $extract .= $container->ownerDocument->saveHTML($container);
                            }
                            elseif(isset($container->nodeValue))
                            {
                                $extract .= $container->nodeValue;
                            }
                        }
                    }
                }
            }
        }
        if($extract == '')
        {
            if(isset($newsomatic_Main_Settings['skip_no_class']) && $newsomatic_Main_Settings['skip_no_class'] == 'on')
            {
                return 'skip_me';
            }
            $extract = newsomatic_convert_readable_html($htmlcontent);
        }
    }
    
    $my_url  = parse_url($url);
	$my_host = $my_url['host'];
    preg_match_all('{src[\s]*=[\s]*["|\'](.*?)["|\'].*?>}is', $extract , $matches);
	$img_srcs =  ($matches[1]);
	foreach ($img_srcs as $img_src){
		$original_src = $img_src;
        if(stristr($img_src, '../')){
			$img_src = str_replace('../', '', $img_src);
		}
		if(stristr($img_src, 'http:') === FALSE && stristr($img_src, 'www.') === FALSE && stristr($img_src, 'https:') === FALSE && stristr($img_src, 'data:image') === FALSE)
		{
			$img_src = trim($img_src);
			if(preg_match('{^//}', $img_src)){
				$img_src = 'http:'.$img_src;
			}elseif( preg_match('{^/}', $img_src) ){
				$img_src = 'http://'.$my_host.$img_src;
			}else{
				$img_src = 'http://'.$my_host.'/'.$img_src;
			}
			$reg_img = '{["|\'][\s]*'.preg_quote($original_src,'{').'[\s]*["|\']}s';
            $extract = preg_replace( $reg_img, '"'.$img_src.'"', $extract);
		}
	}
    
    preg_match_all('{href[\s]*=[\s]*["\'](.*?)["\']}is', $extract , $matches);
	$link_srcs =  ($matches[1]);
	foreach ($link_srcs as $link_src){
		$original_src = $link_src;
        if(stristr($link_src, '../')){
			$link_src = str_replace('../', '', $link_src);
		}
		if(stristr($link_src, 'http:') === FALSE && stristr($link_src, 'www.') === FALSE && stristr($link_src, 'https:') === FALSE)
		{
			$link_src = trim($link_src);
			if(preg_match('{^//}', $link_src)){
				$link_src = 'http:'.$link_src;
			}elseif( preg_match('{^/}', $link_src) ){
				$link_src = 'http://'.$my_host.$link_src;
			}else{
				$link_src = 'http://'.$my_host.'/'.$link_src;
			}
			$reg_img = '{["|\'][\s]*'.preg_quote($original_src,'{').'[\s]*["|\']}s';
            $extract = preg_replace( $reg_img, '"'.$link_src.'"', $extract);
		}
	}
    
    $extract = str_replace('href="../', 'href="http://'.$my_host.'/', $extract);
	$extract = preg_replace('{href="/(\w)}', 'href="http://'.$my_host.'/$1', $extract);
    $extract = preg_replace('{\ssrcset=".*?"}', ' ', $extract);
	$extract = preg_replace('{\ssizes=".*?"}', ' ', $extract);
    //$extract = html_entity_decode($extract, ENT_COMPAT | ENT_HTML5) ;
    if (isset($newsomatic_Main_Settings['strip_scripts']) && $newsomatic_Main_Settings['strip_scripts'] == 'on') {
        $extract = preg_replace('{<ins.*?ins>}s', '', $extract);
        $extract = preg_replace('{<ins.*?>}s', '', $extract);
        $extract = preg_replace('{<script[\s\S]*?\/\s?script>}s', '', $extract);
        $extract = preg_replace('{\(adsbygoogle.*?\);}s', '', $extract);
    }
    if ($only_text == '1') {
        $striphtml = newsomatic_strip_html_tags_nl($extract, $allow_tags);
        if($content_percent != '' && is_numeric($content_percent) && $content_percent != 100)
        {
            $str_count = strlen($striphtml);
            $leave_cont = round($str_count * $content_percent / 100);
            $striphtml = substr($striphtml, 0, $leave_cont);
        }
        return $striphtml;
    } else {
        if($content_percent != '' && is_numeric($content_percent) && $content_percent != 100)
        {
            $str_count = strlen($extract);
            $leave_cont = round($str_count * $content_percent / 100);
            $extract = newsomatic_substr_close_tags($extract, $leave_cont);
        }
        return $extract;
    }
}
use fivefilters\Readability\Readability;
use fivefilters\Readability\Configuration;

use function PHPSTORM_META\map;

function newsomatic_convert_readable_html($html_string) {
    if(!class_exists('\fivefilters\Readability\Readability'))
    {
        require_once (dirname(__FILE__) . "/res/readability/vendor/autoload.php");

        require_once (dirname(__FILE__) . "/res/readability/Readability.php");
        require_once (dirname(__FILE__) . "/res/readability/ParseException.php");
        require_once (dirname(__FILE__) . "/res/readability/Configuration.php");
        require_once (dirname(__FILE__) . "/res/readability/Nodes/NodeUtility.php");
        require_once (dirname(__FILE__) . "/res/readability/Nodes/NodeTrait.php");
        require_once (dirname(__FILE__) . "/res/readability/Nodes/DOM/DOMAttr.php");
        require_once (dirname(__FILE__) . "/res/readability/Nodes/DOM/DOMCdataSection.php");
        require_once (dirname(__FILE__) . "/res/readability/Nodes/DOM/DOMCharacterData.php");
        require_once (dirname(__FILE__) . "/res/readability/Nodes/DOM/DOMComment.php");
        require_once (dirname(__FILE__) . "/res/readability/Nodes/DOM/DOMDocument.php");
        require_once (dirname(__FILE__) . "/res/readability/Nodes/DOM/DOMDocumentFragment.php");
        require_once (dirname(__FILE__) . "/res/readability/Nodes/DOM/DOMDocumentType.php");
        require_once (dirname(__FILE__) . "/res/readability/Nodes/DOM/DOMElement.php");
        require_once (dirname(__FILE__) . "/res/readability/Nodes/DOM/DOMEntity.php");
        require_once (dirname(__FILE__) . "/res/readability/Nodes/DOM/DOMEntityReference.php");
        require_once (dirname(__FILE__) . "/res/readability/Nodes/DOM/DOMNodeList.php");
        require_once (dirname(__FILE__) . "/res/readability/Nodes/DOM/DOMNode.php");
        require_once (dirname(__FILE__) . "/res/readability/Nodes/DOM/DOMNotation.php");
        require_once (dirname(__FILE__) . "/res/readability/Nodes/DOM/DOMProcessingInstruction.php");
        require_once (dirname(__FILE__) . "/res/readability/Nodes/DOM/DOMText.php");
    }
    try {
        $readConf = new Configuration();
        $readConf->setSummonCthulhu(true);
        $readability = new Readability($readConf);
        $readability->parse($html_string);
        $return_me = $readability->getContent();
        if($return_me == '' || $return_me == null)
        {
            throw new Exception('Content blank');
        }
        $return_me = str_replace('</article>', '', $return_me);
        $return_me = str_replace('<article>', '', $return_me);
        return $return_me;
    } catch (Exception $e) {
        try
        {
            require_once (dirname(__FILE__) . "/res/newsomatic-readability.php");
            $readability = new Readability2($html_string);
            $readability->debug = false;
            $readability->convertLinksToFootnotes = false;
            $result = $readability->init();
            if ($result) {
                $content = $readability->getContent()->innerHTML;
                $content = str_replace('</article>', '', $content);
                $content = str_replace('<article>', '', $content);
                return $content;
            } else {
                return '';
            }
        }
        catch(Exception $e2)
        {
            newsomatic_log_to_file('Readability failed: ' . sprintf('Error processing text: %s', $e2->getMessage()));
            return '';
        }
    }
}

function newsomatic_substr_close_tags($text, $max_length)
{
    $tags   = array();
    $result = "";

    $is_open   = false;
    $grab_open = false;
    $is_close  = false;
    $in_double_quotes = false;
    $in_single_quotes = false;
    $tag = "";

    $i = 0;
    $stripped = 0;

    $stripped_text = strip_tags($text);
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        while ($i < mb_strlen($text) && $stripped < mb_strlen($stripped_text) && $stripped < $max_length)
        {
            $symbol  = mb_substr($text,$i,1);
            $result .= $symbol;

            switch ($symbol)
            {
               case '<':
                    $is_open   = true;
                    $grab_open = true;
                    break;

               case '"':
                   if ($in_double_quotes)
                       $in_double_quotes = false;
                   else
                       $in_double_quotes = true;

                break;

                case "'":
                  if ($in_single_quotes)
                      $in_single_quotes = false;
                  else
                      $in_single_quotes = true;

                break;

                case '/':
                    if ($is_open && !$in_double_quotes && !$in_single_quotes)
                    {
                        $is_close  = true;
                        $is_open   = false;
                        $grab_open = false;
                    }

                    break;

                case ' ':
                    if ($is_open)
                        $grab_open = false;
                    else
                        $stripped++;

                    break;

                case '>':
                    if ($is_open)
                    {
                        $is_open   = false;
                        $grab_open = false;
                        array_push($tags, $tag);
                        $tag = "";
                    }
                    else if ($is_close)
                    {
                        $is_close = false;
                        array_pop($tags);
                        $tag = "";
                    }

                    break;

                default:
                    if ($grab_open || $is_close)
                        $tag .= $symbol;

                    if (!$is_open && !$is_close)
                        $stripped++;
            }
            $i++;
        }
    }
    else
    {
        while ($i < strlen($text) && $stripped < strlen($stripped_text) && $stripped < $max_length)
        {
            $symbol  = $text[$i];
            $result .= $symbol;

            switch ($symbol)
            {
               case '<':
                    $is_open   = true;
                    $grab_open = true;
                    break;

               case '"':
                   if ($in_double_quotes)
                       $in_double_quotes = false;
                   else
                       $in_double_quotes = true;

                break;

                case "'":
                  if ($in_single_quotes)
                      $in_single_quotes = false;
                  else
                      $in_single_quotes = true;

                break;

                case '/':
                    if ($is_open && !$in_double_quotes && !$in_single_quotes)
                    {
                        $is_close  = true;
                        $is_open   = false;
                        $grab_open = false;
                    }

                    break;

                case ' ':
                    if ($is_open)
                        $grab_open = false;
                    else
                        $stripped++;

                    break;

                case '>':
                    if ($is_open)
                    {
                        $is_open   = false;
                        $grab_open = false;
                        array_push($tags, $tag);
                        $tag = "";
                    }
                    else if ($is_close)
                    {
                        $is_close = false;
                        array_pop($tags);
                        $tag = "";
                    }

                    break;

                default:
                    if ($grab_open || $is_close)
                        $tag .= $symbol;

                    if (!$is_open && !$is_close)
                        $stripped++;
            }
            $i++;
        }
    }

    while ($tags)
        $result .= "</".array_pop($tags).">";
    return force_balance_tags($result);
}
function newsomatic_my_list_cats() {
    $catsArray = array();
    $cat_args   = array(
        'orderby' => 'name',
        'hide_empty' => 0,
        'order' => 'ASC'
    );
    $categories = get_categories($cat_args);
    foreach($categories as $cat) {
        $catsArray[] = $cat->name;
    }
    return $catsArray;
}
function newsomatic_my_user_by_rand( $ua ) {
  remove_action('pre_user_query', 'newsomatic_my_user_by_rand');
  $ua->query_orderby = str_replace( 'user_login ASC', 'RAND()', $ua->query_orderby );
}

function newsomatic_display_random_user(){
  add_action('pre_user_query', 'newsomatic_my_user_by_rand');
  $args = array(
    'orderby' => 'user_login', 'order' => 'ASC', 'number' => 1, 'role__in' => array( 'contributor','author','editor','administrator','super-admin' )
  );
  $user_query = new WP_User_Query( $args );
  $user_query->query();
  $results = $user_query->results;
  if(empty($results))
  {
      return false;
  }
  shuffle($results);
  return array_pop($results);
}

function newsomatic_get_positions($needle, $haystack)
{
    preg_match_all('#' . $needle . '#', $haystack, $m, PREG_OFFSET_CAPTURE);
    return $m;
}

function newsomatic_url_handle($href, $api_key)
{
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    if (isset($newsomatic_Main_Settings['links_hide']) && $newsomatic_Main_Settings['links_hide'] == 'on') {
        $cloak_urls = true;
    } else {
        $cloak_urls = false;
    }
    if (isset($newsomatic_Main_Settings['apiKey'])) {
        $apiKey = trim($newsomatic_Main_Settings['apiKey']);
    } else {
        $apiKey = '';
    }
    if ($cloak_urls == true && $apiKey != '') {
        $newsomatic_short_group = get_option('newsomatic_short_group', false);
        $found = false;
        if($newsomatic_short_group !== false)
        {
            $newsomatic_short_group = explode('#', $newsomatic_short_group);
            if(isset($newsomatic_short_group[1]) && $newsomatic_short_group[0] == $apiKey)
            {
                $newsomatic_short_group = $newsomatic_short_group[1];
                $found = true;
            }
        }
        if($found == false)
        {
            $url = 'https://api-ssl.Bitly.com/v4/groups';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            $headers = [
                'Authorization: Bearer ' . $apiKey,
                'Accept: application/json',
                'Host: api-ssl.Bitly.com'
            ];
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $serverOutput = json_decode(curl_exec($ch), true);
            curl_close($ch);
            if(isset($serverOutput['groups'][0]['guid']))
            {
                $newsomatic_short_group = $serverOutput['groups'][0]['guid'];
                update_option('newsomatic_short_group', false);
                $found = true;
            }
        }
        if($found == false)
        {
            return $href;
        }
        $url = 'https://api-ssl.Bitly.com/v4/shorten';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'Host: api-ssl.Bitly.com'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        $fdata = "";
        $data['long_url'] = trim($href);
        $data['group_guid'] = $newsomatic_short_group;
        $fdata = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fdata);
        $serverOutput = json_decode(curl_exec($ch), true);
        curl_close($ch);
        if (!isset($serverOutput['link']) || $serverOutput['link'] == '') {
            return $href;
        } else {
            return esc_url_raw($serverOutput['link']);
        }  
    }
    else 
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.shorte.st/v1/data/url");
        curl_setopt($ch, CURLOPT_POSTFIELDS, "urlToShorten=" . trim($href));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $headers = [
            'public-api-token: ' . $api_key,
            'Content-Type: application/x-www-form-urlencoded'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        $serverOutput = json_decode(curl_exec($ch), true);
        curl_close($ch);
        if (!isset($serverOutput['shortenedUrl']) || $serverOutput['shortenedUrl'] == '') {
            return $href;
        } else {
            return esc_url_raw($serverOutput['shortenedUrl']);
        }
    }
}

function newsomatic_get_featured_image($content, $xfull_html, $html_dl_failed, $image_type, $image_expre, $use_phantom, $lazy_tag, $url, $get_img, $custom_cookies = '')
{
    $biggest_img = '';
    if($image_expre != '' && $image_type != 'auto' && $image_type != '' && $url !== '')
    {
        if($html_dl_failed == false && ($xfull_html == false || $xfull_html == ''))
        {
            $xfull_html = newsomatic_get_web_page($url, $custom_cookies);
            if($xfull_html == false || $xfull_html == '')
            {
                $html_dl_failed = true;
            }
        }
        if($xfull_html != FALSE && $xfull_html != '')
        {
            $biggest_img_text = '';
            if ($image_type == 'regex') {
                $matches     = array();
                $rez = preg_match_all($image_expre, $xfull_html, $matches);
                if ($rez === FALSE) {
                    newsomatic_log_to_file('[newsomatic get full content] preg_match_all failed for image_expr: ' . $image_expre);
                }
                if(isset($matches[1][0]))
                {
                    foreach ($matches[1] as $match) {
                        $biggest_img = $match; 
                        break;
                    }
                }
                elseif(isset($matches[0][0]))
                {
                    foreach ($matches[0] as $match) {
                        $biggest_img_text = $match; 
                        if($biggest_img_text != '')
                        {
                            break;
                        }
                    }
                }
            } 
            elseif ($image_type == 'xpath' || $image_type == 'visual') 
            {
                $extractok = false;
                require_once (dirname(__FILE__) . "/res/simple_html_dom.php");
                $html_dom_original_html = newsomatic_str_get_html($xfull_html);
                if($html_dom_original_html !== false && method_exists($html_dom_original_html, 'find')){
                    $ret = $html_dom_original_html->find( trim($image_expre) );
                    foreach ($ret as $citem ) {
                        $extractok = true;
                        $biggest_img_text = $citem->outertext;
                        if($biggest_img_text != '')
                        {
                            break;
                        }
                    }
                    $html_dom_original_html->clear();
                    unset($html_dom_original_html);
                }
                if($extractok == false)
                {
                    $doc = new DOMDocument;
                    $internalErrors = libxml_use_internal_errors(true);
                    $doc->loadHTML('<?xml encoding="utf-8" ?>' . $xfull_html);
                    libxml_use_internal_errors($internalErrors);
                    $xpath = new \DOMXpath($doc);
                    $articles = $xpath->query(trim($image_expre));
                    if($articles !== false && $articles->length > 0)
                    {
                        foreach($articles as $container) {
                            if(method_exists($container, 'saveHTML'))
                            {
                                $biggest_img_text = $container->saveHTML();
                            }
                            elseif(isset($container->ownerDocument) && method_exists($container->ownerDocument, 'saveHTML'))
                            {
                                $biggest_img_text = $container->ownerDocument->saveHTML($container);
                            }
                            elseif(isset($container->nodeValue))
                            {
                                $biggest_img_text = $container->nodeValue;
                            }
                            if($biggest_img_text != '')
                            {
                                break;
                            }
                        }
                    }
                }
            } else {
                $extractok = false;
                require_once (dirname(__FILE__) . "/res/simple_html_dom.php");
                $html_dom_original_html = newsomatic_str_get_html($xfull_html);
                if($html_dom_original_html !== false && method_exists($html_dom_original_html, 'find')){
                    $getnames = explode(',', $image_expre);
                    foreach($getnames as $gname)
                    {
                        $ret = $html_dom_original_html->find('*['.$image_type.'="'.trim($gname).'"]');
                        foreach ($ret as $item ) {
                            $extractok = true;
                            $biggest_img_text =$item->outertext;
                            if($biggest_img_text != '')
                            {
                                break;
                            }
                        }
                    }
                    $html_dom_original_html->clear();
                    unset($html_dom_original_html);
                }
                if($extractok == false)
                {
                    $doc = new DOMDocument;
                    $internalErrors = libxml_use_internal_errors(true);
                    $doc->loadHTML('<?xml encoding="utf-8" ?>' . $xfull_html);
                    libxml_use_internal_errors($internalErrors);
                    $xpath = new \DOMXpath($doc);
                    $getnames = explode(',', $image_expre);
                    foreach($getnames as $gname)
                    {
                        $articles = $xpath->query('//*[@'.$image_type.'="'.trim($gname).'"]');
                        if($articles !== false && $articles->length > 0)
                        {
                            foreach($articles as $container) {
                                if(method_exists($container, 'saveHTML'))
                                {
                                    $biggest_img_text = $container->saveHTML();
                                }
                                elseif(isset($container->ownerDocument) && method_exists($container->ownerDocument, 'saveHTML'))
                                {
                                    $biggest_img_text = $container->ownerDocument->saveHTML($container);
                                }
                                elseif(isset($container->nodeValue))
                                {
                                    $biggest_img_text = $container->nodeValue;
                                }
                                if($biggest_img_text != '')
                                {
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            if($biggest_img_text !== '')
            {
                $tmpdoc = new DOMDocument();
                $internalErrors = libxml_use_internal_errors(true);
                $tmpdoc->loadHTML($biggest_img_text);
                libxml_use_internal_errors($internalErrors);
                $imageTags = $tmpdoc->getElementsByTagName('img');
                if(count($imageTags) > 0)
                {
                    if($imageTags[0] !== null)
                    {
                        if($lazy_tag == '')
                        {
                            $lazy_tag = 'src';
                        }
                        $za_lazy_tags = explode(',', trim($lazy_tag));
                        foreach($za_lazy_tags as $za_lazy_tag)
                        {
                            $biggest_img = $imageTags[0]->getAttribute(trim($za_lazy_tag));
                            if($biggest_img != '')
                            {
                                break;
                            }
                        }
                        if($biggest_img == '' && $lazy_tag != 'src')
                        {
                            $biggest_img = $imageTags[0]->getAttribute('src');
                        }
                        if($biggest_img == '')
                        {
                            preg_match('@src=["\']([^"\']+)["\']@i', $biggest_img_text, $match);
                            if(isset($match[1]) && $match[1] != '')
                            {
                                $biggest_img = $match[1];
                            }
                        }
                    }
                    else
                    {
                        preg_match('@src=["\']([^"\']+)["\']@i', $biggest_img_text, $match);
                        if(isset($match[1]) && $match[1] != '')
                        {
                            $biggest_img = $match[1];
                        }
                    }
                }
            }
            if($biggest_img == '' && $get_img != '')
            {
                return $get_img;
            }
        }
    }
    if($biggest_img == '' && $xfull_html !== false)
    {
        preg_match('{<meta[^<]*?property\s*=["|\']og:image(?::secure_url)?["|\'][^<]*?>}i', $xfull_html, $mathc);
        if(isset($mathc[0]) && stristr($mathc[0], 'og:image')){
            preg_match('{content=["|\'](.*?)["|\']}s', $mathc[0],$matx);
            if(isset($matx[1]))
            {
                $og_img = $matx[1];
                if(trim($og_img) !='')
                {
                    return $og_img;
                }
            }
        }
        preg_match('{<meta[^<]*?property\s*=["|\']twitter:image["|\'][^<]*?>}i', $xfull_html, $mathc);
        if(isset($mathc[0]) && stristr($mathc[0], 'twitter:image')){
            preg_match('{content=["|\'](.*?)["|\']}s', $mathc[0],$matx);
            if(isset($matx[1]))
            {
                $og_img = $matx[1];
                if(trim($og_img) !='')
                {
                    return $og_img;
                }
            }
        }
        preg_match('{[\'"]]thumbnailUrl[\'"]\s*:\s*[\'"]([^\'"]+)[\'"]}i', $xfull_html, $mathc);
        if(isset($mathc[1][0]))
        {
            $og_img = $mathc[1][0];
            if(trim($og_img) !='')
            {
                return $og_img;
            }
        }
        preg_match('{[\'"]@type[\'"]:[\'"]ImageObject[\'"],[\'"]url[\'"]:[\'"]([^\'"]+)[\'"]}i', $xfull_html, $mathc);
        if(isset($mathc[1][0]))
        {
            $og_img = $mathc[1][0];
            if(trim($og_img) !='')
            {
                return $og_img;
            }
        }
        preg_match('{<meta[^<]*?itemprop\s*=["\']thumbnailUrl["\'][^<]*?>}i', $xfull_html, $mathc);
        if(isset($mathc[0]) && stristr($mathc[0], 'content=')){
            preg_match('{content=["|\'](.*?)["|\']}s', $mathc[0],$matx);
            if(isset($matx[1]))
            {
                $og_img = $matx[1];
                if(trim($og_img) !='')
                {
                    return $og_img;
                }
            }
        }
        preg_match('{<meta[^<]*?name\s*=["\']thumbnail["\'][^<]*?>}i', $xfull_html, $mathc);
        if(isset($mathc[0]) && stristr($mathc[0], 'content=')){
            preg_match('{content=["|\'](.*?)["|\']}s', $mathc[0],$matx);
            if(isset($matx[1]))
            {
                $og_img = $matx[1];
                if(trim($og_img) !='')
                {
                    return $og_img;
                }
            }
        }
        preg_match('{<meta[^<]*?itemprop\s*=["\']image["\'][^<]*?>}i', $xfull_html, $mathc);
        if(isset($mathc[0]) && stristr($mathc[0], 'content=')){
            preg_match('{content=["|\'](.*?)["|\']}s', $mathc[0],$matx);
            if(isset($matx[1]))
            {
                $og_img = $matx[1];
                if(trim($og_img) !='')
                {
                    return $og_img;
                }
            }
        }
    }
    if($biggest_img == '' && $content != '' && $content != 'skip_me')
    {
        $doc = new DOMDocument();
        $internalErrors = libxml_use_internal_errors(true);
        $doc->loadHTML($content);
                        libxml_use_internal_errors($internalErrors);
        $tags    = $doc->getElementsByTagName('img');
        $maxSize = 0;
        foreach ($tags as $tag) {
            $temp_get_img = $tag->getAttribute('src');
            if ($temp_get_img != '') {
                $temp_get_img = strtok($temp_get_img, '?');
                $temp_get_img   = rtrim($temp_get_img, '/');
                error_reporting(0);
                $image=getimagesize($temp_get_img);
                error_reporting(E_ALL);
                if(isset($image[0]) && isset($image[1]) && is_numeric($image[0]) && is_numeric($image[1]))
                {
                    if (($image[0] * $image[1]) > $maxSize) {   
                        $maxSize = $image[0] * $image[1]; 
                        $biggest_img = $temp_get_img;
                    }
                }
                else
                {
                    $image = newsomatic_getimgsize($temp_get_img);
                    if(isset($image[0]) && isset($image[1]) && is_numeric($image[0]) && is_numeric($image[1]))
                    {
                        if (($image[0] * $image[1]) > $maxSize) {   
                            $maxSize = $image[0] * $image[1]; 
                            $biggest_img = $temp_get_img;
                        }
                    }
                }
            }
        }
    }
    return $biggest_img;
}
function newsomatic_getimgsize($url, $referer = 'https://www.google.com')
{
    if(!function_exists('imagecreatefromstring'))
    {
        return false;
    }
    
    $headers = array(
        'Range: bytes=0-32768'
    );
    if (!empty($referer)) array_push($headers, 'Referer: '.$referer);
    $curl = curl_init($url);
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    if (isset($newsomatic_Main_Settings['proxy_url']) && $newsomatic_Main_Settings['proxy_url'] != '' && $newsomatic_Main_Settings['proxy_url'] != 'disable' && $newsomatic_Main_Settings['proxy_url'] != 'disabled') {
		$prx = explode(',', $newsomatic_Main_Settings['proxy_url']);
        $randomness = array_rand($prx);
        curl_setopt( $curl, CURLOPT_PROXY, trim($prx[$randomness]));
        if (isset($newsomatic_Main_Settings['proxy_auth']) && $newsomatic_Main_Settings['proxy_auth'] != '') 
        {
            $prx_auth = explode(',', $newsomatic_Main_Settings['proxy_auth']);
            if(isset($prx_auth[$randomness]) && trim($prx_auth[$randomness]) != '')
            {
                curl_setopt( $curl, CURLOPT_PROXYUSERPWD, trim($prx_auth[$randomness]) );
            }
        }
    }
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    $data = curl_exec($curl);
    curl_close($curl);
    if($data == false)
    {
        return false;
    }
    error_reporting(0);
    $image = imagecreatefromstring($data);
    if($image === false)
    {
        return false;
    }
    $return = array(imagesx($image), imagesy($image));

    imagedestroy($image);
    error_reporting(E_ALL);
    return $return;
}
function newsomatic_replaceSynergyShortcodes($the_content)
{
    $regex = '#%%([a-z0-9]+?)(?:_title)?_(\d+?)_(\d+?)%%#';
    $rezz = preg_match_all($regex, $the_content, $matches);
    if ($rezz === FALSE) {
        return $the_content;
    }
    if(isset($matches[1][0]))
    {
        $two_var_functions = array('pdfomatic');
        $three_var_functions = array('bhomatic', 'crawlomatic', 'dmomatic', 'ezinomatic', 'fbomatic', 'flickomatic', 'imguromatic', 'iui', 'instamatic', 'linkedinomatic', 'mediumomatic', 'pinterestomatic', 'echo', 'spinomatic', 'tumblomatic', 'wordpressomatic', 'wpcomomatic', 'youtubomatic', 'mastermind', 'businessomatic');
        $four_var_functions = array('aiomatic', 'contentomatic', 'quoramatic', 'newsomatic', 'aliomatic', 'amazomatic', 'blogspotomatic', 'bookomatic', 'careeromatic', 'cbomatic', 'cjomatic', 'craigomatic', 'ebayomatic', 'etsyomatic', 'rakutenomatic', 'learnomatic', 'eventomatic', 'gameomatic', 'gearomatic', 'giphyomatic', 'gplusomatic', 'hackeromatic', 'imageomatic', 'midas', 'movieomatic', 'nasaomatic', 'ocartomatic', 'okomatic', 'playomatic', 'recipeomatic', 'redditomatic', 'soundomatic', 'mp3omatic', 'ticketomatic', 'tmomatic', 'trendomatic', 'tuneomatic', 'twitchomatic', 'twitomatic', 'vimeomatic', 'viralomatic', 'vkomatic', 'walmartomatic', 'bestbuyomatic', 'wikiomatic', 'xlsxomatic', 'yelpomatic', 'yummomatic');
        for ($i = 0; $i < count($matches[1]); $i++)
        {
            $replace_me = false;
            if(in_array($matches[1][$i], $four_var_functions))
            {
                $za_function = $matches[1][$i] . '_run_rule';
                if(function_exists($za_function))
                {
                    $xreflection = new ReflectionFunction($za_function);
                    if($xreflection->getNumberOfParameters() >= 4)
                    {  
                        $rule_runner = $za_function($matches[3][$i], $matches[2][$i], 0, 1);
                        if($rule_runner != 'fail' && $rule_runner != 'nochange' && $rule_runner != 'ok' && $rule_runner !== false)
                        {
                            if(is_array($rule_runner))
                            {
                                $the_content = str_replace('%%' . $matches[1][$i] . '_' . $matches[2][$i] . '_' . $matches[3][$i] . '%%', $rule_runner[0], $the_content);
                                $the_content = str_replace('%%' . $matches[1][$i] . '_title_' . $matches[2][$i] . '_' . $matches[3][$i] . '%%', $rule_runner[1], $the_content);
                            }
                            else
                            {
                                $the_content = str_replace('%%' . $matches[1][$i] . '_' . $matches[2][$i] . '_' . $matches[3][$i] . '%%', $rule_runner, $the_content);
                                $the_content = str_replace('%%' . $matches[1][$i] . '_title_' . $matches[2][$i] . '_' . $matches[3][$i] . '%%', '', $the_content);
                            }
                            $replace_me = true;
                        }
                    }
                    $xreflection = null;
                    unset($xreflection);
                }
            }
            elseif(in_array($matches[1][$i], $three_var_functions))
            {
                $za_function = $matches[1][$i] . '_run_rule';
                if(function_exists($za_function))
                {
                    $xreflection = new ReflectionFunction($za_function);
                    if($xreflection->getNumberOfParameters() >= 3)
                    {
                        $rule_runner = $za_function($matches[3][$i], 0, 1);
                        if($rule_runner != 'fail' && $rule_runner != 'nochange' && $rule_runner != 'ok' && $rule_runner !== false)
                        {
                            if(is_array($rule_runner))
                            {
                                $the_content = str_replace('%%' . $matches[1][$i] . '_' . $matches[2][$i] . '_' . $matches[3][$i] . '%%', $rule_runner[0], $the_content);
                                $the_content = str_replace('%%' . $matches[1][$i] . '_title_' . $matches[2][$i] . '_' . $matches[3][$i] . '%%', $rule_runner[1], $the_content);
                            }
                            else
                            {
                                $the_content = str_replace('%%' . $matches[1][$i] . '_' . $matches[2][$i] . '_' . $matches[3][$i] . '%%', $rule_runner, $the_content);
                                $the_content = str_replace('%%' . $matches[1][$i] . '_title_' . $matches[2][$i] . '_' . $matches[3][$i] . '%%', '', $the_content);
                            }
                            $replace_me = true;
                        }
                    }
                    $xreflection = null;
                    unset($xreflection);
                }
            }
            elseif(in_array($matches[1][$i], $two_var_functions))
            {
                $za_function = $matches[1][$i] . '_run_rule';
                if(function_exists($za_function))
                {
                    $xreflection = new ReflectionFunction($za_function);
                    if($xreflection->getNumberOfParameters() >= 2)
                    {
                        $rule_runner = $za_function($matches[3][$i], 1);
                        if($rule_runner != 'fail' && $rule_runner != 'nochange' && $rule_runner != 'ok' && $rule_runner !== false)
                        {
                            if(is_array($rule_runner))
                            {
                                $the_content = str_replace('%%' . $matches[1][$i] . '_' . $matches[2][$i] . '_' . $matches[3][$i] . '%%', $rule_runner[0], $the_content);
                                $the_content = str_replace('%%' . $matches[1][$i] . '_title_' . $matches[2][$i] . '_' . $matches[3][$i] . '%%', $rule_runner[1], $the_content);
                            }
                            else
                            {
                                $the_content = str_replace('%%' . $matches[1][$i] . '_' . $matches[2][$i] . '_' . $matches[3][$i] . '%%', $rule_runner, $the_content);
                                $the_content = str_replace('%%' . $matches[1][$i] . '_title_' . $matches[2][$i] . '_' . $matches[3][$i] . '%%', '', $the_content);
                            }
                            $replace_me = true;
                        }
                    }
                    $xreflection = null;
                    unset($xreflection);
                }
            }
            if($replace_me == false)
            {
                $the_content = str_replace('%%' . $matches[1][$i] . '_' . $matches[2][$i] . '_' . $matches[3][$i] . '%%', '', $the_content);
                $the_content = str_replace('%%' . $matches[1][$i] . '_title_' . $matches[2][$i] . '_' . $matches[3][$i] . '%%', '', $the_content);
            }
        }
    }
    if(function_exists('aiomatic_ai_content_replace'))
    {
        $the_content = apply_filters('aiomatic_replace_aicontent_shortcode', $the_content);
    }
    else
    {
        preg_match_all('#\[[\t\s]*aicontent(?:[\t\s]*model=[\'"]?([^\]"\']+)[\'"]?)?[\t\s]*\](.*?)\[\/[\t\s]*aicontent[\t\s]*\]#i', $the_content, $matches);
        if(isset($matches[0][0]) && isset($matches[1][0]))
        {
            for($i = 0; $i < count($matches[0]); $i++)
            {
                $the_content = str_replace($matches[0][$i], '', $the_content);
            }
        }
    }
    return $the_content;
}
function newsomatic_filter_dmca($url)
{
    $dmca_warn = false;
    $dmca_arr = array('onemileatatime.com', 'yourtango.com', 'businessinsider.com', 'bbc.com');
    foreach($dmca_arr as $dm)
    {
        if(strstr($url, $dm) !== false)
        {
            $dmca_warn = true;
            break;
        }
    }
    return $dmca_warn;
}
function newsomatic_fix_google_links($url)
{
    if (stristr ( $url, 'news.google' ) && stristr ( $url, 'url=' )) 
    {
        $urlParts = explode ( 'url=', $url );
        $correctUrl = $urlParts [1];
        $url = $correctUrl;
    }
    elseif(stristr($url,'news.google') && stristr($url,'/articles/') )
    {
        $new_link = newsomatic_get_google_news_link($url);
        if($new_link !== false)
        {
            $url = $new_link;
        }
    }
    elseif(stristr($url, 'https://www.google.com/url?')) 
    {
        preg_match ( '{https:\/\/www\.google\.com\/url\?(?:.*?)&url=([^&]*?)&}', $url, $urlMatches );
        if(isset($urlMatches[1][0]))
        {
            $correctUrl = $urlMatches[1][0];
            if (trim($correctUrl) != '') 
            {
                $url = $correctUrl;
            }
        }
    }
    return $url;
}
function newsomatic_get_google_news_link($link) 
{
    $link = explode('?', $link)[0];
    $base64_partz = preg_match('/\/articles\/(.*?)$/', $link, $matches);
    if (isset($matches[1]) && trim($matches[1]) != '') 
    {
        $base64_partz = $matches[1];
    } 
    else 
    {
        return false;
    }
    $decoded = base64_decode($base64_partz);
    if (stristr($decoded, 'http://') || stristr($decoded, 'https://')) 
    {
        $decoded = preg_replace('/^.*http/', 'http', $decoded);
        $decoded = preg_replace('{\\xd2\\x01\\x00}', '', $decoded);
        $decoded = trim($decoded);
        return $decoded;
    }
    if (stristr($decoded, 'AU_y')) 
    {
        $decoded = newsomatic_decode_google_news_link_base64($base64_partz);
        if ($decoded !== false && trim($decoded) != '') 
        {
            return $decoded;
        }
    }
    return $link;
}
function newsomatic_decode_google_news_link_base64($id)
{
    $decoding_params = newsomatic_get_decoding_params($id);
    if($decoding_params === false)
    {
        return false;
    }
    $articles = array($decoding_params);
    $articles_reqs = [];
    foreach ($articles as $art) 
    {
        $articles_reqs[] = [
            "Fbv4je",
            '["garturlreq",[["X","X",["X","X"],null,null,1,1,"US:en",null,1,null,null,null,null,null,0,1],"X","X",1,[1,1,1],1,1,null,0,0,null,0],"' . $art["gn_art_id"] . '",' . $art["timestamp"] . ',"' . $art["signature"] . '"]',
        ];
    }
    $payload = 'f.req=' . urlencode(json_encode([$articles_reqs]));
    $ch = curl_init();
    if($ch === false)
    {
        return false;
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_REFERER, 'https://news.google.com/');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 20);
    curl_setopt($ch, CURLOPT_URL, "https://news.google.com/_/DotsSplashUi/data/batchexecute");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/x-www-form-urlencoded;charset=UTF-8"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpcode == 302) 
    {
        $redirect_url = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
        if (stristr($redirect_url, 'sorry')) 
        {
            return false;
        }
    }
    if (!stristr($response, 'garturlres')) 
    {
        return false;
    }
    $response = stripslashes($response);
    preg_match_all('#"garturlres","([^"]*?)"#i', $response, $garturMatchs);
    if(isset($garturMatchs[1][0]) && trim($garturMatchs[1][0]) !== '')
    {
        return trim($garturMatchs[1][0]);
    }
    return false;
}
function newsomatic_get_decoding_params($gn_art_id)
{
    $url = "https://news.google.com/articles/" . $gn_art_id;
    $ch = curl_init();
    if($ch === false)
    {
        return false;
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_REFERER, 'https://news.google.com/');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 20);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/html'));
    $server_output = curl_exec ($ch);
    if($server_output === false)
    {
        return false;
    }
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($httpcode != 200) 
    {
        return false;
    }
    if (trim($server_output) == '') 
    {
        return false;
    }
    $dom = new DOMDocument();
    $internalErrors = libxml_use_internal_errors(true);
    $dom->loadHTML($server_output);
    libxml_use_internal_errors($internalErrors);
    $xpath = new DOMXPath($dom);
    /** @var DOMElement $div */
    $div = $xpath->query("//c-wiz/div")->item(0);

    if (!$div) 
    {
        return false;
    }
    return [
        "signature" => $div->getAttribute("data-n-a-sg"),
        "timestamp" => $div->getAttribute("data-n-a-ts"),
        "gn_art_id" => $gn_art_id,
    ];
}
function newsomatic_run_rule($param, $type, $auto = 1, $ret_content = 0)
{
    $plugin = plugin_basename(__FILE__);
    $plugin_slug = explode('/', $plugin);
    $plugin_slug = $plugin_slug[0];
    $uoptions = array();
    $is_activated = newsomatic_is_activated($plugin_slug, $uoptions);
    if($is_activated !== true && $is_activated !== 2)
    {
        newsomatic_log_to_file('You need to activate the plugin using a valid purchase code for this feature to work.');
        return 'nochange';
    }
    do_action( 'wpml_multilingual_options', 'newsomatic_Main_Settings' );
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    if($ret_content == 0)
    {
        $f = fopen(get_temp_dir() . 'newsomatic_' . $type . '_' . $param, 'w');
        if($f !== false)
        {
            $flock_disabled = explode(',', ini_get('disable_functions'));
            if(!in_array('flock', $flock_disabled))
            {
                if (!flock($f, LOCK_EX | LOCK_NB)) {
                    return 'nochange';
                }
            }
        }

        $GLOBALS['wp_object_cache']->delete('newsomatic_running_list', 'options');
        if (!get_option('newsomatic_running_list')) {
            $running = array();
        } else {
            $running = get_option('newsomatic_running_list');
        }
        if (!empty($running)) {
            if (in_array(array(
                $param => $type
            ), $running))
            {
                if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                    newsomatic_log_to_file('Only one instance of this rule is allowed. Rule is already running!');
                }
                return 'nochange';
            }
        }
        $running[] = array(
            $param => $type
        );
        update_option('newsomatic_running_list', $running, false);
        register_shutdown_function('newsomatic_clear_flag_at_shutdown', $param, $type);
    }
    if (isset($newsomatic_Main_Settings['rule_timeout']) && $newsomatic_Main_Settings['rule_timeout'] != '') {
        $timeout = intval($newsomatic_Main_Settings['rule_timeout']);
    } else {
        $timeout = 3600;
    }
    ini_set('safe_mode', 'Off');
    ini_set('max_execution_time', $timeout);
    ini_set('ignore_user_abort', 1);
    ini_set('user_agent', newsomatic_get_random_user_agent());
    if(function_exists('ignore_user_abort'))
    {
        ignore_user_abort(true);
    }
            if(function_exists('set_time_limit'))
    {
        set_time_limit($timeout);
    }
    $posts_inserted         = 0;
    $already_increased = false;
    if (isset($newsomatic_Main_Settings['newsomatic_enabled']) && $newsomatic_Main_Settings['newsomatic_enabled'] == 'on') 
    {
        try {
			if (isset($newsomatic_Main_Settings['newsapi_active']) && trim($newsomatic_Main_Settings['newsapi_active']) == 'on') 
			{
				if (!isset($newsomatic_Main_Settings['app_id']) || trim($newsomatic_Main_Settings['app_id']) == '') {
					newsomatic_log_to_file('You need to insert a valid NewsAPI API Key for this to work!');
					if($auto == 1)
					{
						newsomatic_clearFromList($param, $type);
					}
					return 'fail';
				}
			}
			else
			{
				if (!isset($newsomatic_Main_Settings['newsomatic_app_id']) || trim($newsomatic_Main_Settings['newsomatic_app_id']) == '') {
					newsomatic_log_to_file('You need to insert a valid NewsomaticAPI API Key for this to work!');
					if($auto == 1)
					{
						newsomatic_clearFromList($param, $type);
					}
					return 'fail';
				}
                else
                {
                    if(strlen(trim($newsomatic_Main_Settings['newsomatic_app_id'])) != 66 && strlen(trim($newsomatic_Main_Settings['newsomatic_app_id'])) != 50)
                    {
                        newsomatic_log_to_file('You inserted an invalid NewsomaticAPI key: ' . trim($newsomatic_Main_Settings['newsomatic_app_id']));
                        if($auto == 1)
                        {
                            newsomatic_clearFromList($param, $type);
                        }
                        return 'fail';
                    }
                }
			}
            $draft_me         = false;
            $items            = array();
            $item_img         = '';
            $cont             = 0;
            $found            = 0;
            $schedule         = '';
            $enable_comments  = '1';
            $enable_pingback  = '1';
            $author_link      = '';
            $active           = '0';
            $last_run         = '';
            $ruleType         = 'week';
            $first            = false;
            $others           = array();
            $post_title       = '';
            $post_content     = '';
            $list_item        = '';
            $default_category = '';
            $extra_categories = '';
            $post_status     = 'publish';
            $post_type       = 'post';
            $accept_comments = 'closed';
            $post_user_name  = 1;
            $can_create_cat  = 'off';
            $item_create_tag = '';
            $can_create_tag  = 'disabled';
            $item_tags       = '';
            $date            = 'any';
            $auto_categories = 'disabled';
            $featured_image  = '0';
            $image_url       = '';
            $strip_images    = '0';
            $strip_tsource   = '';
            $limit_title_word_count = '';
            $post_format     = 'post-format-standard';
            $post_array      = array();
            $max             = 50;
            $lon             = '';
            $lat             = '';
            $img_path        = '';
            $full_content    = '0';
            $content_percent = '';
            $only_text       = '';
            $single          = '';
            $full_type       = '';
            $inner           = '';
            $expre           = '';
            $lazy_tag        = '';
            $center          = 'any';
            $search_description = '';
            $search_keywords  = '';
            $search_location  = '';
            $search_id        = '';
            $search_photographer = '';
            $search_secondary_creator = '';
            $start_year       = '';
            $end_year         = '';
            $encoding         = 'NO_CHANGE';
            $media_type       = 'any';
            $search_title     = '';
            $sol              = '';
            $query_string     = '';
            $query_string_title = '';
            $camera           = 'any';
            $disable_excerpt  = '0';
            $import_date      = '0';
            $custom_fields    = '';
            $custom_tax       = '';
            $remove_default   = '0';
            $keyword_category = '';
            $keyword_tag      = '';
            $custom_cookies   = '';
            $post_language    = 'all';
            $post_country     = 'all';
            $strip_by_id      = '';
            $strip_by_class   = '';
            $articles_from    = '';
            $continue_search  = '';
            $articles_to      = '';
            $sort_results     = 'publishedAt';
            $skip_posts       = '';
            $rank_keywords    = '';
            $rule_translate   = '';
			$allow_tags       = '';
            $only_domains     = '';
            $skip_img         = '';
            $rule_description = '';
            $remove_domains   = '';
            $strip_by_regex   = '';
            $replace_regex    = '';
            $use_phantom      = '';
            $wpml_lang        = '';
            $image_type       = '';
            $image_expre      = '';
            $skip_spin_translate = '';
            $rule_translate_source = 'disabled';
            $second_translate = 'disabled';
            $strip_by_regex_title = '';
            $replace_regex_title = '';
            $limit_content_word_count = 'disabled';
            $royalty_free     = '';
            $exclude_country  = '';
            if($type == 0)
            {
                $GLOBALS['wp_object_cache']->delete('newsomatic_rules_list', 'options');
                if (!get_option('newsomatic_rules_list')) {
                    $rules = array();
                } else {
                    $rules = get_option('newsomatic_rules_list');
                }
                if (!empty($rules)) {
                    foreach ($rules as $request => $bundle[]) {
                        if ($cont == $param) {
                            $bundle_values    = array_values($bundle);
                            $myValues         = $bundle_values[$cont];
                            $array_my_values  = array_values($myValues);for($iji=0;$iji<count($array_my_values);++$iji){if(is_string($array_my_values[$iji])){$array_my_values[$iji]=stripslashes($array_my_values[$iji]);}}
                            $limit_title_word_count = isset($array_my_values[0]) ? $array_my_values[0] : '';
                            $schedule         = isset($array_my_values[1]) ? $array_my_values[1] : '';
                            $active           = isset($array_my_values[2]) ? $array_my_values[2] : '';
                            $last_run         = isset($array_my_values[3]) ? $array_my_values[3] : '';
                            $post_status      = isset($array_my_values[4]) ? $array_my_values[4] : '';
                            $post_type        = isset($array_my_values[5]) ? $array_my_values[5] : '';
                            $post_user_name   = isset($array_my_values[6]) ? $array_my_values[6] : '';
                            $item_create_tag  = isset($array_my_values[7]) ? $array_my_values[7] : '';
                            $default_category = isset($array_my_values[8]) ? $array_my_values[8] : '';
                            $auto_categories  = isset($array_my_values[9]) ? $array_my_values[9] : '';
                            $can_create_tag   = isset($array_my_values[10]) ? $array_my_values[10] : '';
                            $enable_comments  = isset($array_my_values[11]) ? $array_my_values[11] : '';
                            $featured_image   = isset($array_my_values[12]) ? $array_my_values[12] : '';
                            $image_url        = isset($array_my_values[13]) ? $array_my_values[13] : '';
                            $post_title       = isset($array_my_values[14]) ? htmlspecialchars_decode($array_my_values[14]) : '';
                            $post_content     = isset($array_my_values[15]) ? htmlspecialchars_decode($array_my_values[15]) : '';
                            $enable_pingback  = isset($array_my_values[16]) ? $array_my_values[16] : '';
                            $post_format      = isset($array_my_values[17]) ? $array_my_values[17] : '';
                            $date             = isset($array_my_values[18]) ? $array_my_values[18] : '';
                            $strip_images     = isset($array_my_values[19]) ? $array_my_values[19] : '';
                            $query_string     = isset($array_my_values[20]) ? $array_my_values[20] : '';
                            $max              = isset($array_my_values[21]) ? $array_my_values[21] : '';
                            $full_content     = isset($array_my_values[22]) ? $array_my_values[22] : '';
                            $only_text        = isset($array_my_values[23]) ? $array_my_values[23] : '';
                            $single           = isset($array_my_values[24]) ? $array_my_values[24] : '';
                            $full_type        = isset($array_my_values[25]) ? $array_my_values[25] : '';
                            $inner            = isset($array_my_values[26]) ? $array_my_values[26] : '';
                            $expre            = isset($array_my_values[27]) ? $array_my_values[27] : '';
                            $lazy_tag         = isset($array_my_values[28]) ? $array_my_values[28] : '';
                            $encoding         = isset($array_my_values[29]) ? $array_my_values[29] : '';
                            $disable_excerpt  = isset($array_my_values[30]) ? $array_my_values[30] : '';
                            $content_percent  = isset($array_my_values[31]) ? $array_my_values[31] : '';
                            $remove_default   = isset($array_my_values[32]) ? $array_my_values[32] : '';
                            $limit_content_word_count = isset($array_my_values[33]) ? $array_my_values[33] : '';
                            $continue_search  = isset($array_my_values[34]) ? $array_my_values[34] : '';
                            $post_country     = isset($array_my_values[35]) ? $array_my_values[35] : '';
                            $skip_spin_translate= isset($array_my_values[36]) ? $array_my_values[36] : '';
                            $rule_translate   = isset($array_my_values[37]) ? $array_my_values[37] : '';
                            $rule_translate_source= isset($array_my_values[38]) ? $array_my_values[38] : '';
                            $rank_keywords    = isset($array_my_values[39]) ? $array_my_values[39] : '';
                            $royalty_free     = isset($array_my_values[40]) ? $array_my_values[40] : '';
                            $import_date      = isset($array_my_values[41]) ? $array_my_values[41] : '';
                            $custom_fields    = isset($array_my_values[42]) ? $array_my_values[42] : '';
                            $custom_tax       = isset($array_my_values[43]) ? $array_my_values[43] : '';
                            $skip_img         = isset($array_my_values[44]) ? $array_my_values[44] : '';
                            $rule_description = isset($array_my_values[45]) ? $array_my_values[45] : '';
                            $strip_tsource    = isset($array_my_values[46]) ? $array_my_values[46] : '';
                            $strip_by_regex   = isset($array_my_values[47]) ? $array_my_values[47] : '';
                            $replace_regex    = isset($array_my_values[48]) ? $array_my_values[48] : '';
                            $remove_domains   = isset($array_my_values[49]) ? $array_my_values[49] : '';
                            $only_domains     = isset($array_my_values[50]) ? $array_my_values[50] : '';
                            $allow_tags       = isset($array_my_values[51]) ? $array_my_values[51] : '';
                            $post_language    = isset($array_my_values[52]) ? $array_my_values[52] : '';
                            $strip_by_id      = isset($array_my_values[53]) ? $array_my_values[53] : '';
                            $strip_by_class   = isset($array_my_values[54]) ? $array_my_values[54] : '';
                            $use_phantom      = isset($array_my_values[55]) ? $array_my_values[55] : '';
                            $wpml_lang        = isset($array_my_values[56]) ? $array_my_values[56] : '';
                            $image_type       = isset($array_my_values[57]) ? $array_my_values[57] : '';
                            $image_expre      = isset($array_my_values[58]) ? $array_my_values[58] : '';
                            $keyword_category = isset($array_my_values[59]) ? $array_my_values[59] : '';
                            $custom_cookies   = isset($array_my_values[60]) ? $array_my_values[60] : '';
                            $exclude_country  = isset($array_my_values[61]) ? $array_my_values[61] : '';
                            $second_translate = isset($array_my_values[62]) ? $array_my_values[62] : '';
                            $strip_by_regex_title = isset($array_my_values[63]) ? $array_my_values[63] : '';
                            $replace_regex_title = isset($array_my_values[64]) ? $array_my_values[64] : '';
                            $keyword_tag      = isset($array_my_values[65]) ? $array_my_values[65] : '';
                            $found            = 1;
                            break;
                        }
                        $cont = $cont + 1;
                    }
                } else {
                    newsomatic_log_to_file('No rules found for newsomatic_rules_list!');
                    if($auto == 1)
                    {
                        newsomatic_clearFromList($param, $type);
                    }
                    return 'fail';
                }
                if ($found == 0) {
                    newsomatic_log_to_file($param . ' not found in newsomatic_rules_list!');
                    if($auto == 1)
                    {
                        newsomatic_clearFromList($param, $type);
                    }
                    return 'fail';
                } else {
                    if($ret_content == 0)
                    {
                        $GLOBALS['wp_object_cache']->delete('newsomatic_rules_list', 'options');
                        $rules = get_option('newsomatic_rules_list');
                        $rules[$param][3] = newsomatic_get_date_now();
                        update_option('newsomatic_rules_list', $rules, false);
                    }
                }
            }
            elseif($type == 1)
            {
                $GLOBALS['wp_object_cache']->delete('newsomatic_all_list', 'options');
                if (!get_option('newsomatic_all_list')) {
                    $rules = array();
                } else {
                    $rules = get_option('newsomatic_all_list');
                }
                if (!empty($rules)) {
                    foreach ($rules as $request => $bundle[]) {
                        if ($cont == $param) {
                            $bundle_values    = array_values($bundle);
                            $myValues         = $bundle_values[$cont];
                            $array_my_values  = array_values($myValues);for($iji=0;$iji<count($array_my_values);++$iji){if(is_string($array_my_values[$iji])){$array_my_values[$iji]=stripslashes($array_my_values[$iji]);}}
                            $limit_title_word_count = isset($array_my_values[0]) ? $array_my_values[0] : '';
                            $schedule         = isset($array_my_values[1]) ? $array_my_values[1] : '';
                            $active           = isset($array_my_values[2]) ? $array_my_values[2] : '';
                            $last_run         = isset($array_my_values[3]) ? $array_my_values[3] : '';
                            $post_status      = isset($array_my_values[4]) ? $array_my_values[4] : '';
                            $post_type        = isset($array_my_values[5]) ? $array_my_values[5] : '';
                            $post_user_name   = isset($array_my_values[6]) ? $array_my_values[6] : '';
                            $item_create_tag  = isset($array_my_values[7]) ? $array_my_values[7] : '';
                            $default_category = isset($array_my_values[8]) ? $array_my_values[8] : '';
                            $auto_categories  = isset($array_my_values[9]) ? $array_my_values[9] : '';
                            $can_create_tag   = isset($array_my_values[10]) ? $array_my_values[10] : '';
                            $enable_comments  = isset($array_my_values[11]) ? $array_my_values[11] : '';
                            $featured_image   = isset($array_my_values[12]) ? $array_my_values[12] : '';
                            $image_url        = isset($array_my_values[13]) ? $array_my_values[13] : '';
                            $post_title       = isset($array_my_values[14]) ? htmlspecialchars_decode($array_my_values[14]) : '';
                            $post_content     = isset($array_my_values[15]) ? htmlspecialchars_decode($array_my_values[15]) : '';
                            $enable_pingback  = isset($array_my_values[16]) ? $array_my_values[16] : '';
                            $post_format      = isset($array_my_values[17]) ? $array_my_values[17] : '';
                            $date             = isset($array_my_values[18]) ? $array_my_values[18] : '';
                            $strip_images     = isset($array_my_values[19]) ? $array_my_values[19] : '';
                            $query_string     = isset($array_my_values[20]) ? $array_my_values[20] : '';
                            $max              = isset($array_my_values[21]) ? $array_my_values[21] : '';
                            $full_content     = isset($array_my_values[22]) ? $array_my_values[22] : '';
                            $only_text        = isset($array_my_values[23]) ? $array_my_values[23] : '';
                            $single           = isset($array_my_values[24]) ? $array_my_values[24] : '';
                            $full_type        = isset($array_my_values[25]) ? $array_my_values[25] : '';
                            $inner            = isset($array_my_values[26]) ? $array_my_values[26] : '';
                            $expre            = isset($array_my_values[27]) ? $array_my_values[27] : '';
                            $lazy_tag         = isset($array_my_values[28]) ? $array_my_values[28] : '';
                            $encoding         = isset($array_my_values[29]) ? $array_my_values[29] : '';
                            $disable_excerpt  = isset($array_my_values[30]) ? $array_my_values[30] : '';
                            $content_percent  = isset($array_my_values[31]) ? $array_my_values[31] : '';
                            $remove_default   = isset($array_my_values[32]) ? $array_my_values[32] : '';
                            $limit_content_word_count = isset($array_my_values[33]) ? $array_my_values[33] : '';
                            $post_language    = isset($array_my_values[34]) ? $array_my_values[34] : '';
                            $articles_from    = isset($array_my_values[35]) ? $array_my_values[35] : '';
                            $articles_to      = isset($array_my_values[36]) ? $array_my_values[36] : '';
                            $sort_results     = isset($array_my_values[37]) ? $array_my_values[37] : '';
                            $continue_search  = isset($array_my_values[38]) ? $array_my_values[38] : '';
                            $skip_spin_translate= isset($array_my_values[39]) ? $array_my_values[39] : '';
                            $rule_translate   = isset($array_my_values[40]) ? $array_my_values[40] : '';
                            $rule_translate_source= isset($array_my_values[41]) ? $array_my_values[41] : '';
                            $rank_keywords    = isset($array_my_values[42]) ? $array_my_values[42] : '';
                            $royalty_free     = isset($array_my_values[43]) ? $array_my_values[43] : '';
                            $import_date      = isset($array_my_values[44]) ? $array_my_values[44] : '';
                            $custom_fields    = isset($array_my_values[45]) ? $array_my_values[45] : '';
                            $custom_tax       = isset($array_my_values[46]) ? $array_my_values[46] : '';
                            $only_domains     = isset($array_my_values[47]) ? $array_my_values[47] : '';
                            $remove_domains   = isset($array_my_values[48]) ? $array_my_values[48] : '';
                            $skip_img         = isset($array_my_values[49]) ? $array_my_values[49] : '';
                            $rule_description = isset($array_my_values[50]) ? $array_my_values[50] : '';
                            $strip_tsource    = isset($array_my_values[51]) ? $array_my_values[51] : '';
                            $strip_by_regex   = isset($array_my_values[52]) ? $array_my_values[52] : '';
                            $replace_regex    = isset($array_my_values[53]) ? $array_my_values[53] : '';
                            $query_string_title= isset($array_my_values[54]) ? $array_my_values[54] : '';
                            $allow_tags       = isset($array_my_values[55]) ? $array_my_values[55] : '';
                            $post_country     = isset($array_my_values[56]) ? $array_my_values[56] : '';
                            $strip_by_id      = isset($array_my_values[57]) ? $array_my_values[57] : '';
                            $strip_by_class   = isset($array_my_values[58]) ? $array_my_values[58] : '';
                            $use_phantom      = isset($array_my_values[59]) ? $array_my_values[59] : '';
                            $wpml_lang        = isset($array_my_values[60]) ? $array_my_values[60] : '';
                            $image_type       = isset($array_my_values[61]) ? $array_my_values[61] : '';
                            $image_expre      = isset($array_my_values[62]) ? $array_my_values[62] : '';
                            $keyword_category = isset($array_my_values[63]) ? $array_my_values[63] : '';
                            $custom_cookies   = isset($array_my_values[64]) ? $array_my_values[64] : '';
                            $exclude_country  = isset($array_my_values[65]) ? $array_my_values[65] : '';
                            $second_translate = isset($array_my_values[66]) ? $array_my_values[66] : '';
                            $strip_by_regex_title = isset($array_my_values[67]) ? $array_my_values[67] : '';
                            $replace_regex_title = isset($array_my_values[68]) ? $array_my_values[68] : '';
                            $keyword_tag      = isset($array_my_values[69]) ? $array_my_values[69] : '';
                            $found            = 1;
                            break;
                        }
                        $cont = $cont + 1;
                    }
                } else {
                    newsomatic_log_to_file('No rules found for newsomatic_all_list!');
                    if($auto == 1)
                    {
                        newsomatic_clearFromList($param, $type);
                    }
                    return 'fail';
                }
                if ($found == 0) {
                    newsomatic_log_to_file($param . ' not found in newsomatic_all_list!');
                    if($auto == 1)
                    {
                        newsomatic_clearFromList($param, $type);
                    }
                    return 'fail';
                } else {
                    if($ret_content == 0)
                    {
                        $GLOBALS['wp_object_cache']->delete('newsomatic_all_list', 'options');
                        $rules = get_option('newsomatic_all_list');
                        $rules[$param][3] = newsomatic_get_date_now();
                        update_option('newsomatic_all_list', $rules, false);
                    }
                }
                $query_string = htmlspecialchars_decode($query_string);
                $query_string_title = htmlspecialchars_decode($query_string_title);
            }
            else
            {
                newsomatic_log_to_file('Invalid rule type provided: ' . $type);
                if($auto == 1)
                {
                    newsomatic_clearFromList($param, $type);
                }
                return 'fail';
            }
            $query_string = newsomatic_replaceSynergyShortcodes($query_string);
            $query_string_title = newsomatic_replaceSynergyShortcodes($query_string_title);
            if ($enable_comments == '1') {
                $accept_comments = 'open';
            }
            $xheaders = false;
            if($type == 0)
            {
                $required_added = false;
                if (isset($newsomatic_Main_Settings['max_query']) && $newsomatic_Main_Settings['max_query'] == 'on') {
                    $query_m = 100;
                }
                else
                {
                    $query_m = $max;
                }
				if (isset($newsomatic_Main_Settings['newsapi_active']) && trim($newsomatic_Main_Settings['newsapi_active']) == 'on')
				{
					$feed_uri='https://newsapi.org/v2/top-headlines';
					$feed_uri .= '?apikey=' . trim($newsomatic_Main_Settings['app_id']) . '&pageSize=' . $query_m;  
				}
				else
				{
                    if(strlen(trim($newsomatic_Main_Settings['newsomatic_app_id'])) == 66)
                    {
                        $feed_uri='https://newsomaticapi.com/apis/news/v1/top';
					    $feed_uri .= '?apikey=' . trim($newsomatic_Main_Settings['newsomatic_app_id']) . '&pageSize=' . $query_m;  
                    }
                    elseif(strlen(trim($newsomatic_Main_Settings['newsomatic_app_id'])) == 50)
                    {
                        $feed_uri='https://newsomaticapi.p.rapidapi.com/top';
					    $feed_uri .= '?apikey=' . trim($newsomatic_Main_Settings['newsomatic_app_id']) . '&pageSize=' . $query_m;  
                        $xheaders = array();
                        $xheaders[] = "x-rapidapi-key: " . trim($newsomatic_Main_Settings['newsomatic_app_id']);
                        $xheaders[] = "x-rapidapi-host: newsomaticapi.p.rapidapi.com";
                        $xheaders[] = "content-type: application/octet-stream";
                        $xheaders[] = "useQueryString: true";
                    }
				}
                if($query_string != '' && $query_string != 'any' && $query_string != 'top' && $query_string != 'latest' && $query_string != 'popular')
                {
                    $required_added = true;
                    $query_string = str_replace('\'', '"', $query_string);
                    $query_string = urlencode($query_string);
                    $feed_uri .= '&q=' . $query_string;
                }
                if($date != 'any')
                {
                    if(substr($date, 0, 9) === "category-")
                    {
                        $required_added = true;
                        $tmp_date = substr($date, 9);
                        if($tmp_date == 'science-and-nature')//changed
                        {
                            $tmp_date = 'science';
                        }
                        elseif($tmp_date == 'health-and-medical')//changed
                        {
                            $tmp_date = 'health';
                        }
                        elseif($tmp_date == 'sport')//changed
                        {
                            $tmp_date = 'sports';
                        }
                        elseif($tmp_date == 'gaming')//deleted
                        {
                            $tmp_date = 'general';
                        }
                        elseif($tmp_date == 'music')//deleted
                        {
                            $tmp_date = 'general';
                        }
                        elseif($tmp_date == 'politics')//deleted
                        {
                            $tmp_date = 'general';
                        }
                        $feed_uri .= '&category=' . $tmp_date;
                    }
                    else
                    {
                        $required_added = true;
                        if (isset($newsomatic_Main_Settings['newsapi_active']) && trim($newsomatic_Main_Settings['newsapi_active']) == 'on')
				        {
                            $feed_uri .= '&sources=' . sanitize_title($date);
                        }
                        else
                        {
                            $feed_uri .= '&sources=' . $date;
                        }
                    }
                }
                if($post_language != '' && $post_language != 'all')
                {
                    $required_added = true;
                    $feed_uri .= '&language=' . $post_language;
                }
                if($post_country != '' && $post_country != 'all' && (substr($date, 0, 9) === "category-" || $date == 'any'))
                {
                    $required_added = true;
                    $feed_uri .= '&country=' . $post_country;
                }
                if($only_domains != '')
                {
                    $only_domains = str_replace(' ', '', $only_domains);
                    $feed_uri .= '&domains=' . $only_domains;
                }
                if($remove_domains != '')
                {
                    $remove_domains = str_replace(' ', '', $remove_domains);
                    $feed_uri .= '&excludeDomains=' . $remove_domains;
                }
                if($exclude_country != '' && is_array($exclude_country))
                {
                    $country_exclusion = '';
                    foreach($exclude_country as $excountry)
                    {
                        if($excountry != 'no_exclude')
                        {
                            $country_exclusion .= ',' . trim($excountry);
                        }
                    }
                    $country_exclusion = trim($country_exclusion, ',');
                    if($country_exclusion != '')
                    {
                        $feed_uri .= '&excludeCountry=' . $country_exclusion;
                    }
                }
                if($required_added == false)
                {
                    $feed_uri .= '&country=us';
                }
                if($continue_search == '1')
                {
                    $GLOBALS['wp_object_cache']->delete('newsomatic_continue_search', 'options');
                    $skip_posts_temp = get_option('newsomatic_continue_search', array());
                    if(isset($skip_posts_temp[$param][$type]) && is_numeric($skip_posts_temp[$param][$type]))
                    {
                        $skip_posts = $skip_posts_temp[$param][$type];
                        if($skip_posts == '0')
                        {
                            $skip_posts = 1;
                            $skip_posts_temp[$param][$type] = '1';
                        }
                    }
                    else
                    {
                        if(!is_array($skip_posts_temp))
                        {
                            $skip_posts_temp = array();
                        }
                        $skip_posts_temp[$param][$type] = 1;
                        $skip_posts = '';
                    }
                }
                if($skip_posts !== '')
                {
                    $feed_uri .= '&page=' . $skip_posts;
                }
                if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                    newsomatic_log_to_file('Getting content from URL ' . $feed_uri);
                }
                $exec = newsomatic_get_web_page_api($feed_uri, $xheaders);
                if ($exec === FALSE) {
                    newsomatic_log_to_file('Failed to exec curl to get News response ' . $feed_uri);
                    if($auto == 1)
                    {
                        newsomatic_clearFromList($param, $type);
                    }
                    return 'fail';
                }
                
                $json  = json_decode($exec);
                if(isset($json->apicalls))
                {
                    update_option('newsomaticapi_calls', esc_html($json->apicalls));
                }
                if(!isset($json->articles))
                {
                    if($continue_search == '1')
                    {
                        $skip_posts_temp[$param][$type] = 1;
                        update_option('newsomatic_continue_search', $skip_posts_temp);
                    }
                    newsomatic_log_to_file('Unrecognized NewsomaticAPI response: ' . print_r($exec, true) . ' url: ' . $feed_uri);
                    if($auto == 1)
                    {
                        newsomatic_clearFromList($param, $type);
                    }
                    return 'fail';
                }
                $items = $json->articles;
            }
            elseif($type == 1)
            {
                
                if (isset($newsomatic_Main_Settings['max_query']) && $newsomatic_Main_Settings['max_query'] == 'on') {
                    $query_m = 100;
                }
                else
                {
                    $query_m = $max;
                }
				if (isset($newsomatic_Main_Settings['newsapi_active']) && trim($newsomatic_Main_Settings['newsapi_active']) == 'on')
				{
					$feed_uri = 'https://newsapi.org/v2/everything';
					$feed_uri .= '?apikey=' . trim($newsomatic_Main_Settings['app_id']) . '&pageSize=' . $query_m;  
				}
				else
				{
                    if(strlen(trim($newsomatic_Main_Settings['newsomatic_app_id'])) == 66)
                    {
                        $feed_uri = 'https://newsomaticapi.com/apis/news/v1/all';
                        $feed_uri .= '?apikey=' . trim($newsomatic_Main_Settings['newsomatic_app_id']) . '&pageSize=' . $query_m;  
                    }
                    elseif(strlen(trim($newsomatic_Main_Settings['newsomatic_app_id'])) == 50)
                    {
                        $feed_uri='https://newsomaticapi.p.rapidapi.com/all';
					    $feed_uri .= '?apikey=' . trim($newsomatic_Main_Settings['newsomatic_app_id']) . '&pageSize=' . $query_m;  
                        $xheaders = array();
                        $xheaders[] = "x-rapidapi-key: " . trim($newsomatic_Main_Settings['newsomatic_app_id']);
                        $xheaders[] = "x-rapidapi-host: newsomaticapi.p.rapidapi.com";
                        $xheaders[] = "content-type: application/octet-stream";
                        $xheaders[] = "useQueryString: true";
                    }
				}
                if($query_string != '')
                {
                    $query_string = str_replace('\'', '"', $query_string);
                    $query_string = urlencode($query_string);
                    $feed_uri .= '&q=' . $query_string;
                }
                if($query_string_title != '')
                {
                    $query_string_title = str_replace('\'', '"', $query_string_title);
                    $query_string_title = urlencode($query_string_title);
                    $feed_uri .= '&qInTitle=' . $query_string_title;
                }
                if($date != 'any')
                {
                    if (isset($newsomatic_Main_Settings['newsapi_active']) && trim($newsomatic_Main_Settings['newsapi_active']) == 'on')
                    {
                        $feed_uri .= '&sources=' . sanitize_title($date);
                    }
                    else
                    {
                        $feed_uri .= '&sources=' . $date;
                    }
                }
                if($post_country != '' && $post_country != 'all' && (substr($date, 0, 9) === "category-" || $date == 'any'))
                {
                    $feed_uri .= '&country=' . $post_country;
                }
                if($post_language != '' && $post_language != 'all')
                {
                    $feed_uri .= '&language=' . $post_language;
                }
                if($sort_results != '' && $sort_results != 'publishedAt')
                {
                    $feed_uri .= '&sortBy=' . $sort_results;
                }
                if($only_domains != '')
                {
                    $only_domains = str_replace(' ', '', $only_domains);
                    $feed_uri .= '&domains=' . $only_domains;
                }
                if($remove_domains != '')
                {
                    $remove_domains = str_replace(' ', '', $remove_domains);
                    $feed_uri .= '&excludeDomains=' . $remove_domains;
                }
                if($exclude_country != '' && is_array($exclude_country))
                {
                    $country_exclusion = '';
                    foreach($exclude_country as $excountry)
                    {
                        if($excountry != 'no_exclude')
                        {
                            $country_exclusion .= ',' . trim($excountry);
                        }
                    }
                    $country_exclusion = trim($country_exclusion, ',');
                    if($country_exclusion != '')
                    {
                        $feed_uri .= '&excludeCountry=' . $country_exclusion;
                    }
                }
                if($articles_from != '')
                {
                    $from_time = strtotime($articles_from);
                    if($from_time !== false)
                    {
                        $the_date = date("Y-m-d\TH:i:s", $from_time);
                        if($the_date !== false)
                        {
                            $feed_uri .= '&from=' . $the_date;
                        }
                    }
                }
                if($articles_to != '')
                {
                    $to_time = strtotime($articles_to);
                    if($to_time !== false)
                    {
                        $the_date = date("Y-m-d\TH:i:s", $to_time);
                        if($the_date !== false)
                        {
                            $feed_uri .= '&to=' . $the_date;
                        }
                    }
                }
                if($continue_search == '1')
                {
                    $GLOBALS['wp_object_cache']->delete('newsomatic_continue_search', 'options');
                    $skip_posts_temp = get_option('newsomatic_continue_search', array());
                    if(isset($skip_posts_temp[$param][$type]) && is_numeric($skip_posts_temp[$param][$type]))
                    {
                        $skip_posts = $skip_posts_temp[$param][$type];
                        if($skip_posts == '0')
                        {
                            $skip_posts = 1;
                            $skip_posts_temp[$param][$type] = '1';
                        }
                    }
                    else
                    {
                        if(!is_array($skip_posts_temp))
                        {
                            $skip_posts_temp = array();
                        }
                        $skip_posts_temp[$param][$type] = 1;
                        $skip_posts = '';
                    }
                }
                if($skip_posts !== '')
                {
                    $feed_uri .= '&page=' . $skip_posts;
                }
                if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                    newsomatic_log_to_file('Getting content from ' . $feed_uri);
                }
                $exec = newsomatic_get_web_page_api($feed_uri, $xheaders);
                if ($exec === FALSE) {
                    newsomatic_log_to_file('Failed to exec curl to get News response2: ' . $feed_uri);
                    if($auto == 1)
                    {
                        newsomatic_clearFromList($param, $type);
                    }
                    return 'fail';
                }
                
                $json  = json_decode($exec);
                if(isset($json->apicalls))
                {
                    update_option('newsomaticapi_calls', esc_html($json->apicalls));
                }
                if(!isset($json->articles))
                {
                    if($continue_search == '1')
                    {
                        $skip_posts_temp[$param][$type] = 1;
                        update_option('newsomatic_continue_search', $skip_posts_temp);
                    }
                    newsomatic_log_to_file('Unrecognized NewsomaticAPI response2: ' . print_r($exec, true) . ' url: ' . $feed_uri);
                    if($auto == 1)
                    {
                        newsomatic_clearFromList($param, $type);
                    }
                    return 'fail';
                }
                $items = $json->articles;
            }
            $json = null;
            unset($json);
            $exec = null;
            unset($exec);
            if (count($items) == 0) {
                if($continue_search == '1')
                {
                    $skip_posts_temp[$param][$type] = 1;
                    update_option('newsomatic_continue_search', $skip_posts_temp);
                }
                if($auto == 1)
                {
                    newsomatic_clearFromList($param, $type);
                }
                return 'nochange';
            }
            
            $count = 1;
            $init_date = time();
            $skip_pcount = 0;
            $skipped_pcount = 0;
            if($ret_content == 1)
            {
                $item_xcounter = count($items);
                $skip_pcount = rand(0, $item_xcounter-1);
            }
            $user_name_type = $post_user_name;
            $processed_items = 0;
            foreach ($items as $item) 
            {
                if($ret_content == 1)
                {
                    if($skip_pcount > $skipped_pcount)
                    {
                        $skipped_pcount++;
                        continue;
                    }
                }
                $get_img = '';
                $item_words = '';
                if ($count > intval($max)) {
                    break;
                }
                $processed_items++;
                if(isset($newsomatic_Main_Settings['attr_text']) && $newsomatic_Main_Settings['attr_text'] != '')
                {
                    $img_attr = $newsomatic_Main_Settings['attr_text'];
                }
                else
                {
                    $img_attr = '';
                }
                $query_words = '';
                $media = '';
                if (!isset($newsomatic_Main_Settings['disable_dmca']) || $newsomatic_Main_Settings['disable_dmca'] != 'on') 
                {
					if($item->url != null)
					{
						$dmca = newsomatic_filter_dmca($item->url);
						if($dmca == true)
						{
							if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
								newsomatic_log_to_file('Skipping post ' . esc_html($item->url) . ', because it is from a source known to protect it\'s content with DMCA take down requests.');
							}
							continue;
						}
					}
                }
                if($type == 0)
                {
                    $title = $item->title;
                    if (!isset($newsomatic_Main_Settings['title_duplicates']) || $newsomatic_Main_Settings['title_duplicates'] != 'on') 
                    {
                        if($item->url != null)
                        {
                            $id = $item->url;
                        }
                        else
                        {
                            $id = $item->title;
                        }
                    }
                    else
                    {
                        $id = $item->title;
                    }
                    
                    if (!isset($newsomatic_Main_Settings['do_not_check_duplicates']) || $newsomatic_Main_Settings['do_not_check_duplicates'] != 'on') {
                        if($ret_content == 0)
                        {
                            $query = array(
                                'post_status' => array(
                                    'publish',
                                    'draft',
                                    'pending',
                                    'trash',
                                    'private',
                                    'future'
                                ),
                                'post_type' => array(
                                    'any'
                                ),
                                'numberposts' => '1',
                                'fields' => 'ids',
                                'meta_query' => array(
                                    array(
                                        'key' => 'newsomatic_post_id',
                                        'value' => $id,
                                        'compare' => '='
                                    )
                                )
                            );
                            $got_me = get_posts($query);
                            if(count($got_me) > 0)
                            {
                                if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                    newsomatic_log_to_file('Skipping post, already published: ' . $title);
                                }
                                continue;
                            }
                        }
                    }
                    if(isset($item->urlToImage))
                    {
                        if($royalty_free == '1')
                        {
                            if(isset($newsomatic_Main_Settings['textrazor_key']) && trim($newsomatic_Main_Settings['textrazor_key']) != '')
                            {
                                try
                                {
                                    if(!class_exists('TextRazor'))
                                    {
                                        require_once(dirname(__FILE__) . "/res/TextRazor.php");
                                    }
                                    TextRazorSettings::setApiKey(trim($newsomatic_Main_Settings['textrazor_key']));
                                    $textrazor = new TextRazor();
                                    $textrazor->addExtractor('entities');
                                    $response = $textrazor->analyze($title);
                                    if (isset($response['response']['entities'])) 
                                    {
                                        foreach ($response['response']['entities'] as $entity) 
                                        {
                                            $query_words = '';
                                            if(isset($entity['entityEnglishId']))
                                            {
                                                $query_words = $entity['entityEnglishId'];
                                            }
                                            else
                                            {
                                                $query_words = $entity['entityId'];
                                            }
                                            if($query_words != '')
                                            {
                                                $get_img = newsomatic_get_free_image($newsomatic_Main_Settings, $query_words, $img_attr, 10);
                                                if($get_img == false)
                                                {
                                                    $get_img = '';
                                                }
                                                if(!empty($get_img))
                                                {
                                                    if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                                        newsomatic_log_to_file('Royalty Free Featured Image Generated with help of TextRazor (kw: "' . $query_words . '"): ' . $get_img);
                                                    }
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                }
                                catch(Exception $e)
                                {
                                    if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                        newsomatic_log_to_file('Failed to search for keywords using TextRazor: ' . $e->getMessage());
                                    }
                                }
                            }
                            if(empty($get_img))
                            {
                                $keyword_class = new Newsomatic_keywords();
                                $query_words = $keyword_class->keywords($title, 2);
                                $get_img = newsomatic_get_free_image($newsomatic_Main_Settings, $query_words, $img_attr, 10);
                                if($get_img == '' || $get_img === false)
                                {
                                    if(isset($newsomatic_Main_Settings['bimage']) && $newsomatic_Main_Settings['bimage'] == 'on')
                                    {
                                        $query_words = $keyword_class->keywords($title, 1);
                                        $get_img = newsomatic_get_free_image($newsomatic_Main_Settings, $query_words, $img_attr, 20);
                                        if($get_img == '' || $get_img === false)
                                        {
                                            if(isset($newsomatic_Main_Settings['no_orig']) && $newsomatic_Main_Settings['no_orig'] == 'on')
                                            {
                                                $get_img = '';
                                            }
                                            else
                                            {
                                                if($skip_img != '1')
                                                {
                                                    $get_img = $item->urlToImage;
                                                    if($get_img == 'null')
                                                    {
                                                        $get_img = '';
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    else
                                    {
                                        if(isset($newsomatic_Main_Settings['no_orig']) && $newsomatic_Main_Settings['no_orig'] == 'on')
                                        {
                                            $get_img = '';
                                        }
                                        else
                                        {
                                            if($skip_img != '1')
                                            {
                                                $get_img = $item->urlToImage;
                                                if($get_img == 'null')
                                                {
                                                    $get_img = '';
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        else
                        {
                            if($skip_img != '1')
                            {
                                $get_img = $item->urlToImage;
                                if($get_img == 'null')
                                {
                                    $get_img = '';
                                }
                            }
                        }
                        $media = '<img src="' . esc_url_raw($get_img) . '" alt="' . esc_html__('news image', 'newsomatic-news-post-generator') . '"/>';
                    }
                    else
                    {
                        if($royalty_free == '1')
                        {
                            if(isset($newsomatic_Main_Settings['textrazor_key']) && trim($newsomatic_Main_Settings['textrazor_key']) != '')
                            {
                                try
                                {
                                    if(!class_exists('TextRazor'))
                                    {
                                        require_once(dirname(__FILE__) . "/res/TextRazor.php");
                                    }
                                    TextRazorSettings::setApiKey(trim($newsomatic_Main_Settings['textrazor_key']));
                                    $textrazor = new TextRazor();
                                    $textrazor->addExtractor('entities');
                                    $response = $textrazor->analyze($title);
                                    if (isset($response['response']['entities'])) 
                                    {
                                        foreach ($response['response']['entities'] as $entity) 
                                        {
                                            $query_words = '';
                                            if(isset($entity['entityEnglishId']))
                                            {
                                                $query_words = $entity['entityEnglishId'];
                                            }
                                            else
                                            {
                                                $query_words = $entity['entityId'];
                                            }
                                            if($query_words != '')
                                            {
                                                $get_img = newsomatic_get_free_image($newsomatic_Main_Settings, $query_words, $img_attr, 10);
                                                if($get_img == false)
                                                {
                                                    $get_img = '';
                                                }
                                                if(!empty($get_img))
                                                {
                                                    if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                                        newsomatic_log_to_file('Royalty Free Featured Image Generated with help of TextRazor (kw: "' . $query_words . '"): ' . $get_img);
                                                    }
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                }
                                catch(Exception $e)
                                {
                                    if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                        newsomatic_log_to_file('Failed to search for keywords using TextRazor: ' . $e->getMessage());
                                    }
                                }
                            }
                            if(empty($get_img))
                            {
                                $keyword_class = new Newsomatic_keywords();
                                $query_words = $keyword_class->keywords($title, 2);
                                $get_img = newsomatic_get_free_image($newsomatic_Main_Settings, $query_words, $img_attr, 10);
                                if($get_img == '' || $get_img === false)
                                {
                                    if(isset($newsomatic_Main_Settings['bimage']) && $newsomatic_Main_Settings['bimage'] == 'on')
                                    {
                                        $query_words = $keyword_class->keywords($title, 1);
                                        $get_img = newsomatic_get_free_image($newsomatic_Main_Settings, $query_words, $img_attr, 20);
                                        if($get_img === false)
                                        {
                                            $get_img = '';
                                        }
                                    }
                                    else
                                    {
                                        $get_img = '';
                                    }
                                }
                            }
                        }
                    }
                    if(isset($item->source->Name))
                    {
                        $source_name = $item->source->Name;
                    }
                    else
                    {
                        $source_name = $item->source->name;
                    }
                    $source_id = urlencode($item->source->name);
					if($item->url != null)
					{
						$url = $item->url;
					}
					else
					{
						$url = '';
					}
                    if($url != '' && (isset($newsomatic_Main_Settings['shortest_api']) && $newsomatic_Main_Settings['shortest_api'] != '') || (isset($newsomatic_Main_Settings['links_hide']) && $newsomatic_Main_Settings['links_hide'] == 'on' && isset($newsomatic_Main_Settings['apiKey'])))
                    {
                        $short_url = newsomatic_url_handle($url, $newsomatic_Main_Settings['shortest_api']);
                    }
                    else
                    {
                        $short_url = $url;
                    }
                    $content = $item->content;
                    $content = preg_replace('@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@', '<a href="$1" target="_blank">$0</a>', $content);
                    $content = preg_replace('@(?:\[\+\d* chars\])@', '', $content);
                    $orig_content = $content;
                    $description = $item->description;
                    $author = $item->author;
                    if($author == '' || $author == '1' || $author == 'null')
                    {
                        $author = newsomatic_randomName();
                    }
					if($item->url != null)
					{
						$author_link = $item->url;
					}
					else
					{
						$author_link = '';
					}
                    $xfull_html = '';
                    $html_dl_failed = false;
                    if ($full_content == '1' && $url != '') {
                        $url = newsomatic_fix_google_links($url);
                        $exp_content = newsomatic_get_full_content($url, $full_type, htmlspecialchars_decode($expre), $only_text, $single, $inner, $encoding, $content_percent, $allow_tags, $use_phantom, $xfull_html, $html_dl_failed, $custom_cookies);
                        if($exp_content != '' && stristr(strip_tags($title), strip_tags($exp_content)) !== false)
                        {
                            $exp_content = 'skip_me';
                        }
                        if(isset($newsomatic_Main_Settings['no_import_no_class']) && $newsomatic_Main_Settings['no_import_no_class'] == 'on')
                        {
                            if($exp_content == '')
                            {
                                if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                    newsomatic_log_to_file('Skipping post ' . esc_html($item->url) . ', because it failed to import extended content.');
                                }
                                continue;
                            }
                            if(strstr($exp_content, ':null,') !== false)
                            {
                                if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                    newsomatic_log_to_file('Skipping post ' . esc_html($item->url) . ', because it failed to import extended content (null).');
                                }
                                continue;
                            }
                        }
                        if(isset($newsomatic_Main_Settings['no_import_full']) && $newsomatic_Main_Settings['no_import_full'] == 'on' && $exp_content == 'skip_me')
                        {
                            if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                newsomatic_log_to_file('Skipping post ' . esc_html($item->url) . ', because it failed to import extended content (skip_me).');
                            }
                            continue;
                        }
                        if ($exp_content !== FALSE && $exp_content != '' && $exp_content != 'skip_me') {
                            $content = $exp_content;
                        }
                    }
                    if($get_img == '' || ($image_type != '' && $image_type != 'auto' && $image_expre != ''))
                    {
                        if(!isset($newsomatic_Main_Settings['no_orig']) || $newsomatic_Main_Settings['no_orig'] != 'on')
                        {
                            $get_img = newsomatic_get_featured_image($content, $xfull_html, $html_dl_failed, $image_type, $image_expre, $use_phantom, $lazy_tag, $url, $get_img, $custom_cookies);
                        }
                    }
                    if (trim($content) == '') {
                        $content = $title;
                        if (trim($content) == '') {
                            if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                newsomatic_log_to_file('Skipping post ' . esc_html($item->url) . ', because it has blank content.');
                            }
                            continue;
                        }
                    }
                    if ($featured_image == '1' && isset($newsomatic_Main_Settings['skip_no_img']) && $newsomatic_Main_Settings['skip_no_img'] == 'on' && $get_img == '') {
                        if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                            newsomatic_log_to_file('Skipping post "' . esc_html($title) . '", because it has no detected image file attached');
                        }
                        continue;
                    }
                    if (trim($lazy_tag) != '' && trim($lazy_tag) != 'src')
                    {
                        $za_lazy_tags = explode(',', trim($lazy_tag));
                        foreach($za_lazy_tags as $za_lazy_tag)
                        {
                            if (trim($za_lazy_tag) != '' && trim($za_lazy_tag) != 'src' && strstr($content, trim($za_lazy_tag)) !== false) {
                                $za_lazy_tag = trim($za_lazy_tag);
                                $lazy_found = false;
                                preg_match_all('{<img .*?>}s', $content, $imgsMatchs);
                                if(isset($imgsMatchs[0]))
                                {
                                    $imgsMatchs = $imgsMatchs[0];
                                    foreach($imgsMatchs as $imgMatch){
                                        if(stristr($imgMatch, $za_lazy_tag )){
                                            $newImg = $imgMatch;
                                            $newImg = preg_replace('{ src=["\'].*?[\'"]}', '', $newImg);
                                            if(stristr($za_lazy_tag, 'srcset') !== false)
                                            {
                                                $newImg = preg_replace('{\ssrcset=["\'].*?[\'"]}', '', $newImg);
                                                $newImg = str_replace($za_lazy_tag, 'srcset', $newImg);
                                                preg_match_all('#srcset=[\'"](?:([^"\'\s,]+)\s*(?:\s+\d+[wx])(?:,\s*)?)+["\']#', $newImg, $imgma);
                                                if(isset($imgma[1][0]))
                                                {
                                                    $newImg = preg_replace('#<img#', '<img src="' . $imgma[1][0] . '"', $newImg);
                                                }
                                            }
                                            else
                                            {
                                                $newImg = str_replace($za_lazy_tag, 'src', $newImg); 
                                            }
                                            $content = str_replace($imgMatch, $newImg, $content);  
                                            $lazy_found = true;       
                                        }
                                    }
                                }
                                if($lazy_found == false)
                                {
                                    $content = str_replace(trim($za_lazy_tag), 'src', $content); 
                                }
                                preg_match_all('{<iframe .*?>}s', $content, $imgsMatchs);
                                if(isset($imgsMatchs[0]))
                                {
                                    $imgsMatchs = $imgsMatchs[0];
                                    if(is_array($imgsMatchs))
                                    {
                                        foreach($imgsMatchs as $imgMatch){
                                            if(stristr($imgMatch, $za_lazy_tag )){
                                                $newImg = $imgMatch;
                                                $newImg = preg_replace('{ src=".*?"}', '', $newImg);
                                                $newImg = str_replace($za_lazy_tag, 'src', $newImg);   
                                                $content = str_replace($imgMatch, $newImg, $content);      
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    else
                    {
                        $content = newsomatic_lazy_loading_auto_fix($content);
                    }
                    $date = $item->publishedAt;
                    if (isset($newsomatic_Main_Settings['skip_old']) && $newsomatic_Main_Settings['skip_old'] == 'on' && isset($newsomatic_Main_Settings['skip_year']) && $newsomatic_Main_Settings['skip_year'] !== '' && isset($newsomatic_Main_Settings['skip_month']) && isset($newsomatic_Main_Settings['skip_day'])) {
                        $old_date      = $newsomatic_Main_Settings['skip_day'] . '-' . $newsomatic_Main_Settings['skip_month'] . '-' . $newsomatic_Main_Settings['skip_year'];
                        $time_date     = strtotime($date);
                        $time_old_date = strtotime($old_date);
                        if ($time_date !== false && $time_old_date !== false) {
                            if ($time_date < $time_old_date) {
                                if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                    newsomatic_log_to_file('Skipping post "' . esc_html($title) . '", because it is older than ' . $old_date . ' - posted on ' . $date);
                                }
                                continue;
                            }
                        }
                    }
                    if (isset($newsomatic_Main_Settings['skip_old']) && $newsomatic_Main_Settings['skip_old'] == 'on' && isset($newsomatic_Main_Settings['old_text']) && $newsomatic_Main_Settings['old_text'] != '') 
                    {
                        $old_date      = $newsomatic_Main_Settings['old_text'];
                        $time_date     = strtotime($date);
                        $time_old_date = strtotime($old_date);
                        if ($time_date !== false && $time_old_date !== false) {
                            if ($time_date < $time_old_date) {
                                if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                    newsomatic_log_to_file('Skipping post "' . esc_html($title) . '", because it is older than predefined date ' . $old_date . ' - posted on ' . $date);
                                }
                                continue;
                            }
                        }
                    }
                }
                elseif($type == 1)
                {
                    $title = $item->title;
                    if (!isset($newsomatic_Main_Settings['title_duplicates']) || $newsomatic_Main_Settings['title_duplicates'] != 'on') 
                    {
                        if($item->url != null)
                        {
                            $id = $item->url;
                        }
                        else
                        {
                            $id = $item->title;
                        }
                    }
                    else
                    {
                        $id = $item->title;
                    }
                    if (!isset($newsomatic_Main_Settings['do_not_check_duplicates']) || $newsomatic_Main_Settings['do_not_check_duplicates'] != 'on') {
                        if($ret_content == 0)
                        {
                            $query = array(
                                'post_status' => array(
                                    'publish',
                                    'draft',
                                    'pending',
                                    'trash',
                                    'private',
                                    'future'
                                ),
                                'post_type' => array(
                                    'any'
                                ),
                                'numberposts' => '1',
                                'fields' => 'ids',
                                'meta_query' => array(
                                    array(
                                        'key' => 'newsomatic_post_id',
                                        'value' => $id,
                                        'compare' => '='
                                    )
                                )
                            );
                            $got_me = get_posts($query);
                            if(count($got_me) > 0)
                            {
                                if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                    newsomatic_log_to_file('Skipping post, already published: ' . $title);
                                }
                                continue;
                            }
                        }
                    }
                    if(isset($item->urlToImage))
                    {
                        if($royalty_free == '1')
                        {
                            if(isset($newsomatic_Main_Settings['textrazor_key']) && trim($newsomatic_Main_Settings['textrazor_key']) != '')
                            {
                                try
                                {
                                    if(!class_exists('TextRazor'))
                                    {
                                        require_once(dirname(__FILE__) . "/res/TextRazor.php");
                                    }
                                    TextRazorSettings::setApiKey(trim($newsomatic_Main_Settings['textrazor_key']));
                                    $textrazor = new TextRazor();
                                    $textrazor->addExtractor('entities');
                                    $response = $textrazor->analyze($title);
                                    if (isset($response['response']['entities'])) 
                                    {
                                        foreach ($response['response']['entities'] as $entity) 
                                        {
                                            $query_words = '';
                                            if(isset($entity['entityEnglishId']))
                                            {
                                                $query_words = $entity['entityEnglishId'];
                                            }
                                            else
                                            {
                                                $query_words = $entity['entityId'];
                                            }
                                            if($query_words != '')
                                            {
                                                $get_img = newsomatic_get_free_image($newsomatic_Main_Settings, $query_words, $img_attr, 10);
                                                if($get_img == false)
                                                {
                                                    $get_img = '';
                                                }
                                                if(!empty($get_img))
                                                {
                                                    if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                                        newsomatic_log_to_file('Royalty Free Featured Image Generated with help of TextRazor (kw: "' . $query_words . '"): ' . $get_img);
                                                    }
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                }
                                catch(Exception $e)
                                {
                                    if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                        newsomatic_log_to_file('Failed to search for keywords using TextRazor: ' . $e->getMessage());
                                    }
                                }
                            }
                            if(empty($get_img))
                            {
                                $keyword_class = new Newsomatic_keywords();
                                $query_words = $keyword_class->keywords($title, 2);
                                $get_img = newsomatic_get_free_image($newsomatic_Main_Settings, $query_words, $img_attr, 10);
                                if($get_img == '' || $get_img === false)
                                {
                                    if(isset($newsomatic_Main_Settings['bimage']) && $newsomatic_Main_Settings['bimage'] == 'on')
                                    {
                                        $query_words = $keyword_class->keywords($title, 1);
                                        $get_img = newsomatic_get_free_image($newsomatic_Main_Settings, $query_words, $img_attr, 20);
                                        if($get_img == '' || $get_img === false)
                                        {
                                            if(isset($newsomatic_Main_Settings['no_orig']) && $newsomatic_Main_Settings['no_orig'] == 'on')
                                            {
                                                $get_img = '';
                                            }
                                            else
                                            {
                                                if($skip_img != '1')
                                                {
                                                    $get_img = $item->urlToImage;
                                                    if($get_img == 'null')
                                                    {
                                                        $get_img = '';
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    else
                                    {
                                        if(isset($newsomatic_Main_Settings['no_orig']) && $newsomatic_Main_Settings['no_orig'] == 'on')
                                        {
                                            $get_img = '';
                                        }
                                        else
                                        {
                                            if($skip_img != '1')
                                            {
                                                $get_img = $item->urlToImage;
                                                if($get_img == 'null')
                                                {
                                                    $get_img = '';
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        else
                        {
                            if($skip_img != '1')
                            {
                                $get_img = $item->urlToImage;
                                if($get_img == 'null')
                                {
                                    $get_img = '';
                                }
                            }
                        }
                        $media = '<img src="' . esc_url_raw($get_img) . '" alt="' . esc_html__('news image', 'newsomatic-news-post-generator') . '"/>';
                    }
                    else
                    {
                        if($royalty_free == '1')
                        {
                            if(isset($newsomatic_Main_Settings['textrazor_key']) && trim($newsomatic_Main_Settings['textrazor_key']) != '')
                            {
                                try
                                {
                                    if(!class_exists('TextRazor'))
                                    {
                                        require_once(dirname(__FILE__) . "/res/TextRazor.php");
                                    }
                                    TextRazorSettings::setApiKey(trim($newsomatic_Main_Settings['textrazor_key']));
                                    $textrazor = new TextRazor();
                                    $textrazor->addExtractor('entities');
                                    $response = $textrazor->analyze($title);
                                    if (isset($response['response']['entities'])) 
                                    {
                                        foreach ($response['response']['entities'] as $entity) 
                                        {
                                            $query_words = '';
                                            if(isset($entity['entityEnglishId']))
                                            {
                                                $query_words = $entity['entityEnglishId'];
                                            }
                                            else
                                            {
                                                $query_words = $entity['entityId'];
                                            }
                                            if($query_words != '')
                                            {
                                                $get_img = newsomatic_get_free_image($newsomatic_Main_Settings, $query_words, $img_attr, 10);
                                                if($get_img == false)
                                                {
                                                    $get_img = '';
                                                }
                                                if(!empty($get_img))
                                                {
                                                    if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                                        newsomatic_log_to_file('Royalty Free Featured Image Generated with help of TextRazor (kw: "' . $query_words . '"): ' . $get_img);
                                                    }
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                }
                                catch(Exception $e)
                                {
                                    if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                        newsomatic_log_to_file('Failed to search for keywords using TextRazor: ' . $e->getMessage());
                                    }
                                }
                            }
                            if(empty($get_img))
                            {
                                $keyword_class = new Newsomatic_keywords();
                                $query_words = $keyword_class->keywords($title, 2);
                                $get_img = newsomatic_get_free_image($newsomatic_Main_Settings, $query_words, $img_attr, 10);
                                if($get_img == '' || $get_img === false)
                                {
                                    if(isset($newsomatic_Main_Settings['bimage']) && $newsomatic_Main_Settings['bimage'] == 'on')
                                    {
                                        $query_words = $keyword_class->keywords($title, 1);
                                        $get_img = newsomatic_get_free_image($newsomatic_Main_Settings, $query_words, $img_attr, 20);
                                        if($get_img === false)
                                        {
                                            $get_img = '';
                                        }
                                    }
                                    else
                                    {
                                        $get_img = '';
                                    }
                                }
                            }
                        }
                    }
                    if(isset($item->source->Name))
                    {
                        $source_name = $item->source->Name;
                    }
                    else
                    {
                        $source_name = $item->source->name;
                    }
                    $source_id = urlencode($item->source->name);
					if($item->url != null)
					{
						$url = $item->url;
					}
					else
					{
						$url = '';
					}
                    if($url != '' && (isset($newsomatic_Main_Settings['shortest_api']) && $newsomatic_Main_Settings['shortest_api'] != '') || (isset($newsomatic_Main_Settings['links_hide']) && $newsomatic_Main_Settings['links_hide'] == 'on' && isset($newsomatic_Main_Settings['apiKey'])))
                    {
                        $short_url = newsomatic_url_handle($url, $newsomatic_Main_Settings['shortest_api']);
                    }
                    else
                    {
                        $short_url = $url;
                    }
                    $content = $item->content;
                    $content = preg_replace('@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@', '<a href="$1" target="_blank">$0</a>', $content);
                    $content = preg_replace('@(?:\[\+\d* chars\])@', '', $content);
                    $orig_content = $content;
                    $description = $item->description;
                    $author = $item->author;
                    if($author == '' || $author == '1' || $author == 'null')
                    {
                        $author = newsomatic_randomName();
                    }
					if($item->url != null)
					{
						$author_link = $item->url;
					}
					else
					{
						$author_link = '';
					}
                    $xfull_html = '';
                    $html_dl_failed = false;
                    if ($full_content == '1' && $url != '') {
                        $url = newsomatic_fix_google_links($url);
                        $exp_content = newsomatic_get_full_content($url, $full_type, htmlspecialchars_decode($expre), $only_text, $single, $inner, $encoding, $content_percent, $allow_tags, $use_phantom, $xfull_html, $html_dl_failed, $custom_cookies);
                        if($exp_content != '' && stristr(strip_tags($title), strip_tags($exp_content)) !== false)
                        {
                            $exp_content = 'skip_me';
                        }
                        if(isset($newsomatic_Main_Settings['no_import_no_class']) && $newsomatic_Main_Settings['no_import_no_class'] == 'on')
                        {
                            if($exp_content == '')
                            {
                                if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                    newsomatic_log_to_file('Skipping post ' . esc_html($item->url) . ', because it has did not get full content.');
                                }
                                continue;
                            }
                            if(strstr($exp_content, ':null,') !== false)
                            {
                                if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                    newsomatic_log_to_file('Skipping post ' . esc_html($item->url) . ', because it did not get full content (null).');
                                }
                                continue;
                            }
                        }
                        if(isset($newsomatic_Main_Settings['no_import_full']) && $newsomatic_Main_Settings['no_import_full'] == 'on' && $exp_content == 'skip_me')
                        {
                            if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                newsomatic_log_to_file('Skipping post ' . esc_html($item->url) . ', because it did not get full content (skip_me).');
                            }
                            continue;
                        }
                        if ($exp_content !== FALSE && $exp_content != '' && $exp_content != 'skip_me') {
                            $content = $exp_content;
                        }
                    }
                    if($get_img == '' || ($image_type != '' && $image_type != 'auto' && $image_expre != ''))
                    {
                        if(!isset($newsomatic_Main_Settings['no_orig']) || $newsomatic_Main_Settings['no_orig'] != 'on')
                        {
                            $get_img = newsomatic_get_featured_image($content, $xfull_html, $html_dl_failed, $image_type, $image_expre, $use_phantom, $lazy_tag, $url, $get_img, $custom_cookies);
                        }
                    }
                    if (trim($content) == '') {
                        $content = $title;
                        if (trim($content) == '') {
                            if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                newsomatic_log_to_file('Skipping post ' . esc_html($item->url) . ', because it has blank content.');
                            }
                            continue;
                        }
                    }
                    if ($featured_image == '1' && isset($newsomatic_Main_Settings['skip_no_img']) && $newsomatic_Main_Settings['skip_no_img'] == 'on' && $get_img == '') {
                        if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                            newsomatic_log_to_file('Skipping post "' . esc_html($title) . '", because it has no detected image file attached');
                        }
                        continue;
                    }
                    
                    if (trim($lazy_tag) != '' && trim($lazy_tag) != 'src')
                    {
                        $za_lazy_tags = explode(',', trim($lazy_tag));
                        foreach($za_lazy_tags as $za_lazy_tag)
                        {
                            if (trim($za_lazy_tag) != '' && trim($za_lazy_tag) != 'src' && strstr($content, trim($za_lazy_tag)) !== false) {
                                $za_lazy_tag = trim($za_lazy_tag);
                                preg_match_all('{<img .*?>}s', $content, $imgsMatchs);
                                if(isset($imgsMatchs[0]))
                                {
                                    $imgsMatchs = $imgsMatchs[0];
                                    foreach($imgsMatchs as $imgMatch){
                                        if(stristr($imgMatch, $za_lazy_tag )){
                                            $newImg = $imgMatch;
                                            $newImg = preg_replace('{ src=["\'].*?[\'"]}', '', $newImg);
                                            if(stristr($za_lazy_tag, 'srcset') !== false)
                                            {
                                                $newImg = preg_replace('{\ssrcset=["\'].*?[\'"]}', '', $newImg);
                                                $newImg = str_replace($za_lazy_tag, 'srcset', $newImg);
                                                preg_match_all('#srcset=[\'"](?:([^"\'\s,]+)\s*(?:\s+\d+[wx])(?:,\s*)?)+["\']#', $newImg, $imgma);
                                                if(isset($imgma[1][0]))
                                                {
                                                    $newImg = preg_replace('#<img#', '<img src="' . $imgma[1][0] . '"', $newImg);
                                                }
                                            }
                                            else
                                            {
                                                $newImg = str_replace($za_lazy_tag, 'src', $newImg); 
                                            }
                                            $content = str_replace($imgMatch, $newImg, $content);      
                                        }
                                    }
                                }
                                preg_match_all('{<iframe .*?>}s', $content, $imgsMatchs);
                                if(isset($imgsMatchs[0]))
                                {
                                    $imgsMatchs = $imgsMatchs[0];
                                    if(is_array($imgsMatchs))
                                    {
                                        foreach($imgsMatchs as $imgMatch){
                                            if(stristr($imgMatch, $za_lazy_tag )){
                                                $newImg = $imgMatch;
                                                $newImg = preg_replace('{ src=".*?"}', '', $newImg);
                                                $newImg = str_replace($za_lazy_tag, 'src', $newImg);   
                                                $content = str_replace($imgMatch, $newImg, $content);      
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    else
                    {
                        $content = newsomatic_lazy_loading_auto_fix($content);
                    }
                    $date = $item->publishedAt;
                    if (isset($newsomatic_Main_Settings['skip_old']) && $newsomatic_Main_Settings['skip_old'] == 'on' && isset($newsomatic_Main_Settings['skip_year']) && $newsomatic_Main_Settings['skip_year'] !== '' && isset($newsomatic_Main_Settings['skip_month']) && isset($newsomatic_Main_Settings['skip_day'])) {
                        $old_date      = $newsomatic_Main_Settings['skip_day'] . '-' . $newsomatic_Main_Settings['skip_month'] . '-' . $newsomatic_Main_Settings['skip_year'];
                        $time_date     = strtotime($date);
                        $time_old_date = strtotime($old_date);
                        if ($time_date !== false && $time_old_date !== false) {
                            if ($time_date < $time_old_date) {
                                if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                    newsomatic_log_to_file('Skipping post "' . esc_html($title) . '", because it is older than ' . $old_date . ' - posted on ' . $date);
                                }
                                continue;
                            }
                        }
                    }
                    if (isset($newsomatic_Main_Settings['skip_old']) && $newsomatic_Main_Settings['skip_old'] == 'on' && isset($newsomatic_Main_Settings['old_text']) && $newsomatic_Main_Settings['old_text'] != '') 
                    {
                        $old_date      = $newsomatic_Main_Settings['old_text'];
                        $time_date     = strtotime($date);
                        $time_old_date = strtotime($old_date);
                        if ($time_date !== false && $time_old_date !== false) {
                            if ($time_date < $time_old_date) {
                                if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                    newsomatic_log_to_file('Skipping post "' . esc_html($title) . '", because it is older than predefined date ' . $old_date . ' - posted on ' . $date);
                                }
                                continue;
                            }
                        }
                    }
                }
                $my_post                              = array();
                $postdate = strtotime($date);
                if($postdate !== FALSE)
                {
                    $postdate = gmdate("Y-m-d H:i:s", intval($postdate));
                }
                if($import_date == '1')
                {
                    if($postdate !== FALSE)
                    {
                        $my_post['post_date_gmt'] = $postdate;
                    }
                    else
                    {
                        $postdatex = gmdate("Y-m-d H:i:s", intval($init_date));
                        $my_post['post_date_gmt'] = $postdatex;
                        $init_date = $init_date - 1;
                    }
                }
                else
                {
                    $postdatex = gmdate("Y-m-d H:i:s", intval($init_date));
                    $my_post['post_date_gmt'] = $postdatex;
                    $init_date = $init_date - 1;
                }
                
                
                $my_post['newsomatic_post_id']          = $id;
                if(substr($get_img, 0, 2) === "//")
                {
                    $get_img = 'http:' . $get_img;
                }
                $my_post['newsomatic_post_image']     = $get_img;
                $my_post['default_category']          = $default_category;
                $my_post['post_type']                 = $post_type;
                $my_post['comment_status']            = $accept_comments;
                if (isset($newsomatic_Main_Settings['draft_first']) && $newsomatic_Main_Settings['draft_first'] == 'on')
                {
                    if($post_status == 'publish')
                    {
                        $draft_me = true;
                        $my_post['post_status'] = 'draft';
                    }
                    else
                    {
                        $my_post['post_status']   = $post_status;
                    }
                }
                else
                {
                    $my_post['post_status'] = $post_status;
                }
                if($user_name_type == 'rand')
                {
                    $randid = newsomatic_display_random_user();
                    if($randid === false)
                    {
                        $my_post['post_author']               = newsomatic_randomName();
                    }
                    else
                    {
                        $my_post['post_author']               = $randid->ID;
                    }
                }
                elseif($user_name_type == 'feed-news')
                {
                    $sp_post_user_name = newsomatic_randomName();
                    if($author == '' || $author == '1' || $author == 'null')
                    {
                        $author = newsomatic_randomName();
                    }
                    if($author != '')
                    {
                        $xauthor = sanitize_user( $author );
                        $xauthor = apply_filters( 'pre_user_login', $xauthor );
                        $xauthor = trim( $xauthor );
                        if(username_exists( $xauthor ))
                        {
                            $user_id_t = get_user_by('login', $xauthor);
                            if($user_id_t)
                            {
                                $sp_post_user_name = $user_id_t->ID;
                            }
                        }
                        else
                        {
                            $palphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890!@#$%^*()-+=_?><,.;:}{][';
                            $ppass = '';
                            $alphaLength = strlen($palphabet) - 1;
                            for ($ipass = 0; $ipass < 8; $ipass++) 
                            {
                                $npass = rand(0, $alphaLength);
                                $ppass .= $palphabet[$npass];
                            }
                            $curr_id = wp_create_user($xauthor, $ppass, newsomatic_generate_random_email());
                            if ( is_int($curr_id) )
                            {
                                $u = new WP_User($curr_id);
                                $u->remove_role('subscriber');
                                $u->add_role('author');
                                $sp_post_user_name               = $curr_id;
                            }
                        }
                    }
                    $my_post['post_author']               = newsomatic_utf8_encode($sp_post_user_name);
                }
                else
                {
                    $my_post['post_author']               = newsomatic_utf8_encode($post_user_name);
                }
                $my_post['newsomatic_post_url']         = $short_url;
                $my_post['newsomatic_post_date']        = $date;
                if ($strip_by_id != '') {
                    $mock = new DOMDocument;
                    $strip_list = explode(',', $strip_by_id);
                    $doc        = new DOMDocument();
                    $internalErrors = libxml_use_internal_errors(true);
                    $doc->loadHTML('<?xml encoding="utf-8" ?>' . $content);
                    libxml_use_internal_errors($internalErrors);
                    foreach ($strip_list as $strip_id) {
                        $element = $doc->getElementById(trim($strip_id));
                        if (isset($element)) {
                            if($element->parentNode !== null)
                            {
                                $element->parentNode->removeChild($element);
                            }
                        }
                    }
                    $body = $doc->getElementsByTagName('body')->item(0);
                    if(isset($body->childNodes))
                    {
                        foreach ($body->childNodes as $child){
                            $mock->appendChild($mock->importNode($child, true));
                        }
                        $temp_cont = $mock->saveHTML();
                        if($temp_cont !== FALSE && $temp_cont != '')
                        {
                            $temp_cont = str_replace('<?xml encoding="utf-8" ?>', '', $temp_cont);$temp_cont = html_entity_decode($temp_cont);$temp_cont = trim($temp_cont);if(substr_compare($temp_cont, '</p>', -strlen('</p>')) === 0){$temp_cont = substr_replace($temp_cont ,"", -4);}if(substr( $temp_cont, 0, 3 ) === "<p>"){$temp_cont = substr($temp_cont, 3);}
                            $content = $temp_cont;
                        }
                    }
                }              
                if ($strip_by_class != '') {
                    $mock = new DOMDocument;
                    $strip_list = explode(',', $strip_by_class);
                    $doc        = new DOMDocument();
                    $internalErrors = libxml_use_internal_errors(true);
                    $doc->loadHTML('<?xml encoding="utf-8" ?>' . $content);
                    libxml_use_internal_errors($internalErrors);
                    foreach ($strip_list as $strip_class) {
                        if(trim($strip_class) == '')
                        {
                            continue;
                        }
                        $finder    = new DomXPath($doc);
                        $classname = trim($strip_class);
                        $nodes     = $finder->query("//*[contains(@class, '$classname')]");
                        if ($nodes === FALSE) {
                            break;
                        }
                        foreach ($nodes as $node) {
                            if($node->parentNode !== null)
                            {
                                $node->parentNode->removeChild($node);
                            }
                        }
                    }
                    $body = $doc->getElementsByTagName('body')->item(0);
                    if(isset($body->childNodes))
                    {
                        foreach ($body->childNodes as $child){
                            $mock->appendChild($mock->importNode($child, true));
                        }
                        $temp_cont = $mock->saveHTML();
                        if($temp_cont !== FALSE && $temp_cont != '')
                        {
                            $temp_cont = str_replace('<?xml encoding="utf-8" ?>', '', $temp_cont);$temp_cont = html_entity_decode($temp_cont);$temp_cont = trim($temp_cont);if(substr_compare($temp_cont, '</p>', -strlen('</p>')) === 0){$temp_cont = substr_replace($temp_cont ,"", -4);}if(substr( $temp_cont, 0, 3 ) === "<p>"){$temp_cont = substr($temp_cont, 3);}
                            $content = $temp_cont;
                        }
                    }
                }
                if (isset($newsomatic_Main_Settings['strip_by_id']) && $newsomatic_Main_Settings['strip_by_id'] != '') {
                    $mock = new DOMDocument;
                    $strip_list = explode(',', $newsomatic_Main_Settings['strip_by_id']);
                    $doc        = new DOMDocument();
                    $internalErrors = libxml_use_internal_errors(true);
                    $doc->loadHTML('<?xml encoding="utf-8" ?>' . $content);
                    libxml_use_internal_errors($internalErrors);
                    foreach ($strip_list as $strip_id) {
                        $element = $doc->getElementById(trim($strip_id));
                        if (isset($element)) {
                            if($element->parentNode !== null)
                            {
                                $element->parentNode->removeChild($element);
                            }
                        }
                    }
                    $body = $doc->getElementsByTagName('body')->item(0);
                    if(isset($body->childNodes))
                    {
                        foreach ($body->childNodes as $child){
                            $mock->appendChild($mock->importNode($child, true));
                        }
                        $temp_cont = $mock->saveHTML();
                        if($temp_cont !== FALSE && $temp_cont != '')
                        {
                            $temp_cont = str_replace('<?xml encoding="utf-8" ?>', '', $temp_cont);$temp_cont = html_entity_decode($temp_cont);$temp_cont = trim($temp_cont);if(substr_compare($temp_cont, '</p>', -strlen('</p>')) === 0){$temp_cont = substr_replace($temp_cont ,"", -4);}if(substr( $temp_cont, 0, 3 ) === "<p>"){$temp_cont = substr($temp_cont, 3);}
                            $content = $temp_cont;
                        }
                    }
                }              
                if (isset($newsomatic_Main_Settings['strip_by_class']) && $newsomatic_Main_Settings['strip_by_class'] != '') {
                    $mock = new DOMDocument;
                    $strip_list = explode(',', $newsomatic_Main_Settings['strip_by_class']);
                    $doc        = new DOMDocument();
                    $internalErrors = libxml_use_internal_errors(true);
                    $doc->loadHTML('<?xml encoding="utf-8" ?>' . $content);
                    libxml_use_internal_errors($internalErrors);
                    foreach ($strip_list as $strip_class) {
                        if(trim($strip_class) == '')
                        {
                            continue;
                        }
                        $finder    = new DomXPath($doc);
                        $classname = trim($strip_class);
                        $nodes     = $finder->query("//*[contains(@class, '$classname')]");
                        if ($nodes === FALSE) {
                            break;
                        }
                        foreach ($nodes as $node) {
                            if($node->parentNode !== null)
                            {
                                $node->parentNode->removeChild($node);
                            }
                        }
                    }
                    $body = $doc->getElementsByTagName('body')->item(0);
                    if(isset($body->childNodes))
                    {
                        foreach ($body->childNodes as $child){
                            $mock->appendChild($mock->importNode($child, true));
                        }
                        $temp_cont = $mock->saveHTML();
                        if($temp_cont !== FALSE && $temp_cont != '')
                        {
                            $temp_cont = str_replace('<?xml encoding="utf-8" ?>', '', $temp_cont);$temp_cont = html_entity_decode($temp_cont);$temp_cont = trim($temp_cont);if(substr_compare($temp_cont, '</p>', -strlen('</p>')) === 0){$temp_cont = substr_replace($temp_cont ,"", -4);}if(substr( $temp_cont, 0, 3 ) === "<p>"){$temp_cont = substr($temp_cont, 3);}
                            $content = $temp_cont;
                        }
                    }
                }
                if (isset($newsomatic_Main_Settings['strip_links']) && $newsomatic_Main_Settings['strip_links'] == 'on') {
                    $content = newsomatic_strip_links($content);
                }
                if ((isset($newsomatic_Main_Settings['strip_internal_content_links']) && $newsomatic_Main_Settings['strip_internal_content_links'] == 'on')) {
                    $content = newsomatic_strip_external_links($content, $url);
                }
                if ($get_img != '' && isset($newsomatic_Main_Settings['strip_featured_image']) && $newsomatic_Main_Settings['strip_featured_image'] == 'on') {
                    $get_img_tmp = explode('?', $get_img);
                    $get_img_tmp = $get_img_tmp[0];
                    $ext = pathinfo($get_img_tmp, PATHINFO_EXTENSION);
                    $get_img_tmp = preg_quote($get_img_tmp);
                    $get_img_tmp = str_replace('\.' . $ext, '(?:-?\d+x\d+)\.' . $ext, $get_img_tmp);
                    $content = preg_replace('#<img(?:[^<>]*?)=[\'"]' . $get_img_tmp . '(?:\?[^<>]*?)?[\'"][^<>]*?\/?>#i', '', $content);
                }
                if($limit_content_word_count != '' && is_numeric($limit_content_word_count))
                {
                    $content = newsomatic_custom_wp_trim_excerpt($content, $limit_content_word_count, $short_url, '...');
                }
                if (!isset($newsomatic_Main_Settings['disable_dmca']) || $newsomatic_Main_Settings['disable_dmca'] != 'on') 
                {
                    if(stristr($content, 'detected unusual activity from your computer network') !== false || stristr($content, 'detected unusual traffic from your computer network') !== false)
                    {
                        if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                            newsomatic_log_to_file('Skipping post ' . esc_html($item->url) . ', because it has bot detection activated.');
                        }
                        continue;
                    }
                }
                if (isset($newsomatic_Main_Settings['restriction_before']) && $newsomatic_Main_Settings['restriction_before'] == 'on') 
                {
                    $title_count = -1;
                    if (isset($newsomatic_Main_Settings['min_word_title']) && $newsomatic_Main_Settings['min_word_title'] != '') {
                        $title_count = str_word_count($title);
                        if ($title_count < intval($newsomatic_Main_Settings['min_word_title'])) {
                            if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                newsomatic_log_to_file('Skipping post "' . esc_html($title) . '", because title length < ' . $newsomatic_Main_Settings['min_word_title']);
                            }
                            continue;
                        }
                    }
                    if (isset($newsomatic_Main_Settings['max_word_title']) && $newsomatic_Main_Settings['max_word_title'] != '') {
                        if ($title_count == -1) {
                            $title_count = str_word_count($title);
                        }
                        if ($title_count > intval($newsomatic_Main_Settings['max_word_title'])) {
                            if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                newsomatic_log_to_file('Skipping post "' . esc_html($title) . '", because title length > ' . $newsomatic_Main_Settings['max_word_title']);
                            }
                            continue;
                        }
                    }
                    $content_count = -1;
                    if (isset($newsomatic_Main_Settings['min_word_content']) && $newsomatic_Main_Settings['min_word_content'] != '') {
                        $content_count = str_word_count(newsomatic_strip_html_tags($content));
                        if ($content_count < intval($newsomatic_Main_Settings['min_word_content'])) {
                            if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                newsomatic_log_to_file('Skipping post "' . esc_html($title) . '", because content length < ' . $newsomatic_Main_Settings['min_word_content']);
                            }
                            continue;
                        }
                    }
                    if (isset($newsomatic_Main_Settings['max_word_content']) && $newsomatic_Main_Settings['max_word_content'] != '') {
                        if ($content_count == -1) {
                            $content_count = str_word_count(newsomatic_strip_html_tags($content));
                        }
                        if ($content_count > intval($newsomatic_Main_Settings['max_word_content'])) {
                            if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                newsomatic_log_to_file('Skipping post "' . esc_html($title) . '", because content length > ' . $newsomatic_Main_Settings['max_word_content']);
                            }
                            continue;
                        }
                    }
                    if (isset($newsomatic_Main_Settings['banned_words']) && $newsomatic_Main_Settings['banned_words'] != '') {
                        $continue    = false;
                        $banned_list = explode(',', $newsomatic_Main_Settings['banned_words']);
                        foreach ($banned_list as $banned_word) {
                            if (stripos($content, trim($banned_word)) !== FALSE) {
                                if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                    newsomatic_log_to_file('Skipping post "' . esc_html($title) . '", because it\'s content contains banned word: ' . $banned_word);
                                }
                                $continue = true;
                                break;
                            }
                            if (stripos($title, trim($banned_word)) !== FALSE) {
                                if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                    newsomatic_log_to_file('Skipping post "' . esc_html($title) . '", because it\'s title contains banned word: ' . $banned_word);
                                }
                                $continue = true;
                                break;
                            }
                            if (stripos($url, trim($banned_word)) !== FALSE) {
                                if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                    newsomatic_log_to_file('Skipping post "' . esc_html($title) . '", because it\'s URL contains banned word: ' . $banned_word);
                                }
                                $continue = true;
                                break;
                            }
                        }
                        if ($continue === true) {
                            continue;
                        }
                    }
                    if (isset($newsomatic_Main_Settings['required_words']) && $newsomatic_Main_Settings['required_words'] != '') {
                        if (isset($newsomatic_Main_Settings['require_all']) && $newsomatic_Main_Settings['require_all'] == 'on') {
                            $require_all = true;
                        }
                        else
                        {
                            $require_all = false;
                        }
                        
                        $required_list = explode(',', $newsomatic_Main_Settings['required_words']);
                        if($require_all === true)
                        {
                            $continue      = false;
                            foreach ($required_list as $required_word) {
                                if (stripos($content, trim($required_word)) === FALSE) {
                                    if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                        newsomatic_log_to_file('Skipping post "' . esc_html($title) . '", because it\'s content doesn\'t contain required word: ' . $required_word);
                                    }
                                    $continue = true;
                                    break;
                                }
                                if (stripos($title, trim($required_word)) === FALSE) {
                                    if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                        newsomatic_log_to_file('Skipping post "' . esc_html($title) . '", because it\'s title doesn\'t contain required word: ' . $required_word);
                                    }
                                    $continue = true;
                                    break;
                                }
                            }
                        }
                        else
                        {
                            $continue      = true;
                            foreach ($required_list as $required_word) {
                                if (stripos($content, trim($required_word)) !== FALSE) {
                                    $continue = false;
                                    break;
                                }
                                if (stripos($title, trim($required_word)) !== FALSE) {
                                    $continue = false;
                                    break;
                                }
                            }
                        }
                        if ($continue === true) {
                            if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                newsomatic_log_to_file('Skipping post ' . esc_html($item->url) . ', because of required words.');
                            }
                            continue;
                        }
                    }
                }
                if(isset($newsomatic_Main_Settings['litte_translate']) && $newsomatic_Main_Settings['litte_translate'] == 'on')
                {
                    $arr = newsomatic_spin_and_translate($title, $content, $rule_translate, $rule_translate_source, $skip_spin_translate, $second_translate);
                    if($arr === false)
                    {
                        if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                            newsomatic_log_to_file('Skipping post: ' . esc_html($item->url) . ', because it failed to be translated/spinned.');
                        }
                        continue;
                    }
                    $title              = $arr[0];
                    $content            = $arr[1];
                }
                $new_post_content = newsomatic_replaceContentShortcodes($post_content, $title, $content, $description, $author, $media, $date, $orig_content, $img_attr, $short_url, $get_img, $author_link, $source_name, $source_id, $query_string);
                $new_post_title = newsomatic_replaceTitleShortcodes($post_title, $title, $content, $short_url, $date, $author, $source_name, $source_id);
                if($strip_tsource == '1')
                {
                    $new_post_title = preg_replace('#-\s([^-]+)#', '', $new_post_title);
                }
                $my_post['description']      = $description;
                $my_post['author']           = $author;
                $my_post['author_link']      = $author_link;
                $keyword_class = new Newsomatic_keywords();
                $title_words = $keyword_class->keywords($title, 2);
                $title_words = str_replace(' ', ',', $title_words);
                if(!isset($newsomatic_Main_Settings['litte_translate']) || $newsomatic_Main_Settings['litte_translate'] != 'on')
                {
                    $arr = newsomatic_spin_and_translate($new_post_title, $new_post_content, $rule_translate, $rule_translate_source, $skip_spin_translate, $second_translate);
                    if($arr === false)
                    {
                        if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                            newsomatic_log_to_file('Skipping post ' . esc_html($item->url) . ', because it failed to be translated/spinned.');
                        }
                        continue;
                    }
                    $new_post_title              = $arr[0];
                    $new_post_content            = $arr[1];
                    $new_post_content = str_replace('%% item_show_image %%', '%%item_show_image%%', $new_post_content);
                }
                if ($auto_categories == 'title') 
                {
                    if(isset($newsomatic_Main_Settings['new_category']) && $newsomatic_Main_Settings['new_category'] == 'on')
                    {
                        $blog_cats = newsomatic_my_list_cats();
                        $blog_cats = array_map('strtolower', $blog_cats);
                        $titler = preg_replace("#[^\sa-zA-Z0-9]+#", "", $new_post_title);
                        $titler = explode(' ', $titler);
                        $title_words = '';
                        foreach($titler as $t)
                        {
                            if(in_array(strtolower($t), $blog_cats))
                            {
                                $title_words .= $t . ',';
                            }
                        }
                        $title_words = trim($title_words, ',');
                        $extra_categories = $title_words;
                    }
                    else
                    {
                        $extra_categories = $title_words;
                    }
                }
                else
                {
                    $extra_categories = '';

                }
                if($keyword_category != '')
                {
                    $content_to_check = $title . ' ' . $content;
                    $splt_keyword_categories = preg_split('/\r\n|\r|\n/', $keyword_category);                    
                    foreach ( $splt_keyword_categories as $splt_keyword_category ) {
                        if (stristr ( $splt_keyword_category, '|' )) 
                        {
                            $splt_keyword_category = trim ( $splt_keyword_category );
                            $splt_keyword_category_parts = explode ( '|', $splt_keyword_category );
                            $splt_keyword_category_keyword = $splt_keyword_category_parts [0];
                            $splt_keyword_category_category = $splt_keyword_category_parts [1];
                            $was_found = true;
                            $splt_keyword_category_keywords = explode ( ',', $splt_keyword_category_keyword );
                            foreach ( $splt_keyword_category_keywords as $splt_keyword_category_single ) 
                            {
                                if (!preg_match ('{\b' . preg_quote($splt_keyword_category_single) . '\b}siu', $content_to_check ) || (stristr($splt_keyword_category_single, '#') && stristr($content_to_check, trim($splt_keyword_category_single)))) 
                                {
                                    $was_found = false;
                                    break;
                                }
                            }
                            
                            if ($was_found) 
                            {
                                $extra_categories .= ',' . $splt_keyword_category_category;
                                $extra_categories = trim($extra_categories, ',');
                            }
                        }
                    }
                }
                $my_post['extra_categories'] = $extra_categories;
                $item_tags = '';
                $title_kw_tags = '';
                if($keyword_tag != '')
                {
                    $content_to_check = $title . ' ' . $content;
                    $splt_keyword_categories = preg_split('/\r\n|\r|\n/', $keyword_tag);                    
                    foreach ( $splt_keyword_categories as $splt_keyword_tag ) {
                        if (stristr ( $splt_keyword_tag, '|' )) 
                        {
                            $splt_keyword_tag = trim ( $splt_keyword_tag );
                            $splt_keyword_tag_parts = explode ( '|', $splt_keyword_tag );
                            $splt_keyword_tag_keyword = $splt_keyword_tag_parts [0];
                            $splt_keyword_tag_category = $splt_keyword_tag_parts [1];
                            $was_found = true;
                            $splt_keyword_tag_keywords = explode ( ',', $splt_keyword_tag_keyword );
                            foreach ( $splt_keyword_tag_keywords as $splt_keyword_tag_single ) 
                            {
                                if (!preg_match ('{\b' . preg_quote($splt_keyword_tag_single) . '\b}siu', $content_to_check ) || (stristr($splt_keyword_tag_single, '#') && stristr($content_to_check, trim($splt_keyword_tag_single)))) 
                                {
                                    $was_found = false;
                                    break;
                                }
                            }
                            
                            if ($was_found) 
                            {
                                $title_kw_tags .= $splt_keyword_tag_category;
                            }
                        }
                    }
                }
                if($item_create_tag != '')
                {
                    $item_create_tag .= ',' . $title_kw_tags;
                }
                else
                {
                    $item_create_tag .= $title_kw_tags;
                }
                if ($can_create_tag == 'title') {
                    $item_tags = $title_words;
                    $post_the_tags = ($item_create_tag != '' ? $item_create_tag . ',' : '') . newsomatic_utf8_encode($item_tags);
                }
                else
                {
                    $post_the_tags = newsomatic_utf8_encode($item_create_tag);
                }
                $my_post['extra_tags'] = $item_tags;
                $my_post['tags_input'] = $post_the_tags;
                $new_post_title   = newsomatic_replaceTitleShortcodesAgain($new_post_title, $extra_categories, $post_the_tags);
                $new_post_content = newsomatic_replaceContentShortcodesAgain($new_post_content, $extra_categories, $post_the_tags);
                if ($strip_images == '1') {
                    $new_post_content = newsomatic_strip_images($new_post_content);
                }
                
                if (!isset($newsomatic_Main_Settings['restriction_before']) || $newsomatic_Main_Settings['restriction_before'] != 'on') 
                {
                    $title_count = -1;
                    if (isset($newsomatic_Main_Settings['min_word_title']) && $newsomatic_Main_Settings['min_word_title'] != '') {
                        $title_count = str_word_count($new_post_title);
                        if ($title_count < intval($newsomatic_Main_Settings['min_word_title'])) {
                            if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                newsomatic_log_to_file('Skipping post "' . esc_html($new_post_title) . '", because title length < ' . $newsomatic_Main_Settings['min_word_title']);
                            }
                            continue;
                        }
                    }
                    if (isset($newsomatic_Main_Settings['max_word_title']) && $newsomatic_Main_Settings['max_word_title'] != '') {
                        if ($title_count == -1) {
                            $title_count = str_word_count($new_post_title);
                        }
                        if ($title_count > intval($newsomatic_Main_Settings['max_word_title'])) {
                            if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                newsomatic_log_to_file('Skipping post "' . esc_html($new_post_title) . '", because title length > ' . $newsomatic_Main_Settings['max_word_title']);
                            }
                            continue;
                        }
                    }
                    $content_count = -1;
                    if (isset($newsomatic_Main_Settings['min_word_content']) && $newsomatic_Main_Settings['min_word_content'] != '') {
                        $content_count = str_word_count(newsomatic_strip_html_tags($new_post_content));
                        if ($content_count < intval($newsomatic_Main_Settings['min_word_content'])) {
                            if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                newsomatic_log_to_file('Skipping post "' . esc_html($new_post_title) . '", because content length < ' . $newsomatic_Main_Settings['min_word_content']);
                            }
                            continue;
                        }
                    }
                    if (isset($newsomatic_Main_Settings['max_word_content']) && $newsomatic_Main_Settings['max_word_content'] != '') {
                        if ($content_count == -1) {
                            $content_count = str_word_count(newsomatic_strip_html_tags($new_post_content));
                        }
                        if ($content_count > intval($newsomatic_Main_Settings['max_word_content'])) {
                            if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                newsomatic_log_to_file('Skipping post "' . esc_html($new_post_title) . '", because content length > ' . $newsomatic_Main_Settings['max_word_content']);
                            }
                            continue;
                        }
                    }
                    if (isset($newsomatic_Main_Settings['banned_words']) && $newsomatic_Main_Settings['banned_words'] != '') {
                        $continue    = false;
                        $banned_list = explode(',', $newsomatic_Main_Settings['banned_words']);
                        foreach ($banned_list as $banned_word) {
                            if (stripos($new_post_content, trim($banned_word)) !== FALSE) {
                                if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                    newsomatic_log_to_file('Skipping post "' . esc_html($new_post_title) . '", because it\'s content contains banned word: ' . $banned_word);
                                }
                                $continue = true;
                                break;
                            }
                            if (stripos($new_post_title, trim($banned_word)) !== FALSE) {
                                if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                    newsomatic_log_to_file('Skipping post "' . esc_html($new_post_title) . '", because it\'s title contains banned word: ' . $banned_word);
                                }
                                $continue = true;
                                break;
                            }
                            if (stripos($url, trim($banned_word)) !== FALSE) {
                                if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                    newsomatic_log_to_file('Skipping post "' . esc_html($new_post_title) . '", because it\'s URL contains banned word: ' . $banned_word);
                                }
                                $continue = true;
                                break;
                            }
                        }
                        if ($continue === true) {
                            continue;
                        }
                    }
                    if (isset($newsomatic_Main_Settings['required_words']) && $newsomatic_Main_Settings['required_words'] != '') {
                        if (isset($newsomatic_Main_Settings['require_all']) && $newsomatic_Main_Settings['require_all'] == 'on') {
                            $require_all = true;
                        }
                        else
                        {
                            $require_all = false;
                        }
                        
                        $required_list = explode(',', $newsomatic_Main_Settings['required_words']);
                        if($require_all === true)
                        {
                            $continue      = false;
                            foreach ($required_list as $required_word) {
                                if (stripos($new_post_content, trim($required_word)) === FALSE) {
                                    if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                        newsomatic_log_to_file('Skipping post "' . esc_html($new_post_title) . '", because it\'s content doesn\'t contain required word: ' . $required_word);
                                    }
                                    $continue = true;
                                    break;
                                }
                                if (stripos($new_post_title, trim($required_word)) === FALSE) {
                                    if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                        newsomatic_log_to_file('Skipping post "' . esc_html($new_post_title) . '", because it\'s title doesn\'t contain required word: ' . $required_word);
                                    }
                                    $continue = true;
                                    break;
                                }
                            }
                        }
                        else
                        {
                            $continue      = true;
                            foreach ($required_list as $required_word) {
                                if (stripos($new_post_content, trim($required_word)) !== FALSE) {
                                    $continue = false;
                                    break;
                                }
                                if (stripos($new_post_title, trim($required_word)) !== FALSE) {
                                    $continue = false;
                                    break;
                                }
                            }
                        }
                        if ($continue === true) {
                            if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                newsomatic_log_to_file('Skipping post ' . esc_html($item->url) . ', because of required words.');
                            }
                            continue;
                        }
                    }
                }
                if (isset($newsomatic_Main_Settings['no_link_translate']) && $newsomatic_Main_Settings['no_link_translate'] == 'on')
                {
                    $new_post_content = preg_replace('{"https:\/\/translate.google.com\/translate\?hl=(?:.*?)&prev=_t&sl=(?:.*?)&tl=(?:.*?)&u=([^"]*?)"}i', "$1", urldecode(html_entity_decode($new_post_content, ENT_COMPAT | ENT_HTML5)));
                }
                else
                {
                    $new_post_content = html_entity_decode($new_post_content, ENT_COMPAT | ENT_HTML5);
                }
                if ($disable_excerpt == '1') 
                {
                    $my_post['post_excerpt'] = '';
                }
                else
                {
                    if (isset($newsomatic_Main_Settings['translate']) && $newsomatic_Main_Settings['translate'] != "disabled" && $newsomatic_Main_Settings['translate'] != "en") {
                        $my_post['post_excerpt'] = newsomatic_utf8_encode(newsomatic_getExcerpt($new_post_content));
                    } else {
                        $my_post['post_excerpt'] = newsomatic_utf8_encode(newsomatic_getExcerpt($content));
                    }
                }
                
                $new_post_title = newsomatic_utf8_encode($new_post_title);
                if ($strip_by_regex_title !== '')
                {
                    $xstrip_by_regex = preg_split('/\r\n|\r|\n/', $strip_by_regex_title);
                    $xreplace_regex = preg_split('/\r\n|\r|\n/', $replace_regex_title);
                    $xcnt = 0;
                    foreach($xstrip_by_regex as $sbr)
                    {
                        if(isset($xreplace_regex[$xcnt]))
                        {
                            $repreg = $xreplace_regex[$xcnt];
                        }
                        else
                        {
                            $repreg = '';
                        }
                        $xcnt++;
                        $temp_contx = preg_replace("~" . $sbr . "~i", $repreg, $new_post_title);
                        if($temp_contx !== NULL)
                        {
                            $new_post_title = $temp_contx;
                        }
                    }
                }
                if($limit_title_word_count != '' && is_numeric($limit_title_word_count))
                {
                    $new_post_title = wp_trim_words($new_post_title, intval($limit_title_word_count), '');
                }
                if ($strip_by_regex !== '')
                {
                    $xstrip_by_regex = preg_split('/\r\n|\r|\n/', $strip_by_regex);
                    $xreplace_regex = preg_split('/\r\n|\r|\n/', $replace_regex);
                    $xcnt = 0;
                    foreach($xstrip_by_regex as $sbr)
                    {
                        if(isset($xreplace_regex[$xcnt]))
                        {
                            $repreg = $xreplace_regex[$xcnt];
                        }
                        else
                        {
                            $repreg = '';
                        }
                        $xcnt++;
                        $temp_cont = preg_replace("~" . $sbr . "~i", $repreg, $new_post_content);
                        if($temp_cont !== NULL)
                        {
                            $new_post_content = $temp_cont;
                        }
                    }
                }
                $new_post_content = str_replace('</ iframe>', '</iframe>', $new_post_content);
                if($rank_keywords != '')
                {
                    if(!is_array($rank_keywords))
                    {
                        $rank_keywords = explode(',', $rank_keywords);
                    }
                    $rank_me = trim($rank_keywords[array_rand($rank_keywords)]);
                    if(strstr($new_post_content, '<h1>'))
                    {
                        $new_post_content = str_replace('<h1>', '<h1>' . ucfirst($rank_me) . ' ', $new_post_content);
                    }
                    elseif(strstr($new_post_content, '<h2>'))
                    {
                        $new_post_content = str_replace('<h2>', '<h2>' . ucfirst($rank_me) . ' ', $new_post_content);
                    }
                    $new_post_content = preg_replace('#<img([^><]+?)title="([^<>"]*)"#i', '<img\\1title="' . esc_attr($rank_me) . ' ' . '\\2"', $new_post_content);
                    $new_post_content = preg_replace('#<img([^><]+?)alt="([^<>"]*)"#i', '<img\\1alt="' . esc_attr($rank_me) . ' ' . '\\2"', $new_post_content);
                    preg_match_all('#<img (.*?)\/>#', $new_post_content, $images);
                    if(!is_null($images))
                    {
                        foreach($images[1] as $index => $value)
                        {
                            if(!preg_match('/alt=/', $value))
                            {
                                $new_img = str_replace('<img', '<img alt="' . esc_html($rank_me) . '"', $images[0][$index]);
                                if(!preg_match('/title=/', $value))
                                {
                                    $new_img = str_replace('<img', '<img title="'.esc_attr($rank_me).'"', $new_img);
                                }
                                $new_post_content = str_replace($images[0][$index], $new_img, $new_post_content);
                            }
                            else
                            {
                                if(!preg_match('/title=/', $value))
                                {
                                    $new_img = str_replace('<img', '<img title="'.esc_attr($rank_me).'"', $images[0][$index]);
                                    $new_post_content = str_replace($images[0][$index], $new_img, $new_post_content);
                                }
                            }
                        }
                    }
                    $new_post_content =  ucfirst($rank_me) . ' ' . $new_post_content;
                }
                if (isset($newsomatic_Main_Settings['copy_images']) && $newsomatic_Main_Settings['copy_images'] == 'on') 
                {
                    $new_post_content = preg_replace("~\ssrcset=['\"](?:[^'\"]*)['\"]~i", ' ', $new_post_content);
                    preg_match_all('/(http|https|ftp|ftps)?:\/\/\S+\.(?:jpg|jpeg|png|gif)/', $new_post_content, $matches);
                    if(isset($matches[0][0]))
                    {
                        $matches[0] = array_unique($matches[0]);
                        foreach($matches[0] as $match)
                        {
                            $file_path = newsomatic_copy_image_locally($match, $custom_cookies);
                            if($file_path != false)
                            {
                                $file_path = str_replace('\\', '/', $file_path);
                                $new_post_content = str_replace($match, $file_path, $new_post_content);
                            }
                        }
                    }
                }
                if (isset($newsomatic_Main_Settings['fix_html']) && $newsomatic_Main_Settings['fix_html'] == "on") {
                    $new_post_content = newsomatic_repairHTML($new_post_content);
                }
                if($ret_content == 1)
                {
                    return array($new_post_content, $new_post_title);
                }
                $my_post['post_content'] = newsomatic_utf8_encode($new_post_content);
                $my_post['post_title']           = newsomatic_truncate_title($new_post_title);
                $my_post['original_title']       = $title;
                $my_post['original_content']     = $content;
                $my_post['newsomatic_source_feed'] = $feed_uri;
                $my_post['newsomatic_timestamp']   = newsomatic_get_date_now();
                $my_post['newsomatic_post_format'] = $post_format;
                if (isset($default_category) && $default_category !== 'newsomatic_no_category_12345678' && $default_category[0] !== 'newsomatic_no_category_12345678') {
                    if(is_array($default_category))
                    {
                        $extra_categories_temp = '';
                        foreach($default_category as $dc)
                        {
                            $extra_categories_temp .= get_cat_name($dc) . ',';
                        }
                        $extra_categories_temp .= $extra_categories;
                        $extra_categories_temp = trim($extra_categories_temp, ',');
                    }
                    else
                    {
                        $extra_categories_temp = trim(get_cat_name($default_category) . ',' .$extra_categories, ',');
                    }
                }
                else
                {
                    $extra_categories_temp = $extra_categories;
                }
                $custom_arr = array();
                if($custom_fields != '')
                {
                    if(stristr($custom_fields, '=>') != false)
                    {
                        $rule_arr = explode(',', trim($custom_fields));
                        foreach($rule_arr as $rule)
                        {
                            $my_args = explode('=>', trim($rule));
                            if(isset($my_args[1]))
                            {
                                if(isset($my_args[2]))
                                {
                                    $req_list = explode(',', $my_args[2]);
                                    $required_found = false;
                                    foreach($req_list as $rl)
                                    {
                                        if(function_exists('mb_stristr'))
                                        {
                                            if(mb_stristr($new_post_content, trim($rl)) !== false)
                                            {
                                                $required_found = true;
                                                break;
                                            }
                                        }
                                        else
                                        {
                                            if(stristr($new_post_content, trim($rl)) === false)
                                            {
                                                $required_found = true;
                                                break;
                                            }
                                        }
                                        if(function_exists('mb_stristr'))
                                        {
                                            if(mb_stristr($new_post_title, trim($rl)) !== false)
                                            {
                                                $required_found = true;
                                                break;
                                            }
                                        }
                                        else
                                        {
                                            if(stristr($new_post_title, trim($rl)) === false)
                                            {
                                                $required_found = true;
                                                break;
                                            }
                                        }
                                    }
                                    if($required_found === false)
                                    {
                                        if(isset($my_args[3]))
                                        {
                                            $my_args[1] = $my_args[3];
                                        }
                                        else
                                        {
                                            continue;
                                        }
                                    }
                                }
                                $custom_field_content = trim($my_args[1]);
                                $custom_field_content = newsomatic_replaceContentShortcodes($custom_field_content, $new_post_title, $new_post_content, $description, $author, $media, $date, $orig_content, $img_attr, $short_url, $get_img, $author_link, $source_name, $source_id, $query_string);
                                $custom_field_content = newsomatic_replaceContentShortcodesAgain($custom_field_content, $extra_categories, $post_the_tags);
                                if(stristr($my_args[0], '[') !== false && stristr($my_args[0], ']') !== false)
                                {
                                    preg_match_all('#([^\[\]]*?)\[([^\[\]]*?)\]#', $my_args[0], $cfm);
                                    if(isset($cfm[2][0]))
                                    {
                                        if(isset($custom_arr[trim($cfm[1][0])]) && is_array($custom_arr[trim($cfm[1][0])]))
                                        {
                                            $custom_arr[trim($cfm[1][0])] = array_merge($custom_arr[trim($cfm[1][0])], array(trim($cfm[2][0]) => $custom_field_content));
                                        }
                                        else
                                        {
                                            $custom_arr[trim($cfm[1][0])] = array(trim($cfm[2][0]) => $custom_field_content);
                                        }
                                    }
                                    else
                                    {
                                        $custom_arr[trim($my_args[0])] = $custom_field_content;
                                    }
                                }
                                else
                                {
                                    $custom_arr[trim($my_args[0])] = $custom_field_content;
                                }
                            }
                        }
                    }
                }
                $custom_arr = array_merge($custom_arr, array('newsomatic_featured_image' => $get_img, 'newsomatic_post_cats' => $extra_categories_temp, 'newsomatic_post_tags' => $post_the_tags));
                $custom_tax_arr = array();
                if($custom_tax != '')
                {
                    if(stristr($custom_tax, '=>') != false)
                    {
                        $rule_arr = explode(';', trim($custom_tax));
                        foreach($rule_arr as $rule)
                        {
                            $my_args = explode('=>', trim($rule));
                            if(isset($my_args[1]))
                            {
                                if(isset($my_args[2]))
                                {
                                    $req_list = explode(',', $my_args[2]);
                                    $required_found = false;
                                    foreach($req_list as $rl)
                                    {
                                        if(function_exists('mb_stristr'))
                                        {
                                            if(mb_stristr($new_post_content, trim($rl)) !== false)
                                            {
                                                $required_found = true;
                                                break;
                                            }
                                        }
                                        else
                                        {
                                            if(stristr($new_post_content, trim($rl)) === false)
                                            {
                                                $required_found = true;
                                                break;
                                            }
                                        }
                                        if(function_exists('mb_stristr'))
                                        {
                                            if(mb_stristr($new_post_title, trim($rl)) !== false)
                                            {
                                                $required_found = true;
                                                break;
                                            }
                                        }
                                        else
                                        {
                                            if(stristr($new_post_title, trim($rl)) === false)
                                            {
                                                $required_found = true;
                                                break;
                                            }
                                        }
                                    }
                                    if($required_found === false)
                                    {
                                        if(isset($my_args[3]))
                                        {
                                            $my_args[1] = $my_args[3];
                                        }
                                        else
                                        {
                                            continue;
                                        }
                                    }
                                }
                                $custom_tax_content = trim($my_args[1]);
                                $custom_tax_content = newsomatic_replaceContentShortcodes($custom_tax_content, $new_post_title, $new_post_content, $description, $author, $media, $date, $orig_content, $img_attr, $short_url, $get_img, $author_link, $source_name, $source_id, $query_string);
                                $custom_tax_content = newsomatic_replaceContentShortcodesAgain($custom_tax_content, $extra_categories, $post_the_tags);
                                if(substr(trim($my_args[0]), 0, 3) === "pa_" && $post_type == 'product' && !empty($custom_tax_content))
                                {
                                    if(isset($custom_arr['_product_attributes']))
                                    {
                                        $custom_arr['_product_attributes'] = array_merge($custom_arr['_product_attributes'], array(trim($my_args[0]) =>array(
                                            'name' => trim($my_args[0]),
                                            'value' => $custom_tax_content,
                                            'is_visible' => '1',
                                            'is_taxonomy' => '1'
                                        )));
                                    }
                                    else
                                    {
                                        $custom_arr['_product_attributes'] = array(trim($my_args[0]) =>array(
                                            'name' => trim($my_args[0]),
                                            'value' => $custom_tax_content,
                                            'is_visible' => '1',
                                            'is_taxonomy' => '1'
                                        ));
                                    }
                                }
                                if(isset($custom_tax_arr[trim($my_args[0])]))
                                {
                                    $custom_tax_arr[trim($my_args[0])] .= ',' . $custom_tax_content;
                                }
                                else
                                {
                                    $custom_tax_arr[trim($my_args[0])] = $custom_tax_content;
                                }
                            }
                        }
                    }
                }
                if(count($custom_tax_arr) > 0)
                {
                    $my_post['taxo_input'] = $custom_tax_arr;
                }
                $my_post['meta_input'] = $custom_arr;
                if ($enable_pingback == '1') {
                    $my_post['ping_status'] = 'open';
                } else {
                    $my_post['ping_status'] = 'closed';
                }
                remove_filter('content_save_pre', 'wp_filter_post_kses');
                remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');remove_filter('title_save_pre', 'wp_filter_kses');
    // echo "hi this is a post username : ";
    // echo $post_user_name;
    // echo "<pre>";
    // print_r($my_post);
    // echo "<pre>";
    
    // die("in a newsomatic rule function ");

                $post_id = wp_insert_post($my_post, true);
				add_filter('content_save_pre', 'wp_filter_post_kses');
                add_filter('content_filtered_save_pre', 'wp_filter_post_kses');add_filter('title_save_pre', 'wp_filter_kses');
                if (!is_wp_error($post_id)) {
                    if($post_id === 0)
                    {
                        newsomatic_log_to_file('An error occurred while inserting post into wp database! Title:' . $my_post['post_title']);
                        continue;
                    }
                    $posts_inserted++;
                    if($wpml_lang != '' && function_exists('pll_set_post_language'))
                    {
                        pll_set_post_language($post_id, $wpml_lang);
                    }
                    if($remove_default == '1' && ($keyword_category != '' || $auto_categories !== 'disabled' || (isset($default_category) && $default_category !== 'newsomatic_no_category_12345678' && $default_category[0] !== 'newsomatic_no_category_12345678')))
                    {
                        $default_categories = wp_get_post_categories($post_id);
                    }
                    if(isset($my_post['taxo_input']))
                    {
                        foreach($my_post['taxo_input'] as $taxn => $taxval)
                        {
                            $taxn = trim($taxn);
                            $taxval = trim($taxval);
                            if(is_taxonomy_hierarchical($taxn))
                            {
                                $taxval = array_map('trim', explode(',', $taxval));
                                for($ii = 0; $ii < count($taxval); $ii++)
                                {
                                    if(!is_numeric($taxval[$ii]))
                                    {
                                        $xtermid = get_term_by('name', $taxval[$ii], $taxn);
                                        if($xtermid !== false)
                                        {
                                            $taxval[$ii] = intval($xtermid->term_id);
                                        }
                                        else
                                        {
                                            wp_insert_term( $taxval[$ii], $taxn);
                                            $xtermid = get_term_by('name', $taxval[$ii], $taxn);
                                            if($xtermid !== false)
                                            {
                                                if($wpml_lang != '' && function_exists('pll_set_term_language'))
                                                {
                                                    pll_set_term_language($xtermid->term_id, $wpml_lang); 
                                                }
                                                elseif($wpml_lang != '' && has_filter('wpml_object_id'))
                                                {
                                                    $wpml_element_type = apply_filters( 'wpml_element_type', $taxn );
                                                    $pars['element_id'] = $xtermid->term_id;
                                                    $pars['element_type'] = $wpml_element_type;
                                                    $pars['language_code'] = $wpml_lang;
                                                    $pars['trid'] = FALSE;
                                                    $pars['source_language_code'] = NULL;
                                                    do_action('wpml_set_element_language_details', $pars);
                                                }
                                                $taxval[$ii] = intval($xtermid->term_id);
                                            }
                                        }
                                    }
                                }
                                wp_set_post_terms($post_id, $taxval, $taxn, true);
                            }
                            else
                            {
                                wp_set_post_terms($post_id, trim($taxval), $taxn, true);
                            }
                        }
                    }
                    if (isset($my_post['newsomatic_post_format']) && $my_post['newsomatic_post_format'] != '' && $my_post['newsomatic_post_format'] != 'post-format-standard') {
                        wp_set_post_terms($post_id, $my_post['newsomatic_post_format'], 'post_format', true);
                    }
                    $featured_path = '';
                    $image_failed  = false;
                    if ($featured_image == '1') {
                        $get_img = $my_post['newsomatic_post_image'];
                        if ($get_img != '') {
                            if (!newsomatic_generate_featured_image($get_img, $post_id, $custom_cookies)) {
                                $image_failed = true;
                                if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                    newsomatic_log_to_file('newsomatic_generate_featured_image failed for ' . $get_img . '!');
                                }
                            } else {
                                $featured_path = $get_img;
                            }
                        } else {
                            $image_failed = true;
                        }
                    }
                    if ($image_failed || $featured_image !== '1') {
                        if ($image_url != '') {
                            $replacement = str_replace(array('[', ']'), '', $my_post['post_title']);
                            $image_url_temp = str_replace('%%item_title%%', $replacement, $image_url);
                            $image_url_temp = preg_replace_callback('#%%random_image\[([^\]]*?)\]%%#', function ($matches) {
                                $my_img = newsomatic_get_random_image_google($matches[1]);
                                return $my_img;
                            }, $image_url_temp);
                            $image_urlx = explode(',', $image_url_temp);
                            $image_urlx = trim($image_urlx[array_rand($image_urlx)]);
                            $retim = false;
                            if(is_numeric($image_urlx) && $image_urlx > 0)
                            {
                                require_once(ABSPATH . 'wp-admin/includes/image.php');
                                require_once(ABSPATH . 'wp-admin/includes/media.php');
                                $res2 = set_post_thumbnail($post_id, $image_urlx);
                                if ($res2 === FALSE) {
                                }
                                else
                                {
                                    $retim = true;
                                }
                            }
                            if($retim == false && $image_urlx != '')
                            {
                                stream_context_set_default( [
                                    'ssl' => [
                                        'verify_peer' => false,
                                        'verify_peer_name' => false,
                                    ],
                                ]);
                                error_reporting(0);
                                $url_headers = get_headers($image_urlx, 1);
                                error_reporting(E_ALL);
                                if (isset($url_headers['Content-Type'])) {
                                    if (is_array($url_headers['Content-Type'])) {
                                        $img_type = strtolower($url_headers['Content-Type'][0]);
                                    } else {
                                        $img_type = strtolower($url_headers['Content-Type']);
                                    }
                                    if (strstr($img_type, 'image/') !== false) {
                                        if (!newsomatic_generate_featured_image($image_urlx, $post_id, $custom_cookies)) {
                                            $image_failed = true;
                                            if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                                                newsomatic_log_to_file('newsomatic_generate_featured_image failed to deafault value: ' . $image_urlx . '!');
                                            }
                                        } else {
                                            $featured_path = $image_urlx;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if($featured_image == '1' && $featured_path == '' && isset($newsomatic_Main_Settings['skip_no_img']) && $newsomatic_Main_Settings['skip_no_img'] == 'on')
                    {
                        if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                            newsomatic_log_to_file('Skipping post "' . $my_post['post_title'] . '", because it failed to generate a featured image for: ' . $get_img . ' and ' . $image_url);
                        }
                        wp_delete_post($post_id, true);
                        $posts_inserted--;
                        continue;
                    }
                    if ($auto_categories != 'disabled' || $keyword_category != '') 
                    {
                        if ($my_post['extra_categories'] != '') {
                            $extra_cats = explode(',', $my_post['extra_categories']);
                            foreach($extra_cats as $extra_cat)
                            {
                                $termid = newsomatic_create_terms('category', null, trim($extra_cat));
                                if($wpml_lang != '' && function_exists('pll_set_term_language'))
                                {
                                    foreach($termid as $tx)
                                    {
                                        pll_set_term_language($tx, $wpml_lang); 
                                    }
                                }
                                elseif($wpml_lang != '' && has_filter('wpml_object_id'))
                                {
                                    $wpml_element_type = apply_filters( 'wpml_element_type', 'category' );
                                    foreach($termid as $tx)
                                    {
                                        $pars['element_id'] = $tx;
                                        $pars['element_type'] = $wpml_element_type;
                                        $pars['language_code'] = $wpml_lang;
                                        $pars['trid'] = FALSE;
                                        $pars['source_language_code'] = NULL;
                                        do_action('wpml_set_element_language_details', $pars);
                                    }
                                }
                                wp_set_post_terms($post_id, $termid, 'category', true);
                            }
                        }
                    }
                    if (isset($default_category) && $default_category !== 'newsomatic_no_category_12345678' && $default_category[0] !== 'newsomatic_no_category_12345678') {
                        $cats   = array();
                        if(is_array($default_category))
                        {
                            foreach($default_category as $dc)
                            {
                                $cats[] = $dc;
                            }
                        }
                        else
                        {
                            $cats[] = $default_category;
                        }
                        global $sitepress;
                        if($wpml_lang != '' && has_filter('wpml_current_language') && $sitepress != null)
                        {
                            $current_language = apply_filters( 'wpml_current_language', NULL );
                            $sitepress->switch_lang($wpml_lang);
                        }
                        wp_set_post_categories($post_id, $cats, true);
                        if($wpml_lang != '' && function_exists('pll_set_term_language'))
                        {
                            foreach($cats as $cc)
                            {
                                pll_set_term_language($cc, $wpml_lang);
                            }
                        }
                        elseif($wpml_lang != '' && has_filter('wpml_current_language') && $sitepress != null)
                        {
                            $sitepress->switch_lang($current_language);
                        }
                    }
                    if($remove_default == '1' && ($keyword_category != '' || $auto_categories !== 'disabled' || (isset($default_category) && $default_category !== 'newsomatic_no_category_12345678' && $default_category[0] !== 'newsomatic_no_category_12345678')))
                    {
                        $new_categories = wp_get_post_categories($post_id);
                        if(isset($default_categories) && !($default_categories == $new_categories))
                        {
                            foreach($default_categories as $dc)
                            {
                                $rem_cat = get_category( $dc );
                                wp_remove_object_terms( $post_id, $rem_cat->slug, 'category' );
                            }
                        }
                    }
                    $tax_rez = wp_set_object_terms( $post_id, 'Newsomatic_' . $type . '_' . $param, 'coderevolution_post_source', true);
                    if (is_wp_error($tax_rez)) {
                        if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                            newsomatic_log_to_file('wp_set_object_terms failed for: ' . $post_id . '!');
                        }
                    }
                    if (isset($newsomatic_Main_Settings['link_source']) && $newsomatic_Main_Settings['link_source'] == 'on') {
                        $title_link_url = '1';
                    }
                    else
                    {
                        $title_link_url = '0';
                    }
                    newsomatic_addPostMeta($post_id, $my_post, $param, $type, $featured_path, $title_link_url);
                    if($wpml_lang != '' && (class_exists('SitePress') || function_exists('wpml_object_id')))
                    {
                        $wpml_element_type = apply_filters( 'wpml_element_type', $post_type );
                        $pars['element_id'] = $post_id;
                        $pars['element_type'] = $wpml_element_type;
                        $pars['language_code'] = $wpml_lang;
                        $pars['source_language_code'] = NULL;
                        do_action('wpml_set_element_language_details', $pars);

                        global $wp_filesystem;
                        if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ){
                            include_once(ABSPATH . 'wp-admin/includes/file.php');$creds = request_filesystem_credentials( site_url() );
                            wp_filesystem($creds);
                        }
                        if($wp_filesystem->exists(WP_PLUGIN_DIR . '/sitepress-multilingual-cms/inc/wpml-api.php'))
                        {
                            include_once( WP_PLUGIN_DIR . '/sitepress-multilingual-cms/inc/wpml-api.php' );
                        }
                        $wpml_lang = trim($wpml_lang);
                        if(function_exists('wpml_update_translatable_content'))
                        {
                            wpml_update_translatable_content('post_' . $post_type, $post_id, $wpml_lang);
                            if($my_post['newsomatic_post_url'] != '')
                            {
                                global $sitepress;
                                global $wpdb;
                                $keyid = md5($my_post['newsomatic_post_url']);
                                $keyName = $keyid . '_wpml';
                                $rezxxxa = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}postmeta WHERE `meta_key` = '$keyName' limit 1", ARRAY_A );
                                if(count($rezxxxa) != 0)
                                {
                                    $metaRow = $rezxxxa[0];
                                    $metaValue = $metaRow['meta_value'];
                                    $metaParts = explode('_', $metaValue);
                                    $sitepress->set_element_language_details($post_id, 'post_'.$my_post['post_type'] , $metaParts[0], $wpml_lang, $metaParts[1] ); 
                                }
                                else
                                {
                                    $ptrid = $sitepress->get_element_trid($post_id);
                                    add_post_meta($post_id, $keyid.'_wpml', $ptrid.'_'.$wpml_lang );
                                }
                            }
                            
                        }
                    }
                    if (isset($newsomatic_Main_Settings['draft_first']) && $newsomatic_Main_Settings['draft_first'] == 'on' && $draft_me == true)
                    {
                        newsomatic_change_post_status($post_id, 'publish');
                    }
                    
                } else {
                    newsomatic_log_to_file('Failed to insert post into database! Title:' . $my_post['post_title'] . '! Error: ' . $post_id->get_error_message() . 'Error code: ' . $post_id->get_error_code() . 'Error data: ' . $post_id->get_error_data());
                    continue;
                }
                $count++;
            }
            if($continue_search == '1' && $query_m == $processed_items)
            {
                $skip_posts_temp[$param][$type] += 1;
                update_option('newsomatic_continue_search', $skip_posts_temp);
                $already_increased = true;
            }
        }
        catch (Exception $e) {
            if($continue_search == '1')
            {
                $skip_posts_temp[$param][$type] = 1;
                update_option('newsomatic_continue_search', $skip_posts_temp);
            }
            newsomatic_log_to_file('Exception thrown ' . esc_html($e->getMessage()) . '!');
            if($auto == 1)
            {
                newsomatic_clearFromList($param, $type);
            }
            return 'fail';
        }
        
        if($posts_inserted == 0 && $already_increased === false)
        {
            if($continue_search == '1')
            {
                $skip_posts_temp[$param][$type] += 1;
                update_option('newsomatic_continue_search', $skip_posts_temp);
            }
        }
        if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
            newsomatic_log_to_file('Rule ID ' . esc_html($param) . ' succesfully run! ' . esc_html($posts_inserted) . ' posts created!');
        }
        if (isset($newsomatic_Main_Settings['send_email']) && $newsomatic_Main_Settings['send_email'] == 'on' && $newsomatic_Main_Settings['email_address'] !== '') {
            try {
                $to        = $newsomatic_Main_Settings['email_address'];
                $subject   = '[newsomatic] Rule running report - ' . newsomatic_get_date_now();
                $message   = 'Rule ID ' . esc_html($param) . ' succesfully run! ' . esc_html($posts_inserted) . ' posts created!';
                $headers[] = 'From: Newsomatic Plugin <newsomatic@noreply.net>';
                $headers[] = 'Reply-To: noreply@newsomatic.com';
                $headers[] = 'X-Mailer: PHP/' . phpversion();
                $headers[] = 'Content-Type: text/html';
                $headers[] = 'Charset: ' . get_option('blog_charset', 'UTF-8');
                wp_mail($to, $subject, $message, $headers);
            }
            catch (Exception $e) {
                if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                    newsomatic_log_to_file('Failed to send mail: Exception thrown ' . esc_html($e->getMessage()) . '!');
                }
            }
        }
    }
    if ($posts_inserted == 0) {
        if($auto == 1)
        {
            newsomatic_clearFromList($param, $type);
        }
        return 'nochange';
    } else {
        if($auto == 1)
        {
            newsomatic_clearFromList($param, $type);
        }
        return 'ok';
    }
}

function newsomatic_change_post_status($post_id, $status){
    $current_post = get_post( $post_id, 'ARRAY_A' );
    $current_post['post_status'] = $status;
    remove_filter('content_save_pre', 'wp_filter_post_kses');
    remove_filter('content_filtered_save_pre', 'wp_filter_post_kses');remove_filter('title_save_pre', 'wp_filter_kses');
    wp_update_post($current_post);
    add_filter('content_save_pre', 'wp_filter_post_kses');
    add_filter('content_filtered_save_pre', 'wp_filter_post_kses');add_filter('title_save_pre', 'wp_filter_kses');
}

function newsomatic_copy_image_locally($image_url, $custom_cookies = '')
{
    $upload_dir = wp_upload_dir();
    global $wp_filesystem;
    if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ){
        include_once(ABSPATH . 'wp-admin/includes/file.php');$creds = request_filesystem_credentials( site_url() );
        wp_filesystem($creds);
    }
    if(substr( $image_url, 0, 10 ) === "data:image")
    {
        $data = explode(',', $image_url);
        if(isset($data[1]))
        {
            $image_data = base64_decode($data[1]);
            if($image_data === FALSE)
            {
                return false;
            }
        }
        else
        {
            return false;
        }
        preg_match('{data:image/(.*?);}', $image_url ,$ex_matches);
        if(isset($ex_matches[1]))
        {
            $image_url = 'image.' . $ex_matches[1];
        }
        else
        {
            $image_url = 'image.jpg';
        }
    }
    else
    {
        $image_data = newsomatic_get_web_page(html_entity_decode($image_url), $custom_cookies);
        if ($image_data === FALSE || strpos($image_data, '<Message>Access Denied</Message>') !== FALSE || strpos($image_data, '<title>Just a moment...</title>') !== FALSE || strpos($image_data, '<html') !== FALSE) {
            return false;
        }
    }
    $filename = basename($image_url);
    $filename = explode("?", $filename);
    $filename = $filename[0];
    $filename = urlencode($filename);
    $filename = str_replace('%', '-', $filename);
    $filename = str_replace('#', '-', $filename);
    $filename = str_replace('&', '-', $filename);
    $filename = str_replace('{', '-', $filename);
    $filename = str_replace('}', '-', $filename);
    $filename = str_replace('\\', '-', $filename);
    $filename = str_replace('<', '-', $filename);
    $filename = str_replace('>', '-', $filename);
    $filename = str_replace('*', '-', $filename);
    $filename = str_replace('/', '-', $filename);
    $filename = str_replace('$', '-', $filename);
    $filename = str_replace('\'', '-', $filename);
    $filename = str_replace('"', '-', $filename);
    $filename = str_replace(':', '-', $filename);
    $filename = str_replace('@', '-', $filename);
    $filename = str_replace('+', '-', $filename);
    $filename = str_replace('|', '-', $filename);
    $filename = str_replace('=', '-', $filename);
    $filename = str_replace('`', '-', $filename);
    $file_parts = pathinfo($filename);
    switch($file_parts['extension'])
    {
        case "":
        if(!newsomatic_endsWith($filename, '.jpg'))
            $filename .= 'jpg';
        break;
        case NULL:
        if(!newsomatic_endsWith($filename, '.jpg'))
            $filename .= '.jpg';
        break;
    }
    if (wp_mkdir_p($upload_dir['path'] . '/localimages'))
    {
        $file = $upload_dir['path'] . '/localimages/' . $filename;
        $ret_path = $upload_dir['url'] . '/localimages/' . $filename;
    }
    else
    {
        $file = $upload_dir['basedir'] . '/' . $filename;
        $ret_path = $upload_dir['baseurl'] . '/' . $filename;
    }
    if($wp_filesystem->exists($file))
    {
        if(empty($file_parts['extension']))
        {
            $file_parts['extension'] = 'jpg';
        }
        $unid = uniqid();
        $file .= $unid . '.' . $file_parts['extension'];
        $ret_path .= $unid . '.' . $file_parts['extension'];
    }
    
    $ret = $wp_filesystem->put_contents($file, $image_data);
    if ($ret === FALSE) {
        return false;
    }
    $wp_filetype = wp_check_filetype( $file, null );
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => sanitize_file_name( $file ),
        'post_content' => '',
        'post_status' => 'inherit'
    );
    $screens_attach_id = wp_insert_attachment( $attachment, $file );
    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    require_once( ABSPATH . 'wp-admin/includes/media.php' );
    $attach_data = wp_generate_attachment_metadata( $screens_attach_id, $file );
    wp_update_attachment_metadata( $screens_attach_id, $attach_data );
    return $ret_path;
}
$newsomatic_fatal = false;
function newsomatic_clear_flag_at_shutdown($param, $type)
{
    $error = error_get_last();
    if ($error !== null && $error['type'] === E_ERROR && $GLOBALS['newsomatic_fatal'] === false) {
        $GLOBALS['newsomatic_fatal'] = true;
        $running = array();
        update_option('newsomatic_running_list', $running);
        newsomatic_log_to_file('[FATAL] Exit error: ' . $error['message'] . ', file: ' . $error['file'] . ', line: ' . $error['line'] . ' - rule ID: ' . $param . '!');
        newsomatic_clearFromList($param, $type);
    }
    else
    {
        newsomatic_clearFromList($param, $type);
    }
}

function newsomatic_strip_external_links($content, $url)
{
    $parse = parse_url($url);
    if(isset($parse['host']) && $parse['host'] != '')
    {
        $content = preg_replace('#<a(?:[^>]*)href=[\'"]http.?:\/\/' . preg_quote($parse['host']) . '[^\'"]*?[\'"](?:[^>]*)>(.*?)<\/a>#', "\\1", $content);
    }
    return $content;
}
function newsomatic_generate_random_email()
{
    $tlds = array("com", "net", "gov", "org", "edu", "biz", "info");
    $char = "0123456789abcdefghijklmnopqrstuvwxyz";
    $ulen = mt_rand(5, 10);
    $dlen = mt_rand(7, 17);
    $a = "";
    for ($i = 1; $i <= $ulen; $i++) {
        $a .= substr($char, mt_rand(0, strlen($char)), 1);
    }
    $a .= "@";
    for ($i = 1; $i <= $dlen; $i++) {
        $a .= substr($char, mt_rand(0, strlen($char)), 1);
    }
    $a .= ".";
    $a .= $tlds[mt_rand(0, (sizeof($tlds)-1))];
    return $a;
}

function newsomatic_strip_links($content)
{
    $content = preg_replace('~<a(?:[^>]*)>~', "", $content);
    $content = preg_replace('~<\/a>~', "", $content);
    return $content;
}
add_filter('the_title', 'newsomatic_add_affiliate_title_keyword');
function newsomatic_add_affiliate_title_keyword($content)
{
    $rules  = get_option('newsomatic_keyword_list');
    if(!is_array($rules))
    {
       $rules = array();
    }
    $output = '';
    if (!empty($rules)) {
        foreach ($rules as $request => $value) {
            if(!isset($value[2]) || $value[2] == 'content')
            {
                continue;
            }
            if (is_array($value) && isset($value[1])) {
                $repl = $value[1];
            } else {
                $repl = $request;
            }
            if (isset($value[0]) && $value[0] != '') {
                $content = preg_replace('\'(?!((<.*?)|(<a.*?)))(\b' . preg_quote($request, '\'') . '\b)(?!(([^<>]*?)>)|([^>]*?<\/a>))\'i', '<a href="' . esc_url_raw($value[0]) . '" target="_blank">' . esc_html($repl) . '</a>', $content);
            } else {
                $content = preg_replace('\'(?!((<.*?)|(<a.*?)))(\b' . preg_quote($request, '\'') . '\b)(?!(([^<>]*?)>)|([^>]*?<\/a>))\'i', esc_html($repl), $content);
            }
        }
    }
    return $content;
}
add_filter('the_content', 'newsomatic_add_affiliate_keyword');
add_filter('the_excerpt', 'newsomatic_add_affiliate_keyword');
function newsomatic_add_affiliate_keyword($content)
{
    $rules  = get_option('newsomatic_keyword_list');
    if(!is_array($rules))
    {
       $rules = array();
    }
    $output = '';
    if (!empty($rules)) {
        foreach ($rules as $request => $value) {
            if(isset($value[2]) && $value[2] == 'title')
            {
                continue;
            }
            if (is_array($value) && isset($value[1]) && $value[1] != '') {
                $repl = $value[1];
            } else {
                $repl = $request;
            }
            if (isset($value[0]) && $value[0] != '') {
                $content = preg_replace('\'(?!((<.*?)|(<a.*?)))(\b' . preg_quote($request, '\'') . '\b)(?!(([^<>]*?)>)|([^>]*?<\/a>))\'i', '<a href="' . esc_url_raw($value[0]) . '" target="_blank">' . esc_html($repl) . '</a>', $content);
            } else {
                $content = preg_replace('\'(?!((<.*?)|(<a.*?)))(\b' . preg_quote($request, '\'') . '\b)(?!(([^<>]*?)>)|([^>]*?<\/a>))\'i', esc_html($repl), $content);
            }
        }
    }
    return $content;
}
function newsomatic_randomName() {
    $firstname = array(
        'Johnathon',
        'Anthony',
        'Erasmo',
        'Raleigh',
        'Nancie',
        'Tama',
        'Camellia',
        'Augustine',
        'Christeen',
        'Luz',
        'Diego',
        'Lyndia',
        'Thomas',
        'Georgianna',
        'Leigha',
        'Alejandro',
        'Marquis',
        'Joan',
        'Stephania',
        'Elroy',
        'Zonia',
        'Buffy',
        'Sharie',
        'Blythe',
        'Gaylene',
        'Elida',
        'Randy',
        'Margarete',
        'Margarett',
        'Dion',
        'Tomi',
        'Arden',
        'Clora',
        'Laine',
        'Becki',
        'Margherita',
        'Bong',
        'Jeanice',
        'Qiana',
        'Lawanda',
        'Rebecka',
        'Maribel',
        'Tami',
        'Yuri',
        'Michele',
        'Rubi',
        'Larisa',
        'Lloyd',
        'Tyisha',
        'Samatha',
    );

    $lastname = array(
        'Mischke',
        'Serna',
        'Pingree',
        'Mcnaught',
        'Pepper',
        'Schildgen',
        'Mongold',
        'Wrona',
        'Geddes',
        'Lanz',
        'Fetzer',
        'Schroeder',
        'Block',
        'Mayoral',
        'Fleishman',
        'Roberie',
        'Latson',
        'Lupo',
        'Motsinger',
        'Drews',
        'Coby',
        'Redner',
        'Culton',
        'Howe',
        'Stoval',
        'Michaud',
        'Mote',
        'Menjivar',
        'Wiers',
        'Paris',
        'Grisby',
        'Noren',
        'Damron',
        'Kazmierczak',
        'Haslett',
        'Guillemette',
        'Buresh',
        'Center',
        'Kucera',
        'Catt',
        'Badon',
        'Grumbles',
        'Antes',
        'Byron',
        'Volkman',
        'Klemp',
        'Pekar',
        'Pecora',
        'Schewe',
        'Ramage',
    );

    $name = $firstname[rand ( 0 , count($firstname) -1)];
    $name .= ' ';
    $name .= $lastname[rand ( 0 , count($lastname) -1)];

    return $name;
}
function newsomatic_generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
function newsomatic_get_free_image($newsomatic_Main_Settings, $query_words, &$img_attr, $res_cnt = 3)
{
    $original_url = '';
    $rand_arr = array();
    if(isset($newsomatic_Main_Settings['pixabay_api']) && $newsomatic_Main_Settings['pixabay_api'] != '')
    {
        $rand_arr[] = 'pixabay';
    }
    if(isset($newsomatic_Main_Settings['flickr_api']) && $newsomatic_Main_Settings['flickr_api'] !== '')
    {
        $rand_arr[] = 'flickr';
    }
    if(isset($newsomatic_Main_Settings['pexels_api']) && $newsomatic_Main_Settings['pexels_api'] !== '')
    {
        $rand_arr[] = 'pexels';
    }
    if(isset($newsomatic_Main_Settings['pixabay_scrape']) && $newsomatic_Main_Settings['pixabay_scrape'] == 'on')
    {
        $rand_arr[] = 'pixabayscrape';
    }
    if(isset($newsomatic_Main_Settings['unsplash_key']) && $newsomatic_Main_Settings['unsplash_key'] != '')
    {
        $rand_arr[] = 'unsplash';
    }
    if(isset($newsomatic_Main_Settings['google_images']) && $newsomatic_Main_Settings['google_images'] == 'on')
    {
        $rand_arr[] = 'google';
    }
    $rez = false;
    while(($rez === false || $rez === '') && count($rand_arr) > 0)
    {
        $rand = array_rand($rand_arr);
        if($rand_arr[$rand] == 'pixabay')
        {
            unset($rand_arr[$rand]);
            if(isset($newsomatic_Main_Settings['img_ss']) && $newsomatic_Main_Settings['img_ss'] == 'on')
            {
                $img_ss = '1';
            }
            else
            {
                $img_ss = '0';
            }
            if(isset($newsomatic_Main_Settings['img_editor']) && $newsomatic_Main_Settings['img_editor'] == 'on')
            {
                $img_editor = '1';
            }
            else
            {
                $img_editor = '0';
            }
            $rez = newsomatic_get_pixabay_image($newsomatic_Main_Settings['pixabay_api'], $query_words, $newsomatic_Main_Settings['img_language'], $newsomatic_Main_Settings['imgtype'], $newsomatic_Main_Settings['scrapeimg_orientation'], $newsomatic_Main_Settings['img_order'], $newsomatic_Main_Settings['img_cat'], $newsomatic_Main_Settings['img_mwidth'], $newsomatic_Main_Settings['img_width'], $img_ss, $img_editor, $original_url, $res_cnt);
            if($rez !== false && $rez !== '')
            {
                $img_attr = str_replace('%%image_source_name%%', 'Pixabay', $img_attr);
                $img_attr = str_replace('%%image_source_url%%', $original_url, $img_attr);
                $img_attr = str_replace('%%image_source_website%%', 'https://pixabay.com/', $img_attr);
            }
        }
        elseif($rand_arr[$rand] == 'morguefile')
        {
            unset($rand_arr[$rand]);
            $rez = newsomatic_get_morguefile_image($newsomatic_Main_Settings['morguefile_api'], $newsomatic_Main_Settings['morguefile_secret'], $query_words, $original_url);
            if($rez !== false && $rez !== '')
            {
                $img_attr = str_replace('%%image_source_name%%', 'MorgueFile', $img_attr);
                $img_attr = str_replace('%%image_source_url%%', 'https://morguefile.com/', $img_attr);
                $img_attr = str_replace('%%image_source_website%%', 'https://morguefile.com/', $img_attr);
            }
        }
        elseif($rand_arr[$rand] == 'flickr')
        {
            unset($rand_arr[$rand]);
            $rez = newsomatic_get_flickr_image($newsomatic_Main_Settings, $query_words, $original_url, $res_cnt);
            if($rez !== false && $rez !== '')
            {
                $img_attr = str_replace('%%image_source_name%%', 'Flickr', $img_attr);
                $img_attr = str_replace('%%image_source_url%%', $original_url, $img_attr);
                $img_attr = str_replace('%%image_source_website%%', 'https://www.flickr.com/', $img_attr);
            }
        }
        elseif($rand_arr[$rand] == 'pexels')
        {
            unset($rand_arr[$rand]);
            $rez = newsomatic_get_pexels_image($newsomatic_Main_Settings, $query_words, $original_url, $res_cnt);
            if($rez !== false && $rez !== '')
            {
                $img_attr = str_replace('%%image_source_name%%', 'Pexels', $img_attr);
                $img_attr = str_replace('%%image_source_url%%', $original_url, $img_attr);
                $img_attr = str_replace('%%image_source_website%%', 'https://www.pexels.com/', $img_attr);
            }
        }
        elseif($rand_arr[$rand] == 'pixabayscrape')
        {
            unset($rand_arr[$rand]);
            $rez = newsomatic_scrape_pixabay_image($newsomatic_Main_Settings, $query_words, $original_url);
            if($rez !== false && $rez !== '')
            {
                $img_attr = str_replace('%%image_source_name%%', 'Pixabay', $img_attr);
                $img_attr = str_replace('%%image_source_url%%', $original_url, $img_attr);
                $img_attr = str_replace('%%image_source_website%%', 'https://pixabay.com/', $img_attr);
            }
        }
        elseif($rand_arr[$rand] == 'unsplash')
        {
            unset($rand_arr[$rand]);
            $rez = newsomatic_scrape_unsplash_image($query_words, $original_url);
            if($rez !== false && $rez !== '')
            {
                $img_attr = str_replace('%%image_source_name%%', 'Unsplash', $img_attr);
                $img_attr = str_replace('%%image_source_url%%', $original_url, $img_attr);
                $img_attr = str_replace('%%image_source_website%%', 'https://unsplash.com/', $img_attr);
            }
        }
        elseif($rand_arr[$rand] == 'google')
        {
            unset($rand_arr[$rand]);
            $original_url = 'https://google.com/';
            $rez = newsomatic_get_random_image_google($query_words);
            if($rez !== false && $rez !== '')
            {
                $img_attr = str_replace('%%image_source_name%%', 'Google Images', $img_attr);
                $img_attr = str_replace('%%image_source_url%%', $original_url, $img_attr);
                $img_attr = str_replace('%%image_source_website%%', 'https://google.com/', $img_attr);
            }
        }
        else
        {
            newsomatic_log_to_file('Unrecognized free file source: ' . $rand_arr[$rand]);
            unset($rand_arr[$rand]);
        }
    }
    $img_attr = str_replace('%%image_source_name%%', '', $img_attr);
    $img_attr = str_replace('%%image_source_url%%', '', $img_attr);
    $img_attr = str_replace('%%image_source_website%%', '', $img_attr);
    return $rez;
}   
function newsomatic_file_get_contents_advanced($url, $headers = '', $referrer = 'self', $user_agent = false)
{
    $content = false;
    if (parse_url($url, PHP_URL_SCHEME) != '' && function_exists('curl_init')) 
    {
        $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
        $max_redirects = 10;
        $ch = curl_init();
        if($ch !== false)
        {
            curl_setopt($ch, CURLOPT_URL, $url);
            if (strtolower($referrer) == 'self') {
                curl_setopt($ch, CURLOPT_REFERER, $url);
            } elseif (strlen($referrer)) {
                curl_setopt($ch, CURLOPT_REFERER, $referrer);
            }
            if ($user_agent) {
                curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
            } 
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $headers = trim($headers);
            if (strlen($headers)) {
                $headers_array = explode(PHP_EOL, $headers);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers_array);
            }
            if (isset($newsomatic_Main_Settings['proxy_url']) && $newsomatic_Main_Settings['proxy_url'] != '' && $newsomatic_Main_Settings['proxy_url'] != 'disable' && $newsomatic_Main_Settings['proxy_url'] != 'disabled') {
                $prx = explode(',', $newsomatic_Main_Settings['proxy_url']);
                $randomness = array_rand($prx);
                curl_setopt( $ch, CURLOPT_PROXY, trim($prx[$randomness]));
                if (isset($newsomatic_Main_Settings['proxy_auth']) && $newsomatic_Main_Settings['proxy_auth'] != '') 
                {
                    $prx_auth = explode(',', $newsomatic_Main_Settings['proxy_auth']);
                    if(isset($prx_auth[$randomness]) && trim($prx_auth[$randomness]) != '')
                    {
                        curl_setopt( $ch, CURLOPT_PROXYUSERPWD, trim($prx_auth[$randomness]));
                    }
                }
            }
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            if (ini_get('open_basedir') == '') 
            {
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_MAXREDIRS, $max_redirects);
            } 
            else 
            {
                $base_url = $url;
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
                $rch = curl_copy_handle($ch);
                curl_setopt($rch, CURLOPT_HEADER, true);
                curl_setopt($rch, CURLOPT_NOBODY, true);
                curl_setopt($rch, CURLOPT_FORBID_REUSE, false);
                curl_setopt($rch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($rch, CURLOPT_SSL_VERIFYPEER, false);
                do 
                {
                    curl_setopt($rch, CURLOPT_URL, $url);
                    curl_setopt($rch, CURLOPT_REFERER, $url);
                    $header = curl_exec($rch);
                    if (curl_errno($rch)) {
                        $code = 0;
                    } else {
                        $code = curl_getinfo($rch, CURLINFO_HTTP_CODE);
                        if ($code == 301 || $code == 302) {
                            preg_match('/Location:(.*?)\n/', $header, $matches);
                            $url = trim(array_pop($matches));
                            if (strlen($url) && substr($url, 0, 1) == '/') {
                                $url = $base_url . $url;
                            }
                        } else {
                            $code = 0;
                        }
                    }
                } 
                while ($code && --$max_redirects);
                curl_close($rch);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_REFERER, $url);
            }
            curl_setopt($ch, CURLOPT_HEADER, false);
            $content = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($code != 200) {
                $content = false;
            }
            curl_close($ch);
        }
    }
    if (!isset($content) || $content === false) {
        stream_context_set_default(array('ssl' => array('verify_peer' => false, 'verify_peer_name' => false,), 'http' => array('method' => 'HEAD', 'timeout' => 10, 'user_agent' => $user_agent)));
        $content = file_get_contents($url);
    }
    return $content;
}
function newsomatic_get_random_image_google($keyword, $min_width = 0, $min_height = 0)
{
    $gimageurl = 'https://www.google.com/search?q=' . urlencode($keyword . ' -site:depositphotos.com -site:123rf.com') . '&tbm=isch&tbs=il:cl&sa=X';
    $res = newsomatic_file_get_contents_advanced($gimageurl, '', 'self', 'Mozilla/5.0 (Windows NT 10.0;WOW64;rv:97.0) Gecko/20100101 Firefox/97.0/3871tuT2p1u-81');
    preg_match_all('/\["([\w%-\.\/:\?&=]+\.jpg|\.jpeg|\.gif|\.png|\.bmp|\.wbmp|\.webm|\.xbm)",\d+,\d+\]/i', $res, $matches);
    $items = $matches[0];
    if (count($items)) {
        shuffle($items);
        foreach ($items as $item) {
            preg_match('#\["(.*?)",(.*?),(.*?)\]#', $item, $matches);
            if (count($matches) == 4 && ($min_width > 0 || $min_width <= $matches[3]) && ($min_height > 0 || $min_height <= $matches[2])) {
                return $matches[1];
            }
        }
    }
    return '';
}
function newsomatic_get_pixabay_image($app_id, $query, $lang, $image_type, $orientation, $order, $image_category, $max_width, $min_width, $safe_search, $editors_choice, &$original_url, $get_max = 3)
{
    $original_url = 'https://pixabay.com';
    $featured_image = '';
    $feed_uri = 'https://pixabay.com/api/?key=' . $app_id;
    if($query != '')
    {
        $feed_uri .= '&q=' . urlencode($query);
    }
    $feed_uri .= '&per_page=' . $get_max;
    if($lang != '' && $lang != 'any')
    {
        $feed_uri .= '&lang=' . $lang;
    }
    if($image_type != '')
    {
        $feed_uri .= '&image_type=' . $image_type;
    }
    if($orientation != '')
    {
        $feed_uri .= '&orientation=' . $orientation;
    }
    if($order != '')
    {
        $feed_uri .= '&order=' . $order;
    }
    if($image_category != '')
    {
        $feed_uri .= '&category=' . $image_category;
    }
    if($max_width != '')
    {
        $feed_uri .= '&max_width=' . $max_width;
    }
    if($min_width != '')
    {
        $feed_uri .= '&min_width=' . $min_width;
    }
    if($safe_search == '1')
    {
        $feed_uri .= '&safesearch=true';
    }
    if($editors_choice == '1')
    {
        $feed_uri .= '&editors_choice=true';
    }
     
    $exec = newsomatic_get_web_page($feed_uri);
    if ($exec !== FALSE) 
    {
        if (stristr($exec, '"hits"') !== FALSE) 
        {
            $exec = preg_replace('#^[a-zA-Z0-9]*#', '', $exec);
            $exec = trim($exec, '()');
            $json  = json_decode($exec);
            $items = $json->hits;
            if (count($items) != 0) 
            {
                shuffle($items);
                foreach($items as $item)
                {
                    $featured_image = $item->webformatURL;
                    $original_url = $item->pageURL;
                    break;
                }
            }
        }
        else
        {
            newsomatic_log_to_file('Unknow response from api: ' . $feed_uri . ' - resp: ' . $exec);
            return false;
        }
    }
    else
    {
        newsomatic_log_to_file('Error while getting api url: ' . $feed_uri);
        return false;
    }
    return $featured_image;
}

function newsomatic_get_redirect_url($url){
    $url_parts = parse_url($url);
    if (!$url_parts) return false;
    if (!isset($url_parts['host'])) return false;
    if (!isset($url_parts['path'])) $url_parts['path'] = '/';

    $sock = fsockopen($url_parts['host'], (isset($url_parts['port']) ? (int)$url_parts['port'] : 80), $errno, $errstr, 30);
    if (!$sock) return false;

    $request = "HEAD " . $url_parts['path'] . (isset($url_parts['query']) ? '?'.$url_parts['query'] : '') . " HTTP/1.1".PHP_EOL; 
    $request .= 'Host: ' . $url_parts['host'] . PHP_EOL; 
    $request .= "Connection: Close".PHP_EOL.PHP_EOL; 
    fwrite($sock, $request);
    $response = '';
    while(!feof($sock)) $response .= fread($sock, 8192);
    fclose($sock);

    if (preg_match('/^Location: (.+?)$/m', $response, $matches)){
        if ( substr($matches[1], 0, 1) == "/" )
            return $url_parts['scheme'] . "://" . $url_parts['host'] . trim($matches[1]);
        else
            return trim($matches[1]);

    } else {
        return false;
    }
}

function newsomatic_get_all_redirects($url){
    $redirects = array();
    while ($newurl = newsomatic_get_redirect_url($url)){
        if (in_array($newurl, $redirects)){
            break;
        }
        $redirects[] = $newurl;
        $url = $newurl;
    }
    return $redirects;
}

function newsomatic_get_final_url($url){
    if (strpos($url, 'localhost') !== false)
    {
        return $url;
    }
    $redirects = newsomatic_get_all_redirects($url);
    if (count($redirects)>0){
        return array_pop($redirects);
    } else {
        return $url;
    }
}
function newsomatic_scrape_unsplash_image($query, &$original_url)
{
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    if(!isset($newsomatic_Main_Settings['unsplash_key']) || trim($newsomatic_Main_Settings['unsplash_key']) == '')
    {
        newsomatic_log_to_file('You need to enter an Unsplash API key for this to work');
        return false;
    }
    if($query == '')
    {
        newsomatic_log_to_file('Empty queries are not allowed for Unsplash.');
        return false;
    }
    $original_url = 'https://unsplash.com/';
    $page = 1;
    $perPage = 30;
    $orderBy = 'relevant';
    $collections = '';
    $contentFilter = 'low';
    $color = '';
    $orientation = '';
    $feed_uri = "https://api.unsplash.com/search/photos";
    $params = [
        'query' => $query,
        'page' => $page,
        'per_page' => $perPage,
        'order_by' => $orderBy,
        'collections' => $collections,
        'content_filter' => $contentFilter,
        'client_id' => trim($newsomatic_Main_Settings['unsplash_key'])
    ];
    if(!empty($orientation))
    {
        $params['orientation'] = $orientation;
    }
    if(!empty($color))
    {
        $params['color'] = $color;
    }
    $featured_image = '';
    $queryUrl = $feed_uri . '?' . http_build_query($params);
    $ch               = curl_init();
    if ($ch === FALSE) {
        newsomatic_log_to_file('Failed to init curl for Unsplash!');
        return false;
    }
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPGET, 1);
    $ztime = 300;
    curl_setopt($ch, CURLOPT_TIMEOUT, $ztime);
    curl_setopt($ch, CURLOPT_URL, $queryUrl);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $exec = curl_exec($ch);
    curl_close($ch);
    $items = json_decode ( $exec, true );
    if(!isset($items['results']))
    {
        newsomatic_log_to_file('Failed to find photo node in Unsplash response URI: ' . $queryUrl);
        return false;
    }
    if(count($items['results']) == 0)
    {
        return $featured_image;
    }
    $x = 0;
    foreach($items['results'] as $photox)
    {
        if(isset($photox['urls']['raw']))
        {
            $full_result_list[] = $photox['urls']['raw'];
        }
    }
    shuffle($items['results']);
    while($featured_image == '' && isset($items['results'][$x]))
    {
        $item = $items['results'][$x];
        if(isset($item['urls']['raw']))
        {
            $featured_image = $item['urls']['raw'];
        }
        if($featured_image != '' && isset($item['links']['html']))
        {
            $original_url = $item['links']['html'];
        }
        $x++;
    }
    return $featured_image;
}
function newsomatic_scrape_pixabay_image($newsomatic_Main_Settings, $query, &$original_url)
{
    $original_url = 'https://pixabay.com';
    $featured_image = '';
    $feed_uri = 'https://pixabay.com/en/photos/';
    if($query != '')
    {
        $feed_uri .= '?q=' . urlencode($query);
    }

    if($newsomatic_Main_Settings['scrapeimgtype'] != 'all')
    {
        $feed_uri .= '&image_type=' . $newsomatic_Main_Settings['scrapeimgtype'];
    }
    if($newsomatic_Main_Settings['scrapeimg_orientation'] != '')
    {
        $feed_uri .= '&orientation=' . $newsomatic_Main_Settings['scrapeimg_orientation'];
    }
    if($newsomatic_Main_Settings['scrapeimg_order'] != '' && $newsomatic_Main_Settings['scrapeimg_order'] != 'any')
    {
        $feed_uri .= '&order=' . $newsomatic_Main_Settings['scrapeimg_order'];
    }
    if($newsomatic_Main_Settings['scrapeimg_cat'] != '')
    {
        $feed_uri .= '&category=' . $newsomatic_Main_Settings['scrapeimg_cat'];
    }
    if($newsomatic_Main_Settings['scrapeimg_height'] != '')
    {
        $feed_uri .= '&min_height=' . $newsomatic_Main_Settings['scrapeimg_height'];
    }
    if($newsomatic_Main_Settings['scrapeimg_width'] != '')
    {
        $feed_uri .= '&min_width=' . $newsomatic_Main_Settings['scrapeimg_width'];
    }
    $exec = newsomatic_get_web_page($feed_uri);
    if ($exec !== FALSE) 
    {
        preg_match_all('/<a href="([^"]+?)".+?(?:data-lazy|src)="([^"]+?\.jpg|png)"/i', $exec, $matches);
        if (!empty($matches[2])) {
            $p = array_combine($matches[1], $matches[2]);
            if(count($p) > 0)
            {
                shuffle($p);
                foreach ($p as $key => $val) {
                    $featured_image = $val;
                    if(!is_numeric($key))
                    {
                        if(substr($key, 0, 4) !== "http")
                        {
                            $key = 'https://pixabay.com' . $key;
                        }
                        $original_url = $key;
                    }
                    else
                    {
                        $original_url = 'https://pixabay.com';
                    }
                    break;
                }
            }
        }
    }
    else
    {
        newsomatic_log_to_file('Error while getting api url: ' . $feed_uri);
        return false;
    }
    return $featured_image;
}
function newsomatic_get_morguefile_image($app_id, $app_secret, $query, &$original_url)
{
    $featured_image = '';
    if(!class_exists('newsomatic_morguefile'))
    {
        require_once (dirname(__FILE__) . "/res/morguefile/mf.api.class.php");
    }
    $query = explode(' ', $query);
    $query = $query[0];
    {
        $mf = new newsomatic_morguefile($app_id, $app_secret);
        $rez = $mf->call('/images/search/sort/page/' . $query);
        if ($rez !== FALSE) 
        {
            $chosen_one = $rez->doc[array_rand($rez->doc)];
            if (isset($chosen_one->file_path_large)) 
            {
                return $chosen_one->file_path_large;
            }
            else
            {
                return false;
            }
        }
        else
        {
            newsomatic_log_to_file('Error while getting api response from morguefile.');
            return false;
        }
    }
    return $featured_image;
}
function newsomatic_get_flickr_image($newsomatic_Main_Settings, $query, &$original_url, $max)
{
    $original_url = 'https://www.flickr.com';
    $featured_image = '';
    $feed_uri = 'https://api.flickr.com/services/rest/?method=flickr.photos.search&api_key=' . $newsomatic_Main_Settings['flickr_api'] . '&media=photos&per_page=' . esc_html($max) . '&format=php_serial&text=' . urlencode($query);
    if(isset($newsomatic_Main_Settings['flickr_license']) && $newsomatic_Main_Settings['flickr_license'] != '-1')
    {
        $feed_uri .= '&license=' . $newsomatic_Main_Settings['flickr_license'];
    }
    if(isset($newsomatic_Main_Settings['flickr_order']) && $newsomatic_Main_Settings['flickr_order'] != '')
    {
        $feed_uri .= '&sort=' . $newsomatic_Main_Settings['flickr_order'];
    }
    $feed_uri .= '&extras=description,license,date_upload,date_taken,owner_name,icon_server,original_format,last_update,geo,tags,machine_tags,o_dims,views,media,path_alias,url_sq,url_t,url_s,url_q,url_m,url_n,url_z,url_c,url_l,url_o';
     
    {
        $ch               = curl_init();
        if ($ch === FALSE) {
            newsomatic_log_to_file('Failed to init curl for flickr!');
            return false;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Referer: https://www.flickr.com/'));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPGET, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_URL, $feed_uri);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $exec = curl_exec($ch);
        curl_close($ch);
        if (stristr($exec, 'photos') === FALSE) {
            newsomatic_log_to_file('Unrecognized Flickr API response: ' . $exec . ' URI: ' . $feed_uri);
            return false;
        }
        $items = unserialize ( $exec );
        if(!isset($items['photos']['photo']))
        {
            newsomatic_log_to_file('Failed to find photo node in response: ' . $exec . ' URI: ' . $feed_uri);
            return false;
        }
        if(count($items['photos']['photo']) == 0)
        {
            return $featured_image;
        }
        $x = 0;
        shuffle($items['photos']['photo']);
        while($featured_image == '' && isset($items['photos']['photo'][$x]))
        {
            $item = $items['photos']['photo'][$x];
            if(isset($item['url_o']))
            {
                $featured_image = esc_url_raw($item['url_o']);
            }
            elseif(isset($item['url_l']))
            {
                $featured_image = esc_url_raw($item['url_l']);
            }
            elseif(isset($item['url_c']))
            {
                $featured_image = esc_url_raw($item['url_c']);
            }
            elseif(isset($item['url_z']))
            {
                $featured_image = esc_url_raw($item['url_z']);
            }
            elseif(isset($item['url_n']))
            {
                $featured_image = esc_url_raw($item['url_n']);
            }
            elseif(isset($item['url_m']))
            {
                $featured_image = esc_url_raw($item['url_m']);
            }
            elseif(isset($item['url_q']))
            {
                $featured_image = esc_url_raw($item['url_q']);
            }
            elseif(isset($item['url_s']))
            {
                $featured_image = esc_url_raw($item['url_s']);
            }
            elseif(isset($item['url_t']))
            {
                $featured_image = esc_url_raw($item['url_t']);
            }
            elseif(isset($item['url_sq']))
            {
                $featured_image = esc_url_raw($item['url_sq']);
            }
            if($featured_image != '')
            {
                $original_url = esc_url_raw('https://www.flickr.com/photos/' . $item['owner'] . '/' . $item['id']);
            }
            $x++;
        }
    }
    return $featured_image;
}
function newsomatic_get_pexels_image($newsomatic_Main_Settings, $query, &$original_url, $max)
{
    $original_url = 'https://pexels.com';
    $featured_image = '';
    $feed_uri = 'https://api.pexels.com/v1/search?query=' . urlencode($query) . '&per_page=' . $max;
     
    {
        $ch               = curl_init();
        if ($ch === FALSE) {
            newsomatic_log_to_file('Failed to init curl for flickr!');
            return false;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: ' . esc_html($newsomatic_Main_Settings['pexels_api'])));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPGET, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_URL, $feed_uri);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $exec = curl_exec($ch);
        curl_close($ch);
        if (stristr($exec, 'photos') === FALSE) {
            newsomatic_log_to_file('Unrecognized Pexels API response: ' . $exec . ' URI: ' . $feed_uri);
            return false;
        }
        $items = json_decode ( $exec, true );
        if(!isset($items['photos']))
        {
            newsomatic_log_to_file('Failed to find photo node in Pexels response: ' . $exec . ' URI: ' . $feed_uri);
            return false;
        }
        if(count($items['photos']) == 0)
        {
            return $featured_image;
        }
        $x = 0;
        shuffle($items['photos']);
        while($featured_image == '' && isset($items['photos'][$x]))
        {
            $item = $items['photos'][$x];
            if(isset($item['src']['large']))
            {
                $featured_image = esc_url_raw($item['src']['large']);
            }
            elseif(isset($item['src']['medium']))
            {
                $featured_image = esc_url_raw($item['src']['medium']);
            }
            elseif(isset($item['src']['small']))
            {
                $featured_image = esc_url_raw($item['src']['small']);
            }
            elseif(isset($item['src']['portrait']))
            {
                $featured_image = esc_url_raw($item['src']['portrait']);
            }
            elseif(isset($item['src']['landscape']))
            {
                $featured_image = esc_url_raw($item['src']['landscape']);
            }
            elseif(isset($item['src']['original']))
            {
                $featured_image = esc_url_raw($item['src']['original']);
            }
            elseif(isset($item['src']['tiny']))
            {
                $featured_image = esc_url_raw($item['src']['tiny']);
            }
            if($featured_image != '')
            {
                $original_url = esc_url_raw($item['url']);
            }
            $x++;
        }
    }
    return $featured_image;
}

function newsomatic_meta_box_function($post)
{
    wp_register_style('newsomatic-browser-style', plugins_url('styles/newsomatic-browser.css', __FILE__), false, NEWSOMATIC_MAJOR_VERSION);
    wp_enqueue_style('newsomatic-browser-style');
    wp_suspend_cache_addition(true);
    $index                     = get_post_meta($post->ID, 'newsomatic_parent_rule', true);
    $img                       = get_post_meta($post->ID, 'newsomatic_featured_img', true);
    $newsomatic_post_url         = get_post_meta($post->ID, 'newsomatic_post_url', true);
    $newsomatic_post_id          = get_post_meta($post->ID, 'newsomatic_post_id', true);
    
    if (isset($index) && $index != '') {
        $ech = '<table class="crf_table"><tr><td><b>' . esc_html__('Post Parent Rule:', 'newsomatic-news-post-generator') . '</b></td><td>&nbsp;' . esc_html($index) . '</td></tr>';
        if ($img != '') {
            $ech .= '<tr><td><b>' . esc_html__('Featured Image:', 'newsomatic-news-post-generator') . '</b></td><td>&nbsp;' . esc_url_raw($img) . '</td></tr>';
        }
        if ($newsomatic_post_url != '') {
            $ech .= '<tr><td><b>' . esc_html__('Item Source URL:', 'newsomatic-news-post-generator') . '</b></td><td>&nbsp;' . esc_url_raw($newsomatic_post_url) . '</td></tr>';
        }
        if ($newsomatic_post_id != '') {
            $ech .= '<tr><td><b>' . esc_html__('Item Source Post ID:', 'newsomatic-news-post-generator') . '</b></td><td>&nbsp;' . esc_html($newsomatic_post_id) . '</td></tr>';
        }
        $ech .= '</table><br/>';
    } else {
        $ech = esc_html__('This is not an automatically generated post.', 'newsomatic-news-post-generator');
    }
    echo $ech;
    wp_suspend_cache_addition(false);
}
foreach( [ 'post', 'page', 'post_type' ] as $type )
{
    add_filter($type . '_link','newsomatic_permalink_changer', 10, 2 );
}
add_filter('the_permalink','newsomatic_permalink_changer', 10, 2 );
function newsomatic_permalink_changer($link, $postid = ''){
	$le_post_id = '';
    if(is_numeric($postid))
    {
        $le_post_id = $postid;
    }
    elseif(is_object($postid) && isset($postid->ID))
    {
        $le_post_id = $postid->ID;
    }
    else
    {
        global $post;
        if(isset($post->ID))
        {
            $le_post_id = $post->ID;
        }
    }
	if (!empty($le_post_id)) {
        $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
        if (isset($newsomatic_Main_Settings['newsomatic_enabled']) && $newsomatic_Main_Settings['newsomatic_enabled'] == 'on') {
            if (isset($newsomatic_Main_Settings['link_source']) && $newsomatic_Main_Settings['link_source'] == 'on') {
                $url = get_post_meta($le_post_id, 'newsomatic_change_title_link', true);
                if ( trim($url) == '1')
                {
                    $new_url = get_post_meta($le_post_id, 'newsomatic_post_url', true);
                    if(trim($new_url) != '') {
                        return $new_url;
                    }
                }
            }
        }
	}
	return $link;
}
function newsomatic_addPostMeta($post_id, $post, $param, $type, $featured_img, $title_url = 0)
{
    add_post_meta($post_id, 'newsomatic_parent_rule', $type . '-' . $param);
    add_post_meta($post_id, 'newsomatic_parent_rule1', $param);
    add_post_meta($post_id, 'newsomatic_parent_type', $type);
    add_post_meta($post_id, 'newsomatic_featured_img', $featured_img);
    add_post_meta($post_id, 'newsomatic_post_url', $post['newsomatic_post_url']);
    add_post_meta($post_id, 'newsomatic_post_id', $post['newsomatic_post_id']);
    if($title_url == '1')
    {
        add_post_meta($post_id, 'newsomatic_change_title_link', '1');
    }
}

function newsomatic_url_is_image( $url ) {
    $url = str_replace(' ', '%20', $url);
    if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
        return FALSE;
    }
    $ext = array( 'jpeg', 'jpg', 'gif', 'png', 'jpe', 'tif', 'tiff', 'svg', 'ico' , 'webp', 'dds', 'heic', 'psd', 'pspimage', 'tga', 'thm', 'yuv', 'ai', 'eps', 'php');
    $info = (array) pathinfo( parse_url( $url, PHP_URL_PATH ) );
    if(!isset( $info['extension'] ))
    {
        return true;
    }
    return isset( $info['extension'] )
        && in_array( strtolower( $info['extension'] ), $ext, TRUE );
}

function newsomatic_generate_featured_image($image_url, $post_id, $custom_cookies = '')
{
    global $wp_filesystem;
    if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ){
        include_once(ABSPATH . 'wp-admin/includes/file.php');$creds = request_filesystem_credentials( site_url() );
        wp_filesystem($creds);
    }
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    $upload_dir = wp_upload_dir();
    if(!function_exists('is_plugin_active'))
    {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    }
    if (isset($newsomatic_Main_Settings['no_local_image']) && $newsomatic_Main_Settings['no_local_image'] == 'on' && (!isset($newsomatic_Main_Settings['url_image']) || $newsomatic_Main_Settings['url_image'] != 'on')) {
        
        if(!newsomatic_url_is_image($image_url))
        {
            return false;
        }
        
        $file = $upload_dir['basedir'] . '/default_img_newsomatic.jpg';
        if(!$wp_filesystem->exists($file))
        {
            $image_data = $wp_filesystem->get_contents(html_entity_decode(dirname(__FILE__) . "/images/icon.png"));
            if ($image_data === FALSE || strpos($image_data, '<Message>Access Denied</Message>') !== FALSE || strpos($image_data, '<title>Just a moment...</title>') !== FALSE || strpos($image_data, '<html') !== FALSE || strpos($image_data, 'ERROR: The requested URL could not be retrieved') !== FALSE) {
                return false;
            }
            $ret = $wp_filesystem->put_contents($file, $image_data);
            if ($ret === FALSE) {
                return false;
            }
        }
        $need_attach = false;
        $checking_id = get_option('newsomatic_attach_id', false);
        if($checking_id === false)
        {
            $need_attach = true;
        }
        else
        {
            $atturl = wp_get_attachment_url($checking_id);
            if($atturl === false)
            {
                $need_attach = true;
            }
        }
        if($need_attach)
        {
            $filename = basename(dirname(__FILE__) . "/images/icon.png");
            $wp_filetype = wp_check_filetype($filename, null);
            if($wp_filetype['type'] == '')
            {
                $wp_filetype['type'] = 'image/png';
            }
            $attachment  = array(
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => sanitize_file_name($filename),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            
            $attach_id   = wp_insert_attachment($attachment, $file, $post_id);
            if ($attach_id === 0) {
                return false;
            }
            update_option('newsomatic_attach_id', $attach_id);
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            $attach_data = wp_generate_attachment_metadata($attach_id, $file);
            wp_update_attachment_metadata($attach_id, $attach_data);
        }
        else
        {
            $attach_id = $checking_id;
        }
        $res2 = set_post_thumbnail($post_id, $attach_id);
        if ($res2 === FALSE) {
            return false;
        }
        
        return true;
    }
    elseif (isset($newsomatic_Main_Settings['url_image']) && $newsomatic_Main_Settings['url_image'] == 'on' && (is_plugin_active('featured-image-from-url/featured-image-from-url.php') || is_plugin_active('fifu-premium/fifu-premium.php')))
    {
        if(!newsomatic_url_is_image($image_url))
        {
            newsomatic_log_to_file('Provided remote image is not valid: ' . $image_url);
            return false;
        }
        
        if(function_exists('fifu_dev_set_image'))
        {
            fifu_dev_set_image($post_id, $image_url);
        }
        else
        {
            $value = newsomatic_get_formatted_value($image_url, '', $post_id);
            $attach_id = newsomatic_insert_attachment_by($value);
            update_post_meta($post_id, '_thumbnail_id', $attach_id);
            update_post_meta($post_id, 'fifu_image_url', $image_url);
            update_post_meta($attach_id, '_wp_attached_file', ';' . $image_url);
            $attach = get_post( $attach_id );
            if($attach !== null)
            {
                $attach->post_author = 77777;
                wp_update_post( $attach );
            }
        }
        return true;
    }
    $image_data = newsomatic_get_web_page($image_url, $custom_cookies);
    if ($image_data === FALSE || strpos($image_data, '<Message>Access Denied</Message>') !== FALSE || strpos($image_data, '<title>Just a moment...</title>') !== FALSE || strpos($image_data, '<html') !== FALSE || strpos($image_data, 'You don\'t have permission') !== FALSE) {
        if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ){
            include_once(ABSPATH . 'wp-admin/includes/file.php');$creds = request_filesystem_credentials( site_url() );
            wp_filesystem($creds);
        }
        $image_data = $wp_filesystem->get_contents($image_url);
        if ($image_data === FALSE || empty($image_data)) {
            return false;
        }
    }
    
    $filename = basename($image_url);
    $temp     = explode("?", $filename);
    $filename = $temp[0];
    $filename = str_replace('%', '-', $filename);
    $filename = str_replace('#', '-', $filename);
    $filename = str_replace('&', '-', $filename);
    $filename = str_replace('{', '-', $filename);
    $filename = str_replace('}', '-', $filename);
    $filename = str_replace('\\', '-', $filename);
    $filename = str_replace('<', '-', $filename);
    $filename = str_replace('>', '-', $filename);
    $filename = str_replace('*', '-', $filename);
    $filename = str_replace('/', '-', $filename);
    $filename = str_replace('$', '-', $filename);
    $filename = str_replace('\'', '-', $filename);
    $filename = str_replace('"', '-', $filename);
    $filename = str_replace(':', '-', $filename);
    $filename = str_replace('@', '-', $filename);
    $filename = str_replace('+', '-', $filename);
    $filename = str_replace('|', '-', $filename);
    $filename = str_replace('=', '-', $filename);
    $filename = str_replace('`', '-', $filename);
    $filename = stripslashes(preg_replace_callback('#(%[a-zA-Z0-9_]*)#', function($matches){ return rand(0, 9); }, preg_quote($filename)));
    $file_parts = pathinfo($filename);
    $post_title = get_the_title($post_id);
    if($post_title != '')
    {
        $post_title = remove_accents( $post_title );
        $invalid = array(
            ' '   => '-',
            '%20' => '-',
            '_'   => '-',
        );
        $post_title = str_replace( array_keys( $invalid ), array_values( $invalid ), $post_title );
        $post_title = preg_replace('/[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0077}\x{E006C}\x{E0073}\x{E007F})|[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0073}\x{E0063}\x{E0074}\x{E007F})|[\x{1F3F4}](?:\x{E0067}\x{E0062}\x{E0065}\x{E006E}\x{E0067}\x{E007F})|[\x{1F3F4}](?:\x{200D}\x{2620}\x{FE0F})|[\x{1F3F3}](?:\x{FE0F}\x{200D}\x{1F308})|[\x{0023}\x{002A}\x{0030}\x{0031}\x{0032}\x{0033}\x{0034}\x{0035}\x{0036}\x{0037}\x{0038}\x{0039}](?:\x{FE0F}\x{20E3})|[\x{1F415}](?:\x{200D}\x{1F9BA})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F467})|[\x{1F468}](?:\x{200D}\x{1F468}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F466}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467}\x{200D}\x{1F466})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F467})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F469}\x{200D}\x{1F466})|[\x{1F469}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F469})|[\x{1F469}\x{1F468}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F468})|[\x{1F469}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F469})|[\x{1F469}\x{1F468}](?:\x{200D}\x{2764}\x{FE0F}\x{200D}\x{1F48B}\x{200D}\x{1F468})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9BD})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9BC})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9AF})|[\x{1F575}\x{1F3CC}\x{26F9}\x{1F3CB}](?:\x{FE0F}\x{200D}\x{2640}\x{FE0F})|[\x{1F575}\x{1F3CC}\x{26F9}\x{1F3CB}](?:\x{FE0F}\x{200D}\x{2642}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F692})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F680})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2708}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3A8})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3A4})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F4BB})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F52C})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F4BC})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3ED})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F527})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F373})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F33E})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2696}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F3EB})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F393})|[\x{1F468}\x{1F469}](?:\x{200D}\x{2695}\x{FE0F})|[\x{1F471}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F9CF}\x{1F647}\x{1F926}\x{1F937}\x{1F46E}\x{1F482}\x{1F477}\x{1F473}\x{1F9B8}\x{1F9B9}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F9DE}\x{1F9DF}\x{1F486}\x{1F487}\x{1F6B6}\x{1F9CD}\x{1F9CE}\x{1F3C3}\x{1F46F}\x{1F9D6}\x{1F9D7}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93C}\x{1F93D}\x{1F93E}\x{1F939}\x{1F9D8}](?:\x{200D}\x{2640}\x{FE0F})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B2})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B3})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B1})|[\x{1F468}\x{1F469}](?:\x{200D}\x{1F9B0})|[\x{1F471}\x{1F64D}\x{1F64E}\x{1F645}\x{1F646}\x{1F481}\x{1F64B}\x{1F9CF}\x{1F647}\x{1F926}\x{1F937}\x{1F46E}\x{1F482}\x{1F477}\x{1F473}\x{1F9B8}\x{1F9B9}\x{1F9D9}\x{1F9DA}\x{1F9DB}\x{1F9DC}\x{1F9DD}\x{1F9DE}\x{1F9DF}\x{1F486}\x{1F487}\x{1F6B6}\x{1F9CD}\x{1F9CE}\x{1F3C3}\x{1F46F}\x{1F9D6}\x{1F9D7}\x{1F3C4}\x{1F6A3}\x{1F3CA}\x{1F6B4}\x{1F6B5}\x{1F938}\x{1F93C}\x{1F93D}\x{1F93E}\x{1F939}\x{1F9D8}](?:\x{200D}\x{2642}\x{FE0F})|[\x{1F441}](?:\x{FE0F}\x{200D}\x{1F5E8}\x{FE0F})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1F9}\x{1F1FA}](?:\x{1F1FF})|[\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F0}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1FA}](?:\x{1F1FE})|[\x{1F1E6}\x{1F1E8}\x{1F1F2}\x{1F1F8}](?:\x{1F1FD})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F7}\x{1F1F9}\x{1F1FF}](?:\x{1F1FC})|[\x{1F1E7}\x{1F1E8}\x{1F1F1}\x{1F1F2}\x{1F1F8}\x{1F1F9}](?:\x{1F1FB})|[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1FB}](?:\x{1F1FA})|[\x{1F1E6}\x{1F1E7}\x{1F1EA}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FE}](?:\x{1F1F9})|[\x{1F1E6}\x{1F1E7}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F7}\x{1F1F8}\x{1F1FA}\x{1F1FC}](?:\x{1F1F8})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EB}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F0}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1F7})|[\x{1F1E6}\x{1F1E7}\x{1F1EC}\x{1F1EE}\x{1F1F2}](?:\x{1F1F6})|[\x{1F1E8}\x{1F1EC}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F3}](?:\x{1F1F5})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1EE}\x{1F1EF}\x{1F1F2}\x{1F1F3}\x{1F1F7}\x{1F1F8}\x{1F1F9}](?:\x{1F1F4})|[\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}](?:\x{1F1F3})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1EC}\x{1F1ED}\x{1F1EE}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F4}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FF}](?:\x{1F1F2})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1EE}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1F1})|[\x{1F1E8}\x{1F1E9}\x{1F1EB}\x{1F1ED}\x{1F1F1}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FD}](?:\x{1F1F0})|[\x{1F1E7}\x{1F1E9}\x{1F1EB}\x{1F1F8}\x{1F1F9}](?:\x{1F1EF})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EB}\x{1F1EC}\x{1F1F0}\x{1F1F1}\x{1F1F3}\x{1F1F8}\x{1F1FB}](?:\x{1F1EE})|[\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F5}\x{1F1F8}\x{1F1F9}](?:\x{1F1ED})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1E9}\x{1F1EA}\x{1F1EC}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}](?:\x{1F1EC})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F9}\x{1F1FC}](?:\x{1F1EB})|[\x{1F1E6}\x{1F1E7}\x{1F1E9}\x{1F1EA}\x{1F1EC}\x{1F1EE}\x{1F1EF}\x{1F1F0}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F7}\x{1F1F8}\x{1F1FB}\x{1F1FE}](?:\x{1F1EA})|[\x{1F1E6}\x{1F1E7}\x{1F1E8}\x{1F1EC}\x{1F1EE}\x{1F1F2}\x{1F1F8}\x{1F1F9}](?:\x{1F1E9})|[\x{1F1E6}\x{1F1E8}\x{1F1EA}\x{1F1EE}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F8}\x{1F1F9}\x{1F1FB}](?:\x{1F1E8})|[\x{1F1E7}\x{1F1EC}\x{1F1F1}\x{1F1F8}](?:\x{1F1E7})|[\x{1F1E7}\x{1F1E8}\x{1F1EA}\x{1F1EC}\x{1F1F1}\x{1F1F2}\x{1F1F3}\x{1F1F5}\x{1F1F6}\x{1F1F8}\x{1F1F9}\x{1F1FA}\x{1F1FB}\x{1F1FF}](?:\x{1F1E6})|[\x{00A9}\x{00AE}\x{203C}\x{2049}\x{2122}\x{2139}\x{2194}-\x{2199}\x{21A9}-\x{21AA}\x{231A}-\x{231B}\x{2328}\x{23CF}\x{23E9}-\x{23F3}\x{23F8}-\x{23FA}\x{24C2}\x{25AA}-\x{25AB}\x{25B6}\x{25C0}\x{25FB}-\x{25FE}\x{2600}-\x{2604}\x{260E}\x{2611}\x{2614}-\x{2615}\x{2618}\x{261D}\x{2620}\x{2622}-\x{2623}\x{2626}\x{262A}\x{262E}-\x{262F}\x{2638}-\x{263A}\x{2640}\x{2642}\x{2648}-\x{2653}\x{265F}-\x{2660}\x{2663}\x{2665}-\x{2666}\x{2668}\x{267B}\x{267E}-\x{267F}\x{2692}-\x{2697}\x{2699}\x{269B}-\x{269C}\x{26A0}-\x{26A1}\x{26AA}-\x{26AB}\x{26B0}-\x{26B1}\x{26BD}-\x{26BE}\x{26C4}-\x{26C5}\x{26C8}\x{26CE}-\x{26CF}\x{26D1}\x{26D3}-\x{26D4}\x{26E9}-\x{26EA}\x{26F0}-\x{26F5}\x{26F7}-\x{26FA}\x{26FD}\x{2702}\x{2705}\x{2708}-\x{270D}\x{270F}\x{2712}\x{2714}\x{2716}\x{271D}\x{2721}\x{2728}\x{2733}-\x{2734}\x{2744}\x{2747}\x{274C}\x{274E}\x{2753}-\x{2755}\x{2757}\x{2763}-\x{2764}\x{2795}-\x{2797}\x{27A1}\x{27B0}\x{27BF}\x{2934}-\x{2935}\x{2B05}-\x{2B07}\x{2B1B}-\x{2B1C}\x{2B50}\x{2B55}\x{3030}\x{303D}\x{3297}\x{3299}\x{1F004}\x{1F0CF}\x{1F170}-\x{1F171}\x{1F17E}-\x{1F17F}\x{1F18E}\x{1F191}-\x{1F19A}\x{1F201}-\x{1F202}\x{1F21A}\x{1F22F}\x{1F232}-\x{1F23A}\x{1F250}-\x{1F251}\x{1F300}-\x{1F321}\x{1F324}-\x{1F393}\x{1F396}-\x{1F397}\x{1F399}-\x{1F39B}\x{1F39E}-\x{1F3F0}\x{1F3F3}-\x{1F3F5}\x{1F3F7}-\x{1F3FA}\x{1F400}-\x{1F4FD}\x{1F4FF}-\x{1F53D}\x{1F549}-\x{1F54E}\x{1F550}-\x{1F567}\x{1F56F}-\x{1F570}\x{1F573}-\x{1F57A}\x{1F587}\x{1F58A}-\x{1F58D}\x{1F590}\x{1F595}-\x{1F596}\x{1F5A4}-\x{1F5A5}\x{1F5A8}\x{1F5B1}-\x{1F5B2}\x{1F5BC}\x{1F5C2}-\x{1F5C4}\x{1F5D1}-\x{1F5D3}\x{1F5DC}-\x{1F5DE}\x{1F5E1}\x{1F5E3}\x{1F5E8}\x{1F5EF}\x{1F5F3}\x{1F5FA}-\x{1F64F}\x{1F680}-\x{1F6C5}\x{1F6CB}-\x{1F6D2}\x{1F6D5}\x{1F6E0}-\x{1F6E5}\x{1F6E9}\x{1F6EB}-\x{1F6EC}\x{1F6F0}\x{1F6F3}-\x{1F6FA}\x{1F7E0}-\x{1F7EB}\x{1F90D}-\x{1F93A}\x{1F93C}-\x{1F945}\x{1F947}-\x{1F971}\x{1F973}-\x{1F976}\x{1F97A}-\x{1F9A2}\x{1F9A5}-\x{1F9AA}\x{1F9AE}-\x{1F9CA}\x{1F9CD}-\x{1F9FF}\x{1FA70}-\x{1FA73}\x{1FA78}-\x{1FA7A}\x{1FA80}-\x{1FA82}\x{1FA90}-\x{1FA95}]/u', '', $post_title);
        
        $post_title = preg_replace('/\.(?=.*\.)/', '', $post_title);
        $post_title = preg_replace('/-+/', '-', $post_title);
        $post_title = str_replace('-.', '.', $post_title);
        $post_title = strtolower( $post_title );
        if($post_title == '')
        {
            $post_title = uniqid();
        }
        if(isset($file_parts['extension']))
        {
            switch($file_parts['extension'])
            {
                case "":
                $filename = sanitize_title($post_title) . '.jpg';
                break;
                case NULL:
                $filename = sanitize_title($post_title) . '.jpg';
                break;
                default:
                $filename = sanitize_title($post_title) . '.' . $file_parts['extension'];
                break;
            }
        }
        else
        {
            $filename = sanitize_title($post_title) . '.jpg';
        }
    }
    else
    {
        if(isset($file_parts['extension']))
        {
            switch($file_parts['extension'])
            {
                case "":
                if(!newsomatic_endsWith($filename, '.jpg'))
                    $filename .= '.jpg';
                break;
                case NULL:
                if(!newsomatic_endsWith($filename, '.jpg'))
                    $filename .= '.jpg';
                break;
                default:
                if(!newsomatic_endsWith($filename, '.' . $file_parts['extension']))
                    $filename .= '.' . $file_parts['extension'];
                break;
            }
        }
        else
        {
            if(!newsomatic_endsWith($filename, '.jpg'))
                $filename .= '.jpg';
        }
    }
    $filename = sanitize_file_name($filename);
    if(strlen($filename) > 100)
    {
        $filename = substr($filename, 0, 90) . uniqid();
    }
    if(newsomatic_endsWith($filename, '.'))
    {
        $filename .= 'jpg';
    }
    if(!strstr($filename, '.'))
    {
        $filename .= '.jpg';
    }
    if (wp_mkdir_p($upload_dir['path']))
        $file = $upload_dir['path'] . '/' . $post_id . '-' . $filename;
    else
        $file = $upload_dir['basedir'] . '/' . $post_id . '-' . $filename;
    if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ){
        include_once(ABSPATH . 'wp-admin/includes/file.php');$creds = request_filesystem_credentials( site_url() );
        wp_filesystem($creds);
    }
    $ret = $wp_filesystem->put_contents($file, $image_data);
    if ($ret === FALSE) {
        return false;
    }
    $wp_filetype = wp_check_filetype($filename, null);
    if($wp_filetype['type'] == '')
    {
        $wp_filetype['type'] = 'image/png';
    }
    $attachment  = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => sanitize_file_name($filename),
        'post_content' => '',
        'post_status' => 'inherit'
    );
    
    if ((isset($newsomatic_Main_Settings['resize_height']) && $newsomatic_Main_Settings['resize_height'] !== '') || (isset($newsomatic_Main_Settings['resize_width']) && $newsomatic_Main_Settings['resize_width'] !== ''))
    {
        try
        {
            if(!class_exists('\Eventviva\ImageResize')){require_once (dirname(__FILE__) . "/res/ImageResize/ImageResize.php");}
            $imageRes = new ImageResize($file);
            $imageRes->quality_jpg = 100;
            if ((isset($newsomatic_Main_Settings['resize_height']) && $newsomatic_Main_Settings['resize_height'] !== '') && (isset($newsomatic_Main_Settings['resize_width']) && $newsomatic_Main_Settings['resize_width'] !== ''))
            {
                $imageRes->resizeToBestFit($newsomatic_Main_Settings['resize_width'], $newsomatic_Main_Settings['resize_height'], true);
            }
            elseif (isset($newsomatic_Main_Settings['resize_width']) && $newsomatic_Main_Settings['resize_width'] !== '')
            {
                $imageRes->resizeToWidth($newsomatic_Main_Settings['resize_width'], true);
            }
            elseif (isset($newsomatic_Main_Settings['resize_height']) && $newsomatic_Main_Settings['resize_height'] !== '')
            {
                $imageRes->resizeToHeight($newsomatic_Main_Settings['resize_height'], true);
            }
            $imageRes->save($file);
        }
        catch(Exception $e)
        {
            newsomatic_log_to_file('Failed to resize featured image: ' . $image_url . ' to sizes ' . $newsomatic_Main_Settings['resize_width'] . ' - ' . $newsomatic_Main_Settings['resize_height'] . '. Exception thrown ' . esc_html($e->getMessage()) . '!');
        }
    }
    $attach_id   = wp_insert_attachment($attachment, $file, $post_id);
    if ($attach_id === 0) {
        return false;
    }
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $file);
    wp_update_attachment_metadata($attach_id, $attach_data);
    $res2 = set_post_thumbnail($post_id, $attach_id);
    if ($res2 === FALSE) {
        return false;
    }
    $post_title = get_the_title($post_id);
    if($post_title != '')
    {
        update_post_meta($attach_id, '_wp_attachment_image_alt', $post_title);
    }
    return true;
}
function newsomatic_insert_attachment_by($value) {
    global $wpdb;
    $wpdb->get_results("
        INSERT INTO " . $wpdb->prefix . "posts" . " (post_author, guid, post_title, post_mime_type, post_type, post_status, post_parent, post_date, post_date_gmt, post_modified, post_modified_gmt, post_content, post_excerpt, to_ping, pinged, post_content_filtered) 
        VALUES " . $value);
    return $wpdb->insert_id;
}
function newsomatic_get_formatted_value($url, $alt, $post_parent) {
    return "(77777, '" . $url . "', '" . str_replace("'", "", $alt) . "', 'image/jpeg', 'attachment', 'inherit', '" . $post_parent . "', now(), now(), now(), now(), '', '', '', '', '')";
}
function newsomatic_endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }

    return (substr($haystack, -$length) === $needle);
}

function newsomatic_copy_image($image_url, $custom_cookies = '')
{
    $upload_dir = wp_upload_dir();
    global $wp_filesystem;
    if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ){
        include_once(ABSPATH . 'wp-admin/includes/file.php');$creds = request_filesystem_credentials( site_url() );
        wp_filesystem($creds);
    }
    $image_data = $wp_filesystem->get_contents($image_url);
    if ($image_data === FALSE || empty($image_data)) {
        $image_data = newsomatic_get_web_page($image_url, $custom_cookies);
        if ($image_data === FALSE || strpos($image_data, '<Message>Access Denied</Message>') !== FALSE || strpos($image_data, '<title>Just a moment...</title>') !== FALSE || strpos($image_data, '<html') !== FALSE) {
            return false;
        }
    }
    $filename = basename($image_url);
    $temp     = explode("?", $filename);
    $filename = $temp[0];
    $filename = str_replace('%', '-', $filename);
    $filename = str_replace('#', '-', $filename);
    $filename = str_replace('&', '-', $filename);
    $filename = str_replace('{', '-', $filename);
    $filename = str_replace('}', '-', $filename);
    $filename = str_replace('\\', '-', $filename);
    $filename = str_replace('<', '-', $filename);
    $filename = str_replace('>', '-', $filename);
    $filename = str_replace('*', '-', $filename);
    $filename = str_replace('/', '-', $filename);
    $filename = str_replace('$', '-', $filename);
    $filename = str_replace('\'', '-', $filename);
    $filename = str_replace('"', '-', $filename);
    $filename = str_replace(':', '-', $filename);
    $filename = str_replace('@', '-', $filename);
    $filename = str_replace('+', '-', $filename);
    $filename = str_replace('|', '-', $filename);
    $filename = str_replace('=', '-', $filename);
    $filename = str_replace('`', '-', $filename);
    if (wp_mkdir_p($upload_dir['path'] . '/localimages'))
    {
        $file = $upload_dir['path'] . '/localimages/' . $filename;
        $retval = $upload_dir['url'] . '/localimages/' . $filename;
    }
    else
    {
        $file = $upload_dir['basedir'] . '/localimages/' . $filename;
        $retval = $upload_dir['baseurl'] . '/localimages/' . $filename;
    }
    if ( ! is_a( $wp_filesystem, 'WP_Filesystem_Base') ){
        include_once(ABSPATH . 'wp-admin/includes/file.php');$creds = request_filesystem_credentials( site_url() );
        wp_filesystem($creds);
    }
    $ret = $wp_filesystem->put_contents($file, $image_data);
    if ($ret === FALSE) {
        return false;
    }
    return $retval;
}

function newsomatic_repairHTML($text)
{
    $text = htmlspecialchars_decode($text);
    $text = str_replace("< ", "<", $text);
    $text = str_replace(" >", ">", $text);
    $text = str_replace("= ", "=", $text);
    $text = str_replace(" =", "=", $text);
    $text = str_replace("\/ ", "\/", $text);
    $text = str_replace("</ iframe>", "</iframe>", $text);
    $text = str_replace("frameborder ", "frameborder=\"0\" allowfullscreen></iframe>", $text);
    $doc = new DOMDocument();
    $doc->substituteEntities = false;
    $internalErrors = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8" ?>' . $text);
    $text = $doc->saveHTML();
                    libxml_use_internal_errors($internalErrors);
	$text = preg_replace('#<!DOCTYPE html PUBLIC "-\/\/W3C\/\/DTD HTML 4\.0 Transitional\/\/EN" "http:\/\/www\.w3\.org\/TR\/REC-html40\/loose\.dtd">(?:[^<]*)<\?xml encoding="utf-8" \?><html><body>(?:<p>)?#i', '', $text);
	$text = str_replace('</p></body></html>', '', $text);
    $text = str_replace('</body></html></p>', '', $text);
    $text = str_replace('</body></html>', '', $text);
    return $text;
}
function newsomatic_hour_diff($date1, $date2)
{
    $date1 = new DateTime($date1, newsomatic_get_blog_timezone());
    $date2 = new DateTime($date2, newsomatic_get_blog_timezone());
    
    $number1 = (int) $date1->format('U');
    $number2 = (int) $date2->format('U');
    return ($number1 - $number2) / 60;
}

function newsomatic_add_hour($date, $hour)
{
    $date1 = new DateTime($date, newsomatic_get_blog_timezone());
    $date1->modify("$hour hours");
    $date1 = (array)$date1;
    foreach ($date1 as $key => $value) {
        if ($key == 'date') {
            return $value;
        }
    }
    return $date;
}
function newsomatic_get_blog_timezone() {

    $tzstring = get_option( 'timezone_string' );
    $offset   = get_option( 'gmt_offset' );

    if( empty( $tzstring ) && 0 != $offset && floor( $offset ) == $offset ){
        $offset_st = $offset > 0 ? "-$offset" : '+'.absint( $offset );
        $tzstring  = 'Etc/GMT'.$offset_st;
    }
    if( empty( $tzstring ) ){
        $tzstring = 'UTC';
    }
    $timezone = new DateTimeZone( $tzstring );
    return $timezone; 
}
function newsomatic_minute_diff($date1, $date2)
{
    $date1 = new DateTime($date1, newsomatic_get_blog_timezone());
    $date2 = new DateTime($date2, newsomatic_get_blog_timezone());
    
    $number1 = (int) $date1->format('U');
    $number2 = (int) $date2->format('U');
    return ($number1 - $number2);
}

function newsomatic_add_minute($date, $minute)
{
    $date1 = new DateTime($date, newsomatic_get_blog_timezone());
    $date1->modify("$minute minutes");
    $date1 = (array)$date1;
    foreach ($date1 as $key => $value) {
        if ($key == 'date') {
            return $value;
        }
    }
    return $date;
}

function newsomatic_wp_custom_css_files($src, $cont)
{
    wp_enqueue_style('newsomatic-thumbnail-css-' . $cont, $src, __FILE__, NEWSOMATIC_MAJOR_VERSION);
}

function newsomatic_get_date_now($param = 'now')
{
    $date = new DateTime($param, newsomatic_get_blog_timezone());
    $date = (array)$date;
    foreach ($date as $key => $value) {
        if ($key == 'date') {
            return $value;
        }
    }
    return '';
}

function newsomatic_create_terms($taxonomy, $parent, $terms_str)
{
    $terms          = explode('/', $terms_str);
    $categories     = array();
    $parent_term_id = $parent;
    foreach ($terms as $term) {
        $res = term_exists($term, $taxonomy, $parent);
        if ($res != NULL && $res != 0 && count($res) > 0 && isset($res['term_id'])) {
            $parent_term_id = $res['term_id'];
            $categories[]   = $parent_term_id;
        } else {
            if($parent === null)
            {
                $insert_parent = 0;
            }
            else
            {
                $insert_parent = $parent;
            }
            $new_term = wp_insert_term($term, $taxonomy, array(
                'parent' => $insert_parent
            ));
            if (!is_wp_error( $new_term ) && $new_term != NULL && $new_term != 0 && count($new_term) > 0 && isset($new_term['term_id'])) {
                $parent_term_id = $new_term['term_id'];
                $categories[]   = $parent_term_id;
            }
        }
    }
    
    return $categories;
}
function newsomatic_getExcerpt($the_content)
{
    $preview = newsomatic_strip_html_tags($the_content);
    $preview = wp_trim_words($preview, 55, '');
    $preview = trim($preview, ',');
    return $preview;
}

function newsomatic_getPlainContent($the_content)
{
    $preview = newsomatic_strip_html_tags($the_content);
    $preview = wp_trim_words($preview, 999999);
    return $preview;
}
function newsomatic_getItemImage($img)
{
    if($img == '')
    {
        return '';
    }
    $preview = '<img src="' . esc_url_raw($img) . '" alt="image" />';
    return $preview;
}

function newsomatic_getReadMoreButton($url, $text, $newsomatic_Main_Settings)
{
    $link = '';
    if (isset($url)) {
        $link = '<a href="' . $url . '" class="button purchase" rel="nofollow noopener"';
        if (!isset($newsomatic_Main_Settings['no_new_tab']) || $newsomatic_Main_Settings['no_new_tab'] != 'on') 
        {
            $link .= ' target="_blank"';
        }
        $link .= '>' . esc_html($text) . '</a>';
    }
    return $link;
}
add_action('init', 'newsomatic_create_taxonomy', 0);

add_action( 'enqueue_block_editor_assets', 'newsomatic_enqueue_block_editor_assets' );
function newsomatic_enqueue_block_editor_assets() {
    wp_register_style('newsomatic-browser-style', plugins_url('styles/newsomatic-browser.css', __FILE__), false, NEWSOMATIC_MAJOR_VERSION);
    wp_enqueue_style('newsomatic-browser-style');
	$block_js_display   = 'scripts/display-posts.js';
	wp_enqueue_script(
		'newsomatic-display-block-js', 
        plugins_url( $block_js_display, __FILE__ ), 
        array(
			'wp-blocks',
			'wp-i18n',
			'wp-element',
		),
        NEWSOMATIC_MAJOR_VERSION
	);
    $block_js_list   = 'scripts/list-posts.js';
	wp_enqueue_script(
		'newsomatic-list-block-js', 
        plugins_url( $block_js_list, __FILE__ ), 
        array(
			'wp-blocks',
			'wp-i18n',
			'wp-element',
		),
        NEWSOMATIC_MAJOR_VERSION
	);
    $news_js_list   = 'scripts/news-aggregator.js';
	wp_enqueue_script(
		'newsomatic-news-aggregator-js', 
        plugins_url( $news_js_list, __FILE__ ), 
        array(
			'wp-blocks',
			'wp-i18n',
			'wp-element',
		),
        NEWSOMATIC_MAJOR_VERSION
	);
}
function newsomatic_create_taxonomy()
{
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    if (isset($newsomatic_Main_Settings['newsomatic_enabled']) && $newsomatic_Main_Settings['newsomatic_enabled'] === 'on') {
        if (isset($newsomatic_Main_Settings['no_local_image']) && $newsomatic_Main_Settings['no_local_image'] == 'on' && (!isset($newsomatic_Main_Settings['url_image']) || $newsomatic_Main_Settings['url_image'] != 'on')) {
            add_filter('wp_get_attachment_url', 'newsomatic_replace_attachment_url', 10, 2);
            add_filter('wp_get_attachment_image_src', 'newsomatic_replace_attachment_image_src', 10, 3);
            add_filter('post_thumbnail_html', 'newsomatic_thumbnail_external_replace', 10, 6);
        }
    }
    if ( function_exists( 'register_block_type' ) ) {
        register_block_type( 'newsomatic-news-post-generator/newsomatic-display', array(
            'render_callback' => 'newsomatic_display_posts_shortcode',
        ) );
        register_block_type( 'newsomatic-news-post-generator/newsomatic-list', array(
            'render_callback' => 'newsomatic_list_posts',
        ) );
        register_block_type( 'newsomatic-news-post-generator/newsomatic-aggregator', array(
            'render_callback' => 'newsomatic_load_shortcode_view',
        ) );
    }
    if (isset($newsomatic_Main_Settings['newsomatic_enabled']) && $newsomatic_Main_Settings['newsomatic_enabled'] == 'on') {
        if (isset($newsomatic_Main_Settings['rel_canonical']) && $newsomatic_Main_Settings['rel_canonical'] == 'on') {
            remove_action( 'wp_head', 'rel_canonical' );
            add_action( 'wp_head', 'newsomatic_rel_canonical' );
            add_filter( 'wpseo_canonical', '__return_false' );
            add_filter( 'rank_math/frontend/canonical', '__return_false' );
        }
        if (isset($newsomatic_Main_Settings['meta_noindex']) && $newsomatic_Main_Settings['meta_noindex'] == 'on') {
            add_action( 'wp_head', 'newsomatic_noindex' );
        }
    }
    if(!taxonomy_exists('coderevolution_post_source'))
    {
        $labels = array(
            'name' => _x('Post Source', 'taxonomy general name', 'newsomatic-news-post-generator'),
            'singular_name' => _x('Post Source', 'taxonomy singular name', 'newsomatic-news-post-generator'),
            'search_items' => esc_html__('Search Post Source', 'newsomatic-news-post-generator'),
            'popular_items' => esc_html__('Popular Post Source', 'newsomatic-news-post-generator'),
            'all_items' => esc_html__('All Post Sources', 'newsomatic-news-post-generator'),
            'parent_item' => null,
            'parent_item_colon' => null,
            'edit_item' => esc_html__('Edit Post Source', 'newsomatic-news-post-generator'),
            'update_item' => esc_html__('Update Post Source', 'newsomatic-news-post-generator'),
            'add_new_item' => esc_html__('Add New Post Source', 'newsomatic-news-post-generator'),
            'new_item_name' => esc_html__('New Post Source Name', 'newsomatic-news-post-generator'),
            'separate_items_with_commas' => esc_html__('Separate Post Source with commas', 'newsomatic-news-post-generator'),
            'add_or_remove_items' => esc_html__('Add or remove Post Source', 'newsomatic-news-post-generator'),
            'choose_from_most_used' => esc_html__('Choose from the most used Post Source', 'newsomatic-news-post-generator'),
            'not_found' => esc_html__('No Post Sources found.', 'newsomatic-news-post-generator'),
            'menu_name' => esc_html__('Post Source', 'newsomatic-news-post-generator')
        );
        
        $args = array(
            'hierarchical' => false,
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'description' => 'Post Source',
            'labels' => $labels,
            'show_admin_column' => true,
            'update_count_callback' => '_update_post_term_count',
            'rewrite' => false
        );
        
        $add_post_type = array(
            'post',
            'page'
        );
        $xargs = array(
            'public'   => true,
            '_builtin' => false
        );
        $output = 'names'; 
        $operator = 'and';
        $post_types = get_post_types( $xargs, $output, $operator );
        if ( $post_types ) 
        {
            foreach ( $post_types  as $post_type ) {
                $add_post_type[] = $post_type;
            }
        }
        register_taxonomy('coderevolution_post_source', $add_post_type, $args);
        add_action('pre_get_posts', function($qry) {
            if (is_admin()) return;
            if (is_tax('coderevolution_post_source')){
                $qry->set_404();
            }
        });
    }
}

register_activation_hook(__FILE__, 'newsomatic_activation_callback');
function newsomatic_activation_callback($defaults = FALSE)
{
    if (!get_option('newsomatic_Main_Settings') || $defaults === TRUE) {
        $newsomatic_Main_Settings = array(
            'newsomatic_enabled' => 'on',
            'enable_metabox' => 'on',
            'newsomatic_app_id' => '',
			'newsapi_active' => '',
            'disable_dmca' => '',
            'no_new_tab' => '',
            'phantom_path' => '',
            'phantom_timeout' => '',
            'skip_no_img' => '',
            'skip_old' => '',
            'skip_year' => '',
            'skip_month' => '',
            'skip_day' => '',
            'date_format' => '',
            'translate' => 'disabled',
            'translate_source' => 'disabled',
            'second_translate' => 'disabled',
            'litte_translate' => '',
            'no_title_spin' => '',
            'exclude_words_title' => '',
            'custom_html2' => '',
            'read_more' => '',
            'deepl_auth' => '',
            'deppl_free' => '',
            'bing_auth' => '',
            'textrazor_key' => '',
            'bing_region' => '',
            'custom_html' => '',
            'strip_by_id' => '',
            'strip_by_class' => '',
            'user_agent' => '',
            'google_trans_auth' => '',
            'sentence_list' => 'This is one %adjective %noun %sentence_ending
This is another %adjective %noun %sentence_ending
I %love_it %nouns , because they are %adjective %sentence_ending
My %family says this plugin is %adjective %sentence_ending
These %nouns are %adjective %sentence_ending',
            'sentence_list2' => 'Meet this %adjective %noun %sentence_ending
This is the %adjective %noun ever %sentence_ending
I %love_it %nouns , because they are the %adjective %sentence_ending
My %family says this plugin is very %adjective %sentence_ending
These %nouns are quite %adjective %sentence_ending',
            'variable_list' => 'adjective_very => %adjective;very %adjective;

adjective => clever;interesting;smart;huge;astonishing;unbelievable;nice;adorable;beautiful;elegant;fancy;glamorous;magnificent;helpful;awesome

noun_with_adjective => %noun;%adjective %noun

noun => plugin;WordPress plugin;item;ingredient;component;constituent;module;add-on;plug-in;addon;extension

nouns => plugins;WordPress plugins;items;ingredients;components;constituents;modules;add-ons;plug-ins;addons;extensions

love_it => love;adore;like;be mad for;be wild about;be nuts about;be crazy about

family => %adjective %family_members;%family_members

family_members => grandpa;brother;sister;mom;dad;grandma

sentence_ending => .;!;!!',
            'auto_clear_logs' => 'No',
            'enable_logging' => 'on',
            'run_after' => '',
            'run_before' => '',
            'enable_detailed_logging' => '',
            'rule_timeout' => '3600',
            'strip_links' => '',
            'strip_internal_content_links' => '',
            'fix_html' => '',
            'new_category' => '',
            'apiKey' => '',
            'links_hide' => '',
            'skip_no_class' => 'on',
            'no_import_full' => '',
            'no_import_no_class' => '',
            'strip_scripts' => '',
            'email_address' => '',
            'rel_canonical' => '',
            'shortest_api' => '',
            'link_source' => '',
            'send_email' => '',
            'request_delay' => '',
            'best_password' => '',
            'best_user' => '',
            'wordai_uniqueness' => '1',
            'exclude_words' => '',
            'spin_text' => 'disabled',
            'tldr_max' => '',
            'tldr_min' => '',
            'required_words' => '',
            'banned_words' => '',
            'max_word_content' => '',
            'min_word_content' => '',
            'max_word_title' => '',
            'min_word_title' => '',
            'strip_featured_image' => '',
            'resize_width' => '',
            'resize_height' => '',
            'copy_images' => '',
            'no_local_image' => '',
            'old_text' => '',
            'url_image' => '',
            'do_not_check_duplicates' => '',
            'title_duplicates' => '',
            'no_check' => '',
            'draft_first' => '',
            'require_all' => '',
            'restriction_before' => '',
            'max_query' => '',
            'custom_ciphers' => '',
            'secret_word' => '',
            'no_link_translate' => '',
            'skip_failed_tr' => '',
            'proxy_url' => '',
            'proxy_auth' => '',
            'disable_backend_content' => '',
            'headlessbrowserapi_key' => '',
            'flickr_order' => 'date-posted-desc',
            'flickr_license' => '-1',
            'flickr_api' => '',
            'scrapeimg_height' => '',
            'attr_text' => 'Photo Credit: <a href="%%image_source_url%%" target="_blank">%%image_source_name%%</a>',
            'scrapeimg_width' => '',
            'scrapeimg_cat' => 'all',
            'scrapeimg_order' => 'any',
            'scrapeimg_orientation' => 'all',
            'imgtype' => 'all',
            'pixabay_api' => '',
            'pexels_api' => '',
            'unsplash_key' => '',
            'google_images' => '',
            'morguefile_secret' => '',
            'morguefile_api' => '',
            'bimage' => 'on',
            'no_orig' => '',
            'img_order' => 'popular',
            'img_cat' => 'all',
            'img_width' => '',
            'img_mwidth' => '',
            'img_ss' => '',
            'img_editor' => '',
            'img_language' => 'any',
            'pixabay_scrape' => '',
            'scrapeimgtype' => 'all'
        );
        if ($defaults === FALSE) {
            add_option('newsomatic_Main_Settings', $newsomatic_Main_Settings, '', false);
        } else {
            update_option('newsomatic_Main_Settings', $newsomatic_Main_Settings, false);
        }
    }
}
add_action('wp_loaded', 'newsomatic_run_cron', 0);
function newsomatic_run_cron()
{
    if(isset($_GET['run_newsomatic']))
    {
        $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
        if(isset($newsomatic_Main_Settings['secret_word']) && $_GET['run_newsomatic'] == urlencode($newsomatic_Main_Settings['secret_word']))
        {
            newsomatic_cron();
            die();
        }
    }
}

function newsomatic_testPhantom()
{
    if(!function_exists('shell' . '_exec')) {
        return 0;
    }
    $disabled = explode(',', ini_get('disable_functions'));
    if(in_array('shell' . '_exec', $disabled))
    {
        return 0;
    }
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    if (isset($newsomatic_Main_Settings['phantom_path']) && $newsomatic_Main_Settings['phantom_path'] != '') 
    {
        $phantomjs_comm = $newsomatic_Main_Settings['phantom_path'] . ' ';
    }
    else
    {
        $phantomjs_comm = 'phantomjs ';
    }
    $shefunc = trim(' s ') . trim(' h ') . 'ell' . '_exec';
    $cmdResult = $shefunc($phantomjs_comm . '-h 2>&1');
    if(stristr($cmdResult, 'Usage') !== false)
    {
        return 1;
    }
    return 0;
}

function newsomatic_get_page_Puppeteer($url, $custom_cookies, $custom_user_agent, $use_proxy, $user_pass)
{
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    if(!function_exists('shell' . '_exec')) {
        if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
            newsomatic_log_to_file('shel' . 'l_exec not found!');
        }
        return false;
    }
    $disabled = explode(',', ini_get('disable_functions'));
    if(in_array('shell' . '_exec', $disabled))
    {
        if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
            newsomatic_log_to_file('shel' . 'l_exec disabled');
        }
        return false;
    }
    if($custom_user_agent == '')
    {
        $custom_user_agent = 'default';
    }
    if($custom_cookies == '')
    {
        $custom_cookies = 'default';
    }
    if($user_pass == '')
    {
        $user_pass = 'default';
    }
    $delay = '';
    if (isset($newsomatic_Main_Settings['request_delay']) && $newsomatic_Main_Settings['request_delay'] != '') 
    {
        if(stristr($newsomatic_Main_Settings['request_delay'], ',') !== false)
        {
            $tempo = explode(',', $newsomatic_Main_Settings['request_delay']);
            if(isset($tempo[1]) && is_numeric(trim($tempo[1])) && is_numeric(trim($tempo[0])))
            {
                $delay = rand(trim($tempo[0]), trim($tempo[1]));
            }
        }
        else
        {
            if(is_numeric(trim($newsomatic_Main_Settings['request_delay'])))
            {
                $delay = intval(trim($newsomatic_Main_Settings['request_delay']));
            }
        }
    }
    if($delay != '' && is_numeric($delay))
    {
        $GLOBALS['wp_object_cache']->delete('newsomatic_last_time', 'options');
        $last_time = get_option('newsomatic_last_time', false);
        if($last_time !== false && intval(((intval($last_time) - time()) * 1000 + $delay ) * 1000) > 0)
        {
            $sleep_time = intval(((intval($last_time) - time()) * 1000 + $delay ) * 1000);
            if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) 
            {
                newsomatic_log_to_file('Delay between requests set, waiting ' . ($sleep_time/1000) . ' ms');
            }
            if($sleep_time < 21600000)
            {
                usleep($sleep_time);
            }
        }
    }
    $phantomjs_proxcomm = '"null"';
    if ($use_proxy == '1' && isset($newsomatic_Main_Settings['proxy_url']) && $newsomatic_Main_Settings['proxy_url'] != '' && $newsomatic_Main_Settings['proxy_url'] != 'disable' && $newsomatic_Main_Settings['proxy_url'] != 'disabled') 
    {
        $prx = explode(',', $newsomatic_Main_Settings['proxy_url']);
        $randomness = array_rand($prx);
        $phantomjs_proxcomm = '"' . trim($prx[$randomness]);
        if (isset($newsomatic_Main_Settings['proxy_auth']) && $newsomatic_Main_Settings['proxy_auth'] != '') 
        {
            $prx_auth = explode(',', $newsomatic_Main_Settings['proxy_auth']);
            if(isset($prx_auth[$randomness]) && trim($prx_auth[$randomness]) != '')
            {
                $phantomjs_proxcomm .= '~~~' . trim($prx_auth[$randomness]);
            }
        }
        $phantomjs_proxcomm .= '"';
    }
    
    $puppeteer_comm = 'node ';
    $puppeteer_comm .= '"' . dirname(__FILE__) . '/res/puppeteer/puppeteer.js" "' . $url . '" ' . $phantomjs_proxcomm . '  "' . $custom_user_agent . '" "' . $custom_cookies . '" "' . $user_pass . '"';
    $puppeteer_comm .= ' 2>&1';
    if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
        newsomatic_log_to_file('Puppeteer command: ' . $puppeteer_comm);
    }
    $shefunc = trim(' s ') . trim(' h ') . 'ell' . '_exec';
    $cmdResult = $shefunc($puppeteer_comm);
    if($delay != '' && is_numeric($delay))
    {
        update_option('newsomatic_last_time', time());
    }
    if($cmdResult === NULL || $cmdResult == '')
    {
        newsomatic_log_to_file('puppeteer did not return usable info for: ' . $url);
        return false;
    }
    if(trim($cmdResult) === 'timeout')
    {
        newsomatic_log_to_file('puppeteer timed out while getting page: ' . $url. ' - please increase timeout in Main Settings');
        return false;
    }
    if(stristr($cmdResult, 'sh: node: command not found') !== false || stristr($cmdResult, 'throw err;') !== false)
    {
        newsomatic_log_to_file('nodeJS not found, please install it on your server');
        return false;
    }
    if(stristr($cmdResult, 'sh: puppeteer: command not found') !== false)
    {
        newsomatic_log_to_file('puppeteer not found, please install it on your server');
        return false;
    }
    if(stristr($cmdResult, 'res/puppeteer/puppeteer.js:') !== false)
    {
        newsomatic_log_to_file('puppeteer failed to run, error: ' . $cmdResult);
        return false;
    }
    return $cmdResult;
}
function newsomatic_get_page_PhantomJS($url, $custom_cookies, $custom_user_agent, $use_proxy)
{
    if(!function_exists('shell' . '_exec')) {
        return false;
    }
    if(empty($url))
    {
        return false;
    }
    $disabled = explode(',', ini_get('disable_functions'));
    if(in_array('shell' . '_exec', $disabled))
    {
        return false;
    }
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    $delay = '';
    if (isset($newsomatic_Main_Settings['request_delay']) && $newsomatic_Main_Settings['request_delay'] != '') 
    {
        if(stristr($newsomatic_Main_Settings['request_delay'], ',') !== false)
        {
            $tempo = explode(',', $newsomatic_Main_Settings['request_delay']);
            if(isset($tempo[1]) && is_numeric(trim($tempo[1])) && is_numeric(trim($tempo[0])))
            {
                $delay = rand(trim($tempo[0]), trim($tempo[1]));
            }
        }
        else
        {
            if(is_numeric(trim($newsomatic_Main_Settings['request_delay'])))
            {
                $delay = intval(trim($newsomatic_Main_Settings['request_delay']));
            }
        }
    }
    if($delay != '' && is_numeric($delay))
    {
        $GLOBALS['wp_object_cache']->delete('newsomatic_last_time', 'options');
        $last_time = get_option('newsomatic_last_time', false);
        if($last_time !== false && intval(((intval($last_time) - time()) * 1000 + $delay ) * 1000) > 0)
        {
            $sleep_time = intval(((intval($last_time) - time()) * 1000 + $delay ) * 1000);
            if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) 
            {
                newsomatic_log_to_file('Delay between requests set, waiting ' . ($sleep_time/1000) . ' ms');
            }
            if($sleep_time < 21600000)
            {
                usleep($sleep_time);
            }
        }
    }
    if (isset($newsomatic_Main_Settings['phantom_path']) && $newsomatic_Main_Settings['phantom_path'] != '') 
    {
        $phantomjs_comm = $newsomatic_Main_Settings['phantom_path'];
    }
    else
    {
        $phantomjs_comm = 'phantomjs';
    }
    if (isset($newsomatic_Main_Settings['phantom_timeout']) && $newsomatic_Main_Settings['phantom_timeout'] != '')
    {
        $phantomjs_timeout = ((int)$newsomatic_Main_Settings['phantom_timeout']);
    }
    else
    {
        $phantomjs_timeout = '15000';
    }
    if($custom_user_agent == '')
    {
        $custom_user_agent = 'default';
    }
    if($custom_cookies == '')
    {
        $custom_cookies = 'default';
    }
    if($use_proxy && isset($newsomatic_Main_Settings['proxy_url']) && $newsomatic_Main_Settings['proxy_url'] != '' && $newsomatic_Main_Settings['proxy_url'] != 'disable' && $newsomatic_Main_Settings['proxy_url'] != 'disabled') 
    {
        $prx = explode(',', $newsomatic_Main_Settings['proxy_url']);
        $randomness = array_rand($prx);
        $phantomjs_comm .= ' --proxy=' . trim($prx[$randomness]) . ' ';
        if (isset($newsomatic_Main_Settings['proxy_auth']) && $newsomatic_Main_Settings['proxy_auth'] != '') 
        {
            $prx_auth = explode(',', $newsomatic_Main_Settings['proxy_auth']);
            if(isset($prx_auth[$randomness]) && trim($prx_auth[$randomness]) != '')
            {
                $phantomjs_comm .= '--proxy-auth=' . trim($prx_auth[$randomness]) . ' ';
            }
        }
    }
    $phantomjs_comm .= ' --ignore-ssl-errors=true ';
    $phantomjs_comm .= '"' . dirname(__FILE__) . '/res/phantomjs/phantom.js" "' . $url . '" "' . $phantomjs_timeout . '" "' . $custom_user_agent . '" "' . $custom_cookies . '"';
    $phantomjs_comm .= ' 2>&1';
    $shefunc = trim(' s ') . trim(' h ') . 'ell' . '_exec';
    $cmdResult = $shefunc($phantomjs_comm);
    if($delay != '' && is_numeric($delay))
    {
        update_option('newsomatic_last_time', time());
    }
    if($cmdResult === NULL || $cmdResult == '')
    {
        return false;
    }
    if(trim($cmdResult) === 'timeout')
    {
        newsomatic_log_to_file('phantomjs timed out while getting page: ' . $url. ' - please increase timeout in Main Settings');
        return false;
    }
    if(stristr($cmdResult, 'sh: phantomjs: command not found') !== false)
    {
        newsomatic_log_to_file('phantomjs not found, please install it on your server');
        return false;
    }
    return $cmdResult;
}

function newsomatic_summarize_content($title, $content)
{
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    if (!isset($newsomatic_Main_Settings['best_password']) || trim($newsomatic_Main_Settings['best_password']) == '') {
        newsomatic_log_to_file('Please insert a valid "TLDR" API key.');
        return FALSE;
    }
    $curl = curl_init();
    if($curl === false)
    {
        return false;
    }
    $content = strip_tags($content, '<br>');
    $content = preg_replace('#<br\s*/?>#i', "\n", $content);
    $fdata = "";
    $xdata = array();
    if (!isset($newsomatic_Main_Settings['tldr_max']) || trim($newsomatic_Main_Settings['tldr_max']) == '') 
    {
        $tmax = 300;
    }
    else
    {
        $tmax = intval($newsomatic_Main_Settings['tldr_max']);
    }
    if (!isset($newsomatic_Main_Settings['tldr_min']) || trim($newsomatic_Main_Settings['tldr_min']) == '') 
    {
        $tmin = 100;
    }
    else
    {
        $tmin = intval($newsomatic_Main_Settings['tldr_min']);
    }
    
    $xdata['min_length'] = $tmin;
    $xdata['max_length'] = $tmax;
    $xdata['text'] = str_replace('"', '\'', $content);
    $fdata = json_encode($xdata);
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://tldrthis.p.rapidapi.com/v1/model/abstractive/summarize-text/",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $fdata,
        CURLOPT_HTTPHEADER => [
            "content-type: application/json",
            "x-rapidapi-host: tldrthis.p.rapidapi.com",
            "x-rapidapi-key: " . trim($newsomatic_Main_Settings['best_password'])
        ],
    ]);
    $response = curl_exec($curl);
    if($response === false)
    {
        return false;
    }
    $err = curl_error($curl);
    curl_close($curl);
    if ($err) {
        newsomatic_log_to_file( "cURL Error #:" . $err);
        return false;
    } else {
        $zali = json_decode($response);
        if($zali == false)
        {
            newsomatic_log_to_file( "Failed to decode response: " . $response);
            return false;
        }
        if(!isset($zali->summary))
        {
            newsomatic_log_to_file( "Failed to understand response: " . $response);
            return false;
        }
        $xsum = array($title, nl2br($zali->summary));
        return $xsum;
    }
}
function newsomatic_get_page_PuppeteerAPI($url, $custom_cookies, $custom_user_agent, $use_proxy, $user_pass, $timeout = '')
{
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    if (!isset($newsomatic_Main_Settings['headlessbrowserapi_key']) || trim($newsomatic_Main_Settings['headlessbrowserapi_key']) == '')
    {
        newsomatic_log_to_file('You need to add your HeadlessBrowserAPI key in the plugin\'s \'Main Settings\' before you can use this feature.');
        return false;
    }
    if($custom_user_agent == '')
    {
        $custom_user_agent = 'default';
    }
    if($custom_cookies == '')
    {
        $custom_cookies = 'default';
    }
    if($user_pass == '')
    {
        $user_pass = 'default';
    }
    if($timeout != '')
    {
        $phantomjs_timeout = $timeout;
    }
    else
    {
        if (isset($newsomatic_Main_Settings['phantom_timeout']) && $newsomatic_Main_Settings['phantom_timeout'] != '') 
        {
            $phantomjs_timeout = ((int)$newsomatic_Main_Settings['phantom_timeout']);
        }
        else
        {
            $phantomjs_timeout = 'default';
        }
    }
    $delay = '';
    if (isset($newsomatic_Main_Settings['request_delay']) && $newsomatic_Main_Settings['request_delay'] != '') 
    {
        if(stristr($newsomatic_Main_Settings['request_delay'], ',') !== false)
        {
            $tempo = explode(',', $newsomatic_Main_Settings['request_delay']);
            if(isset($tempo[1]) && is_numeric(trim($tempo[1])) && is_numeric(trim($tempo[0])))
            {
                $delay = rand(trim($tempo[0]), trim($tempo[1]));
            }
        }
        else
        {
            if(is_numeric(trim($newsomatic_Main_Settings['request_delay'])))
            {
                $delay = intval(trim($newsomatic_Main_Settings['request_delay']));
            }
        }
    }
    if($delay != '' && is_numeric($delay))
    {
        $GLOBALS['wp_object_cache']->delete('newsomatic_last_time', 'options');
        $last_time = get_option('newsomatic_last_time', false);
        if($last_time !== false && intval(((intval($last_time) - time()) * 1000 + $delay ) * 1000) > 0)
        {
            $sleep_time = intval(((intval($last_time) - time()) * 1000 + $delay ) * 1000);
            if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) 
            {
                newsomatic_log_to_file('Delay between requests set, waiting ' . ($sleep_time/1000) . ' ms');
            }
            if($sleep_time < 21600000)
            {
                usleep($sleep_time);
            }
        }
    }
    $phantomjs_proxcomm = '"null"';
    if ($use_proxy == '1' && isset($newsomatic_Main_Settings['proxy_url']) && $newsomatic_Main_Settings['proxy_url'] != '') 
    {
        $proxy_url = $newsomatic_Main_Settings['proxy_url'];
        if(isset($newsomatic_Main_Settings['proxy_auth']) && $newsomatic_Main_Settings['proxy_auth'] != '')
        {
            $proxy_auth = $newsomatic_Main_Settings['proxy_auth'];
        }
        else
        {
            $proxy_auth = 'default';
        }
    }
    else
    {
        $proxy_url = 'default';
        $proxy_auth = 'default';
    }
    
    $za_api_url = 'https://headlessbrowserapi.com/apis/scrape/v1/puppeteer?apikey=' . trim($newsomatic_Main_Settings['headlessbrowserapi_key']) . '&url=' . urlencode($url) . '&custom_user_agent=' . urlencode($custom_user_agent) . '&custom_cookies=' . urlencode($custom_cookies) . '&user_pass=' . urlencode($user_pass) . '&timeout=' . urlencode($phantomjs_timeout) . '&proxy_url=' . urlencode($proxy_url) . '&proxy_auth=' . urlencode($proxy_auth);
    $api_timeout = 120;
    $args = array(
       'timeout'     => $api_timeout,
       'redirection' => 10,
       'blocking'    => true,
       'compress'    => false,
       'decompress'  => true,
       'sslverify'   => false,
       'stream'      => false
    );
    $ret_data = wp_remote_get($za_api_url, $args);
    $response_code       = wp_remote_retrieve_response_code( $ret_data );
    $response_message    = wp_remote_retrieve_response_message( $ret_data );    
    if($delay != '' && is_numeric($delay))
    {
        update_option('newsomatic_last_time', time());
    }
    if ( 200 != $response_code ) {
        if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) 
        {
            newsomatic_log_to_file('Failed to get response from HeadlessBrowserAPI: ' . $za_api_url . ' code: ' . $response_code . ' message: ' . $response_message);
            if(isset($ret_data->errors['http_request_failed']))
            {
                foreach($ret_data->errors['http_request_failed'] as $errx)
                {
                    newsomatic_log_to_file('Error message: ' . html_entity_decode($errx));
                }
            }
        }
        return false;
    } else {
        $cmdResult = wp_remote_retrieve_body( $ret_data );
    }
    $jcmdResult = json_decode($cmdResult, true);
    if($jcmdResult === false)
    {
        newsomatic_log_to_file('Failed to decode response from HeadlessBrowserAPI: ' . $za_api_url . ' - ' . print_r($cmdResult, true));
        return false;
    }
    $cmdResult = $jcmdResult;
    if(isset($cmdResult['apicalls']))
    {
        update_option('headless_calls', esc_html($cmdResult['apicalls']));
    }
    if(isset($cmdResult['error']))
    {
        newsomatic_log_to_file('An error occurred while getting content from HeadlessBrowserAPI: ' . $za_api_url . ' - ' . print_r($cmdResult['error'], true));
        return false;
    }
    if(!isset($cmdResult['html']))
    {
        newsomatic_log_to_file('Malformed data imported from HeadlessBrowserAPI: ' . $za_api_url . ' - ' . print_r($cmdResult, true));
        return false;
    }
    return '<html><body>' . $cmdResult['html'] . '</body></html>';
}
function newsomatic_get_page_TorAPI($url, $custom_cookies, $custom_user_agent, $use_proxy, $user_pass, $timeout = '')
{
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    if (!isset($newsomatic_Main_Settings['headlessbrowserapi_key']) || trim($newsomatic_Main_Settings['headlessbrowserapi_key']) == '')
    {
        newsomatic_log_to_file('You need to add your HeadlessBrowserAPI key in the plugin\'s \'Main Settings\' before you can use this feature.');
        return false;
    }
    if($custom_user_agent == '')
    {
        $custom_user_agent = 'default';
    }
    if($custom_cookies == '')
    {
        $custom_cookies = 'default';
    }
    if($user_pass == '')
    {
        $user_pass = 'default';
    }
    if($timeout != '')
    {
        $phantomjs_timeout = $timeout;
    }
    else
    {
        if (isset($newsomatic_Main_Settings['phantom_timeout']) && $newsomatic_Main_Settings['phantom_timeout'] != '') 
        {
            $phantomjs_timeout = ((int)$newsomatic_Main_Settings['phantom_timeout']);
        }
        else
        {
            $phantomjs_timeout = 'default';
        }
    }
    $phantomjs_proxcomm = '"null"';
    if ($use_proxy == '1' && isset($newsomatic_Main_Settings['proxy_url']) && $newsomatic_Main_Settings['proxy_url'] != '') 
    {
        $proxy_url = $newsomatic_Main_Settings['proxy_url'];
        if(isset($newsomatic_Main_Settings['proxy_auth']) && $newsomatic_Main_Settings['proxy_auth'] != '')
        {
            $proxy_auth = $newsomatic_Main_Settings['proxy_auth'];
        }
        else
        {
            $proxy_auth = 'default';
        }
    }
    else
    {
        $proxy_url = 'default';
        $proxy_auth = 'default';
    }
    $delay = '';
    if (isset($newsomatic_Main_Settings['request_delay']) && $newsomatic_Main_Settings['request_delay'] != '') 
    {
        if(stristr($newsomatic_Main_Settings['request_delay'], ',') !== false)
        {
            $tempo = explode(',', $newsomatic_Main_Settings['request_delay']);
            if(isset($tempo[1]) && is_numeric(trim($tempo[1])) && is_numeric(trim($tempo[0])))
            {
                $delay = rand(trim($tempo[0]), trim($tempo[1]));
            }
        }
        else
        {
            if(is_numeric(trim($newsomatic_Main_Settings['request_delay'])))
            {
                $delay = intval(trim($newsomatic_Main_Settings['request_delay']));
            }
        }
    }
    if($delay != '' && is_numeric($delay))
    {
        $GLOBALS['wp_object_cache']->delete('newsomatic_last_time', 'options');
        $last_time = get_option('newsomatic_last_time', false);
        if($last_time !== false && intval(((intval($last_time) - time()) * 1000 + $delay ) * 1000) > 0)
        {
            $sleep_time = intval(((intval($last_time) - time()) * 1000 + $delay ) * 1000);
            if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) 
            {
                newsomatic_log_to_file('Delay between requests set, waiting ' . ($sleep_time/1000) . ' ms');
            }
            if($sleep_time < 21600000)
            {
                usleep($sleep_time);
            }
        }
    }
    $za_api_url = 'https://headlessbrowserapi.com/apis/scrape/v1/tor?apikey=' . trim($newsomatic_Main_Settings['headlessbrowserapi_key']) . '&url=' . urlencode($url) . '&custom_user_agent=' . urlencode($custom_user_agent) . '&custom_cookies=' . urlencode($custom_cookies) . '&user_pass=' . urlencode($user_pass) . '&timeout=' . urlencode($phantomjs_timeout) . '&proxy_url=' . urlencode($proxy_url) . '&proxy_auth=' . urlencode($proxy_auth);
    $api_timeout = 120;
    $args = array(
       'timeout'     => $api_timeout,
       'redirection' => 10,
       'blocking'    => true,
       'compress'    => false,
       'decompress'  => true,
       'sslverify'   => false,
       'stream'      => false
    );
    $ret_data = wp_remote_get($za_api_url, $args);
    $response_code       = wp_remote_retrieve_response_code( $ret_data );
    $response_message    = wp_remote_retrieve_response_message( $ret_data );  
    if($delay != '' && is_numeric($delay))
    {
        update_option('newsomatic_last_time', time());
    }  
    if ( 200 != $response_code ) {
        if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) 
        {
            newsomatic_log_to_file('Failed to get response from HeadlessBrowserAPI: ' . $za_api_url . ' code: ' . $response_code . ' message: ' . $response_message);
            if(isset($ret_data->errors['http_request_failed']))
            {
                foreach($ret_data->errors['http_request_failed'] as $errx)
                {
                    newsomatic_log_to_file('Error message: ' . html_entity_decode($errx));
                }
            }
        }
        return false;
    } else {
        $cmdResult = wp_remote_retrieve_body( $ret_data );
    }
    $jcmdResult = json_decode($cmdResult, true);
    if($jcmdResult === false)
    {
        newsomatic_log_to_file('Failed to decode response from HeadlessBrowserAPI: ' . $za_api_url . ' - ' . print_r($cmdResult, true));
        return false;
    }
    $cmdResult = $jcmdResult;
    if(isset($cmdResult['apicalls']))
    {
        update_option('headless_calls', esc_html($cmdResult['apicalls']));
    }
    if(isset($cmdResult['error']))
    {
        newsomatic_log_to_file('An error occurred while getting content from HeadlessBrowserAPI: ' . $za_api_url . ' - ' . print_r($cmdResult['error'], true));
        return false;
    }
    if(!isset($cmdResult['html']))
    {
        newsomatic_log_to_file('Malformed data imported from HeadlessBrowserAPI: ' . $za_api_url . ' - ' . print_r($cmdResult, true));
        return false;
    }
    return '<html><body>' . $cmdResult['html'] . '</body></html>';
}
function newsomatic_get_page_PhantomJSAPI($url, $custom_cookies, $custom_user_agent, $use_proxy, $user_pass, $timeout = '')
{
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    if (!isset($newsomatic_Main_Settings['headlessbrowserapi_key']) || trim($newsomatic_Main_Settings['headlessbrowserapi_key']) == '')
    {
        newsomatic_log_to_file('You need to add your HeadlessBrowserAPI key in the plugin\'s \'Main Settings\' before you can use this feature.');
        return false;
    }
    if($custom_user_agent == '')
    {
        $custom_user_agent = 'default';
    }
    if($custom_cookies == '')
    {
        $custom_cookies = 'default';
    }
    if($user_pass == '')
    {
        $user_pass = 'default';
    }
    if($timeout != '')
    {
        $phantomjs_timeout = $timeout;
    }
    else
    {
        if (isset($newsomatic_Main_Settings['phantom_timeout']) && $newsomatic_Main_Settings['phantom_timeout'] != '') 
        {
            $phantomjs_timeout = ((int)$newsomatic_Main_Settings['phantom_timeout']);
        }
        else
        {
            $phantomjs_timeout = 'default';
        }
    }
    $delay = '';
    if (isset($newsomatic_Main_Settings['request_delay']) && $newsomatic_Main_Settings['request_delay'] != '') 
    {
        if(stristr($newsomatic_Main_Settings['request_delay'], ',') !== false)
        {
            $tempo = explode(',', $newsomatic_Main_Settings['request_delay']);
            if(isset($tempo[1]) && is_numeric(trim($tempo[1])) && is_numeric(trim($tempo[0])))
            {
                $delay = rand(trim($tempo[0]), trim($tempo[1]));
            }
        }
        else
        {
            if(is_numeric(trim($newsomatic_Main_Settings['request_delay'])))
            {
                $delay = intval(trim($newsomatic_Main_Settings['request_delay']));
            }
        }
    }
    if($delay != '' && is_numeric($delay))
    {
        $GLOBALS['wp_object_cache']->delete('newsomatic_last_time', 'options');
        $last_time = get_option('newsomatic_last_time', false);
        if($last_time !== false && intval(((intval($last_time) - time()) * 1000 + $delay ) * 1000) > 0)
        {
            $sleep_time = intval(((intval($last_time) - time()) * 1000 + $delay ) * 1000);
            if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) 
            {
                newsomatic_log_to_file('Delay between requests set, waiting ' . ($sleep_time/1000) . ' ms');
            }
            if($sleep_time < 21600000)
            {
                usleep($sleep_time);
            }
        }
    }
    $phantomjs_proxcomm = '"null"';
    if ($use_proxy == '1' && isset($newsomatic_Main_Settings['proxy_url']) && $newsomatic_Main_Settings['proxy_url'] != '') 
    {
        $proxy_url = $newsomatic_Main_Settings['proxy_url'];
        if(isset($newsomatic_Main_Settings['proxy_auth']) && $newsomatic_Main_Settings['proxy_auth'] != '')
        {
            $proxy_auth = $newsomatic_Main_Settings['proxy_auth'];
        }
        else
        {
            $proxy_auth = 'default';
        }
    }
    else
    {
        $proxy_url = 'default';
        $proxy_auth = 'default';
    }
    $delay = '';
    if (isset($newsomatic_Main_Settings['request_delay']) && $newsomatic_Main_Settings['request_delay'] != '') 
    {
        if(stristr($newsomatic_Main_Settings['request_delay'], ',') !== false)
        {
            $tempo = explode(',', $newsomatic_Main_Settings['request_delay']);
            if(isset($tempo[1]) && is_numeric(trim($tempo[1])) && is_numeric(trim($tempo[0])))
            {
                $delay = rand(trim($tempo[0]), trim($tempo[1]));
            }
        }
        else
        {
            if(is_numeric(trim($newsomatic_Main_Settings['request_delay'])))
            {
                $delay = intval(trim($newsomatic_Main_Settings['request_delay']));
            }
        }
    }
    if($delay != '' && is_numeric($delay))
    {
        $GLOBALS['wp_object_cache']->delete('newsomatic_last_time', 'options');
        $last_time = get_option('newsomatic_last_time', false);
        if($last_time !== false && intval(((intval($last_time) - time()) * 1000 + $delay ) * 1000) > 0)
        {
            $sleep_time = intval(((intval($last_time) - time()) * 1000 + $delay ) * 1000);
            if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) 
            {
                newsomatic_log_to_file('Delay between requests set, waiting ' . ($sleep_time/1000) . ' ms');
            }
            if($sleep_time < 21600000)
            {
                usleep($sleep_time);
            }
        }
    }
    $za_api_url = 'https://headlessbrowserapi.com/apis/scrape/v1/phantomjs?apikey=' . trim($newsomatic_Main_Settings['headlessbrowserapi_key']) . '&url=' . urlencode($url) . '&custom_user_agent=' . urlencode($custom_user_agent) . '&custom_cookies=' . urlencode($custom_cookies) . '&user_pass=' . urlencode($user_pass) . '&timeout=' . urlencode($phantomjs_timeout) . '&proxy_url=' . urlencode($proxy_url) . '&proxy_auth=' . urlencode($proxy_auth);
    $api_timeout = 120;
    $args = array(
       'timeout'     => $api_timeout,
       'redirection' => 10,
       'blocking'    => true,
       'compress'    => false,
       'decompress'  => true,
       'sslverify'   => false,
       'stream'      => false
    );
    $ret_data = wp_remote_get($za_api_url, $args);
    $response_code       = wp_remote_retrieve_response_code( $ret_data );
    $response_message    = wp_remote_retrieve_response_message( $ret_data );    
    if($delay != '' && is_numeric($delay))
    {
        update_option('newsomatic_last_time', time());
    }
    if ( 200 != $response_code ) {
        if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) 
        {
            newsomatic_log_to_file('Failed to get response from HeadlessBrowserAPI: ' . $za_api_url . ' code: ' . $response_code . ' message: ' . $response_message);
            if(isset($ret_data->errors['http_request_failed']))
            {
                foreach($ret_data->errors['http_request_failed'] as $errx)
                {
                    newsomatic_log_to_file('Error message: ' . html_entity_decode($errx));
                }
            }
        }
        return false;
    } else {
        $cmdResult = wp_remote_retrieve_body( $ret_data );
    }
    $jcmdResult = json_decode($cmdResult, true);
    if($jcmdResult === false)
    {
        newsomatic_log_to_file('Failed to decode response from HeadlessBrowserAPI: ' . $za_api_url . ' - ' . print_r($cmdResult, true));
        return false;
    }
    $cmdResult = $jcmdResult;
    if(isset($cmdResult['apicalls']))
    {
        update_option('headless_calls', esc_html($cmdResult['apicalls']));
    }
    if(isset($cmdResult['error']))
    {
        newsomatic_log_to_file('An error occurred while getting content from HeadlessBrowserAPI: ' . $za_api_url . ' - ' . print_r($cmdResult['error'], true));
        return false;
    }
    if(!isset($cmdResult['html']))
    {
        newsomatic_log_to_file('Malformed data imported from HeadlessBrowserAPI: ' . $za_api_url . ' - ' . print_r($cmdResult, true));
        return false;
    }
    return '<html><body>' . $cmdResult['html'] . '</body></html>';
}
function newsomatic_rel_canonical() {
	$link = false;
	
	if ( is_singular() ) {
        $set = false;
        $source_url = get_post_meta(get_the_ID(), 'newsomatic_post_url', true);
        if($source_url != '')
        {
            $link = $source_url;
            $set = true;
        }
        if($set == false)
        {
            $link = get_permalink( get_queried_object() );
            $page = get_query_var('page');
            if ( $page != '' && 1 != $page && 0 != $page )
                $link = user_trailingslashit( trailingslashit( $link ) . get_query_var( 'page' ) );
        }
	} else {
		if ( is_front_page() ) {
			$link = trailingslashit( get_bloginfo('url') );
		} else if ( is_home() && get_option('show_on_front') == "page" ) {
			$link = get_permalink( get_option( 'page_for_posts' ) );
		} else if ( is_tax() || is_tag() || is_category() ) {
			$term = get_queried_object();
			$link = get_term_link( $term, $term->taxonomy );
		} else if ( is_post_type_archive() ) {
			$link = get_post_type_archive_link( get_post_type() );
		} else if ( is_archive() ) {
			if ( is_date() ) {
				if ( is_day() ) {
					$link = get_day_link( get_query_var('year'), get_query_var('monthnum'), get_query_var('day') );
				} else if ( is_month() ) {
					$link = get_month_link( get_query_var('year'), get_query_var('monthnum') );
				} else if ( is_year() ) {
					$link = get_year_link( get_query_var('year') );
				}						
			}
		}
		$paged = get_query_var('paged');
		if ( $link && $paged != '' && $paged > 1 ) {
			global $wp_rewrite;
			$link = user_trailingslashit( trailingslashit( $link ) . trailingslashit( $wp_rewrite->pagination_base ) . $paged );
		}
	}
	$link = apply_filters( 'rel_canonical', $link );
	
	if ( $link )
    {
		echo "<link rel='canonical' href='" . esc_url_raw($link) .  "' />";
    }
}

function newsomatic_noindex() {
	if ( is_singular() ) {
        $source_url = get_post_meta(get_the_ID(), 'newsomatic_post_url', true);
        if($source_url != '')
        {
            echo '<meta name="robots" content="noindex,follow" />';
        }
	}
}

function newsomatic_get_words($sentence, $count = 100) {
  preg_match("/(?:\w+(?:\W+|$)){0,$count}/", $sentence, $matches);
  return $matches[0];
}

function newsomatic_generate_thumbmail( $post_id )
{
    $post = get_post($post_id);
    $post_parent_id = $post->post_parent === 0 ? $post->ID : $post->post_parent;
    if ( has_post_thumbnail($post_parent_id) )
    {
        if ($id_attachment = get_post_thumbnail_id($post_parent_id)) {
            $the_image  = wp_get_attachment_url($id_attachment, false);
            return $the_image;
        }
    }
    $attachments = array_values(get_children(array(
        'post_parent' => $post_parent_id, 
        'post_status' => 'inherit', 
        'post_type' => 'attachment', 
        'post_mime_type' => 'image', 
        'order' => 'ASC', 
        'orderby' => 'menu_order ID') 
    ));
    if( sizeof($attachments) > 0 ) {
        $the_image  = wp_get_attachment_url($attachments[0]->ID, false);
        return $the_image;
    }
    $image_url = newsomatic_extractThumbnail($post->post_content);
    return $image_url;
}
function newsomatic_extractThumbnail($content) {
    $att = newsomatic_getUrls($content);
    if(count($att) > 0)
    {
        foreach($att as $link)
        {
            if(newsomatic_is_image_url($link))
            {
                $mime = newsomatic_get_mime($link);
                if(stristr($mime, "image/") !== FALSE){
                    return $link;
                }
            }
        }
    }
    else
    {
        return '';
    }
    return '';
}
function newsomatic_is_image_url($l) 
{
    $arr = explode("?", $l);
    return preg_match("#\.(jpg|jpeg|gif|png)$#i", $arr[0]);
}
function newsomatic_getUrls($string) {
    $regex = '/https?\:\/\/[^\"\' \n\s]+/i';
    preg_match_all($regex, $string, $matches);
    return ($matches[0]);
}
function newsomatic_get_mime ($filename) {
    $mime_types = array(
        'txt' => 'text/plain',
        'htm' => 'text/html',
        'html' => 'text/html',
        'php' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',
        'flv' => 'video/x-flv',
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'mts' => 'video/mp2t',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',
        'zip' => 'application/zip',
        'rar' => 'application/x-rar-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',
        'cab' => 'application/vnd.ms-cab-compressed',
        'mp3' => 'audio/mpeg',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',
        'wmv' => 'video/x-ms-wmv',
        'mp4' => 'video/mp4',
        'm4p' => 'video/m4p',
        'm4v' => 'video/m4v',
        'mpg' => 'video/mpg',
        'mp2' => 'video/mp2',
        'mpe' => 'video/mpe',
        'mpv' => 'video/mpv',
        'm2v' => 'video/m2v',
        'm4v' => 'video/m4v',
        '3g2' => 'video/3g2',
        '3gpp' => 'video/3gpp',
        'f4v' => 'video/f4v',
        'f4p' => 'video/f4p',
        'f4a' => 'video/f4a',
        'f4b' => 'video/f4b',
        '3gp' => 'video/3gp',
        'avi' => 'video/x-msvideo',
        'mpeg' => 'video/mpeg',
        'mpegps' => 'video/mpeg',
        'webm' => 'video/webm',
        'mpeg4' => 'video/mp4',
        'mkv' => 'video/mkv',
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',
        'doc' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        'docx' => 'application/msword',
        'xlsx' => 'application/vnd.ms-excel',
        'pptx' => 'application/vnd.ms-powerpoint',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
    );
    $ext = array_values(array_slice(explode('.', $filename), -1));$ext = $ext[0];

    if(stristr($filename, 'dailymotion.com'))
    {
        return 'application/octet-stream';
    }
    if (function_exists('mime_content_type')) {
        error_reporting(0);
        $mimetype = mime_content_type($filename);
        error_reporting(E_ALL);
        if($mimetype == '')
        {
            if (array_key_exists($ext, $mime_types)) {
                return $mime_types[$ext];
            } else {
                return 'application/octet-stream';
            }
        }
        return $mimetype;
    }
    elseif (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME);
        $mimetype = finfo_file($finfo, $filename);
        finfo_close($finfo);
        if($mimetype === false)
        {
            if (array_key_exists($ext, $mime_types)) {
                return $mime_types[$ext];
            } else {
                return 'application/octet-stream';
            }
        }
        return $mimetype;

    } elseif (array_key_exists($ext, $mime_types)) {
        return $mime_types[$ext];
    } else {
        return 'application/octet-stream';
    }
}

function newsomatic_spin_text($title, $content, $alt = false)
{
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    $titleSeparator         = '[19459000]';
    $text                   = $title . ' ' . $titleSeparator . ' ' . $content;
    $text                   = html_entity_decode($text);
    preg_match_all("/<[^<>]+>/is", $text, $matches, PREG_PATTERN_ORDER);
    $htmlfounds         = array_filter(array_unique($matches[0]));
    $htmlfounds[]       = '&quot;';
    $imgFoundsSeparated = array();
    foreach ($htmlfounds as $key => $currentFound) {
        if (stristr($currentFound, '<img') && stristr($currentFound, 'alt')) {
            $altSeparator   = '';
            $colonSeparator = '';
            if (stristr($currentFound, 'alt="')) {
                $altSeparator   = 'alt="';
                $colonSeparator = '"';
            } elseif (stristr($currentFound, 'alt = "')) {
                $altSeparator   = 'alt = "';
                $colonSeparator = '"';
            } elseif (stristr($currentFound, 'alt ="')) {
                $altSeparator   = 'alt ="';
                $colonSeparator = '"';
            } elseif (stristr($currentFound, 'alt= "')) {
                $altSeparator   = 'alt= "';
                $colonSeparator = '"';
            } elseif (stristr($currentFound, 'alt=\'')) {
                $altSeparator   = 'alt=\'';
                $colonSeparator = '\'';
            } elseif (stristr($currentFound, 'alt = \'')) {
                $altSeparator   = 'alt = \'';
                $colonSeparator = '\'';
            } elseif (stristr($currentFound, 'alt= \'')) {
                $altSeparator   = 'alt= \'';
                $colonSeparator = '\'';
            } elseif (stristr($currentFound, 'alt =\'')) {
                $altSeparator   = 'alt =\'';
                $colonSeparator = '\'';
            }
            if (trim($altSeparator) != '') {
                $currentFoundParts = explode($altSeparator, $currentFound);
                $preAlt            = $currentFoundParts[1];
                $preAltParts       = explode($colonSeparator, $preAlt);
                $altText           = $preAltParts[0];
                if (trim($altText) != '') {
                    unset($preAltParts[0]);
                    $imgFoundsSeparated[] = $currentFoundParts[0] . $altSeparator;
                    $imgFoundsSeparated[] = $colonSeparator . implode('', $preAltParts);
                    $htmlfounds[$key]     = '';
                }
            }
        }
    }
    if (count($imgFoundsSeparated) != 0) {
        $htmlfounds = array_merge($htmlfounds, $imgFoundsSeparated);
    }
    preg_match_all("/<\!--.*?-->/is", $text, $matches2, PREG_PATTERN_ORDER);
    $newhtmlfounds = $matches2[0];
    preg_match_all("/\[.*?\]/is", $text, $matches3, PREG_PATTERN_ORDER);
    $shortcodesfounds = $matches3[0];
    $htmlfounds       = array_merge($htmlfounds, $newhtmlfounds, $shortcodesfounds);
    $in               = 0;
    $cleanHtmlFounds  = array();
    foreach ($htmlfounds as $htmlfound) {
        if ($htmlfound == '[19459000]') {
        } elseif (trim($htmlfound) == '') {
        } else {
            $cleanHtmlFounds[] = $htmlfound;
        }
    }
    $htmlfounds = $cleanHtmlFounds;
    $start      = 19459001;
    foreach ($htmlfounds as $htmlfound) {
        $text = str_replace($htmlfound, '[' . $start . ']', $text);
        $start++;
    }
    try {
        require_once(dirname(__FILE__) . "/res/newsomatic-text-spinner.php");
        $phpTextSpinner = new PhpTextSpinner();
        if ($alt === FALSE) {
            $spinContent = $phpTextSpinner->spinContent($text);
        } else {
            $spinContent = $phpTextSpinner->spinContentAlt($text);
        }
        $translated = $phpTextSpinner->runTextSpinner($spinContent);
    }
    catch (Exception $e) {
        if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
            newsomatic_log_to_file('Exception thrown in spinText ' . $e);
        }
        return false;
    }
    preg_match_all('{\[.*?\]}', $translated, $brackets);
    $brackets = $brackets[0];
    $brackets = array_unique($brackets);
    foreach ($brackets as $bracket) {
        if (stristr($bracket, '19')) {
            $corrrect_bracket = str_replace(' ', '', $bracket);
            $corrrect_bracket = str_replace('.', '', $corrrect_bracket);
            $corrrect_bracket = str_replace(',', '', $corrrect_bracket);
            $translated       = str_replace($bracket, $corrrect_bracket, $translated);
        }
    }
    if (stristr($translated, $titleSeparator)) {
        $start = 19459001;
        foreach ($htmlfounds as $htmlfound) {
            $translated = str_replace('[' . $start . ']', $htmlfound, $translated);
            $start++;
        }
        $contents = explode($titleSeparator, $translated);
        $title    = $contents[0];
        $content  = $contents[1];
    } else {
        if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
            newsomatic_log_to_file('Failed to parse spinned content, separator not found');
        }
        return false;
    }
    return array(
        $title,
        $content
    );
}

function newsomatic_builtin_spin_text($title, $content)
{
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    $titleSeparator         = '[19459000]';
    $text                   = $title . ' ' . $titleSeparator . ' ' . $content;
    if (isset($newsomatic_Main_Settings['exclude_words']) && $newsomatic_Main_Settings['exclude_words'] != '') {
        $excw = explode(',', $newsomatic_Main_Settings['exclude_words']);
        $excw = array_map('trim', $excw);
    }
    else
    {
        $excw = array();
    }
    if (isset($newsomatic_Main_Settings['exclude_words_title']) && $newsomatic_Main_Settings['exclude_words_title'] != '') 
    {
        $t_arr = explode(' ', $title);
        $excw = array_merge($excw, $t_arr);
    }
    try 
    {
        $file=file(dirname(__FILE__)  .'/res/synonyms.dat');
		foreach($file as $line){
			$synonyms=explode('|',$line);
			foreach($synonyms as $word){
				if(trim($word) != ''){
                    $must_cont = false;
                    foreach($excw as $exw)
                    {
                        if(strstr($word, $exw) !== false)
                        {
                            $must_cont = true;
                            break;
                        }
                    }
                    if($must_cont == true)
                    {
                        continue;
                    }
                    $word=str_replace('/','\/',$word);
					if(preg_match('/\b'. $word .'\b/u', $text)) {
						$rand = array_rand($synonyms, 1);
						$text = preg_replace('/\b'.$word.'\b/u', trim($synonyms[$rand]), $text);
					}
                    $uword=ucfirst($word);
					if(preg_match('/\b'. $uword .'\b/u', $text)) {
						$rand = array_rand($synonyms, 1);
						$text = preg_replace('/\b'.$uword.'\b/u', ucfirst(trim($synonyms[$rand])), $text);
					}
				}
			}
		}
        $translated = $text;
    }
    catch (Exception $e) {
        if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
            newsomatic_log_to_file('Exception thrown in spinText ' . $e);
        }
        return false;
    }
    if (stristr($translated, $titleSeparator)) {
        $contents = explode($titleSeparator, $translated);
        $title    = $contents[0];
        $content  = $contents[1];
    } else {
        if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
            newsomatic_log_to_file('Failed to parse spinned content, separator not found');
        }
        return false;
    }
    return array(
        $title,
        $content
    );
}

function newsomatic_best_spin_text($title, $content)
{
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    if (!isset($newsomatic_Main_Settings['best_user']) || $newsomatic_Main_Settings['best_user'] == '' || !isset($newsomatic_Main_Settings['best_password']) || $newsomatic_Main_Settings['best_password'] == '') {
        newsomatic_log_to_file('Please insert a valid "The Best Spinner" user name and password.');
        return FALSE;
    }
    $titleSeparator   = '[19459000]';
    $newhtml          = $title . ' ' . $titleSeparator . ' ' . $content;
    $url              = 'http://thebestspinner.com/api.php';
    $data             = array();
    $data['action']   = 'authenticate';
    $data['format']   = 'php';
    $data['username'] = $newsomatic_Main_Settings['best_user'];
    $data['password'] = $newsomatic_Main_Settings['best_password'];
    $ch               = curl_init();
    if ($ch === FALSE) {
        if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
            newsomatic_log_to_file('Failed to init curl!');
        }
        return FALSE;
    }
	
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $fdata = "";
    foreach ($data as $key => $val) {
        $fdata .= "$key=" . urlencode($val) . "&";
    }
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fdata);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_REFERER, $url);
    $html = curl_exec($ch);
    curl_close($ch);
    if ($html === FALSE) {
        if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
            newsomatic_log_to_file('"The Best Spinner" failed to exec curl.');
        }
        return FALSE;
    }
    $output = unserialize($html);
    if ($output['success'] == 'true') {
        $session                = $output['session'];
        $data                   = array();
        $data['session']        = $session;
        $data['format']         = 'php';
        $data['protectedterms'] = '';
        $data['action']         = 'replaceEveryonesFavorites';
        $data['maxsyns']        = '100';
        $data['quality']        = '1';
        $ch = curl_init();
        if ($ch === FALSE) {
            if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                newsomatic_log_to_file('Failed to init curl');
            }
            return FALSE;
        }
        $newhtml = html_entity_decode($newhtml);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        $spinned = '';
        if(str_word_count($newhtml) > 4000)
        {
            while($newhtml != '')
            {
                $first30k = substr($newhtml, 0, 30000);
                $first30k = rtrim($first30k, '(*');
                $first30k = ltrim($first30k, ')*');
                $newhtml = substr($newhtml, 30000);
                $data['text']           = $first30k;
                $fdata = "";
                foreach ($data as $key => $val) {
                    $fdata .= "$key=" . urlencode($val) . "&";
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fdata);
                $output = curl_exec($ch);
                if ($output === FALSE) {
                    if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                        newsomatic_log_to_file('"The Best Spinner" failed to exec curl after auth.');
                    }
                    return FALSE;
                }
                $output = unserialize($output);
                if ($output['success'] == 'true') {
                    $spinned .= ' ' . $output['output'];
                } else {
                    if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                        newsomatic_log_to_file('"The Best Spinner" failed to spin article.');
                    }
                    return FALSE;
                }
            }
        }
        else
        {
            $data['text'] = $newhtml;
            $fdata = "";
            foreach ($data as $key => $val) {
                $fdata .= "$key=" . urlencode($val) . "&";
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fdata);
            $output = curl_exec($ch);
            if ($output === FALSE) {
                if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                    newsomatic_log_to_file('"The Best Spinner" failed to exec curl after auth.');
                }
                return FALSE;
            }
            $output = unserialize($output);
            if ($output['success'] == 'true') {
                $spinned = $output['output'];
            } else {
                if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                    newsomatic_log_to_file('"The Best Spinner" failed to spin article: ' . print_r($output, true));
                }
                return FALSE;
            }
        }
        curl_close($ch);
        $result = explode($titleSeparator, $spinned);
        if (count($result) < 2) {
            if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                newsomatic_log_to_file('"The Best Spinner" failed to spin article - titleseparator not found.' . print_r($output, true));
            }
            return FALSE;
        }
        $spintax = new Newsomatic_Spintax();
        $result[0] = $spintax->process($result[0]);
        $result[1] = $spintax->process($result[1]);
        return $result;

    } else {
        if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
            newsomatic_log_to_file('"The Best Spinner" authentification failed.');
        }
        return FALSE;
    }
}
function newsomatic_wordai_spin_text($title, $content)
{
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    if (!isset($newsomatic_Main_Settings['best_user']) || $newsomatic_Main_Settings['best_user'] == '' || !isset($newsomatic_Main_Settings['best_password']) || $newsomatic_Main_Settings['best_password'] == '') {
        newsomatic_log_to_file('Please insert a valid "Wordai" user name and password.');
        return FALSE;
    }
    if (isset($newsomatic_Main_Settings['wordai_uniqueness']) && $newsomatic_Main_Settings['wordai_uniqueness'] != '') 
    {
        $wordai_uniqueness = trim($newsomatic_Main_Settings['wordai_uniqueness']);
    }
    else
    {
        $wordai_uniqueness = '2';
    }
    if($wordai_uniqueness != '1' && $wordai_uniqueness != '2' && $wordai_uniqueness != '3')
    {
        $wordai_uniqueness = '2';
    }
    $titleSeparator   = '[19459000]';
    $quality = 'Readable';
    $html             = $title . ' ' . $titleSeparator . ' ' . $content;
    $email = $newsomatic_Main_Settings['best_user'];
    $pass = $newsomatic_Main_Settings['best_password'];
    $html = urlencode($html);
    $ch = curl_init('https://wai.wordai.com/api/rewrite');
    if($ch === false)
    {
        newsomatic_log_to_file('Failed to init curl in wordai spinning.');
        return FALSE;
    }
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_POST, 1);
    curl_setopt ($ch, CURLOPT_POSTFIELDS, "input=" . $html . "&uniqueness=" . $wordai_uniqueness . "&rewrite_num=1&return_rewrites=true&email=" . $email . "&key=" . $pass);
	curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    $result = curl_exec($ch);
    if ($result === FALSE) {
        newsomatic_log_to_file('"Wordai" failed to exec curl after auth: ' . curl_error($ch));
        curl_close ($ch);
        return FALSE;
    }
    curl_close ($ch);
    $result = json_decode($result);
    if(!isset($result->rewrites))
    {
        newsomatic_log_to_file('"Wordai" unrecognized response: ' . print_r($result, true));
        return FALSE;
    }
    $result = explode($titleSeparator, $result->rewrites[0]);
    if (count($result) < 2) {
        $result[1] = $result[0];
        $result[0] = $title;
    }
    return $result;
}
 
function newsomatic_spinnerchief_spin_text($title, $content)
{
    $titleSeparator = '[19459000]';
    
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    if (!isset($newsomatic_Main_Settings['best_user']) || $newsomatic_Main_Settings['best_user'] == '' || !isset($newsomatic_Main_Settings['best_password']) || $newsomatic_Main_Settings['best_password'] == '') {
        newsomatic_log_to_file('Please insert a valid "SpinnerChief" user email and password.');
        return FALSE;
    }
    $pss = $newsomatic_Main_Settings['best_password'];
    $html = stripslashes($title). ' ' . $titleSeparator . ' ' . stripslashes($content);
    if(str_word_count($html) > 5000)
    {
        $result = '';
        while($html != '')
        {
            $first30k = substr($html, 0, 20000);
            $first30k = rtrim($first30k, '(*');
            $first30k = ltrim($first30k, ')*');
            $html = substr($html, 20000);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HEADER,0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 600);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            $curlpost =  $first30k;
            $curlpost1 = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $curlpost);
            if($curlpost1 !== null)
            {
                $curlpost = $curlpost1;
            }
            $post_me = 'dev_key=api2409357d02fa474d8&api_key=' . $pss . '&text=' . urlencode($curlpost);
            $url = "https://www.spinnerchief.com/api/paraphraser";
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_me); 
            $result_temp = curl_exec($ch);
            if ($result_temp === FALSE) {
                $cer = 'Curl error: ' . curl_error($ch);
                newsomatic_log_to_file('"SpinnerChief" failed to exec curl after auth. ' . $cer);
                curl_close ($ch);
                return FALSE;
            }
            else
            {
                $json_res = json_decode($result_temp);
                if($json_res !== false && isset($json_res->text))
                {
                    $result .= $json_res->text;
                }
                else
                {
                    $result .= $result_temp;
                }
            }
            curl_close ($ch);
        }
    }
    else
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $curlpost = $html;
        //to fix issue with unicode characters where the API times out
        $curlpost1 = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $curlpost);
        if($curlpost1 !== null)
        {
            $curlpost = $curlpost1;
        }
        $url = "https://www.spinnerchief.com/api/paraphraser";
        $post_me = 'dev_key=api2409357d02fa474d8&api_key=' . $pss . '&text=' . urlencode($curlpost);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_me); 
        $result = curl_exec($ch);
        if ($result === FALSE) {
            $cer = 'Curl error: ' . curl_error($ch);
            newsomatic_log_to_file('"SpinnerChief" failed to exec curl after auth. ' . $cer);
            curl_close ($ch);
            return FALSE;
        }
        $json_res = json_decode($result);
        if($json_res !== false && isset($json_res->text))
        {
            $result = $json_res->text;
        }
        curl_close ($ch);
    }
    $result = preg_replace('#\](\d+\])#', '[$1', $result);
    $result = explode($titleSeparator, $result);
    if (count($result) < 2) {
        $spintax = new Newsomatic_Spintax();
        $result[1] = $spintax->process(trim($result[0]));
        $result[0] = $title;
    }
    else
    {
        $spintax = new Newsomatic_Spintax();
        $result[0] = $spintax->process(trim($result[0]));
        $result[1] = $spintax->process(trim($result[1]));
    }
    return $result;
}
function newsomatic_spinrewriter_spin_text($title, $content)
{
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    if (!isset($newsomatic_Main_Settings['best_user']) || $newsomatic_Main_Settings['best_user'] == '' || !isset($newsomatic_Main_Settings['best_password']) || $newsomatic_Main_Settings['best_password'] == '') {
        newsomatic_log_to_file('Please insert a valid "SpinRewriter" user name and password.');
        return FALSE;
    }
    $titleSeparator = '(19459000)';
    $quality = '50';
    $html = $title . ' ' . $titleSeparator . ' ' . $content;
    $html = preg_replace('/\s+/', ' ', $html);
    $data = array();
    $data['email_address'] = $newsomatic_Main_Settings['best_user'];
    $data['api_key'] = $newsomatic_Main_Settings['best_password'];
    $data['action'] = "unique_variation";
    $data['auto_protected_terms'] = "true";					
    $data['confidence_level'] = "high";							
    $data['auto_sentences'] = "true";							
    $data['auto_paragraphs'] = "false";							
    $data['auto_new_paragraphs'] = "false";						
    $data['auto_sentence_trees'] = "false";						
    $data['use_only_synonyms'] = "true";						
    $data['reorder_paragraphs'] = "false";						
    $data['nested_spintax'] = "false";
    if (isset($newsomatic_Main_Settings['exclude_words']) && $newsomatic_Main_Settings['exclude_words'] != '') {
        $excw = explode(',', $newsomatic_Main_Settings['exclude_words']);
        $excw = array_map('trim', $excw);
        if(count($excw) > 0)
        {
            $data['protected_terms'] = implode('\n', $excw);	
        }
    }
    if(str_word_count($html) >= 2500)
    {
        $result = '';
        while($html != '' && $html != ' ')
        {
            $words = explode(" ", $html);
            $first30k = join(" ", array_slice($words, 0, 2500));
            $html = join(" ", array_slice($words, 2500));
            
            $data['text'] = $first30k;	
            $api_response = newsomatic_spinrewriter_api_post($data);
            if ($api_response === FALSE) {
                newsomatic_log_to_file('"SpinRewriter" failed to exec curl after auth.');
                return FALSE;
            }
            $api_response = json_decode($api_response);
            if(!isset($api_response->response) || !isset($api_response->status) || $api_response->status != 'OK')
            {
                if(isset($api_response->status) && $api_response->status == 'ERROR')
                {
                    if(isset($api_response->response) && $api_response->response == 'You can only submit entirely new text for analysis once every 7 seconds.')
                    {
                        $api_response = newsomatic_spinrewriter_api_post($data);
                        if ($api_response === FALSE) {
                            newsomatic_log_to_file('"SpinRewriter" failed to exec curl after auth (after resubmit).');
                            return FALSE;
                        }
                        $api_response = json_decode($api_response);
                        if(!isset($api_response->response) || !isset($api_response->status) || $api_response->status != 'OK')
                        {
                            newsomatic_log_to_file('"SpinRewriter" failed to wait and resubmit spinning: ' . print_r($api_response, true) . ' params: ' . print_r($data, true));
                            return FALSE;
                        }
                    }
                    else
                    {
                        newsomatic_log_to_file('"SpinRewriter" error response: ' . print_r($api_response, true) . ' params: ' . print_r($data, true));
                        return FALSE;
                    }
                }
                else
                {
                    newsomatic_log_to_file('"SpinRewriter" error response: ' . print_r($api_response, true) . ' params: ' . print_r($data, true));
                    return FALSE;
                }
            }
            $spinned = $api_response->response;
            $result .= ' ' . $spinned;
            if($html != '' && $html != ' ')
            {
                sleep(7);
            }
        }
    }
    else
    {
        $data['text'] = $html;	
        $api_response = newsomatic_spinrewriter_api_post($data);
        if ($api_response === FALSE) {
            newsomatic_log_to_file('"SpinRewriter" failed to exec curl after auth.');
            return FALSE;
        }
        $api_response = json_decode($api_response);
        if(!isset($api_response->response) || !isset($api_response->status) || $api_response->status != 'OK')
        {
            if(isset($api_response->status) && $api_response->status == 'ERROR')
            {
                if(isset($api_response->response) && $api_response->response == 'You can only submit entirely new text for analysis once every 7 seconds.')
                {
                    $api_response = newsomatic_spinrewriter_api_post($data);
                    if ($api_response === FALSE) {
                        newsomatic_log_to_file('"SpinRewriter" failed to exec curl after auth (after resubmit).');
                        return FALSE;
                    }
                    $api_response = json_decode($api_response);
                    if(!isset($api_response->response) || !isset($api_response->status) || $api_response->status != 'OK')
                    {
                        newsomatic_log_to_file('"SpinRewriter" failed to wait and resubmit spinning: ' . print_r($api_response, true) . ' params: ' . print_r($data, true));
                        return FALSE;
                    }
                }
                else
                {
                    newsomatic_log_to_file('"SpinRewriter" error response: ' . print_r($api_response, true) . ' params: ' . print_r($data, true));
                    return FALSE;
                }
            }
            else
            {
                newsomatic_log_to_file('"SpinRewriter" error response: ' . print_r($api_response, true) . ' params: ' . print_r($data, true));
                return FALSE;
            }
        }
        $result = $api_response->response;
    }
    $result = explode($titleSeparator, $result);
    if (count($result) < 2) {
        if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
            newsomatic_log_to_file('"SpinRewriter" failed to spin article - titleseparator not found: ' . $api_response->response);
        }
        return FALSE;
    }
    return $result;
}
function newsomatic_spinrewriter_api_post($data)
{
	$data_raw = "";
    
    $GLOBALS['wp_object_cache']->delete('crspinrewriter_spin_time', 'options');
    $spin_time = get_option('crspinrewriter_spin_time', false);
    if($spin_time !== false && is_numeric($spin_time))
    {
        $c_time = time();
        $spassed = $c_time - $spin_time;
        if($spassed < 10 && $spassed >= 0)
        {
            sleep(10 - $spassed);
        }
    }
    update_option('crspinrewriter_spin_time', time());
    
	foreach ($data as $key => $value){
		$data_raw = $data_raw . $key . "=" . urlencode($value) . "&";
	}
	$ch = curl_init();
    if($ch === false)
    {
        return false;
    }
	curl_setopt($ch, CURLOPT_URL, "http://www.spinrewriter.com/action/api");
	curl_setopt($ch, CURLOPT_SSL_CIPHER_LIST, "DHE_RSA_AES_128_CBC_SHA1,DHE_RSA_AES_128_CBC_SHA256,DHE_RSA_CAMELLIA_128_CBC_SHA1,DHE_RSA_AES_256_CBC_SHA1,DHE_RSA_AES_256_CBC_SHA256DHE_RSA_CAMELLIA_256_CBC_SHA1,DHE_RSA_3DES_EDE_CBC_SHA1,DHE_DSS_AES_128_CBC_SHA1,DHE_DSS_AES_128_CBC_SHA256,DHE_DSS_CAMELLIA_128_CBC_SHA1,DHE_DSS_AES_256_CBC_SHA1,DHE_DSS_AES_256_CBC_SHA256,DHE_DSS_CAMELLIA_256_CBC_SHA1,DHE_DSS_3DES_EDE_CBC_SHA1,DHE_DSS_ARCFOUR_SHA1,RSA_AES_128_CBC_SHA1,RSA_AES_128_CBC_SHA256,RSA_CAMELLIA_128_CBC_SHA1,RSA_AES_256_CBC_SHA1,RSA_AES_256_CBC_SHA256,RSA_CAMELLIA_256_CBC_SHA1,RSA_3DES_EDE_CBC_SHA1,RSA_ARCFOUR_SHA1,RSA_ARCFOUR_MD5");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data_raw);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
	$response = trim(curl_exec($ch));
	curl_close($ch);
	return $response;
}
function newsomatic_replaceExcludes($text, &$htmlfounds, &$pre_tags_matches, &$pre_tags_matches_s, &$conseqMatchs)
{
    preg_match_all ( '{<script.*?script>}s', $text, $script_matchs );
    $script_matchs = $script_matchs [0];
    preg_match_all ( '{<pre.*?/pre>}s', $text, $pre_matchs );
    $pre_matchs = $pre_matchs [0];
    preg_match_all ( '{<code.*?/code>}s', $text, $code_matchs );
    $code_matchs = $code_matchs [0];
    preg_match_all ( "/<[^<>]+>/is", $text, $matches, PREG_PATTERN_ORDER );
    $htmlfounds = array_filter ( array_unique ( $matches [0] ) );
    $htmlfounds = array_merge ( $script_matchs, $pre_matchs, $code_matchs, $htmlfounds );
    preg_match_all ( '{".*?"}', $text, $quote_matchs );
    $quote_matchs = $quote_matchs [0];
    $htmlfounds = array_merge($quote_matchs, $htmlfounds);
    $htmlfounds [] = '&quot;';
    $imgFoundsSeparated = array ();
    $new_imgFoundsSeparated = array ();
    $altSeparator = '';
    $colonSeparator = '';
    foreach ( $htmlfounds as $key => $currentFound ) 
    {
        if (stristr ( $currentFound, '<img' ) && stristr ( $currentFound, 'alt' ) && ! stristr ( $currentFound, 'alt=""' )) 
        {
            $altSeparator = '';
            $colonSeparator = '';
            if (stristr ( $currentFound, 'alt="' )) {
                $altSeparator = 'alt="';
                $colonSeparator = '"';
            } elseif (stristr ( $currentFound, 'alt = "' )) {
                $altSeparator = 'alt = "';
                $colonSeparator = '"';
            } elseif (stristr ( $currentFound, 'alt ="' )) {
                $altSeparator = 'alt ="';
                $colonSeparator = '"';
            } elseif (stristr ( $currentFound, 'alt= "' )) {
                $altSeparator = 'alt= "';
                $colonSeparator = '"';
            } elseif (stristr ( $currentFound, 'alt=\'' )) {
                $altSeparator = 'alt=\'';
                $colonSeparator = '\'';
            } elseif (stristr ( $currentFound, 'alt = \'' )) {
                $altSeparator = 'alt = \'';
                $colonSeparator = '\'';
            } elseif (stristr ( $currentFound, 'alt= \'' )) {
                $altSeparator = 'alt= \'';
                $colonSeparator = '\'';
            } elseif (stristr ( $currentFound, 'alt =\'' )) {
                $altSeparator = 'alt =\'';
                $colonSeparator = '\'';
            }
            if (trim ( $altSeparator ) != '') 
            {
                $currentFoundParts = explode ( $altSeparator, $currentFound );
                $preAlt = $currentFoundParts [1];
                $preAltParts = explode ( $colonSeparator, $preAlt );
                $altText = $preAltParts [0];
                if (trim ( $altText ) != '') 
                {
                    unset ( $preAltParts [0] );
                    $past_alt_text = implode ( $colonSeparator, $preAltParts );
                    $imgFoundsSeparated [] = $currentFoundParts [0] . $altSeparator;
                    $imgFoundsSeparated [] = $colonSeparator . $past_alt_text;
                    $htmlfounds [$key] = '';
                }
            }
        }
    }
    $title_separator = str_replace ( 'alt', 'title', $altSeparator );
    if($title_separator == '')
    {
        $title_separator = 'title';
    }
    if($colonSeparator != '')
    {
        foreach ( $imgFoundsSeparated as $img_part ) 
        {
            if (stristr ( $img_part, ' title' )) 
            {
                $img_part_parts = explode ( $title_separator, $img_part );
                $pre_title_part = $img_part_parts [0] . $title_separator;
                $post_title_parts = explode ( $colonSeparator, $img_part_parts [1] );
                $found_title = $post_title_parts [0];
                unset ( $post_title_parts [0] );
                $past_title_text = implode ( $colonSeparator, $post_title_parts );
                $post_title_part = $colonSeparator . $past_title_text;
                $new_imgFoundsSeparated [] = $pre_title_part;
                $new_imgFoundsSeparated [] = $post_title_part;
            } else {
                $new_imgFoundsSeparated [] = $img_part;
            }
        }
    }
    if (count ( $new_imgFoundsSeparated ) != 0) {
        $htmlfounds = array_merge ( $htmlfounds, $new_imgFoundsSeparated );
    }
    preg_match_all ( "/<\!--.*?-->/is", $text, $matches2, PREG_PATTERN_ORDER );
    $newhtmlfounds = $matches2 [0];
    preg_match_all ( "/\[.*?\]/is", $text, $matches3, PREG_PATTERN_ORDER );
    $shortcodesfounds = $matches3 [0];
    $htmlfounds = array_merge ( $htmlfounds, $newhtmlfounds, $shortcodesfounds );
    $in = 0;
    $cleanHtmlFounds = array ();
    foreach ( $htmlfounds as $htmlfound ) {
        
        if ($htmlfound == '[19459000]') {
        } elseif (trim ( $htmlfound ) == '') {
        } else {
            $cleanHtmlFounds [] = $htmlfound;
        }
    }
    $htmlfounds = array_filter ( $cleanHtmlFounds );
    $start = 19459001;
    foreach ( $htmlfounds as $htmlfound ) {
        $text = str_replace ( $htmlfound, '[' . $start . ']', $text );
        $start ++;
    }
    $text = str_replace ( '.{', '. {', $text );
    preg_match_all ( '!(?:\[1945\d*\][\s]*){2,}!s', $text, $conseqMatchs );
    $startConseq = 19659001;
    foreach ( $conseqMatchs [0] as $conseqMatch ) {
        $text = preg_replace ( '{' . preg_quote ( trim ( $conseqMatch ) ) . '}', '[' . $startConseq . ']', $text, 1 );
        $startConseq ++;
    }
    preg_match_all ( '{\[.*?\]}', $text, $pre_tags_matches );
    $pre_tags_matches = ($pre_tags_matches [0]);
    preg_match_all ( '{\s*\[.*?\]\s*}u', $text, $pre_tags_matches_s );
    $pre_tags_matches_s = ($pre_tags_matches_s [0]);
    $text = str_replace ( '[', "\n\n[", $text );
    $text = str_replace ( ']', "]\n\n", $text );
	return $text;	
}
function newsomatic_restoreExcludes($translated, $htmlfounds, $pre_tags_matches, $pre_tags_matches_s, $conseqMatchs){
    $translated = preg_replace ( '{]\s*?1945}', '][1945', $translated );
    $translated = preg_replace ( '{ 19459(\d*?)]}', ' [19459$1]', $translated );
    $translated = str_replace ( '[ [1945', '[1945', $translated );
    $translated = str_replace ( '], ', ']', $translated );
    preg_match_all ( '{\[.*?\]}', $translated, $bracket_matchs );
    $bracket_matchs = $bracket_matchs [0];
    foreach ( $bracket_matchs as $single_bracket ) 
    {
        if (stristr ( $single_bracket, '1' ) && stristr ( $single_bracket, '9' )) {
            $single_bracket_clean = str_replace ( array (
                    ',',
                    ' ' 
            ), '', $single_bracket );
            $translated = str_replace ( $single_bracket, $single_bracket_clean, $translated );
        }
    }
    preg_match_all ( '{\[\d*?\]}', $translated, $post_tags_matches );
    $post_tags_matches = ($post_tags_matches [0]);
    if (count ( $pre_tags_matches ) == count ( $post_tags_matches )) 
    {
        if ($pre_tags_matches !== $post_tags_matches) 
        {
            $i = 0;
            foreach ( $post_tags_matches as $post_tags_match ) {
                $translated = preg_replace ( '{' . preg_quote ( trim ( $post_tags_match ) ) . '}', '[' . $i . ']', $translated, 1 );
                $i ++;
            }
            $i = 0;
            foreach ( $pre_tags_matches as $pre_tags_match ) {
                $translated = str_replace ( '[' . $i . ']', $pre_tags_match, $translated );
                $i ++;
            }
        }
    }
    $translated = str_replace ( "\n\n[", '[', $translated );
    $translated = str_replace ( "]\n\n", ']', $translated );
    $i = 0;
    foreach ( $pre_tags_matches_s as $pre_tags_match ) 
    {
        $pre_tags_match_h = htmlentities ( $pre_tags_match );
        if (stristr ( $pre_tags_match_h, '&nbsp;' )) {
            $pre_tags_match = str_replace ( '&nbsp;', ' ', $pre_tags_match_h );
        }
        $translated = preg_replace ( '{' . preg_quote ( trim ( $pre_tags_match ) ) . '}', "[$i]", $translated, 1 );
        $i ++;
    }
    $translated = preg_replace ( '{\s*\[}u', '[', $translated );
    $translated = preg_replace ( '{\]\s*}u', ']', $translated );
    $i = 0;
    foreach ( $pre_tags_matches_s as $pre_tags_match ) 
    {
        $pre_tags_match_h = htmlentities ( $pre_tags_match );
        if (stristr ( $pre_tags_match_h, '&nbsp;' )) {
            $pre_tags_match = str_replace ( '&nbsp;', ' ', $pre_tags_match_h );
        }
        $translated = preg_replace ( '{' . preg_quote ( "[$i]" ) . '}', $pre_tags_match, $translated, 1 );
        $i ++;
    }
    $startConseq = 19659001;
    foreach ( $conseqMatchs [0] as $conseqMatch ) {
        $translated = str_replace ( '[' . $startConseq . ']', $conseqMatch, $translated );
        $startConseq ++;
    }
    preg_match_all ( '!\[.*?\]!', $translated, $brackets );
    $brackets = $brackets [0];
    $brackets = array_unique ( $brackets );
    foreach ( $brackets as $bracket ) {
        if (stristr ( $bracket, '19' )) 
        {
            $corrrect_bracket = str_replace ( ' ', '', $bracket );
            $corrrect_bracket = str_replace ( '.', '', $corrrect_bracket );
            $corrrect_bracket = str_replace ( ',', '', $corrrect_bracket );
            $translated = str_replace ( $bracket, $corrrect_bracket, $translated );
        }
    }
    $start = 19459001;
    foreach ( $htmlfounds as $htmlfound ) {
        $translated = str_replace ( '[' . $start . ']', $htmlfound, $translated );
        $start ++;
    }
    return $translated;
}
function newsomatic_fix_spinned_content($final_content, $spinner)
{
    if ($spinner == 'wordai') {
        $final_content = str_replace('-LRB-', '(', $final_content);
        $final_content1 = preg_replace("/{\*\|.*?}/", '*', $final_content);
        if($final_content1 !== null)
        {
            $final_content = $final_content1;
        }
    }
    elseif ($spinner == 'spinnerchief') {
        $final_content = preg_replace('#\[[\s\\\/]*([\d]*?)[\s\\\/]*\[#', '[$1]', $final_content);
        $final_content = preg_replace('#\][\s\\\/]*([\d]*?)[\s\\\/]*\]#', '[$1]', $final_content);
        $final_content = preg_replace('#\[[\s\\\/]*([\d]*?)[\s\\\/]*\]#', '[$1]', $final_content);
    }
    elseif ($spinner == 'spinrewriter' || $spinner == 'translate') {
        $final_content = str_replace('& #', '&#', $final_content);
        $final_content = preg_replace('#&\s([a-zA-Z]+?);#', '', $final_content);
    }
    return $final_content;
}
function newsomatic_spin_and_translate($post_title, $final_content, $rule_translate, $rule_translate_source, $skip_spin_translate, $second_translate = 'disabled')
{
    $translation = false;
    $pre_tags_matches = array();
    $pre_tags_matches_s = array();
    $conseqMatchs = array();
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    if($skip_spin_translate != '1')
    {
        if (isset($newsomatic_Main_Settings['spin_text']) && $newsomatic_Main_Settings['spin_text'] !== 'disabled') {
            
            $htmlfounds = array();
            $final_content = newsomatic_replaceExcludes($final_content, $htmlfounds, $pre_tags_matches, $pre_tags_matches_s, $conseqMatchs);
            
            if ($newsomatic_Main_Settings['spin_text'] == 'builtin') {
                $translation = newsomatic_builtin_spin_text($post_title, $final_content);
            } elseif ($newsomatic_Main_Settings['spin_text'] == 'wikisynonyms') {
                $translation = newsomatic_spin_text($post_title, $final_content, false);
            } elseif ($newsomatic_Main_Settings['spin_text'] == 'freethesaurus') {
                $translation = newsomatic_spin_text($post_title, $final_content, true);
            } elseif ($newsomatic_Main_Settings['spin_text'] == 'best') {
                $translation = newsomatic_best_spin_text($post_title, $final_content);
            } elseif ($newsomatic_Main_Settings['spin_text'] == 'wordai') {
                $translation = newsomatic_wordai_spin_text($post_title, $final_content);
            } elseif ($newsomatic_Main_Settings['spin_text'] == 'spinrewriter') {
                $translation = newsomatic_spinrewriter_spin_text($post_title, $final_content);
            } elseif ($newsomatic_Main_Settings['spin_text'] == 'tldr') {
                $translation = newsomatic_summarize_content($post_title, $final_content);
            } elseif ($newsomatic_Main_Settings['spin_text'] == 'spinnerchief') {
                $translation = newsomatic_spinnerchief_spin_text($post_title, $final_content);
            }
            if ($translation !== FALSE) {
                if (is_array($translation) && isset($translation[0]) && isset($translation[1])) {
                    if (isset($newsomatic_Main_Settings['no_title_spin']) && $newsomatic_Main_Settings['no_title_spin'] == 'on') {
                    }
                    else
                    {
                        $post_title    = $translation[0];
                    }
                    $final_content = $translation[1];
                    
                    $final_content = newsomatic_fix_spinned_content($final_content, $newsomatic_Main_Settings['spin_text']);
                    $final_content = newsomatic_restoreExcludes($final_content, $htmlfounds, $pre_tags_matches, $pre_tags_matches_s, $conseqMatchs);
                    
                } else {
                    $final_content = newsomatic_restoreExcludes($final_content, $htmlfounds, $pre_tags_matches, $pre_tags_matches_s, $conseqMatchs);
                    if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                        newsomatic_log_to_file('Text Spinning failed - malformed data ' . $newsomatic_Main_Settings['spin_text']);
                    }
                }
            } else {
                $final_content = newsomatic_restoreExcludes($final_content, $htmlfounds, $pre_tags_matches, $pre_tags_matches_s, $conseqMatchs);
                if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                    newsomatic_log_to_file('Text Spinning Failed - returned false ' . $newsomatic_Main_Settings['spin_text']);
                }
            }
        }
    }
    if($rule_translate != '' && $rule_translate != 'disabled')
    {
        if (isset($rule_translate_source) && $rule_translate_source != 'disabled' && $rule_translate_source != '') {
            $tr = $rule_translate_source;
        }
        else
        {
            $tr = 'auto';
        }
        $htmlfounds = array();
        $final_content = newsomatic_replaceExcludes($final_content, $htmlfounds, $pre_tags_matches, $pre_tags_matches_s, $conseqMatchs);
        
        $translation = newsomatic_translate($post_title, $final_content, $tr, $rule_translate);
        if (is_array($translation) && isset($translation[1]))
        {
            $translation[1] = preg_replace('#(?<=[\*(])\s+(?=[\*)])#', '', $translation[1]);
            $translation[1] = preg_replace('#([^(*\s]\s)\*+\)#', '$1', $translation[1]);
            $translation[1] = preg_replace('#\(\*+([\s][^)*\s])#', '$1', $translation[1]);
            if($second_translate != '' && $second_translate != 'disabled')
            {
                $translation = newsomatic_translate($translation[0], $translation[1], $rule_translate, $second_translate);
                if (is_array($translation) && isset($translation[1]))
                {
                    $translation[1] = newsomatic_restoreExcludes($translation[1], $htmlfounds, $pre_tags_matches, $pre_tags_matches_s, $conseqMatchs);
                }
                else
                {
                    $final_content = newsomatic_restoreExcludes($final_content, $htmlfounds, $pre_tags_matches, $pre_tags_matches_s, $conseqMatchs);
                    $translation = false;
                    if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                        newsomatic_log_to_file('Failed to translate text the second time, from ' . $rule_translate . ' to ' . $second_translate);
                    }
                }
            }
            else
            {
                $translation[1] = newsomatic_restoreExcludes($translation[1], $htmlfounds, $pre_tags_matches, $pre_tags_matches_s, $conseqMatchs);
            }
        }
        else
        {
            $final_content = newsomatic_restoreExcludes($final_content, $htmlfounds, $pre_tags_matches, $pre_tags_matches_s, $conseqMatchs);
        }
        if ($translation !== FALSE) {
            if (is_array($translation) && isset($translation[0]) && isset($translation[1])) {
                $post_title    = $translation[0];
                $final_content = $translation[1];
                $final_content = str_replace('</ iframe>', '</iframe>', $final_content);
                if(stristr($final_content, '<head>') !== false)
                {
                    $d = new DOMDocument;
                    $mock = new DOMDocument;
                    $internalErrors = libxml_use_internal_errors(true);
                    $d->loadHTML('<?xml encoding="utf-8" ?>' . $final_content);
                    libxml_use_internal_errors($internalErrors);
                    $body = $d->getElementsByTagName('body')->item(0);
                    foreach ($body->childNodes as $child)
                    {
                        $mock->appendChild($mock->importNode($child, true));
                    }
                    $new_post_content_temp = $mock->saveHTML();
                    if($new_post_content_temp !== '' && $new_post_content_temp !== false)
                    {
						$new_post_content_temp = str_replace('<?xml encoding="utf-8" ?>', '', $new_post_content_temp);
                        $final_content = preg_replace("/_addload\(function\(\){([^<]*)/i", "", $new_post_content_temp); 
                    }
                }
                $final_content = newsomatic_repairHTML($final_content);
                $final_content = str_replace('%20', '', $final_content);
                $final_content = str_replace('/V/', '/v/', $final_content);
                $final_content = str_replace('?Oh=', '?oh=', $final_content);
                $final_content = htmlspecialchars_decode($final_content);
                $final_content = str_replace('</ ', '</', $final_content);
                $final_content = str_replace(' />', '/>', $final_content);
                $final_content = str_replace('< br/>', '<br/>', $final_content);
                $final_content = str_replace('< / ', '</', $final_content);
                $final_content = str_replace(' / >', '/>', $final_content);
                $final_content = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $final_content);
                $post_title = preg_replace('{&\s*#\s*(\d+)\s*;}', '&#$1;', $post_title);
                $post_title = htmlspecialchars_decode($post_title);
                $post_title = str_replace('</ ', '</', $post_title);
                $post_title = str_replace(' />', '/>', $post_title);
                $post_title = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $post_title);
            } else {
                if (isset($newsomatic_Main_Settings['skip_failed_tr']) && $newsomatic_Main_Settings['skip_failed_tr'] == 'on')
                {
                    return false;
                }
                if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                    newsomatic_log_to_file('Translation failed - malformed data!');
                }
            }
        } else {
            if (isset($newsomatic_Main_Settings['skip_failed_tr']) && $newsomatic_Main_Settings['skip_failed_tr'] == 'on')
            {
                return false;
            }
            if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                newsomatic_log_to_file('Translation Failed - returned false!');
            }
        }
    }
    else
    {
        if (isset($newsomatic_Main_Settings['translate']) && $newsomatic_Main_Settings['translate'] != 'disabled') {
            if (isset($newsomatic_Main_Settings['translate_source']) && $newsomatic_Main_Settings['translate_source'] != 'disabled') {
                $tr = $newsomatic_Main_Settings['translate_source'];
            }
            else
            {
                $tr = 'auto';
            }
            $htmlfounds = array();
            $final_content = newsomatic_replaceExcludes($final_content, $htmlfounds, $pre_tags_matches, $pre_tags_matches_s, $conseqMatchs);
        
            $translation = newsomatic_translate($post_title, $final_content, $tr, $newsomatic_Main_Settings['translate']);
            if (is_array($translation) && isset($translation[1]))
            {
                $translation[1] = preg_replace('#(?<=[\*(])\s+(?=[\*)])#', '', $translation[1]);
                $translation[1] = preg_replace('#([^(*\s]\s)\*+\)#', '$1', $translation[1]);
                $translation[1] = preg_replace('#\(\*+([\s][^)*\s])#', '$1', $translation[1]);
                if (isset($newsomatic_Main_Settings['second_translate']) && $newsomatic_Main_Settings['second_translate'] != 'disabled')
                {
                    $translation = newsomatic_translate($translation[0], $translation[1], $newsomatic_Main_Settings['translate'], $newsomatic_Main_Settings['second_translate']);
                    if (is_array($translation) && isset($translation[1]))
                    {
                        $translation[1] = newsomatic_restoreExcludes($translation[1], $htmlfounds, $pre_tags_matches, $pre_tags_matches_s, $conseqMatchs);
                    }
                    else
                    {
                        $final_content = newsomatic_restoreExcludes($final_content, $htmlfounds, $pre_tags_matches, $pre_tags_matches_s, $conseqMatchs);
                        $translation = false;
                        if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                            newsomatic_log_to_file('Failed to translate text the second time, from ' . $newsomatic_Main_Settings['translate'] . ' to ' . $newsomatic_Main_Settings['second_translate']);
                        }
                    }
                }
                else
                {
                    $translation[1] = newsomatic_restoreExcludes($translation[1], $htmlfounds, $pre_tags_matches, $pre_tags_matches_s, $conseqMatchs);
                }
            }
            else
            {
                $final_content = newsomatic_restoreExcludes($final_content, $htmlfounds, $pre_tags_matches, $pre_tags_matches_s, $conseqMatchs);
            }
            if ($translation !== FALSE) {
                if (is_array($translation) && isset($translation[0]) && isset($translation[1])) {
                    $post_title    = $translation[0];
                    $final_content = $translation[1];
                    $final_content = str_replace('</ iframe>', '</iframe>', $final_content);
                    if(stristr($final_content, '<head>') !== false)
                    {
                        $d = new DOMDocument;
                        $mock = new DOMDocument;
                        $internalErrors = libxml_use_internal_errors(true);
                        $d->loadHTML('<?xml encoding="utf-8" ?>' . $final_content);
                        libxml_use_internal_errors($internalErrors);
                        $body = $d->getElementsByTagName('body')->item(0);
                        foreach ($body->childNodes as $child)
                        {
                            $mock->appendChild($mock->importNode($child, true));
                        }
                        $new_post_content_temp = $mock->saveHTML();
                        if($new_post_content_temp !== '' && $new_post_content_temp !== false)
                        {
                            $final_content = preg_replace("/_addload\(function\(\){([^<]*)/i", "", $new_post_content_temp); 
                        }
                    }
                    $final_content = newsomatic_repairHTML($final_content);
                    $final_content = str_replace('%20', '', $final_content);
                    $final_content = str_replace('/V/', '/v/', $final_content);
                    $final_content = str_replace('?Oh=', '?oh=', $final_content);
                    $final_content = htmlspecialchars_decode($final_content);
                    $final_content = str_replace('</ ', '</', $final_content);
                    $final_content = str_replace(' />', '/>', $final_content);
                    $final_content = str_replace('< br/>', '<br/>', $final_content);
                    $final_content = str_replace('< / ', '</', $final_content);
                    $final_content = str_replace(' / >', '/>', $final_content);
                    $final_content = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $final_content);
                    $post_title = preg_replace('{&\s*#\s*(\d+)\s*;}', '&#$1;', $post_title);
                    $post_title = htmlspecialchars_decode($post_title);
                    $post_title = str_replace('</ ', '</', $post_title);
                    $post_title = str_replace(' />', '/>', $post_title);
                    $post_title = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $post_title);
                } else {
                    if (isset($newsomatic_Main_Settings['skip_failed_tr']) && $newsomatic_Main_Settings['skip_failed_tr'] == 'on')
                    {
                        return false;
                    }
                    if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                        newsomatic_log_to_file('Translation failed - malformed data!');
                    }
                }
            } else {
                if (isset($newsomatic_Main_Settings['skip_failed_tr']) && $newsomatic_Main_Settings['skip_failed_tr'] == 'on')
                {
                    return false;
                }
                if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                    newsomatic_log_to_file('Translation Failed - returned false!');
                }
            }
        }
    }
    return array(
        $post_title,
        $final_content
    );
}

function newsomatic_translate($title, $content, $from, $to)
{
    $ch = FALSE;
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    try {
        if($from == 'disabled')
        {
            if(strstr($to, '-') !== false && $to != 'zh-CN' && $to != 'zh-TW')
            {
                $from = 'auto-';
            }
            else
            {
                $from = 'auto';
            }
        }
        if($from != 'en' && $from != 'EN-' && $from != 'en!' && $from == $to)
        {
            if(strstr($to, '-') !== false && $to != 'zh-CN' && $to != 'zh-TW')
            {
                $from = 'en-';
            }
            else
            {
                $from = 'en';
            }
        }
        elseif(($from == 'en' || $from == 'EN-' || $from == 'en!') && $from == $to)
        {
            return false;
        }
        if(strstr($to, '!') !== false)
        {
            if (!isset($newsomatic_Main_Settings['bing_auth']) || trim($newsomatic_Main_Settings['bing_auth']) == '')
            {
                throw new Exception('You must enter a Microsoft Translator API key from plugin settings, to use this feature!');
            }
            require_once (dirname(__FILE__) . "/res/newsomatic-translator-microsoft.php");
            $options    = array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_SSL_VERIFYPEER => 0
            );
            $ch = curl_init();
            if ($ch === FALSE) {
                newsomatic_log_to_file ('Failed to init curl in Microsoft Translator');
				return false;
            }
            if (isset($newsomatic_Main_Settings['proxy_url']) && $newsomatic_Main_Settings['proxy_url'] != '' && $newsomatic_Main_Settings['proxy_url'] != 'disable' && $newsomatic_Main_Settings['proxy_url'] != 'disabled') {
				$prx = explode(',', $newsomatic_Main_Settings['proxy_url']);
                $randomness = array_rand($prx);
                $options[CURLOPT_PROXY] = trim($prx[$randomness]);
                if (isset($newsomatic_Main_Settings['proxy_auth']) && $newsomatic_Main_Settings['proxy_auth'] != '') 
                {
                    $prx_auth = explode(',', $newsomatic_Main_Settings['proxy_auth']);
                    if(isset($prx_auth[$randomness]) && trim($prx_auth[$randomness]) != '')
                    {
                        $options[CURLOPT_PROXYUSERPWD] = trim($prx_auth[$randomness]);
                    }
                }
            }
            curl_setopt_array($ch, $options);
			$MicrosoftTranslator = new MicrosoftTranslator ( $ch );	
			try 
            {
                if (!isset($newsomatic_Main_Settings['bing_region']) || trim($newsomatic_Main_Settings['bing_region']) == '')
                {
                    $mt_region = 'global';
                }
                else
                {
                    $mt_region = trim($newsomatic_Main_Settings['bing_region']);
                }
                if($from == 'auto' || $from == 'auto-' || $from == 'disabled')
                {
                    $from = 'no';
                }
				$accessToken = $MicrosoftTranslator->getToken ( trim($newsomatic_Main_Settings['bing_auth']) , $mt_region  );
                $from = trim($from, '!');
                $to = trim($to, '!');
				$translated = $MicrosoftTranslator->translateWrap ( $content, $from, $to );
                $translated_title = $MicrosoftTranslator->translateWrap ( $title, $from, $to );
                curl_close($ch);
			} 
            catch ( Exception $e ) 
            {
                curl_close($ch);
				newsomatic_log_to_file ('Microsoft Translation error: ' . $e->getMessage());
				return false;
			}
        }
        elseif(strstr($to, '-') !== false && $to != 'zh-CN' && $to != 'zh-TW')
        {
            if (!isset($newsomatic_Main_Settings['deepl_auth']) || trim($newsomatic_Main_Settings['deepl_auth']) == '')
            {
                throw new Exception('You must enter a DeepL API key from plugin settings, to use this feature!');
            }
            $to = rtrim($to, '-');
            $from = rtrim($from, '-');
            if(strlen($content) > 13000)
            {
                $translated = '';
                while($content != '')
                {
                    $first30k = substr($content, 0, 13000);
                    $content = substr($content, 13000);
                    if (isset($newsomatic_Main_Settings['deppl_free']) && trim($newsomatic_Main_Settings['deppl_free']) == 'on')
                    {
                        $ch = curl_init('https://api-free.deepl.com/v2/translate');
                    }
                    else
                    {
                        $ch = curl_init('https://api.deepl.com/v2/translate');
                    }
                    if($ch !== false)
                    {
                        $data           = array();
                        $data['text']   = $first30k;
                        if($from != 'auto')
                        {
                            $data['source_lang']   = $from;
                        }
                        $data['tag_handling']  = 'xml';
                        $data['non_splitting_tags']  = 'div';
                        $data['preserve_formatting']  = '1';
                        $data['target_lang']   = $to;
                        $data['auth_key']   = trim($newsomatic_Main_Settings['deepl_auth']);
                        $fdata = "";
                        foreach ($data as $key => $val) {
                            $fdata .= "$key=" . urlencode(trim($val)) . "&";
                        }
                        $headers = [
                            'Content-Type: application/x-www-form-urlencoded',
                            'Content-Length: ' . strlen($fdata)
                        ];
                        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_USERAGENT, newsomatic_get_random_user_agent());
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $fdata);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                        $translated_temp = curl_exec($ch);
                        if($translated_temp === false)
                        {
                            throw new Exception('Failed to post to DeepL: ' . curl_error($ch));
                        }
                        curl_close($ch);
                    }
                    $trans_json = json_decode($translated_temp, true);
                    if($trans_json === false)
                    {
                        throw new Exception('Incorrect multipart response from DeepL: ' . $translated_temp);
                    }
                    if(!isset($trans_json['translations'][0]['text']))
                    {
                        throw new Exception('Unrecognized multipart response from DeepL: ' . $translated_temp);
                    }
                    $translated .= ' ' . $trans_json['translations'][0]['text'];
                }
            }
            else
            {
                if (isset($newsomatic_Main_Settings['deppl_free']) && trim($newsomatic_Main_Settings['deppl_free']) == 'on')
                {
                    $ch = curl_init('https://api-free.deepl.com/v2/translate');
                }
                else
                {
                    $ch = curl_init('https://api.deepl.com/v2/translate');
                }
                if($ch !== false)
                {
                    $data           = array();
                    $data['text']   = $content;
                    if($from != 'auto')
                    {
                        $data['source_lang']   = $from;
                    }
                    $data['tag_handling']  = 'xml';
                    $data['non_splitting_tags']  = 'div';
                    $data['preserve_formatting']  = '1';
                    $data['target_lang']   = $to;
                    $data['auth_key']   = trim($newsomatic_Main_Settings['deepl_auth']);
                    $fdata = "";
                    foreach ($data as $key => $val) {
                        $fdata .= "$key=" . urlencode(trim($val)) . "&";
                    }
                    curl_setopt($ch, CURLOPT_POST, 1);
                    $headers = [
                        'Content-Type: application/x-www-form-urlencoded',
                        'Content-Length: ' . strlen($fdata)
                    ];
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $fdata);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_USERAGENT, newsomatic_get_random_user_agent());
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                    $translated = curl_exec($ch);
                    if($translated === false)
                    {
                        throw new Exception('Failed to post to DeepL: ' . curl_error($ch));
                    }
                    curl_close($ch);
                }
                $trans_json = json_decode($translated, true);
                if($trans_json === false)
                {
                    throw new Exception('Incorrect text response from DeepL: ' . $translated);
                }
                if(!isset($trans_json['translations'][0]['text']))
                {
                    throw new Exception('Unrecognized text response from DeepL: ' . 'https://api.deepl.com/v2/translate?text=' . urlencode($content) . '&source_lang=' . $from . '&target_lang=' . $to . '&auth_key=' . trim($newsomatic_Main_Settings['deepl_auth']) . '&tag_handling=xml&preserve_formatting=1' . ' --- ' . $translated);
                }
                $translated = $trans_json['translations'][0]['text'];
            }
            $translated = str_replace('<strong>', ' <strong>', $translated);
            $translated = str_replace('</strong>', '</strong> ', $translated);
            if($from != 'auto')
            {
                $from_from = '&source_lang=' . $from;
            }
            else
            {
                $from_from = '';
            }
            if (isset($newsomatic_Main_Settings['deppl_free']) && trim($newsomatic_Main_Settings['deppl_free']) == 'on')
            {
                $translated_title = newsomatic_get_web_page('https://api-free.deepl.com/v2/translate?text=' . urlencode($title) . $from_from . '&target_lang=' . $to . '&auth_key=' . trim($newsomatic_Main_Settings['deepl_auth']) . '&tag_handling=xml&preserve_formatting=1');
            }
            else
            {
                $translated_title = newsomatic_get_web_page('https://api.deepl.com/v2/translate?text=' . urlencode($title) . $from_from . '&target_lang=' . $to . '&auth_key=' . trim($newsomatic_Main_Settings['deepl_auth']) . '&tag_handling=xml&preserve_formatting=1');
            }
            $trans_json = json_decode($translated_title, true);
            if($trans_json === false)
            {
                throw new Exception('Incorrect title response from DeepL: ' . $translated_title);
            }
            if(!isset($trans_json['translations'][0]['text']))
            {
                throw new Exception('Unrecognized title response from DeepL: ' . $translated_title);
            }
            $translated_title = $trans_json['translations'][0]['text'];
        }
        else
        {
            if (isset($newsomatic_Main_Settings['google_trans_auth']) && trim($newsomatic_Main_Settings['google_trans_auth']) != '')
            {
                require_once(dirname(__FILE__) . "/res/translator-api.php");
                $ch = curl_init();
                if ($ch === FALSE) {
                    if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                        newsomatic_log_to_file('Failed to init cURL in translator!');
                    }
                    return false;
                }
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                $GoogleTranslatorAPI = new GoogleTranslatorAPI($ch, $newsomatic_Main_Settings['google_trans_auth']);
                $translated = '';
                $translated_title = '';
                if($content != '')
                {
                    if(strlen($content) > 13000)
                    {
                        while($content != '')
                        {
                            $first30k = substr($content, 0, 13000);
                            $content = substr($content, 13000);
                            $translated_temp       = $GoogleTranslatorAPI->translateText($first30k, $from, $to);
                            $translated .= ' ' . $translated_temp;
                        }
                    }
                    else
                    {
                        $translated       = $GoogleTranslatorAPI->translateText($content, $from, $to);
                    }
                }
                if($title != '')
                {
                    $translated_title = $GoogleTranslatorAPI->translateText($title, $from, $to);
                }
                curl_close($ch);
            }
            else
            {
                require_once(dirname(__FILE__) . "/res/newsomatic-translator.php");
                $ch = curl_init();
                if ($ch === FALSE) {
                    if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
                        newsomatic_log_to_file('Failed to init cURL in translator!');
                    }
                    return false;
                }
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                curl_setopt($ch, CURLOPT_USERAGENT, newsomatic_get_random_user_agent());
				if (isset($newsomatic_Main_Settings['proxy_url']) && $newsomatic_Main_Settings['proxy_url'] != '' && $newsomatic_Main_Settings['proxy_url'] != 'disable' && $newsomatic_Main_Settings['proxy_url'] != 'disabled') {
					$prx = explode(',', $newsomatic_Main_Settings['proxy_url']);
					$randomness = array_rand($prx);
					curl_setopt( $ch, CURLOPT_PROXY, trim($prx[$randomness]));
					if (isset($newsomatic_Main_Settings['proxy_auth']) && $newsomatic_Main_Settings['proxy_auth'] != '') 
					{
						$prx_auth = explode(',', $newsomatic_Main_Settings['proxy_auth']);
						if(isset($prx_auth[$randomness]) && trim($prx_auth[$randomness]) != '')
						{
							curl_setopt( $ch, CURLOPT_PROXYUSERPWD, trim($prx_auth[$randomness]) );
						}
					}
				}
				$GoogleTranslator = new GoogleTranslator($ch);
                if(strlen($content) > 13000)
                {
                    $translated = '';
                    while($content != '')
                    {
                        $first30k = substr($content, 0, 13000);
                        $content = substr($content, 13000);
                        $translated_temp       = $GoogleTranslator->translateText($first30k, $from, $to);
                        if (strpos($translated, '<h2>The page you have attempted to translate is already in ') !== false) {
                            throw new Exception('Page content already in ' . $to);
                        }
                        if (strpos($translated, 'Error 400 (Bad Request)!!1') !== false) {
                            throw new Exception('Unexpected error while translating page!');
                        }
                        if(substr_compare($translated_temp, '</pre>', -strlen('</pre>')) === 0){$translated_temp = substr_replace($translated_temp ,"", -6);}if(substr( $translated_temp, 0, 5 ) === "<pre>"){$translated_temp = substr($translated_temp, 5);}
                        $translated .= ' ' . $translated_temp;
                    }
                }
                else
                {
                    $translated       = $GoogleTranslator->translateText($content, $from, $to);
                    if (strpos($translated, '<h2>The page you have attempted to translate is already in ') !== false) {
                        throw new Exception('Page content already in ' . $to);
                    }
                    if (strpos($translated, 'Error 400 (Bad Request)!!1') !== false) {
                        throw new Exception('Unexpected error while translating page!');
                    }
                }
                $translated_title = $GoogleTranslator->translateText($title, $from, $to);
                if (strpos($translated_title, '<h2>The page you have attempted to translate is already in ') !== false) {
                    throw new Exception('Page title already in ' . $to);
                }
                if (strpos($translated_title, 'Error 400 (Bad Request)!!1') !== false) {
                    throw new Exception('Unexpected error while translating page title!');
                }
                curl_close($ch);
            }
        }
    }
    catch (Exception $e) {
        if($ch !== false)
        {
            curl_close($ch);
        }
        if (isset($newsomatic_Main_Settings['enable_detailed_logging'])) {
            newsomatic_log_to_file('Exception thrown in Translator ' . $e);
        }
        return false;
    }
    if(substr_compare($translated_title, '</pre>', -strlen('</pre>')) === 0){$title = substr_replace($translated_title ,"", -6);}else{$title = $translated_title;}if(substr( $title, 0, 5 ) === "<pre>"){$title = substr($title, 5);}
    if(substr_compare($translated, '</pre>', -strlen('</pre>')) === 0){$text = substr_replace($translated ,"", -6);}else{$text = $translated;}if(substr( $text, 0, 5 ) === "<pre>"){$text = substr($text, 5);}
    $text  = preg_replace('/' . preg_quote('html lang=') . '.*?' . preg_quote('>') . '/', '', $text);
    $text  = preg_replace('/' . preg_quote('!DOCTYPE') . '.*?' . preg_quote('<') . '/', '', $text);
    $text = str_replace('%% item_cat %%', '%%item_cat%%', $text);
    $text = str_replace('%% item_tags %%', '%%item_tags%%', $text);
    $text = str_replace('%% item_url %%', '%%item_url%%', $text);
    $text = str_replace('%% item_read_more_button %%', '%%item_read_more_button%%', $text);
    $text = str_replace('%%item_read_more_button %%', '%%item_read_more_button%%', $text);
    $text = str_replace('%% item_read_more_button%%', '%%item_read_more_button%%', $text);
    $text = str_replace('%% item_image_URL %%', '%%item_image_URL%%', $text);
    $text = str_replace('%% author_link %%', '%%author_link%%', $text);
    $text = str_replace('%% custom_html2 %%', '%%custom_html2%%', $text);
    $text = str_replace('%% custom_html %%', '%%custom_html%%', $text);
    $text = str_replace('%% random_sentence %%', '%%random_sentence%%', $text);
    $text = str_replace('%% random_sentence2 %%', '%%random_sentence2%%', $text);
    $text = str_replace('%% item_title %%', '%%item_title%%', $text);
    $text = str_replace('%% item_content %%', '%%item_content%%', $text);
    $text = str_replace('%% item_original_content %%', '%%item_original_content%%', $text);
    $text = str_replace('%% item_content_plain_text %%', '%%item_content_plain_text%%', $text);
    $text = str_replace('%% item_description %%', '%%item_description%%', $text);
    $text = str_replace('%% author %%', '%%author%%', $text);
    $text = str_replace('%% item_media %%', '%%item_media%%', $text);
    $text = str_replace('%% item_date %%', '%%item_date%%', $text);
    $text = str_replace('&amp; # 039;', '\'', $text);
    $text = str_replace('%% %% item_read_more_button', '%%item_read_more_button%%', $text);
    $text = str_replace('&amp; ldquo;', '"', $text);
    $text = str_replace('&amp; rdquo;', '"', $text);
    $text = str_replace(' \' ', '\'', $text);
    $text = preg_replace('{<iframe src="https://translate.google.com/translate(?:.*?)></iframe>}i', "", html_entity_decode($text, ENT_QUOTES));
    $text = preg_replace('{<span class="google-src-text.*?>.*?</span>}', "", $text);
    $text = preg_replace('{<span class="notranslate.*?>(.*?)</span>}', "$1", $text);
    $title = str_replace('%% random_sentence %%', '%%random_sentence%%', $title);
    $title = str_replace('%% random_sentence2 %%', '%%random_sentence2%%', $title);
    $title = str_replace('%% item_title %%', '%%item_title%%', $title);
    $title = str_replace('%% item_description %%', '%%item_description%%', $title);
    $title = str_replace('%% item_url %%', '%%item_url%%', $title);
    $title = str_replace('%% item_date %%', '%%item_date%%', $title);
    $title = str_replace('%% author %%', '%%author%%', $title);
    $title = str_replace('%% item_cat %%', '%%item_cat%%', $title);
    $title = str_replace('%% item_tags %%', '%%item_tags%%', $title);
    $title = str_replace('&amp; # 039;', '\'', $title);
    $title = str_replace('&amp; ldquo;', '"', $title);
    $title = str_replace('&amp; rdquo;', '"', $title);
    $title = str_replace(' \' ', '\'', $title);

    return array(
        $title,
        $text
    );
}

function newsomatic_strip_html_tags_nl($str, $allow_tags = '')
{
	$orignall = $allow_tags;
	$str = html_entity_decode($str);
    $str = preg_replace('/(<|>)\1{2}/is', '', $str);
    if($allow_tags != '')
    {
        $rparr = array();
        if(stristr($allow_tags, '<head>') == false)
        {
            $rparr[] = '@<head[^>]*?>.*?</head>@siu';
        }
        if(stristr($allow_tags, '<style>') == false)
        {
            $rparr[] = '@<style[^>]*?>.*?</style>@siu';
        }
        if(stristr($allow_tags, '<script>') == false)
        {
            $rparr[] = '@<script[^>]*?.*?</script>@siu';
        }
        if(stristr($allow_tags, '<noscript>') == false)
        {
            $rparr[] = '@<noscript[^>]*?.*?</noscript>@siu';
        }
        if(count($rparr) > 0)
        {
            $str = preg_replace($rparr, "", $str);
        }
    }
    else
    {
        $str = preg_replace(array(
            '@<head[^>]*?>.*?</head>@siu',
            '@<style[^>]*?>.*?</style>@siu',
            '@<script[^>]*?.*?</script>@siu',
            '@<noscript[^>]*?.*?</noscript>@siu'
        ), "", $str);
    }
	if(stristr($allow_tags, 'p') === false)
	{
		$allow_tags .= '<p>';
	}
	if(stristr($allow_tags, 'br') === false)
	{
		$allow_tags .= '<br>';
	}
    $str = str_replace('><', '> <', $str);
    if($allow_tags != '')
    {
		$str = strip_tags($str, $allow_tags);
    }
    else
    {
        $str = strip_tags($str);
    }
	if(stristr($orignall, 'br') === false)
	{
		$str = preg_replace('#<br\s*\/?>#i', PHP_EOL, $str);
	}
	if(stristr($orignall, 'p') === false)
	{
		$str = preg_replace('#<\/p>#i', PHP_EOL . PHP_EOL, $str);
		$str = preg_replace('#<p([^>]*?)>#i', '', $str);
	}
    return $str;
}
function newsomatic_strip_html_tags($str)
{
    $str = html_entity_decode($str);
    $str1 = preg_replace('/(<|>)\1{2}/is', '', $str);
    if($str1 !== null)
    {
        $str = $str1;
    }
    $str1 = preg_replace(array(
        '@<head[^>]*?>.*?</head>@siu',
        '@<style[^>]*?>.*?</style>@siu',
        '@<script[^>]*?.*?</script>@siu',
        '@<noscript[^>]*?.*?</noscript>@siu'
    ), "", $str);
    if($str1 !== null)
    {
        $str = $str1;
    }
    $str = str_replace('><', '> <', $str);
    $str = strip_tags($str);
    return $str;
}

function newsomatic_DOMinnerHTML(DOMNode $element)
{
    $innerHTML = "";
    $children  = $element->childNodes;
    
    foreach ($children as $child) {
        $innerHTML .= $element->ownerDocument->saveHTML($child);
    }
    
    return $innerHTML;
}

function newsomatic_url_exists($url)
{
    stream_context_set_default( [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ]);
    error_reporting(0);
    $headers = get_headers($url);
    error_reporting(E_ALL);
    if (!isset($headers[0]) || strpos($headers[0], '200') === false)
        return false;
    return true;
}

register_activation_hook(__FILE__, 'newsomatic_check_version');
function newsomatic_check_version()
{
    if (!function_exists('curl_init')) {
        echo '<h3>'.esc_html__('Please enable curl PHP extension. Please contact your hosting provider\'s support to help you in this matter.', 'newsomatic-news-post-generator').'</h3>';
        die;
    }
    global $wp_version;
    if (!current_user_can('activate_plugins')) {
        echo '<p>' . esc_html__('You are not allowed to activate plugins!', 'newsomatic-news-post-generator') . '</p>';
        die;
    }
    $php_version_required = '7.4';
    $wp_version_required  = '2.7';
    
    if (version_compare(PHP_VERSION, $php_version_required, '<')) {
        deactivate_plugins(basename(__FILE__));
        echo '<p>' . sprintf(esc_html__('This plugin can not be activated because it requires a PHP version greater than %1$s. Please update your PHP version before you activate it.', 'newsomatic-news-post-generator'), $php_version_required) . '</p>';
        die;
    }
    
    if (version_compare($wp_version, $wp_version_required, '<')) {
        deactivate_plugins(basename(__FILE__));
        echo '<p>' . sprintf(esc_html__('This plugin can not be activated because it requires a WordPress version greater than %1$s. Please go to Dashboard -> Updates to get the latest version of WordPress.', 'newsomatic-news-post-generator'), $wp_version_required) . '</p>';
        die;
    }
}
function newsomatic_license_not_activated_notice() {
?>
    <div class="notice notice-error is-dismissible">
        <p><?php 
        $result = sprintf( wp_kses( __( 'The Newsomatic plugin\'s license is not activated. Please <a href="%s" target="_blank">activate the license</a> to use the plugin. You can get a new license, <a href="%s" target="_blank">here</a>.', 'newsomatic-news-post-generator'), array(  'a' => array( 'href' => array(), 'target' => array() ) ) ), admin_url('admin.php?page=newsomatic_admin_settings'), 'https://1.envato.market/newsomatic');
        echo $result; ?></p>
    </div>
<?php
}

function newsomatic_register_mysettings()
{
    newsomatic_cron_schedule();
    register_setting('newsomatic_option_group', 'newsomatic_Main_Settings');
    if(isset($_GET['newsomatic_page']))
    {
        $curent_page = $_GET["newsomatic_page"];
    }
    else
    {
        $curent_page = '';
    }
    $last_url = (newsomatic_isSecure() ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    if(stristr($last_url, 'newsomatic_items_panel') !== false)
    {
        $GLOBALS['wp_object_cache']->delete('newsomatic_rules_list', 'options');
        $all_rules = get_option('newsomatic_rules_list', array());
        if($all_rules === false || $all_rules == '')
        {
            $all_rules = array();
        }
        $rules_count = count($all_rules);
        $rules_per_page = get_option('newsomatic_posts_per_page', 10);
        $max_pages = ceil($rules_count/$rules_per_page);
        if($max_pages == 0)
        {
            $max_pages = 1;
        }
        if((!is_numeric($curent_page) || $curent_page > $max_pages || $curent_page <= 0))
        {
            if(stristr($last_url, 'newsomatic_page=') === false)
            {
                if(stristr($last_url, '?') === false)
                {
                    $last_url .= '?newsomatic_page=' . $max_pages;
                }
                else
                {
                    $last_url .= '&newsomatic_page=' . $max_pages;
                }
            }
            else
            {
                if(isset($_GET['newsomatic_page']))
                {
                    $curent_page = $_GET["newsomatic_page"];
                }
                else
                {
                    $curent_page = '';
                }
                if(is_numeric($curent_page))
                {
                    $last_url = str_replace('newsomatic_page=' . $curent_page, 'newsomatic_page=' . $max_pages, $last_url);
                }
                else
                {
                    if(stristr($last_url, '?') === false)
                    {
                        $last_url .= '?newsomatic_page=' . $max_pages;
                    }
                    else
                    {
                        $last_url .= '&newsomatic_page=' . $max_pages;
                    }
                }
            }
            newsomatic_redirect($last_url);
        }
    }
    elseif(stristr($last_url, 'newsomatic_all_panel') !== false)
    {
        $GLOBALS['wp_object_cache']->delete('newsomatic_all_list', 'options');
        $all_rules = get_option('newsomatic_all_list', array());
        if($all_rules === false || $all_rules == '')
        {
            $all_rules = array();
        }
        $rules_count = count($all_rules);
        $rules_per_page = get_option('newsomatic_posts_per_page', 10);
        $max_pages = ceil($rules_count/$rules_per_page);
        if($max_pages == 0)
        {
            $max_pages = 1;
        }
        if((!is_numeric($curent_page) || $curent_page > $max_pages || $curent_page <= 0))
        {
            if(stristr($last_url, 'newsomatic_page=') === false)
            {
                if(stristr($last_url, '?') === false)
                {
                    $last_url .= '?newsomatic_page=' . $max_pages;
                }
                else
                {
                    $last_url .= '&newsomatic_page=' . $max_pages;
                }
            }
            else
            {
                if(isset($_GET['newsomatic_page']))
                {
                    $curent_page = $_GET["newsomatic_page"];
                }
                else
                {
                    $curent_page = '';
                }
                if(is_numeric($curent_page))
                {
                    $last_url = str_replace('newsomatic_page=' . $curent_page, 'newsomatic_page=' . $max_pages, $last_url);
                }
                else
                {
                    if(stristr($last_url, '?') === false)
                    {
                        $last_url .= '?newsomatic_page=' . $max_pages;
                    }
                    else
                    {
                        $last_url .= '&newsomatic_page=' . $max_pages;
                    }
                }
            }
            newsomatic_redirect($last_url);
        }
    }
    if (is_multisite()) {
        if (!get_option('newsomatic_Main_Settings')) {
            newsomatic_activation_callback(TRUE);
        }
    }
}

function newsomatic_get_plugin_url()
{
    return plugins_url('', __FILE__);
}

function newsomatic_get_file_url($url)
{
    return esc_url_raw(newsomatic_get_plugin_url() . '/' . $url);
}

function newsomatic_admin_load_files()
{
    wp_register_style('newsomatic-browser-style', plugins_url('styles/newsomatic-browser.css', __FILE__), false, NEWSOMATIC_MAJOR_VERSION);
    wp_enqueue_style('newsomatic-browser-style');
    wp_register_style('newsomatic-custom-style', plugins_url('styles/coderevolution-style.css', __FILE__), false, NEWSOMATIC_MAJOR_VERSION);
    wp_enqueue_style('newsomatic-custom-style');
    wp_enqueue_script('jquery');
    wp_enqueue_script('media-upload');
    wp_enqueue_script('thickbox');
    wp_enqueue_style('thickbox');
    $rating_th = get_option('newsomatic_rating_trigger', false);
    if($rating_th === '1')
    {
        wp_enqueue_script( 'newsomatic-rate-notice-js', plugins_url("js/rating.js", __FILE__), false, NEWSOMATIC_MAJOR_VERSION);
        add_action( 'admin_notices', 'newsomatic_five_star_wp_rate_notice');
    }
}

add_action( 'wp_ajax_newsomatic_iframe', 'newsomatic_iframe_callback' );
function newsomatic_iframe_callback() {
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    if(!current_user_can('administrator')) die();
    $started = '%3Cs';
    $url = null;
    $use_phantom = isset($_GET['usephantom']) ? $_GET['usephantom'] : '' ;
    $url = $_GET['address'];

    $url = newsomatic_replaceSynergyShortcodes($url);
    if ( !$url ) {
        newsomatic_log_to_file('Empty URL value in Visual Selector.');
        echo 'Empty URL value in Visual Selector.';
        exit();
    }
    if($url == 'any' || substr($url, 0, 9) === "category-")
    {
        newsomatic_log_to_file('This feature is not supported when source is set to Any or to a specific News Category.');
        echo 'This feature is not supported when source is set to Any or to a specific News Category.';
        exit();
    }
    $xheaders = false;
    if (isset($newsomatic_Main_Settings['newsapi_active']) && trim($newsomatic_Main_Settings['newsapi_active']) == 'on')
    {
        $feed_uri = 'https://newsapi.org/v2/everything';
        $feed_uri .= '?apikey=' . trim($newsomatic_Main_Settings['app_id']) . '&pageSize=1';  
    }
    else
    {
        if(strlen(trim($newsomatic_Main_Settings['newsomatic_app_id'])) == 66)
        {
            $feed_uri = 'https://newsomaticapi.com/apis/news/v1/all';
            $feed_uri .= '?apikey=' . trim($newsomatic_Main_Settings['newsomatic_app_id']) . '&pageSize=1';  
        }
        elseif(strlen(trim($newsomatic_Main_Settings['newsomatic_app_id'])) == 50)
        {
            $feed_uri='https://newsomaticapi.p.rapidapi.com/all';
            $feed_uri .= '?apikey=' . trim($newsomatic_Main_Settings['newsomatic_app_id']) . '&pageSize=1';  
            $xheaders = array();
            $xheaders[] = "x-rapidapi-key: " . trim($newsomatic_Main_Settings['newsomatic_app_id']);
            $xheaders[] = "x-rapidapi-host: newsomaticapi.p.rapidapi.com";
            $xheaders[] = "content-type: application/octet-stream";
            $xheaders[] = "useQueryString: true";
        }
        else
        {
            newsomatic_log_to_file('You inserted an invalid NewsomaticAPI key');
            echo 'You inserted an invalid NewsomaticAPI key';
            exit();
        }
    }
    if (isset($newsomatic_Main_Settings['newsapi_active']) && trim($newsomatic_Main_Settings['newsapi_active']) == 'on')
    {
        $feed_uri .= '&sources=' . sanitize_title($url);
    }
    else
    {
        $feed_uri .= '&sources=' . $url;
    }
    $exec = newsomatic_get_web_page_api($feed_uri, $xheaders);
    if ($exec === FALSE) {
        newsomatic_log_to_file('Failed to exec curl to get News response ' . $feed_uri);
        echo 'Failed to exec curl to get News response ' . $feed_uri;
        exit();
    }
    $json  = json_decode($exec);
    if(isset($json->apicalls))
    {
        update_option('newsomaticapi_calls', esc_html($json->apicalls));
    }
    if(!isset($json->articles))
    {
        newsomatic_log_to_file('Unrecognized NewsomaticAPI response: ' . print_r($exec, true) . ' url: ' . $feed_uri);
        echo 'Unrecognized NewsomaticAPI response: ' . print_r($exec, true) . ' url: ' . $feed_uri;
        exit();
    }
    $items = $json->articles;
    if(count($items) == 0)
    {
        newsomatic_log_to_file('No items found for news source: ' . print_r($url, true));
        echo 'No items found for news source: ' . print_r($url, true);
        exit();
    }
    if (isset($newsomatic_Main_Settings['user_agent']) && $newsomatic_Main_Settings['user_agent'] != '') {
        $customUA = $newsomatic_Main_Settings['user_agent'];
    }
    else
    {
        $customUA = 'random';
    }
    if($customUA == 'random')
    {
        $customUA = newsomatic_get_random_user_agent();
    }
    $fitem_url = '';
    foreach ($items as $item)
    {
        if($item->url != null)
        {
            $fitem_url = $item->url;
        }
    }
    if($fitem_url == '')
    {
        newsomatic_log_to_file('No URLs found for news source: ' . print_r($url, true));
        echo 'No URLs found for news source: ' . print_r($url, true);
        exit();
    }
    $htmlcontent = '';
    $got_phantom = false;
    if($use_phantom == '1')
    {
        $htmlcontent = newsomatic_get_page_PhantomJS($fitem_url, '', $customUA, true);
        if($htmlcontent !== false)
        {
            $got_phantom = true;
        }
    }
    elseif($use_phantom == '2')
    {
        $htmlcontent = newsomatic_get_page_Puppeteer($fitem_url, '', $customUA, true, '');
        if($htmlcontent !== false)
        {
            $got_phantom = true;
        }
    }
    elseif($use_phantom == '4')
    {
        $htmlcontent = newsomatic_get_page_PuppeteerAPI($fitem_url, '', $customUA, true, '', '');
        if($htmlcontent !== false)
        {
            $got_phantom = true;
        }
    }
    elseif($use_phantom == '5')
    {
        $htmlcontent = newsomatic_get_page_TorAPI($fitem_url, '', $customUA, true, '', '');
        if($htmlcontent !== false)
        {
            $got_phantom = true;
        }
    }
    elseif($use_phantom == '6')
    {
        $htmlcontent = newsomatic_get_page_PhantomJSAPI($fitem_url, '', $customUA, true, '', '');
        if($htmlcontent !== false)
        {
            $got_phantom = true;
        }
    }
    if($got_phantom === false)
    {
        $htmlcontent = newsomatic_get_web_page($fitem_url);
    }
    if($htmlcontent === FALSE)
    {
        newsomatic_log_to_file('Failed to download webpage when using Visual Selector: ' . print_r($fitem_url, true));
        echo 'Failed to download webpage when using Visual Selector: ' . print_r($fitem_url, true);
        exit();
    }
    if ( !preg_match('/<base\s/i', $htmlcontent) ) {
        $base = '<base href="' . $fitem_url . '">';
        $htmlcontent = str_replace('</head>', $base . '</head>', $htmlcontent);
    }
    $htmlcontent = preg_replace('/src="\/\/(.*?)"/', 'src="https://$1"', $htmlcontent);
    $htmlcontent = preg_replace('/href="\/\/(.*?)"/', 'href="https://$1"', $htmlcontent);
    if ( preg_match('!^https?://[^/]+!', $fitem_url, $matches) ) {
        $stem = $matches[0];
        $htmlcontent = preg_replace('!(\s)(src|href)(=")\/!i', "\\1\\2\\3$stem/", $htmlcontent);
        $htmlcontent = preg_replace('!(\s)(url)(\s*\(\s*["\']?)\/!i', "\\1\\2\\3$stem/", $htmlcontent);
    }
    $url_changed_prompt = '';
    if($fitem_url != '')
    {
        $url_changed_prompt = urldecode('%3Cscript%3E') . 'alert("' . esc_html__('Showing URL: ', 'newsomatic-news-post-generator') . esc_url_raw($fitem_url) . '");' . urldecode('%3C%2Fscript%3E');
    }
    $htmlcontent = preg_replace('{<script[\s\S]*?\/\s?script>}s', '', $htmlcontent);
    echo $htmlcontent . urldecode($started . "tyle%3E%5Bclass~%3Dhighlight%5D%7Bbox-shadow%3Ainset%200%200%200%201000px%20rgba%28255%2C0%2C0%2C.5%29%20%21important%3B%7D%5Bclass~%3Dhighlight%5D%7Boutline%3A.010416667in%20solid%20red%20%21important%3B%7D") . urldecode("%3C%2Fstyle%3E") . $url_changed_prompt;
    die();
}
add_action('wp_ajax_newsomatic-five-star-wp-rate', 'newsomatic_rate_clicked');
function newsomatic_rate_clicked()
{
    update_option('newsomatic_rating_trigger', '2');
    die();
}
function newsomatic_five_star_wp_rate_notice()
{
?>
<div class="notice notice-success is-dismissible newsomatic-five-star-wp-rate-action">
<div>
	<?php echo sprintf( wp_kses( __( 'Hey, I noticed you created quite a lot of posts using the Newsomatic plugin - that\'s really awesome! Could you please do me a BIG favor and give it <b><a href=\'%s\' target=\'_blank\'>a 5-star rating on CodeCanyon</a></b>? Just to help us spread the word and boost our motivation. Thank you!', 'newsomatic-news-post-generator'), array(  'b' => array(), 'a' => array( 'href' => array(), 'target' => array() ) ) ), esc_url_raw('//codecanyon.net/user/coderevolution/portfolio') ); ?>
	<br/>
	<strong><em>Szabi Kisded - CodeRevolution</em></strong>
</div>
<ul>
	<li><a
	       href="//codecanyon.net/downloads" target="_blank"><?php echo esc_html__('Yes, you deserve it!', 'newsomatic-news-post-generator');?></a>
	</li>
	<li><a href="#"><?php echo esc_html__('I already did.', 'newsomatic-news-post-generator');?></a></li>
	<li><a href="#"><?php echo esc_html__('No, sorry.', 'newsomatic-news-post-generator');?></a></li>
</ul>
</div>
<?php
}

function newsomatic_random_sentence_generator($first = true)
{
    $current_lang = apply_filters( 'wpml_current_language', NULL );
    do_action( 'wpml_switch_language', $current_lang );
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    if ($first == false) {
        $r_sentences = $newsomatic_Main_Settings['sentence_list2'];
    } else {
        $r_sentences = $newsomatic_Main_Settings['sentence_list'];
    }
    $r_variables = $newsomatic_Main_Settings['variable_list'];
    $r_sentences = trim($r_sentences);
    $r_variables = trim($r_variables, ';');
    $r_variables = trim($r_variables);
    $r_sentences = str_replace("\r\n", "\n", $r_sentences);
    $r_sentences = str_replace("\r", "\n", $r_sentences);
    $r_sentences = explode("\n", $r_sentences);
    $r_variables = str_replace("\r\n", "\n", $r_variables);
    $r_variables = str_replace("\r", "\n", $r_variables);
    $r_variables = explode("\n", $r_variables);
    $r_vars      = array();
    for ($x = 0; $x < count($r_variables); $x++) {
        $var = explode("=>", trim($r_variables[$x]));
        if (isset($var[1])) {
            $key          = strtolower(trim($var[0]));
            $words        = explode(";", trim($var[1]));
            $r_vars[$key] = $words;
        }
    }
    $max_s    = count($r_sentences) - 1;
    $rand_s   = rand(0, $max_s);
    $sentence = $r_sentences[$rand_s];
    $sentence = str_replace(' ,', ',', ucfirst(newsomatic_replace_words($sentence, $r_vars)));
    $sentence = str_replace(' .', '.', $sentence);
    $sentence = str_replace(' !', '!', $sentence);
    $sentence = str_replace(' ?', '?', $sentence);
    $sentence = trim($sentence);
    return $sentence;
}

function newsomatic_get_word($key, $r_vars)
{
    if (isset($r_vars[$key])) {
        
        $words  = $r_vars[$key];
        $w_max  = count($words) - 1;
        $w_rand = rand(0, $w_max);
        return newsomatic_replace_words(trim($words[$w_rand]), $r_vars);
    } else {
        return "";
    }
    
}

function newsomatic_replace_words($sentence, $r_vars)
{
    
    if (str_replace('%', '', $sentence) == $sentence)
        return $sentence;
    
    $words = explode(" ", $sentence);
    
    $new_sentence = array();
    for ($w = 0; $w < count($words); $w++) {
        
        $word = trim($words[$w]);
        
        if ($word != '') {
            if (preg_match('/^%([^%\n]*)$/', $word, $m)) {
                $varkey         = trim($m[1]);
                $new_sentence[] = newsomatic_get_word($varkey, $r_vars);
            } else {
                $new_sentence[] = $word;
            }
        }
    }
    return implode(" ", $new_sentence);
}

function newsomatic_fetch_url($url){
    $url = "https://translate.google.com/translate?hl=en&ie=UTF8&prev=_t&sl=ar&tl=en&u=".urlencode($url);
    $exec = newsomatic_get_web_page($url);
    if($exec === false)
    {
        return false;
    }
	preg_match('{(https://translate.googleusercontent.com.*?)"}', $exec, $get_urls);
	$get_url = $get_urls[1];
	if(!stristr($get_url, '_p')){
		return false;
    }
    $exec = newsomatic_get_web_page($get_url);
    if($exec === false)
    {
        return false;
    }
	preg_match('{URL=(.*?)"}', $exec ,$final_url);
	$get_url2 = html_entity_decode( $final_url[1] );
	if(!stristr($get_url2, '_c')){
		return false;
	}
    $exec = newsomatic_get_web_page($get_url2);
	if(trim($exec) == ''){
		return false;
    }
    $exec = str_replace('id=article-content"', 'id="article-content"', $exec);
    $exec = str_replace('article-content>','article-content">',$exec);
	$exec = preg_replace('{<span class="google-src-text.*?>.*?</span>}', "", $exec);
    $exec = preg_replace('{<span class="notranslate.*?>(.*?)</span>}', "$1", $exec);
    
    return $exec;
}

class Newsomatic_keywords{ 
    public static $charset = 'UTF-8';
    public static $banned_words = array('adsbygoogle', 'able', 'about', 'above', 'act', 'add', 'afraid', 'after', 'again', 'against', 'age', 'ago', 'agree', 'all', 'almost', 'alone', 'along', 'already', 'also', 'although', 'always', 'am', 'amount', 'an', 'and', 'anger', 'angry', 'animal', 'another', 'answer', 'any', 'appear', 'apple', 'are', 'arrive', 'arm', 'arms', 'around', 'arrive', 'as', 'ask', 'at', 'attempt', 'aunt', 'away', 'back', 'bad', 'bag', 'bay', 'be', 'became', 'because', 'become', 'been', 'before', 'began', 'begin', 'behind', 'being', 'bell', 'belong', 'below', 'beside', 'best', 'better', 'between', 'beyond', 'big', 'body', 'bone', 'born', 'borrow', 'both', 'bottom', 'box', 'boy', 'break', 'bring', 'brought', 'bug', 'built', 'busy', 'but', 'buy', 'by', 'call', 'came', 'can', 'cause', 'choose', 'close', 'close', 'consider', 'come', 'consider', 'considerable', 'contain', 'continue', 'could', 'cry', 'cut', 'dare', 'dark', 'deal', 'dear', 'decide', 'deep', 'did', 'die', 'do', 'does', 'dog', 'done', 'doubt', 'down', 'during', 'each', 'ear', 'early', 'eat', 'effort', 'either', 'else', 'end', 'enjoy', 'enough', 'enter', 'even', 'ever', 'every', 'except', 'expect', 'explain', 'fail', 'fall', 'far', 'fat', 'favor', 'fear', 'feel', 'feet', 'fell', 'felt', 'few', 'fill', 'find', 'fit', 'fly', 'follow', 'for', 'forever', 'forget', 'from', 'front', 'gave', 'get', 'gives', 'goes', 'gone', 'good', 'got', 'gray', 'great', 'green', 'grew', 'grow', 'guess', 'had', 'half', 'hang', 'happen', 'has', 'hat', 'have', 'he', 'hear', 'heard', 'held', 'hello', 'help', 'her', 'here', 'hers', 'high', 'hill', 'him', 'his', 'hit', 'hold', 'hot', 'how', 'however', 'I', 'if', 'ill', 'in', 'indeed', 'instead', 'into', 'iron', 'is', 'it', 'its', 'just', 'keep', 'kept', 'knew', 'know', 'known', 'late', 'least', 'led', 'left', 'lend', 'less', 'let', 'like', 'likely', 'likr', 'lone', 'long', 'look', 'lot', 'make', 'many', 'may', 'me', 'mean', 'met', 'might', 'mile', 'mine', 'moon', 'more', 'most', 'move', 'much', 'must', 'my', 'near', 'nearly', 'necessary', 'neither', 'never', 'next', 'no', 'none', 'nor', 'not', 'note', 'nothing', 'now', 'number', 'of', 'off', 'often', 'oh', 'on', 'once', 'only', 'or', 'other', 'ought', 'our', 'out', 'please', 'prepare', 'probable', 'pull', 'pure', 'push', 'put', 'raise', 'ran', 'rather', 'reach', 'realize', 'reply', 'require', 'rest', 'run', 'said', 'same', 'sat', 'saw', 'say', 'see', 'seem', 'seen', 'self', 'sell', 'sent', 'separate', 'set', 'shall', 'she', 'should', 'side', 'sign', 'since', 'so', 'sold', 'some', 'soon', 'sorry', 'stay', 'step', 'stick', 'still', 'stood', 'such', 'sudden', 'suppose', 'take', 'taken', 'talk', 'tall', 'tell', 'ten', 'than', 'thank', 'that', 'the', 'their', 'them', 'then', 'there', 'therefore', 'these', 'they', 'this', 'those', 'though', 'through', 'till', 'to', 'today', 'told', 'tomorrow', 'too', 'took', 'tore', 'tought', 'toward', 'tried', 'tries', 'trust', 'try', 'turn', 'two', 'under', 'until', 'up', 'upon', 'us', 'use', 'usual', 'various', 'verb', 'very', 'visit', 'want', 'was', 'we', 'well', 'went', 'were', 'what', 'when', 'where', 'whether', 'which', 'while', 'white', 'who', 'whom', 'whose', 'why', 'will', 'with', 'within', 'without', 'would', 'yes', 'yet', 'you', 'young', 'your', 'br', 'img', 'p','lt', 'gt', 'quot', 'copy');
    public static $min_word_length = 4;
    
    public static function text($text, $length = 160)
    {
        return self::limit_chars(self::clean($text), $length,'',TRUE);
    } 

    public static function keywords($text, $max_keys = 3)
    {
        include (dirname(__FILE__) . "/res/diacritics.php");
        $wordcount = array_count_values(str_word_count(self::clean($text), 1, $diacritics));
        foreach ($wordcount as $key => $value) 
        {
            if ( (strlen($key)<= self::$min_word_length) OR in_array($key, self::$banned_words))
                unset($wordcount[$key]);
        }
        uasort($wordcount, array(self::class, 'cmp'));
        $wordcount = array_slice($wordcount,0, $max_keys);
        return implode(' ', array_keys($wordcount));
    } 

    private static function clean($text)
    { 
        $text = html_entity_decode($text,ENT_QUOTES,self::$charset);
        $text = strip_tags($text);
        $text = preg_replace('/\s\s+/', ' ', $text);
        $text = str_replace (array('\r\n', '\n', '+'), ',', $text);
        return trim($text); 
    } 

    private static function cmp($a, $b) 
    {
        if ($a == $b) return 0; 

        return ($a < $b) ? 1 : -1; 
    } 

    private static function limit_chars($str, $limit = 100, $end_char = NULL, $preserve_words = FALSE)
    {
        $end_char = ($end_char === NULL) ? '&#8230;' : $end_char;
        $limit = (int) $limit;
        if (trim($str) === '' OR strlen($str) <= $limit)
            return $str;
        if ($limit <= 0)
            return $end_char;
        if ($preserve_words === FALSE)
            return rtrim(substr($str, 0, $limit)).$end_char;
        if ( ! preg_match('/^.{0,'.$limit.'}\s/us', $str, $matches))
            return $end_char;
        return rtrim($matches[0]).((strlen($matches[0]) === strlen($str)) ? '' : $end_char);
    }
} 
class Newsomatic_Spintax
{
    public function process($text)
    {
        return stripslashes(preg_replace_callback(
            '/\{(((?>[^\{\}]+)|(?R))*)\}/x',
            array($this, 'replace'),
            preg_quote($text)
        ));
    }
    public function replace($text)
    {
        $text = $this->process($text[1]);
        $parts = explode('|', $text);
        return $parts[array_rand($parts)];
    }
}

function newsomatic_get_api_data( $query_string, $query_string_title, $sources, $country, $language, $sort_results, $only_domains, $remove_domains, $from_date, $to_date, $max_results, $newsomatic_caching_time = 1, $exclude_country = array() )
{
    $transient = '';
    if(is_array($exclude_country))
    {
        $exctrans = implode(',', $exclude_country);
    }
    else
    {
        $exctrans = $exclude_country;
    }
    $transient = "newsomatic_api_cached_data_" . sanitize_title($query_string) . sanitize_title($query_string_title) . sanitize_title($sources) . sanitize_title($country) . sanitize_title($language) . sanitize_title($sort_results) . sanitize_title($only_domains) . sanitize_title($remove_domains) . sanitize_title($from_date) . sanitize_title($to_date) . sanitize_title($exctrans);
    $xheaders = false;
    $newsomatic_Main_Settings = get_option('newsomatic_Main_Settings', false);
    $api_data = get_transient( $transient );
    if ( ( false === $api_data ) or ( empty( $api_data ) ) )
    {
        if (isset($newsomatic_Main_Settings['newsapi_active']) && trim($newsomatic_Main_Settings['newsapi_active']) == 'on')
        {
            $feed_uri = 'https://newsapi.org/v2/everything';
            $feed_uri .= '?apikey=' . trim($newsomatic_Main_Settings['app_id']) . '&pageSize=' . $max_results;  
        }
        else
        {
            if(strlen(trim($newsomatic_Main_Settings['newsomatic_app_id'])) == 66)
            {
                $feed_uri = 'https://newsomaticapi.com/apis/news/v1/all';
                $feed_uri .= '?apikey=' . trim($newsomatic_Main_Settings['newsomatic_app_id']) . '&pageSize=' . $max_results;  
            }
            elseif(strlen(trim($newsomatic_Main_Settings['newsomatic_app_id'])) == 50)
            {
                $feed_uri='https://newsomaticapi.p.rapidapi.com/all';
                $feed_uri .= '?apikey=' . trim($newsomatic_Main_Settings['newsomatic_app_id']) . '&pageSize=' . $max_results;  
                $xheaders = array();
                $xheaders[] = "x-rapidapi-key: " . trim($newsomatic_Main_Settings['newsomatic_app_id']);
                $xheaders[] = "x-rapidapi-host: newsomaticapi.p.rapidapi.com";
                $xheaders[] = "content-type: application/octet-stream";
                $xheaders[] = "useQueryString: true";
            }
            else
            {
                newsomatic_log_to_file('You inserted an invalid NewsomaticAPI key');
                return false;
            }
        }
        if($query_string != '')
        {
            $query_string = str_replace('\'', '"', $query_string);
            $query_string = urlencode($query_string);
            $feed_uri .= '&q=' . $query_string;
        }
        if($query_string_title != '')
        {
            $query_string_title = str_replace('\'', '"', $query_string_title);
            $query_string_title = urlencode($query_string_title);
            $feed_uri .= '&qInTitle=' . $query_string_title;
        }
        if($sources != 'any')
        {
            if (isset($newsomatic_Main_Settings['newsapi_active']) && trim($newsomatic_Main_Settings['newsapi_active']) == 'on')
            {
                $feed_uri .= '&sources=' . sanitize_title($sources);
            }
            else
            {
                $feed_uri .= '&sources=' . $sources;
            }
        }
        if($country != '' && $country != 'all' && ($sources == '' || substr($sources, 0, 9) === "category-" || $sources == 'any'))
        {
            $feed_uri .= '&country=' . $country;
        }
        if($language != '' && $language != 'all')
        {
            $feed_uri .= '&language=' . $language;
        }
        if($sort_results != '' && $sort_results != 'publishedAt')
        {
            $feed_uri .= '&sortBy=' . $sort_results;
        }
        if($only_domains != '')
        {
            $only_domains = str_replace(' ', '', $only_domains);
            $feed_uri .= '&domains=' . $only_domains;
        }
        if($remove_domains != '')
        {
            $remove_domains = str_replace(' ', '', $remove_domains);
            $feed_uri .= '&excludeDomains=' . $remove_domains;
        }
        if($exclude_country != '' && is_array($exclude_country))
        {
            $country_exclusion = '';
            foreach($exclude_country as $excountry)
            {
                if($excountry != 'no_exclude')
                {
                    $country_exclusion .= ',' . trim($excountry);
                }
            }
            $country_exclusion = trim($country_exclusion, ',');
            if($country_exclusion != '')
            {
                $feed_uri .= '&excludeCountry=' . $country_exclusion;
            }
        }
        if($from_date != '')
        {
            $from_time = strtotime($from_date);
            if($from_time !== false)
            {
                $the_date = date("Y-m-d\TH:i:s", $from_time);
                if($the_date !== false)
                {
                    $feed_uri .= '&from=' . $the_date;
                }
            }
        }
        if($to_date != '')
        {
            $to_time = strtotime($to_date);
            if($to_time !== false)
            {
                $the_date = date("Y-m-d\TH:i:s", $to_time);
                if($the_date !== false)
                {
                    $feed_uri .= '&to=' . $the_date;
                }
            }
        }
        $exec = newsomatic_get_web_page_api($feed_uri, $xheaders);
        if ($exec === FALSE) 
        {
            newsomatic_log_to_file('Failed to get shortcode results for: '. $feed_uri);
            return false;
        }
        $api_data  = json_decode($exec, true);
        if(isset($api_data['apicalls']))
        {
            update_option('newsomaticapi_calls', esc_html($api_data['apicalls']));
        }
        if(!isset($api_data['articles']))
        {
            newsomatic_log_to_file('Incorrect API response for: '. $feed_uri);
            return false;
        }
        delete_transient( $transient );
        set_transient( $transient , $api_data, 60 * $newsomatic_caching_time );
    }
    return $api_data;
}
add_shortcode( 'newsomatic-news-aggregator', 'newsomatic_load_shortcode_view' );
function newsomatic_load_shortcode_view($atts)
{
    if ( is_admin() ) 
    {
        return;
    }
    $atts = shortcode_atts( array(
		'max_news_number'                => 9,
		'layout'                         => 'ticker',
		'grid_columns'                   => 3,
		'title_max_length'               => 6,
		'desciption_max_length'          => 18,
		'display_news_source'            => 'false',
		'display_date'                   => 'false',
		'enable_rtl'                     => 'false',
		'enable_font_awesome'            => 'false',
		'show_description'               => 'true',
		'show_source_name'               => 'false',
		'country'                        => '',
		'exclude_country'                => '',
		'language'                       => '',
		'sources'                        => 'CNET',
		'query_string'                   => '',
		'query_string_title'             => '',
		'sort_results'                   => '',
		'only_domains'                   => '',
		'remove_domains'                 => '',
		'from_date'                      => '',
		'to_date'                        => '',
		'ticker_type'                    => 'marquee',
        'ticker_text_color'              => 'red',
        'ticker_label_color'             => 'white',
        'ticker_color'                   => 'red',
		'caching_time'                   => 360
	), $atts, 'newsomatic-news-aggregator' );
    $newsomatic_news_number         = sanitize_text_field( $atts['max_news_number'] );
	$newsomatic_layout              = sanitize_text_field( $atts['layout'] );
	$newsomatic_grid_columns        = sanitize_text_field( $atts['grid_columns'] );
	$newsomatic_title_length        = sanitize_text_field( $atts['title_max_length'] );
	$newsomatic_desc_length         = sanitize_text_field( $atts['desciption_max_length'] );
	$newsomatic_display_news_source = sanitize_text_field( $atts['display_news_source'] );
	$newsomatic_display_date        = sanitize_text_field( $atts['display_date'] );
	$newsomatic_enable_rtl          = sanitize_text_field( $atts['enable_rtl'] );
	$newsomatic_ticker_type         = sanitize_text_field( $atts['ticker_type'] );
	$country                        = sanitize_text_field( $atts['country'] );
	$exclude_country                = sanitize_text_field( $atts['exclude_country'] );
	$language                       = sanitize_text_field( $atts['language'] );
	$sources                        = sanitize_text_field( $atts['sources'] );
	$query_string                   = sanitize_text_field( $atts['query_string'] );
	$query_string_title             = sanitize_text_field( $atts['query_string_title'] );
	$sort_results                   = sanitize_text_field( $atts['sort_results'] );
	$only_domains                   = sanitize_text_field( $atts['only_domains'] );
	$remove_domains                 = sanitize_text_field( $atts['remove_domains'] );
	$from_date                      = sanitize_text_field( $atts['from_date'] );
	$to_date                        = sanitize_text_field( $atts['to_date'] );
	$newsomatic_caching_time        = sanitize_text_field( $atts['caching_time'] );
	$enable_font_awesome            = sanitize_text_field( $atts['enable_font_awesome'] );
	$show_description               = sanitize_text_field( $atts['show_description'] );
	$show_source_name               = sanitize_text_field( $atts['show_source_name'] );
	$ticker_text_color              = sanitize_text_field( $atts['ticker_text_color'] );
	$ticker_label_color             = sanitize_text_field( $atts['ticker_label_color'] );
	$ticker_color                   = sanitize_text_field( $atts['ticker_color'] );
    $exclude_country = explode(',', $exclude_country);
    $exclude_country = array_map('trim', $exclude_country);
    if($enable_font_awesome == 'true')
    {
        $enable_font_awesome = true;
    }
    else
    {
        $enable_font_awesome = false;
    }
    if($show_description == 'true')
    {
        $show_description = true;
    }
    else
    {
        $show_description = false;
    }
    if($newsomatic_display_news_source == 'true')
    {
        $newsomatic_display_news_source = true;
    }
    else
    {
        $newsomatic_display_news_source = false;
    }
    if($newsomatic_display_date == 'true')
    {
        $newsomatic_display_date = true;
    }
    else
    {
        $newsomatic_display_date = false;
    }
    if($newsomatic_enable_rtl == 'true')
    {
        $newsomatic_enable_rtl = true;
    }
    else
    {
        $newsomatic_enable_rtl = false;
    }
    if($show_source_name == 'true')
    {
        $show_source_name = true;
    }
    else
    {
        $show_source_name = false;
    }
    if($enable_font_awesome == true)
    {
        wp_enqueue_style(
            'newsomatic-font-awesome', 
            plugins_url( 'res/shortcode/assets/', __FILE__ ) .'css/font-awesome/css/font-awesome.min.css',
            array(),
            NEWSOMATIC_MAJOR_VERSION,
            FALSE
        );
    }
    wp_enqueue_style( 
        'newsomatic-shortcode-front', 
        plugins_url( 'res/shortcode/assets/', __FILE__ ) . 'css/newsomatic-front.css', 
        array(), 
        NEWSOMATIC_MAJOR_VERSION
    );
    $reg_css_code = '.newsomatic-main-wrapper{grid-template-columns: repeat(' . esc_html__( $newsomatic_grid_columns ) . ', 1fr);}.acme-news-ticker {border: 1px solid ' . $ticker_color . ';}.acme-news-ticker-label {background:' . $ticker_color . ';color:' . $ticker_label_color . ';}.acme-news-ticker-box ul li a {color:' . $ticker_text_color . ';}';
    if ( $newsomatic_enable_rtl ) 
    {
        $reg_css_code .= '@media(min-width: 768px){.acme-news-ticker{padding-left: 15px;padding-right: 0;}.acme-news-ticker-label{float: right;margin-left: 15px;margin-right: 0;}.acme-news-ticker-box ul li {border: 0px solid #000;text-align: right;}}';
    }
    wp_add_inline_style( 'newsomatic-shortcode-front', $reg_css_code );
    if ( ! wp_script_is( 'jquery' ) ) 
    {
        wp_enqueue_script('jquery');
    }
    if($newsomatic_layout == 'ticker')
    {
        wp_enqueue_script(
            'newsomatic-acmeticker', 
            plugins_url( 'res/shortcode/assets/', __FILE__ ) . 'js/acmeticker.min.js', 
            ['jquery'], 
            NEWSOMATIC_MAJOR_VERSION, 
            true 
        );
        wp_enqueue_script(
            'newsomatic-shortcode-front', 
            plugins_url( 'res/shortcode/assets/', __FILE__ ) . 'js/newsomatic-front.js', 
            ['jquery'], 
            NEWSOMATIC_MAJOR_VERSION, 
            true 
        );
    }
    $output = '';
    ob_start();
    include plugin_dir_path( __FILE__ ) . 'res/shortcode/front/newsomatic-front-view.php';
    $output .= ob_get_clean();
    return $output;
}
?>