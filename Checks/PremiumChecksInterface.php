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

use Piwik\Plugins\Audit\Checklist\ChecklistItem;

/**
 * Implemented by the Premium add-on, which hands it over on the `Audit.getPremiumChecks` event: the premium items
 * of checklist.yaml only list an id, a category and a severity here, the add-on ships their full definition and
 * their check classes.
 */
interface PremiumChecksInterface
{
    /**
     * Full definition of a premium item, null when the add-on does not ship it.
     */
    public function getItem(string $id): ?ChecklistItem;

    /**
     * @param bool $allowHttp the user allowed the checks that probe Matomo over HTTP
     */
    public function createCheck(ChecklistItem $item, bool $allowHttp): ?CheckInterface;
}
