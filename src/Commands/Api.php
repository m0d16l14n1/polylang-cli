<?php

namespace Polylang_CLI\Commands;

if ( ! class_exists( 'Polylang_CLI\Commands\ApiCommand' ) ) {

    /**
     * Inspect Polylang procedural API functions.
     *
     * @package Polylang_CLI
     */
    class ApiCommand extends BaseCommand {

        /**
         * List Polylang procedural API functions.
         *
         * ## OPTIONS
         *
         * [--format=<format>]
         * : Accepted values: table, csv, json, count, yaml. Default: table
         *
         * ## EXAMPLES
         *
         *     $ wp pll api list
         *     $ wp pll api list --format=csv
         *
         * @subcommand list
         */
        public function list_( $args, $assoc_args )
        {
            // Ensure $this->api is set. (It should be, from BaseCommand.)
            if ( empty( $this->api ) ) {
                return $this->cli->error( 'Polylang API not available.' );
            }

            $api_functions = array();

            // $this->api->functions() would be your Polylang API function collector.
            foreach ( $this->api->functions() as $index => $func ) {
                $obj = new \stdClass();
                $obj->index    = $index;
                $obj->function = $func;

                $api_functions[] = $obj;
            }

            // Format and display the results.
            $formatter = $this->cli->formatter( $assoc_args, array( 'function' ) );
            $formatter->display_items( $api_functions );
        }

    }
}
