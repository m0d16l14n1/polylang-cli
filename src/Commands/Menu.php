namespace Polylang_CLI\Commands;

if ( ! class_exists( 'Polylang_CLI\Commands\MenuCommand' ) ) {

/**
 * Manage the WP Nav Menus with Polylang.
 */
class MenuCommand extends BaseCommand {

    /**
     * Create a new menu for each language, all assigned to the same location.
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

        // $menu_name = "Main Menu" (for example)
        // $location  = "primary"
        list( $menu_name, $location ) = $args;

        $post_ids = [];

        // Get the list of language slugs (e.g. ["en", "es", "fr"])
        $languages = wp_list_pluck( $this->pll->model->get_languages_list(), 'slug' );
        $default_lang = $this->api->default_language();

        foreach ( $languages as $slug ) {

            // Generate a nice display name for each language’s menu
            // e.g. "Main Menu (es)" for Spanish
            $menu_name_i18n = ( $slug === $default_lang )
                ? $menu_name
                : sprintf( '%s (%s)', $menu_name, $slug );

            // Create the WP menu via CLI
            ob_start();
            $this->cli->command(
                [ 'menu', 'create', $menu_name_i18n ],
                [ 'porcelain' => true ]
            );
            $post_id = trim( ob_get_clean() ); // Capture the newly created menu ID

            // Assign the newly created menu to the SINGLE location (e.g. "primary")
            // This is the crucial part: we do NOT append ___{slug}.
            $this->cli->runcommand(
                sprintf( 'menu location assign %d %s', $post_id, $location ),
                [
                    'return'     => $this->cli->flag( $assoc_args, 'porcelain' ),
                    'launch'     => false,
                    'exit_error' => false
                ]
            );

            // Tell Polylang that this new "nav_menu" post is in language $slug.
            // (This ensures Polylang knows which language each menu belongs to.)
            $this->pll->model->post->set_language( $post_id, $slug );

            // Optionally, confirm or log success.
            // $this->cli->log( "Created and assigned menu '{$menu_name_i18n}' for language '{$slug}'" );

            $post_ids[] = $post_id;
        }

        // Output IDs if --porcelain is passed
        if ( $this->cli->flag( $assoc_args, 'porcelain' ) ) {
            echo implode( ' ', array_map( 'absint', $post_ids ) ) . "\n";
        }
    }
}

}
