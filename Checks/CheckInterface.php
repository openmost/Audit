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

interface CheckInterface
{
    public function execute(ChecklistItem $item): CheckResult;
}
