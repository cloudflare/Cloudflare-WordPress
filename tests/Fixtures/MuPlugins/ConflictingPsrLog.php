<?php

/**
 * Plugin Name: Conflicting psr/log (test fixture)
 * Description: Build test environment only (CLOUDFLARE_TEST_CONFLICTING_PSR_LOG
 * in .wp-env.build.json). Stands in for another plugin that bundles psr/log 3
 * without prefixing it: declares Psr\Log\LoggerInterface with the void return
 * types psr/log 3 added. The unprefixed psr/log 1 logger in this plugin's
 * source tree cannot implement that interface, so only the PHP-Scoper build
 * keeps working next to it.
 */

namespace Psr\Log;

if (defined('CLOUDFLARE_TEST_CONFLICTING_PSR_LOG') && CLOUDFLARE_TEST_CONFLICTING_PSR_LOG && !interface_exists(LoggerInterface::class, false)) {
    interface LoggerInterface
    {
        public function emergency($message, array $context = array()): void;

        public function alert($message, array $context = array()): void;

        public function critical($message, array $context = array()): void;

        public function error($message, array $context = array()): void;

        public function warning($message, array $context = array()): void;

        public function notice($message, array $context = array()): void;

        public function info($message, array $context = array()): void;

        public function debug($message, array $context = array()): void;

        public function log($level, $message, array $context = array()): void;
    }
}
