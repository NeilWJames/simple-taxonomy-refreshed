<?php
/**
 * Exception thrown in place of a redirect.
 *
 * @author Neil W. James <neil@familyjames.com>
 * @package test-simple-taxonomy-refreshed
 */

/**
 * Thrown by the wp_redirect filter from STaxo_Fixtures::expect_redirect().
 *
 * The message is the redirect location.
 */
class STaxo_Redirect_Exception extends Exception {
}
