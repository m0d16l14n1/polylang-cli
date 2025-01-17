<?php

namespace Polylang_CLI\Commands;

use Polylang_CLI\Api\Api;
use Polylang_CLI\Api\Cli;

use Polylang_CLI\Traits\Properties;
use Polylang_CLI\Traits\Utils;
use Polylang_CLI\Traits\SettingsErrors;

# Ensure PLL_Admin_Model is available
if ( ! defined( 'PLL_ADMIN' ) ) {
    define( 'PLL_ADMIN', true );
}
if ( ! defined( 'PLL_SETTINGS' ) ) {
    define( 'PLL_SETTINGS', true );
}

if ( ! class_exists( 'Polylang_CLI\Commands\BaseCommand' ) ) {

    /**
     * Class BaseCommand
     *
     * @package Polylang_CLI
     */
    class BaseCommand extends \WP_CLI_Command
    {
        use Properties, Utils, SettingsErrors;

        /**
         * @var Cli
         */
        protected $cli;

        /**
         * @var \Polylang
         */
        protected $pll;

        /**
         * @var Api
         */
        protected $api;

        public function __construct()
        {
            parent::__construct();

            // WP-CLI helper.
            $this->cli = new Cli();

            // Check if Polylang is installed and active.
            if ( ! defined( 'POLYLANG_VERSION' ) ) {
                return $this->cli->error(
                    sprintf(
                        'This WP-CLI command requires the Polylang plugin: %s (%s)',
                        'wp plugin install polylang && wp plugin activate polylang',
                        ABSPATH
                    )
                );
            }

            // Check Polylang required version.
            if ( version_compare( POLYLANG_VERSION, '2.0.9', '<' ) ) {
                return $this->cli->error(
                    sprintf(
                        'This WP-CLI command requires Polylang version %s or higher: %s',
                        '2.0.9',
                        'wp plugin update polylang'
                    )
                );
            }

            // Grab Polylang instance.
            if ( function_exists( 'PLL' ) ) {
                $this->pll = \PLL();
            } else {
                return $this->cli->error(
                    'Could not find Polylang instance. Is Polylang installed and activated?'
                );
            }

            // Ensure PLL_INC is defined if Polylang itself no longer defines it.
            if ( ! defined( 'PLL_INC' ) ) {
                if ( defined( 'POLYLANG_DIR' ) ) {
                    define( 'PLL_INC', POLYLANG_DIR . '/include' );
                } else {
                    // Fallback approach to guess the path from Polylang's main file.
                    define( 'PLL_INC', plugin_dir_path( $this->pll->file ) . 'include' );
                }
            }

            // Make Polylang API functions available.
            $this->api = new Api( PLL_INC . '/api.php' );
        }
    }
}
