<?php

namespace Polylang_CLI\Commands;

if ( ! class_exists( 'Polylang_CLI\Commands\MenuCommand' ) ) {

/**
 * Manage the WP Nav Menus with Polylang.
 */
class MenuCommand extends BaseCommand {

    /**
     * Create a new menu for each language, and assign it to a single location
     * (Polylang will internally store which menu belongs to which language).
     *
     * ## OPTIONS
     *
     * <menu-name>
     * : A descriptive name for the menu.
     *
     * <location>
     * : Location’s slug (e.g. "primary").
     *
     * [--porcelain]
     * : Output just the new menu IDs.
     *
     * ## EXAMPLES
     *
     *     # Create multi-language menus for "Primary"
     *     $ wp pll menu create "Main Menu" primary
     *
     *     # Output created menu IDs only
     *     $ wp pll menu create "Main Menu" primary --porcelain
     *     21 22 23
     *
     * @when init
     */
    public function create( $args, $assoc_args ) {

        // For example: $menu_name = "Main Menu", $location = "primary"
        list( $menu_name, $location ) = $args;

        // This is needed to update Polylang's internal nav_menus data
        $theme_slug = get_option( 'stylesheet' );

        // Get language slugs, e.g. ["en", "es", "fr"]
        $languages    = wp_list_pluck( $this->pll->model->get_languages_list(), 'slug' );
        $default_lang = $this->api->default_language();

        // We'll store all newly created menu IDs
        $post_ids = [];

        foreach ( $languages as $slug ) {

            // Generate a display name for each language’s menu
            // e.g., "Main Menu (es)" for Spanish
            $menu_name_i18n = ( $slug === $default_lang )
                ? $menu_name
                : sprintf( '%s (%s)', $menu_name, $slug );

            // 1) Create the menu via WP-CLI, capturing the new menu ID
            ob_start();
            $this->cli->command(
                [ 'menu', 'create', $menu_name_i18n ],
                [ 'porcelain' => true ]
            );
            $post_id = trim( ob_get_clean() );
            $post_id = (int) $post_id;

            // 2) Mark the new nav_menu post as belonging to language $slug
            $this->pll->model->post->set_language( $post_id, $slug );

            // 3) Assign to the WP location if this is the default language
            //    (So WP sees at least one standard location assignment).
            //    Non-default menus will be handled by Polylang’s internal nav_menu logic.
            if ( $slug === $default_lang ) {
                $this->cli->runcommand(
                    sprintf( 'menu location assign %d %s', $post_id, $location ),
                    [
                        'return'     => $this->cli->flag( $assoc_args, 'porcelain' ),
                        'launch'     => false,
                        'exit_error' => false
                    ]
                );
            }

            // 4) Update Polylang's internal "which menu for which language" data.
            //    This is crucial: Polylang looks at `nav_menus[ $theme_slug ][ $location ][ $lang ] = $menu_id`.
            $polylang_options = get_option( 'polylang', [] );
            if ( ! isset( $polylang_options['nav_menus'] ) ) {
                $polylang_options['nav_menus'] = [];
            }
            if ( ! isset( $polylang_options['nav_menus'][ $theme_slug ] ) ) {
                $polylang_options['nav_menus'][ $theme_slug ] = [];
            }
            if ( ! isset( $polylang_options['nav_menus'][ $theme_slug ][ $location ] ) ) {
                $polylang_options['nav_menus'][ $theme_slug ][ $location ] = [];
            }

            $polylang_options['nav_menus'][ $theme_slug ][ $location ][ $slug ] = $post_id;
            update_option( 'polylang', $polylang_options );

            // Optionally, log a success message
            // $this->cli->log( "Created menu '{$menu_name_i18n}' (ID {$post_id}) for language '{$slug}'" );

            $post_ids[] = $post_id;
        }

        // If --porcelain is passed, output just the menu IDs
        if ( $this->cli->flag( $assoc_args, 'porcelain' ) ) {
            echo implode( ' ', array_map( 'absint', $post_ids ) ) . "\n";
        }
    }
}

}
