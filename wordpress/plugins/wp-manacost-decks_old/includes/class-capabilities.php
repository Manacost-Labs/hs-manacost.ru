<?php
/**
 * Central capability registry for Manacost: Decks.
 */

if (!defined('ABSPATH')) {
    exit;
}

class Unified_HS_Capabilities {

    const GROUP = 'manacost-decks';

    const CAP_MANAGE_DECKS = 'edit_hs_decks';
    const CAP_CREATE_DECKS = 'create_hs_decks';
    const CAP_MANAGE_SETTINGS = 'manage_hs_deck_settings';
    const CAP_MANAGE_ANNOUNCEMENTS = 'manage_hs_deck_announcements';
    const CAP_MANAGE_LAYOUT = 'manage_hs_deck_layout';
    const CAP_MANAGE_TAGS = 'manage_hs_deck_tags';
    const CAP_MANAGE_TERMS = 'manage_hs_deck_terms';
    const CAP_EDIT_TERMS = 'edit_hs_deck_terms';
    const CAP_DELETE_TERMS = 'delete_hs_deck_terms';
    const CAP_ASSIGN_TERMS = 'assign_hs_deck_terms';
    const CAP_VIEW_SHORTCODES = 'view_hs_deck_shortcodes';
    const CAP_VIEW_STATS = 'view_hs_deck_stats';
    const CAP_VIEW_LOGS = 'view_hs_deck_logs';
    const CAP_CLEAR_LOGS = 'clear_hs_deck_logs';
    const CAP_VIEW_ACTIVITY_LOG = 'view_hs_deck_activity_log';
    const CAP_CLEAR_ACTIVITY_LOG = 'clear_hs_deck_activity_log';
    const CAP_IMPORT_DECKS = 'import_hs_decks';

    public static function init() {
        add_action('members_register_cap_groups', array(__CLASS__, 'register_members_cap_group'));
        add_action('members_register_caps', array(__CLASS__, 'register_members_caps'));
    }

    public static function post_type_capabilities() {
        return array(
            'edit_post'              => 'edit_hs_deck',
            'read_post'              => 'read_hs_deck',
            'delete_post'            => 'delete_hs_deck',
            'edit_posts'             => 'edit_hs_decks',
            'edit_others_posts'      => 'edit_others_hs_decks',
            'delete_posts'           => 'delete_hs_decks',
            'publish_posts'          => 'publish_hs_decks',
            'read_private_posts'     => 'read_private_hs_decks',
            'read'                   => 'read',
            'delete_private_posts'   => 'delete_private_hs_decks',
            'delete_published_posts' => 'delete_published_hs_decks',
            'delete_others_posts'    => 'delete_others_hs_decks',
            'edit_private_posts'     => 'edit_private_hs_decks',
            'edit_published_posts'   => 'edit_published_hs_decks',
            'create_posts'           => self::CAP_CREATE_DECKS,
        );
    }

    public static function taxonomy_capabilities() {
        return array(
            'manage_terms' => self::CAP_MANAGE_TERMS,
            'edit_terms'   => self::CAP_EDIT_TERMS,
            'delete_terms' => self::CAP_DELETE_TERMS,
            'assign_terms' => self::CAP_ASSIGN_TERMS,
        );
    }

    public static function all_capability_labels() {
        return array(
            'read_hs_deck'                 => __('Читать отдельную колоду', 'unified-hs-plugins'),
            'edit_hs_deck'                 => __('Редактировать отдельную колоду', 'unified-hs-plugins'),
            'delete_hs_deck'               => __('Удалять отдельную колоду', 'unified-hs-plugins'),
            'edit_hs_decks'                => __('Просматривать и редактировать свои колоды', 'unified-hs-plugins'),
            'edit_others_hs_decks'         => __('Редактировать чужие колоды', 'unified-hs-plugins'),
            'publish_hs_decks'             => __('Публиковать колоды', 'unified-hs-plugins'),
            'read_private_hs_decks'        => __('Читать приватные колоды', 'unified-hs-plugins'),
            'delete_hs_decks'              => __('Удалять свои колоды', 'unified-hs-plugins'),
            'delete_private_hs_decks'      => __('Удалять приватные колоды', 'unified-hs-plugins'),
            'delete_published_hs_decks'    => __('Удалять опубликованные колоды', 'unified-hs-plugins'),
            'delete_others_hs_decks'       => __('Удалять чужие колоды', 'unified-hs-plugins'),
            'edit_private_hs_decks'        => __('Редактировать приватные колоды', 'unified-hs-plugins'),
            'edit_published_hs_decks'      => __('Редактировать опубликованные колоды', 'unified-hs-plugins'),
            self::CAP_CREATE_DECKS         => __('Добавлять новые колоды', 'unified-hs-plugins'),
            self::CAP_MANAGE_TERMS         => __('Управлять классами и режимами колод', 'unified-hs-plugins'),
            self::CAP_EDIT_TERMS           => __('Редактировать классы и режимы колод', 'unified-hs-plugins'),
            self::CAP_DELETE_TERMS         => __('Удалять классы и режимы колод', 'unified-hs-plugins'),
            self::CAP_ASSIGN_TERMS         => __('Назначать классы и режимы колод', 'unified-hs-plugins'),
            self::CAP_MANAGE_SETTINGS      => __('Управлять настройками и текстом помощи', 'unified-hs-plugins'),
            self::CAP_MANAGE_ANNOUNCEMENTS => __('Управлять объявлениями колод', 'unified-hs-plugins'),
            self::CAP_MANAGE_LAYOUT        => __('Управлять компоновкой страниц колод', 'unified-hs-plugins'),
            self::CAP_MANAGE_TAGS          => __('Управлять тегами колод', 'unified-hs-plugins'),
            self::CAP_VIEW_SHORTCODES      => __('Смотреть справку по шорткодам', 'unified-hs-plugins'),
            self::CAP_VIEW_STATS           => __('Смотреть статистику колод и архетипов', 'unified-hs-plugins'),
            self::CAP_VIEW_LOGS            => __('Смотреть логи импорта колод', 'unified-hs-plugins'),
            self::CAP_CLEAR_LOGS           => __('Очищать логи импорта колод', 'unified-hs-plugins'),
            self::CAP_VIEW_ACTIVITY_LOG    => __('Смотреть историю действий с колодами', 'unified-hs-plugins'),
            self::CAP_CLEAR_ACTIVITY_LOG   => __('Очищать историю действий с колодами', 'unified-hs-plugins'),
            self::CAP_IMPORT_DECKS         => __('Импортировать колоды и статистику через REST, CSV и JSON', 'unified-hs-plugins'),
        );
    }

    public static function administrator_capabilities() {
        return array_keys(self::all_capability_labels());
    }

    public static function add_caps_to_administrator() {
        $role = get_role('administrator');
        if (!$role) {
            return;
        }

        foreach (self::administrator_capabilities() as $capability) {
            $role->add_cap($capability);
        }
    }

    public static function register_members_cap_group() {
        if (!function_exists('members_register_cap_group')) {
            return;
        }

        members_register_cap_group(self::GROUP, array(
            'label'    => __('Manacost: Decks', 'unified-hs-plugins'),
            'caps'     => array_keys(self::all_capability_labels()),
            'icon'     => 'dashicons-database',
            'priority' => 10,
        ));
    }

    public static function register_members_caps() {
        if (!function_exists('members_register_cap')) {
            return;
        }

        foreach (self::all_capability_labels() as $capability => $label) {
            members_register_cap($capability, array(
                'label' => $label,
                'group' => self::GROUP,
            ));
        }
    }
}
