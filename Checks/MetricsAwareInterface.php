<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\Audit\Checks;

use Piwik\Plugins\Audit\Context\InstanceMetrics;

/**
 * Optional contract for checks whose recommendation depends on the
 * actual size of the audited instance (DB size, monthly traffic, …).
 *
 * The runner detects this interface and injects the shared
 * `InstanceMetrics` instance before calling `execute()`.
 */
interface MetricsAwareInterface
{
    public function setMetrics(InstanceMetrics $metrics): void;
}
